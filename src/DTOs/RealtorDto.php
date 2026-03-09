<?php

namespace R2Soft\AppFacilita\DTOs;

class RealtorDto
{
    public ?string $name;
    public ?string $email;
    public ?string $taxpayer;
    public ?string $creci;
    public array $phones = [];

    public function __construct(array $data)
    {
        $this->name = $data['name'] ?? null;
        $this->email = $data['email'] ?? null;
        $this->taxpayer = $data['taxpayer'] ?? null;
        $this->creci = $data['creci'] ?? null;
        $this->phones = array_map(static fn($phone) => new PhoneDto($phone), $this->normalizePhones($data['phones'] ?? []));
    }

    private function normalizePhones($phones): array
    {
        if (empty($phones)) {
            return [];
        }
        if (is_string($phones)) {
            $decoded = json_decode($phones, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
            return [];
        }
        return is_array($phones) ? $phones : [];
    }
}
