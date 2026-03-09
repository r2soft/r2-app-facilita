<?php
declare(strict_types=1);

namespace R2Soft\AppFacilita\DTOs;

class PhoneDto
{
    public ?string $id;
    public ?string $number;
    public ?int $type;
    public ?string $description;
    public ?string $countryAcronym;
    public ?string $codeCountry;

    public function __construct(array $phone)
    {
        $this->id = $phone['id'] ?? null;
        $this->number = $phone['number'] ?? null;
        $this->type = isset($phone['type']) ? (int) $phone['type'] : null;
        $this->description = $phone['description'] ?? null;
        $this->countryAcronym = $phone['country_acronym'] ?? null;
        $this->codeCountry = isset($phone['code_country']) ? (string) $phone['code_country'] : null;
    }
}
