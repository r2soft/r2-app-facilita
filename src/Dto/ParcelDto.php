<?php

namespace App\Dto;

class ParcelDto
{
    public ?string $name;
    public ?float $value;
    public ?int $number;
    public ?int $position;
    public ?string $costCenterId;
    public ?string $costCenterName;
    public ?string $expirationDate;
    public ?string $paymentMethodId;
    public ?string $paymentMethodName;
    public ?int $periodicityId;
    public ?float $financingInterest;

    public function __construct(array $parcel)
    {
        $this->name = $parcel['name'] ?? null;
        $this->value = isset($parcel['value']) ? (float) $parcel['value'] : null;
        $this->number = isset($parcel['number']) ? (int) $parcel['number'] : null;
        $this->position = isset($parcel['position']) ? (int) $parcel['position'] : null;
        $this->costCenterId = $parcel['cost_center_id'] ?? null;
        $this->costCenterName = $parcel['cost_center_name'] ?? null;
        $this->expirationDate = $parcel['expiration_date'] ?? null;
        $this->paymentMethodId = $parcel['payment_method_id'] ?? null;
        $this->paymentMethodName = $parcel['payment_method_name'] ?? null;
        $this->periodicityId = isset($parcel['periodicity_id']) ? (int) $parcel['periodicity_id'] : null;
        $this->financingInterest = isset($parcel['financing_interest']) ? (float) $parcel['financing_interest'] : null;
    }
}
