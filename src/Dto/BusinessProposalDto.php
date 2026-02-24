<?php

namespace App\Dto;

class BusinessProposalDto
{
    public ?string $id;
    public ?string $name;
    public ?string $status;
    public ?float $priceOfProperty;
    public ?float $valueWithInterest;
    public ?string $proposalHtml;
    public array $parcels = [];

    public function __construct(array $payload)
    {
        $this->id = $payload['id'] ?? null;
        $this->name = $payload['name'] ?? null;
        $this->status = $payload['status'] ?? null;
        $this->priceOfProperty = isset($payload['price_of_property']) ? (float) $payload['price_of_property'] : null;
        $this->valueWithInterest = isset($payload['value_with_interest']) ? (float) $payload['value_with_interest'] : null;
        $this->proposalHtml = $payload['proposal'] ?? null;
        $this->parcels = $this->mapParcels($payload['parcel_configs'] ?? []);
    }

    private function mapParcels(array $parcels): array
    {
        return array_map(static fn($parcel) => new ParcelDto($parcel), $parcels);
    }
}
