<?php

namespace App\Dto;

class SaleDto
{
    public ?string $companyId;
    public ?string $reserveId;
    public ?string $lotId;
    public ?string $lotExternalId;
    public ?PropertyDto $property;
    public ?AllotmentDto $allotment;
    public ?bool $isSale;
    public ?bool $hasProposal;
    public ?string $saleDate;
    public ?string $occurredAt;
    public ?string $origin;
    public ?BusinessProposalDto $businessProposal;
    public array $clients = [];
    public ?RealtorDto $realtor;
    public ?float $valueWithInterest;
    public ?string $parceriaId;
    public ?string $proposalJson;

    private function __construct()
    {
    }

    public static function fromWebhook(array $payload): self
    {
        $data = $payload['data'] ?? [];
        $sale = new self();
        $sale->companyId = $payload['event']['company'] ?? null;
        $sale->reserveId = $data['id'] ?? null;
        $sale->lotId = $data['lot_id'] ?? null;
        $sale->lotExternalId = $data['properties']['external_id'] ?? null;
        $sale->property = !empty($data['properties']) ? new PropertyDto($data['properties']) : null;
        $sale->allotment = $sale->property && !empty($data['properties']['allotments'])
            ? new AllotmentDto($data['properties']['allotments'])
            : null;
        $sale->isSale = $data['isSale'] ?? null;
        $sale->hasProposal = $data['hasProposal'] ?? null;
        $sale->saleDate = $data['confirmed_at'] ?? $data['sale_date'] ?? null;
        $sale->occurredAt = $payload['event']['occurred_at'] ?? null;
        $sale->origin = $payload['event']['origin'] ?? null;
        $sale->businessProposal = $sale->mapBusinessProposal($data['business_proposal'] ?? []);
        $sale->valueWithInterest = $sale->businessProposal->valueWithInterest ?? null;
        $sale->parceriaId = $data['properties']['parceria_id'] ?? null;
        $sale->proposalJson = !empty($data['business_proposal']) ? json_encode($data['business_proposal'], JSON_UNESCAPED_UNICODE) : null;
        $sale->clients = array_map(static fn($client) => ClientDto::fromWebhookEntry($client), $data['reserves_clients'] ?? []);
        $sale->realtor = !empty($data['realtor_data']) ? new RealtorDto($data['realtor_data']) : null;
        return $sale;
    }

    private function mapBusinessProposal(array $proposals): BusinessProposalDto
    {
        $proposal = reset($proposals) ?: [];
        if (empty($proposal)) {
            return new BusinessProposalDto([]);
        }
        return new BusinessProposalDto($proposal);
    }

    public function getSaleValue(): ?float
    {
        return $this->valueWithInterest;
    }

    public function getSaleName(int $nextSequence): ?string
    {
        if (empty($this->allotment?->prefixSale)) {
            return str_pad($nextSequence, 6, '0', STR_PAD_LEFT);
        }
        $suffix = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
        return $this->allotment->prefixSale . $suffix;
    }

    public function toArray(): array
    {
        return [
            'companyId' => $this->companyId,
            'reserveId' => $this->reserveId,
            'lotId' => $this->lotId,
            'lotExternalId' => $this->lotExternalId,
            'isSale' => $this->isSale,
            'valueWithInterest' => $this->valueWithInterest,
            'parceriaId' => $this->parceriaId,
            'saleDate' => $this->saleDate,
            'businessProposal' => $this->businessProposal ? $this->businessProposal->name : null,
            'clients' => array_map(fn(ClientDto $client) => $client->name, $this->clients),
        ];
    }
}
