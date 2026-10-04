<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Appointment;

use Nxi\Factro\Exception\NotFoundException;
use Nxi\Factro\Resource\AbstractResource;
use Nxi\Factro\Resource\Appointment\Input\AppointmentChanges;
use Nxi\Factro\Resource\Appointment\Input\NewAppointment;
use Nxi\Factro\Resource\Appointment\Output\Appointment;

final class Appointments extends AbstractResource
{
    /**
     * GET /appointments. The appointments visible to the requesting user.
     *
     * @return list<Appointment>
     */
    public function list(): array
    {
        return array_map(Appointment::fromArray(...), $this->rows($this->transport->request('GET', '/appointments')));
    }

    /**
     * GET /appointments/{id}.
     */
    public function get(string $appointmentId): Appointment
    {
        return Appointment::fromArray($this->object($this->transport->request('GET', '/appointments/'.rawurlencode($appointmentId))));
    }

    /**
     * Like get(), but null on 404. Every other exception passes through.
     */
    public function find(string $appointmentId): ?Appointment
    {
        try {
            return $this->get($appointmentId);
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * POST /appointments.
     */
    public function create(NewAppointment $appointment): Appointment
    {
        return Appointment::fromArray($this->object($this->transport->request('POST', '/appointments', json: $appointment->toPayload())));
    }

    /**
     * PUT /appointments/{id}.
     */
    public function update(string $appointmentId, AppointmentChanges $changes): Appointment
    {
        if ($changes->isEmpty()) {
            throw new \InvalidArgumentException('AppointmentChanges must set at least one field.');
        }

        return Appointment::fromArray($this->object($this->transport->request('PUT', '/appointments/'.rawurlencode($appointmentId), json: $changes->toPayload())));
    }

    /**
     * DELETE /appointments/{id}. Requires a RequestPolicy that permits DELETE; the deleted appointment in the response is discarded.
     */
    public function delete(string $appointmentId): void
    {
        $this->transport->request('DELETE', '/appointments/'.rawurlencode($appointmentId));
    }

    /**
     * POST /appointments/appointments. factro creates the valid entries even if some are rejected.
     *
     * @param list<NewAppointment> $appointments
     *
     * @return list<Appointment>
     */
    public function createMany(array $appointments): array
    {
        if ([] === $appointments) {
            throw new \InvalidArgumentException('createMany() needs at least one NewAppointment.');
        }
        $payload = array_map(static fn (NewAppointment $appointment): array => $appointment->toPayload(), array_values($appointments));

        return array_map(Appointment::fromArray(...), $this->rows($this->transport->request('POST', '/appointments/appointments', json: $payload)));
    }

    /**
     * PUT /appointments/appointments. Keys are appointment ids; each payload carries its id.
     *
     * @param array<string, AppointmentChanges> $changesById
     *
     * @return list<Appointment>
     */
    public function updateMany(array $changesById): array
    {
        if ([] === $changesById) {
            throw new \InvalidArgumentException('updateMany() needs at least one AppointmentChanges.');
        }
        $payload = [];
        foreach ($changesById as $appointmentId => $changes) {
            if ($changes->isEmpty()) {
                throw new \InvalidArgumentException(sprintf('AppointmentChanges for "%s" must set at least one field.', $appointmentId));
            }
            $payload[] = ['id' => $appointmentId] + $changes->toPayload();
        }

        return array_map(Appointment::fromArray(...), $this->rows($this->transport->request('PUT', '/appointments/appointments', json: $payload)));
    }
}
