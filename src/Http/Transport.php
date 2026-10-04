<?php

declare(strict_types=1);

namespace Nxi\Factro\Http;

use Nxi\Factro\Exception\FactroException;
use Nxi\Factro\Exception\FactroRequestException;
use Nxi\Factro\Exception\NotFoundException;
use Nxi\Factro\Exception\TransportException;
use Nxi\Factro\FactroOptions;
use Nxi\Factro\Policy\OperationNotPermittedException;
use Nxi\Factro\RateLimitInfo;
use Psr\Log\LogLevel;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Sends requests through the decorated client, decodes JSON and maps failures onto SDK exceptions.
 *
 * Paths are accepted with a leading slash ("/tasks/abc") and sent without it so that the
 * base URI keeps its own path segment (see docs/DECISIONS.md).
 *
 * @internal
 */
final readonly class Transport
{
    public function __construct(
        private HttpClientInterface $http,
        private FactroOptions $options,
        private ErrorMapper $errors = new ErrorMapper(),
    ) {
    }

    /**
     * @param array<string, scalar> $query
     * @param MultipartBody|null    $multipart a multipart/form-data body (document uploads); mutually exclusive with $json
     *
     * @return array<mixed>|null decoded JSON; null for 204 or an empty body
     *
     * @throws FactroRequestException|TransportException|OperationNotPermittedException|\JsonException
     */
    public function request(string $method, string $path, array $query = [], mixed $json = null, ?MultipartBody $multipart = null): ?array
    {
        $this->assertRelativePath($path);
        $method = strtoupper($method);
        $requestOptions = ['query' => $query];
        if (null !== $json && null !== $multipart) {
            throw new \InvalidArgumentException('Transport::request() accepts either $json or $multipart, not both.');
        }
        if (null !== $json) {
            $requestOptions['json'] = $json;
        }
        if (null !== $multipart) {
            $requestOptions['body'] = $multipart->content;
            $requestOptions['headers'] = ['Content-Type' => $multipart->contentType()];
        }

        try {
            $response = $this->http->request($method, ltrim($path, '/'), $requestOptions);

            return $this->consume($method, $path, $response);
        } catch (TransportExceptionInterface $e) {
            throw $this->errors->fromTransport($method, $path, $e);
        }
    }

    /**
     * Sends GET requests concurrently in batches of $concurrency and returns decoded bodies keyed by path.
     * A 404 yields null for that path; other errors throw after all responses of the batch were consumed.
     *
     * @param list<string> $paths
     *
     * @return array<string, array<mixed>|null>
     */
    public function getMany(array $paths, int $concurrency = 5): array
    {
        $result = [];
        foreach (array_chunk($paths, max(1, $concurrency)) as $batch) {
            $responses = [];
            foreach ($batch as $path) {
                $this->assertRelativePath($path);
                try {
                    $responses[$path] = $this->http->request('GET', ltrim($path, '/'));
                } catch (TransportExceptionInterface $e) {
                    throw $this->errors->fromTransport('GET', $path, $e);
                }
            }
            $pending = null;
            foreach ($responses as $path => $response) {
                try {
                    $result[$path] = $this->consume('GET', $path, $response);
                } catch (NotFoundException) {
                    $result[$path] = null;
                } catch (FactroException $e) {
                    // Finish consuming the batch so no response is left dangling, then rethrow the first error.
                    $pending ??= $e;
                } catch (TransportExceptionInterface $e) {
                    $pending ??= $this->errors->fromTransport('GET', $path, $e);
                }
            }
            if (null !== $pending) {
                throw $pending;
            }
        }

        return $result;
    }

    /**
     * @return array<mixed>|null
     */
    private function consume(string $method, string $path, ResponseInterface $response): ?array
    {
        $status = $response->getStatusCode();
        $body = $response->getContent(false);
        $headers = $response->getHeaders(false);
        $info = RateLimitHeaders::parse($headers);
        if (null !== $info && null !== $this->options->onRateLimit) {
            ($this->options->onRateLimit)($info);
        }
        $this->log($method, $path, $status, $response, $info, $body);
        if ($status >= 400) {
            throw $this->errors->fromResponse($method, $path, $status, $body, $headers);
        }
        if ('' === trim($body)) {
            return null;
        }
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            throw new \UnexpectedValueException(sprintf('factro returned a JSON scalar for %s %s', $method, $path));
        }

        return $decoded;
    }

    private function log(string $method, string $path, int $status, ResponseInterface $response, ?RateLimitInfo $info, string $body): void
    {
        $level = match (true) {
            $status < 400 => LogLevel::DEBUG,
            404 === $status => LogLevel::INFO,
            default => LogLevel::ERROR,
        };
        $totalTime = $response->getInfo('total_time');
        $context = [
            'method' => $method,
            'path' => $path,
            'status' => $status,
            'duration_ms' => (int) round((is_float($totalTime) || is_int($totalTime) ? (float) $totalTime : 0.0) * 1000),
            'remaining' => $info?->remaining,
        ];
        if ($status >= 400) {
            $context['factro_message'] = ErrorMapper::extractMessage($body);
        }
        // Never log headers or bodies: the token travels in a header and bodies may carry personal data.
        $this->options->logger->log($level, 'factro {method} {path} -> {status} ({duration_ms} ms, remaining {remaining})', $context);
    }

    private function assertRelativePath(string $path): void
    {
        if (!str_starts_with($path, '/') || str_starts_with($path, '//')) {
            throw new \InvalidArgumentException(sprintf('Transport accepts relative paths starting with "/", got "%s".', $path));
        }
    }
}
