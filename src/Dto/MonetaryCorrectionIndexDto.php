<?php

namespace App\Dto;

class MonetaryCorrectionIndexDto
{
    public ?string $id;
    public ?string $allotmentId;
    public ?bool $default;
    public ?bool $readjustAuto;
    public ?float $readjustLimit;
    public ?float $readjustLimitMin;
    public ?string $code;
    public ?string $name;
    public ?string $acronym;

    public function __construct(array $payload)
    {
        $this->id = $payload['id'] ?? null;
        $this->allotmentId = $payload['allotment_id'] ?? null;
        $this->default = isset($payload['default']) ? (bool) $payload['default'] : null;
        $this->readjustAuto = isset($payload['readjust_auto']) ? $this->castBool($payload['readjust_auto']) : null;
        $this->readjustLimit = isset($payload['readjust_limit']) ? (float) $payload['readjust_limit'] : null;
        $this->readjustLimitMin = isset($payload['readjust_limit_min']) ? (float) $payload['readjust_limit_min'] : null;
        $this->code = $payload['monetary_correction_index']['code'] ?? null;
        $this->name = $payload['monetary_correction_index']['name'] ?? null;
        $this->acronym = $payload['monetary_correction_index']['acronym'] ?? null;
    }

    private function castBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
