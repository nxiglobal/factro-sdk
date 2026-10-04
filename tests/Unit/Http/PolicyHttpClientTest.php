<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Http;

use Nxi\Factro\Http\PolicyHttpClient;
use Nxi\Factro\Policy\OperationNotPermittedException;
use Nxi\Factro\Policy\RequestPolicy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(PolicyHttpClient::class)]
#[CoversClass(OperationNotPermittedException::class)]
final class PolicyHttpClientTest extends TestCase
{
    public function testForbiddenRequestNeverReachesInnerClient(): void
    {
        $calls = 0;
        $inner = new MockHttpClient(static function () use (&$calls) {
            ++$calls;

            return new MockResponse('');
        });
        $client = new PolicyHttpClient($inner, RequestPolicy::withoutDeletes());

        try {
            $client->request('DELETE', '/tasks/1?foo=bar');
            self::fail('expected exception');
        } catch (OperationNotPermittedException $e) {
            self::assertSame('DELETE', $e->method);
            self::assertSame('/tasks/1', $e->path);
            self::assertSame('Operation "DELETE /tasks/1" is not permitted by the request policy.', $e->getMessage());
        }
        self::assertSame(0, $calls);
    }

    public function testPathWithoutLeadingSlashIsNormalisedForThePolicy(): void
    {
        $inner = new MockHttpClient(static fn () => new MockResponse(''));
        $client = new PolicyHttpClient($inner, RequestPolicy::all()->denyPaths('#^/tasks/[^/]+/package$#'));

        try {
            $client->request('PUT', 'tasks/1/package');
            self::fail('expected exception');
        } catch (OperationNotPermittedException $e) {
            self::assertSame('/tasks/1/package', $e->path);
        }
    }

    public function testPermittedRequestIsDelegated(): void
    {
        $inner = new MockHttpClient(new MockResponse('ok'));
        $client = new PolicyHttpClient($inner, RequestPolicy::readOnly());

        self::assertSame('ok', $client->request('GET', '/tasks/1')->getContent());
    }
}
