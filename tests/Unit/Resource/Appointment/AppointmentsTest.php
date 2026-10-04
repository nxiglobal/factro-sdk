<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Appointment;

use Nxi\Factro\FactroOptions;
use Nxi\Factro\Policy\OperationNotPermittedException;
use Nxi\Factro\Policy\RequestPolicy;
use Nxi\Factro\Resource\Appointment\Appointments;
use Nxi\Factro\Resource\Appointment\Input\AppointmentChanges;
use Nxi\Factro\Resource\Appointment\Input\NewAppointment;
use Nxi\Factro\Resource\Appointment\Output\Appointment;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Tests\Support\MockFactro;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(Appointments::class)]
final class AppointmentsTest extends TestCase
{
    public function testListGetAndFind(): void
    {
        $client = MockFactro::client([
            'GET /appointments' => JsonMockResponse::fromFile(Fixtures::path('appointments')),
            'GET /appointments/a1' => static fn (): MockResponse => JsonMockResponse::fromFile(Fixtures::path('appointment')),
            'GET /appointments/missing' => new MockResponse('Appointment with id "missing" not found', ['http_code' => 404]),
        ]);

        $appointments = $client->appointments()->list();
        self::assertCount(3, $appointments);
        self::assertContainsOnlyInstancesOf(Appointment::class, $appointments);
        self::assertInstanceOf(Appointment::class, $client->appointments()->get('a1'));
        self::assertInstanceOf(Appointment::class, $client->appointments()->find('a1'));
        self::assertNull($client->appointments()->find('missing'));
    }

    public function testCreatePostsPayloadAndReturnsAppointment(): void
    {
        $client = MockFactro::client(['POST /appointments' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString(
                '{"employeeId":"e1","startDate":"2026-09-15T08:00:00.000Z","endDate":"2026-09-15T09:30:00.000Z","subject":"Meeting"}',
                $o['body'],
            );

            return JsonMockResponse::fromFile(Fixtures::path('appointment'));
        }]);

        $appointment = $client->appointments()->create(new NewAppointment(
            employeeId: 'e1',
            startDate: new \DateTimeImmutable('2026-09-15T10:00:00+02:00'),
            endDate: new \DateTimeImmutable('2026-09-15T11:30:00+02:00'),
            subject: 'Meeting',
        ));

        self::assertInstanceOf(Appointment::class, $appointment);
    }

    public function testUpdateSendsOnlyChangedFields(): void
    {
        $client = MockFactro::client(['PUT /appointments/a1' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('{"location":"Berlin","distance":42.5}', $o['body']);

            return JsonMockResponse::fromFile(Fixtures::path('appointment'));
        }]);

        self::assertInstanceOf(Appointment::class, $client->appointments()->update('a1', new AppointmentChanges(location: 'Berlin', distance: 42.5)));
    }

    public function testUpdateWithEmptyChangesIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->appointments()->update('a1', new AppointmentChanges());
    }

    public function testDeleteDiscardsResponseBody(): void
    {
        $client = MockFactro::client(['DELETE /appointments/a1' => JsonMockResponse::fromFile(Fixtures::path('appointment'))]);

        $client->appointments()->delete('a1');
        $this->addToAssertionCount(1);
    }

    public function testDeleteIsBlockedByPolicy(): void
    {
        $client = MockFactro::client([], new FactroOptions(baseUrl: MockFactro::BASE_URL, policy: RequestPolicy::withoutDeletes(), maxRetries: 0));

        $this->expectException(OperationNotPermittedException::class);
        $client->appointments()->delete('a1');
    }

    public function testCreateManyPostsListOfPayloads(): void
    {
        $client = MockFactro::client(['POST /appointments/appointments' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            $body = json_decode($o['body'], true, 512, JSON_THROW_ON_ERROR);
            self::assertIsArray($body);
            self::assertCount(2, $body);
            self::assertIsArray($body[0]);
            self::assertIsArray($body[1]);
            self::assertSame(['employeeId', 'startDate', 'endDate', 'subject'], array_keys($body[0]));
            self::assertSame('e2', $body[1]['employeeId']);
            self::assertSame('t1', $body[1]['referencedTaskId']);
            self::assertArrayNotHasKey('subject', $body[1]);

            return JsonMockResponse::fromFile(Fixtures::path('appointments'));
        }]);

        $start = new \DateTimeImmutable('2026-09-15T08:00:00Z');
        $end = new \DateTimeImmutable('2026-09-15T09:00:00Z');
        $created = $client->appointments()->createMany([
            new NewAppointment('e1', $start, $end, subject: 'A'),
            new NewAppointment('e2', $start, $end, referencedTaskId: 't1'),
        ]);

        self::assertCount(3, $created);
        self::assertContainsOnlyInstancesOf(Appointment::class, $created);
    }

    public function testCreateManyWithEmptyListIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->appointments()->createMany([]);
    }

    public function testUpdateManySendsIdWithEachChangeSet(): void
    {
        $client = MockFactro::client(['PUT /appointments/appointments' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString(
                '[{"id":"a1","subject":"New"},{"id":"a2","location":"Berlin","endDate":"2026-09-15T12:00:00.000Z"}]',
                $o['body'],
            );

            return JsonMockResponse::fromFile(Fixtures::path('appointments'));
        }]);

        $updated = $client->appointments()->updateMany([
            'a1' => new AppointmentChanges(subject: 'New'),
            'a2' => new AppointmentChanges(location: 'Berlin', endDate: new \DateTimeImmutable('2026-09-15T14:00:00+02:00')),
        ]);

        self::assertCount(3, $updated);
        self::assertContainsOnlyInstancesOf(Appointment::class, $updated);
    }

    public function testUpdateManyWithEmptyMapIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->appointments()->updateMany([]);
    }

    public function testUpdateManyWithAnEmptyChangeSetIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->appointments()->updateMany(['a1' => new AppointmentChanges()]);
    }
}
