<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Document\Output;

use Nxi\Factro\Mapping\Field;

/**
 * Disk space of the tenant in bytes (IGetDataQuotaResponse).
 */
final readonly class DataQuota
{
    public function __construct(public float $maxDiskSpace, public float $usedDiskSpace)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $o = self::class;

        return new self(
            maxDiskSpace: Field::float($data, 'maxDiskSpace', $o),
            usedDiskSpace: Field::float($data, 'usedDiskSpace', $o),
        );
    }

    public function freeDiskSpace(): float
    {
        return max(0.0, $this->maxDiskSpace - $this->usedDiskSpace);
    }
}
