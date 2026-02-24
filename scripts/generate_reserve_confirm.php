<?php
declare(strict_types=1);

/**
 * Gera um JSON no formato de Reserve_confirm_recente.json
 * combinando dados de venda_ganha.json e simulation.json.
 *
 * Uso:
 * php scripts/generate_reserve_confirm.php \
 *   --template=files/Reserve_confirm_recente.json \
 *   --sale=files/venda_ganha.json \
 *   --simulation=files/simulation.json \
 *   --output=files/Reserve_confirm_gerado.json
 */

function readJson(string $path): array
{
    if (!is_file($path)) {
        throw new RuntimeException("Arquivo nao encontrado: {$path}");
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        throw new RuntimeException("Falha ao ler arquivo: {$path}");
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        throw new RuntimeException("JSON invalido: {$path}");
    }

    return $data;
}

function toFloatOrNull($value): ?float
{
    if ($value === null || $value === '') {
        return null;
    }
    return (float) $value;
}

function normalizePhone(?string $countryCode, ?string $number): array
{
    return [
        'id' => null,
        'number' => $number,
        'type' => 2,
        'description' => null,
        'company_id' => null,
        'code_country' => $countryCode !== null ? (int) $countryCode : 55,
        'country_acronym' => 'BR',
    ];
}

function pickValue(...$values)
{
    foreach ($values as $value) {
        if ($value !== null && $value !== '') {
            return $value;
        }
    }

    return null;
}

function clearTemplateValues($value)
{
    if (is_array($value)) {
        $result = [];
        foreach ($value as $k => $v) {
            $result[$k] = clearTemplateValues($v);
        }
        return $result;
    }

    return null;
}

function parseInstallmentRows(array $installment): array
{
    $rawRows = $installment['installments'] ?? null;
    if (!is_string($rawRows) || $rawRows === '') {
        return [];
    }

    $rows = json_decode($rawRows, true);
    return is_array($rows) ? $rows : [];
}

function normalizeOccurredAt(?string $value): ?string
{
    if ($value === null || trim($value) === '') {
        return null;
    }

    $normalized = trim($value);
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}(?:\.\d+)?$/', $normalized) === 1) {
        $normalized = str_replace(' ', 'T', $normalized);
    }

    if (preg_match('/(Z|[+-]\d{2}:?\d{2})$/', $normalized) !== 1) {
        $normalized .= 'Z';
    }

    return $normalized;
}

function positionByType(string $typeInstallment): int
{
    $map = [
        'signal' => 1,
        'entry' => 1,
        'down_payment' => 1,
        'monthly' => 5,
        'regular' => 5,
        'unique' => 4,
        'brokerage' => 4,
    ];

    return $map[$typeInstallment] ?? 5;
}

function labelByPosition(int $position): string
{
    $labels = [
        1 => 'Entrada',
        4 => 'Corretagem',
        5 => 'Parcelas Regulares',
    ];

    return $labels[$position] ?? 'Parcela';
}

function paymentMethodByPosition(array $templateParcels, int $position): array
{
    foreach ($templateParcels as $parcel) {
        if ((int) ($parcel['position'] ?? 0) !== $position) {
            continue;
        }

        return [
            'id' => $parcel['payment_method_id'] ?? null,
            'name' => $parcel['payment_method_name'] ?? null,
        ];
    }

    $fallback = $templateParcels[0] ?? [];

    return [
        'id' => $fallback['payment_method_id'] ?? null,
        'name' => $fallback['payment_method_name'] ?? null,
    ];
}

function buildParcelConfigs(array $simulation, array $templateProposal): array
{
    $templateParcels = $templateProposal['parcel_configs'] ?? [];

    $result = [];
    foreach (($simulation['installments'] ?? []) as $inst) {
        $rows = parseInstallmentRows($inst);
        $position = positionByType((string) ($inst['type_installment'] ?? ''));
        $name = labelByPosition($position);
        $interest = toFloatOrNull($simulation['monthly_rate'] ?? null);
        $paymentMethod = paymentMethodByPosition($templateParcels, $position);

        foreach ($rows as $row) {
            $result[] = [
                'name' => $name,
                'value' => isset($row['valueInstallments']) ? (float) $row['valueInstallments'] : toFloatOrNull($inst['first_value'] ?? null),
                'number' => isset($row['portion']) ? (int) $row['portion'] : 1,
                'position' => $position,
                'cost_center_id' => $position === 5 ? 21 : 20,
                'periodicity_id' => 1,
                'expiration_date' => $row['duedate'] ?? ($inst['first_date'] ?? null),
                'cost_center_name' => $position === 5 ? 'Saldo devedor' : 'Entrada',
                'payment_method_id' => $paymentMethod['id'],
                'financing_interest' => $interest,
                'payment_method_name' => $paymentMethod['name'],
            ];
        }
    }

    return $result;
}

function mapCustomerToReserveClient(array $customer, array $templateClient, $companyId = null): array
{
    $mobile = pickValue($customer['mobile'] ?? null, $customer['phone'] ?? null);
    $phone = null;
    if ($mobile !== null) {
        $phone = normalizePhone(
            isset($customer['country_code_mobile']) ? (string) $customer['country_code_mobile'] : null,
            (string) $mobile
        );
    }

    $client = $templateClient;
    $client['id'] = pickValue($customer['uuid'] ?? null, $customer['id_customer'] ?? null);
    $client['name'] = pickValue($customer['name'] ?? null);
    $client['email'] = pickValue($customer['email'] ?? null);
    $client['taxpayer'] = pickValue($customer['cpf'] ?? null, $customer['cnpj'] ?? null);
    $client['phones'] = $phone !== null ? [$phone] : null;

    return [
        'company_id' => $companyId,
        'clients' => $client,
    ];
}

function mapRealtor(array $owners, array $templateRealtor): array
{
    $brokerUser = $owners['broker']['user'] ?? [];
    $brokerMobile = pickValue($brokerUser['mobile'] ?? null, $brokerUser['phone'] ?? null);
    $phones = null;
    if ($brokerMobile !== null) {
        $phones = json_encode([
            normalizePhone(null, (string) $brokerMobile),
        ], JSON_UNESCAPED_UNICODE);
    }

    $templateRealtor['name'] = pickValue($brokerUser['first_name'] ?? null, $brokerUser['name'] ?? null);
    $templateRealtor['email'] = pickValue($brokerUser['email'] ?? null);
    $templateRealtor['taxpayer'] = pickValue($brokerUser['cpf'] ?? null);
    $templateRealtor['creci'] = pickValue($brokerUser['creci'] ?? null);
    $templateRealtor['phones'] = $phones;
    $templateRealtor['birthday'] = null;
    $templateRealtor['real_estate_agency_name'] = null;
    $templateRealtor['real_estate_agency_id'] = null;
    $templateRealtor['user_id'] = pickValue($owners['broker']['user_id'] ?? null, $brokerUser['id'] ?? null);

    return $templateRealtor;
}

function applyMapping(array $template, array $sale, array $simulation): array
{
    $out = clearTemplateValues($template);

    $saleParsed = $sale['body']['parsed'] ?? [];
    $deal = $simulation['deal'] ?? [];
    $simDataDeal = $simulation['data']['deal'] ?? [];
    $simCustomer = $simulation['data']['customer'] ?? [];
    $simUnity = $simulation['unity'] ?? [];

    $out['event']['origin'] = 'Reserve:confirm';
    $out['event']['company'] = pickValue(
        $sale['http']['headers']['facilita-hook-company'] ?? null,
        $sale['http']['headers']['x-company-id'] ?? null,
        $saleParsed['company_id'] ?? null,
        $template['event']['company'] ?? null
    );
    $out['event']['controlador'] = $template['event']['controlador'] ?? null;
    $out['event']['funcionalidade'] = $template['event']['funcionalidade'] ?? null;
    $occurredAt = pickValue($saleParsed['saled_at'] ?? null, $saleParsed['updated_at'] ?? null);
    $out['event']['occurred_at'] = normalizeOccurredAt($occurredAt);
    $out['event']['app_env'] = pickValue(
        $sale['http']['headers']['facilita-hook-ambient'] ?? null,
        $template['event']['app_env'] ?? null
    );

    $out['data']['id'] = ($deal['id'] ?? $simDataDeal['id'] ?? null) !== null ? (string) ($deal['id'] ?? $simDataDeal['id']) : null;
    $out['data']['company_id'] = pickValue($out['event']['company'] ?? null, $template['data']['company_id'] ?? null);
    $out['data']['reserve_status_id'] = $template['data']['reserve_status_id'] ?? null;
    $out['data']['reserve_type_id'] = $template['data']['reserve_type_id'] ?? null;
    $out['data']['isSale'] = true;
    $out['data']['hasProposal'] = true;
    $out['data']['lot_id'] = isset($simUnity['id']) ? (string) $simUnity['id'] : null;
    $out['data']['confirmed_at'] = pickValue($saleParsed['saled_at'] ?? null);
    $out['data']['sale_date'] = pickValue($saleParsed['saled_at'] ?? null);

    $out['data']['property_id'] = isset($simUnity['id']) ? (string) $simUnity['id'] : null;
    $out['data']['properties']['id'] = isset($simUnity['id']) ? (string) $simUnity['id'] : null;
    $out['data']['properties']['block'] = isset($simUnity['block']) ? (string) $simUnity['block'] : null;
    $out['data']['properties']['name'] = isset($simUnity['number']) ? (string) $simUnity['number'] : null;
    $out['data']['properties']['external_id'] = isset($simUnity['id']) ? (string) $simUnity['id'] : null;
    $out['data']['properties']['price'] = isset($simulation['property_value']) ? (string) $simulation['property_value'] : null;
    $out['data']['properties']['total_area'] = isset($simUnity['area']) ? str_replace(',', '.', (string) $simUnity['area']) : null;
    $out['data']['properties']['properties_status']['name'] = 'Vendido';
    $out['data']['properties']['properties_status']['type'] = 3;

    if (isset($out['data']['properties']['allotments']) && is_array($out['data']['properties']['allotments'])) {
        $out['data']['properties']['allotments']['name'] = pickValue(
            $deal['product']['name'] ?? null,
            $template['data']['properties']['allotments']['name'] ?? null
        );
        $out['data']['properties']['allotments']['prefix_sale'] = pickValue(
            $template['data']['properties']['allotments']['prefix_sale'] ?? null
        );
    }

    $out['data']['reserve_type'] = $template['data']['reserve_type'] ?? [];
    $out['data']['reserve_status'] = $template['data']['reserve_status'] ?? [];

    $templateReserveClient = $template['data']['reserves_clients'][0] ?? [];
    $templateClient = $templateReserveClient['clients'] ?? [];
    $out['data']['reserves_clients'] = [
        mapCustomerToReserveClient($simCustomer, $templateClient, $templateReserveClient['company_id'] ?? null),
    ];

    $out['data']['realtor_data'] = mapRealtor($saleParsed['owners'] ?? [], $template['data']['realtor_data'] ?? []);
    $out['data']['realtor_id'] = isset($saleParsed['owners']['broker']['id']) ? (string) $saleParsed['owners']['broker']['id'] : null;

    $proposal = $template['data']['business_proposal'][0] ?? [];
    $proposal['id'] = isset($simulation['id']) ? (string) $simulation['id'] : null;
    $proposal['reserve_id'] = isset($out['data']['id']) ? (string) $out['data']['id'] : null;
    $proposal['name'] = pickValue($saleParsed['name'] ?? null);
    $proposal['status'] = 'APROVADA';
    $proposal['price_of_property'] = isset($simulation['property_value']) ? (string) $simulation['property_value'] : null;
    $proposal['value_with_interest'] = isset($simulation['proposal_value_total']) ? (string) $simulation['proposal_value_total'] : null;
    $proposal['veredict_date'] = pickValue($saleParsed['saled_at'] ?? null);
    $proposal['parcel_configs'] = buildParcelConfigs($simulation, $proposal);
    $proposal['description_installment_type_one'] = null;
    $proposal['description_installment_type_two'] = null;
    $proposal['description_installment_type_three'] = null;
    $proposal['description_installment_type_four'] = null;
    $proposal['description_installment_type_five'] = null;
    $out['data']['business_proposal'] = [$proposal];

    return $out;
}

$opts = getopt('', ['template:', 'sale:', 'simulation:', 'output:']);

$templatePath = $opts['template'] ?? 'files/Reserve_confirm_recente.json';
$salePath = $opts['sale'] ?? 'files/venda_ganha.json';
$simulationPath = $opts['simulation'] ?? 'files/simulation.json';
$outputPath = $opts['output'] ?? 'files/Reserve_confirm_gerado.json';

try {
    $template = readJson($templatePath);
    $sale = readJson($salePath);
    $simulation = readJson($simulationPath);

    $output = applyMapping($template, $sale, $simulation);
    $json = json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException('Falha ao serializar JSON final.');
    }

    if (file_put_contents($outputPath, $json . PHP_EOL) === false) {
        throw new RuntimeException("Nao foi possivel salvar arquivo de saida: {$outputPath}");
    }

    echo "Arquivo gerado com sucesso: {$outputPath}" . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'Erro: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
