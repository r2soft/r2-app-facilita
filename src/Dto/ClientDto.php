<?php

namespace App\Dto;

class ClientDto
{
    public ?string $id;
    public ?string $name;
    public ?string $tradeName;
    public ?string $taxpayer;
    public ?string $email;
    public ?string $maritalStatus;
    public ?string $responsibleId;
    public array $phones = [];
    public ?AddressDto $address;
    public ?AddressDto $commercialAddress;
    public ?self $spouse = null;
    public ?self $responsible = null;

    public function __construct(array $clientData)
    {
        $this->id = $clientData['id'] ?? null;
        $this->name = $clientData['name'] ?? null;
        $this->tradeName = $clientData['trade_name'] ?? null;
        $this->taxpayer = $clientData['taxpayer'] ?? null;
        $this->email = $clientData['email'] ?? null;
        $this->maritalStatus = $clientData['marital_statuses']['name'] ?? $clientData['marital_status'] ?? null;
        $this->responsibleId = $clientData['responsible_id'] ?? null;
        $this->address = !empty($clientData['address']) ? new AddressDto($clientData['address']) : null;
        $this->commercialAddress = !empty($clientData['comercial_address']) ? new AddressDto($clientData['comercial_address']) : null;
        $this->phones = array_map(static fn($phone) => new PhoneDto($phone), $this->normalizePhones($clientData['phones'] ?? []));
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

    public static function fromWebhookEntry(array $entry): self
    {
        $client = $entry['clients'] ?? $entry;
        $dto = new self($client);
        if (!empty($client['spouse'])) {
            $dto->spouse = new self($client['spouse']);
        }
        if (!empty($client['responsible'])) {
            $dto->responsible = new self($client['responsible']);
        }
        return $dto;
    }
}
