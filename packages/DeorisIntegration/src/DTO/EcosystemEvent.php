<?php

namespace Deoris\Integration\DTO;

use DateTimeImmutable;
use Illuminate\Support\Str;
use InvalidArgumentException;

final readonly class EcosystemEvent
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $sourceModule,
        public array $payload,
        public string $occurredAt,
        public string $correlationId,
        public string $schemaVersion = '1.0',
    ) {
        if ($this->id === '' || $this->name === '' || $this->sourceModule === '') {
            throw new InvalidArgumentException('Event id, name, and source module are required.');
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function make(string $name, string $sourceModule, array $payload, ?string $correlationId = null): self
    {
        return new self(
            id: (string) Str::uuid(),
            name: $name,
            sourceModule: $sourceModule,
            payload: $payload,
            occurredAt: (new DateTimeImmutable())->format(DATE_ATOM),
            correlationId: $correlationId ?: (string) Str::uuid(),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            sourceModule: (string) ($data['source_module'] ?? ''),
            payload: is_array($data['payload'] ?? null) ? $data['payload'] : [],
            occurredAt: (string) ($data['occurred_at'] ?? now()->toAtomString()),
            correlationId: (string) ($data['correlation_id'] ?? Str::uuid()),
            schemaVersion: (string) ($data['schema_version'] ?? '1.0'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'source_module' => $this->sourceModule,
            'payload' => $this->payload,
            'occurred_at' => $this->occurredAt,
            'correlation_id' => $this->correlationId,
            'schema_version' => $this->schemaVersion,
        ];
    }
}
