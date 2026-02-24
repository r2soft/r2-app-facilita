<?php

namespace App\Dto;

class AllotmentDto
{
    public ?string $id;
    public ?string $name;
    public ?string $prefixSale;
    public ?float $readjustPricePerMeter;
    public ?float $additionalAdjustment;
    public array $monetaryCorrectionIndexes = [];

    public function __construct(array $payload)
    {
        $this->id = $payload['id'] ?? null;
        $this->name = $payload['name'] ?? null;
        $this->prefixSale = $payload['prefix_sale'] ?? null;
        $this->readjustPricePerMeter = isset($payload['readjust_price_per_meter']) ? (float) $payload['readjust_price_per_meter'] : null;
        $this->additionalAdjustment = isset($payload['additional_adjustment']) ? (float) $payload['additional_adjustment'] : null;
        $this->monetaryCorrectionIndexes = $this->mapMonetaryCorrectionIndexes($payload['monetary_correction_indexes'] ?? []);
    }

    private function mapMonetaryCorrectionIndexes(array $indexes): array
    {
        return array_map(static fn($index) => new MonetaryCorrectionIndexDto($index), $indexes);
    }
}
