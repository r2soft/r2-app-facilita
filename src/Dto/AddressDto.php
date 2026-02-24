<?php

namespace App\Dto;

class AddressDto
{
    public ?string $id;
    public ?string $cep;
    public ?string $street;
    public ?string $number;
    public ?string $neighborhood;
    public ?string $complement;
    public ?string $cityId;
    public ?string $cityName;
    public ?string $stateId;
    public ?string $stateAcronym;
    public ?string $stateName;

    public function __construct(array $address)
    {
        $this->id = $address['id'] ?? null;
        $this->cep = $address['cep'] ?? null;
        $this->street = $address['street'] ?? null;
        $this->number = $address['number'] ?? null;
        $this->neighborhood = $address['neighborhood'] ?? null;
        $this->complement = $address['complement'] ?? null;
        $this->cityId = $address['city_id'] ?? null;
        $this->cityName = $address['cities']['name'] ?? null;
        $this->stateId = $address['cities']['states']['id'] ?? null;
        $this->stateAcronym = $address['cities']['states']['acronyms'] ?? null;
        $this->stateName = $address['cities']['states']['name'] ?? null;
    }
}
