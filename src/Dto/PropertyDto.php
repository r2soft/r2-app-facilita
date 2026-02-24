<?php

namespace App\Dto;

class PropertyDto
{
    public ?string $id;
    public ?string $block;
    public ?string $name;
    public ?string $externalId;
    public ?string $status;
    public ?float $price;
    public ?float $totalArea;
    public ?string $propertiesStatusName;

    public function __construct(array $payload)
    {
        $this->id = $payload['id'] ?? null;
        $this->block = $payload['block'] ?? null;
        $this->name = $payload['name'] ?? null;
        $this->externalId = $payload['external_id'] ?? null;
        $this->price = isset($payload['price']) ? (float) $payload['price'] : null;
        $this->totalArea = isset($payload['total_area']) ? (float) $payload['total_area'] : null;
        $this->propertiesStatusName = $payload['properties_status']['name'] ?? $payload['propertiesStatus']['name'] ?? null;
        $this->status = $payload['properties_status']['type'] ?? $payload['propertiesStatus']['type'] ?? null;
    }
}
