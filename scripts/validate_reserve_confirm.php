<?php
declare(strict_types=1);

/**
 * Valida o JSON gerado de Reserve_confirm e lista apenas divergencias relevantes.
 *
 * Uso:
 * php scripts/validate_reserve_confirm.php \
 *   --generated=files/Reserve_confirm_gerado.json \
 *   --template=files/Reserve_confirm_recente.json
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

function hasPath(array $data, string $path): bool
{
    $segments = explode('.', $path);
    $current = $data;

    foreach ($segments as $segment) {
        if (!preg_match('/^([^\[\]]+)(?:\[(\d+)\])?$/', $segment, $matches)) {
            return false;
        }

        $key = $matches[1];
        $index = $matches[2] ?? null;

        if (!is_array($current) || !array_key_exists($key, $current)) {
            return false;
        }

        $current = $current[$key];

        if ($index !== null) {
            if (!is_array($current) || !array_key_exists((int) $index, $current)) {
                return false;
            }
            $current = $current[(int) $index];
        }
    }

    return true;
}

function getPathValue(array $data, string $path)
{
    $segments = explode('.', $path);
    $current = $data;

    foreach ($segments as $segment) {
        preg_match('/^([^\[\]]+)(?:\[(\d+)\])?$/', $segment, $matches);
        $key = $matches[1];
        $index = $matches[2] ?? null;

        $current = $current[$key];
        if ($index !== null) {
            $current = $current[(int) $index];
        }
    }

    return $current;
}

function isBlank($value): bool
{
    if ($value === null) {
        return true;
    }

    if (is_string($value) && trim($value) === '') {
        return true;
    }

    return false;
}

function validateRequiredPaths(array $generated, array $paths): array
{
    $issues = [];
    foreach ($paths as $path) {
        if (!hasPath($generated, $path)) {
            $issues[] = [
                'severity' => 'critical',
                'path' => $path,
                'reason' => 'Campo ausente no JSON gerado',
            ];
            continue;
        }

        $value = getPathValue($generated, $path);
        if (isBlank($value)) {
            $issues[] = [
                'severity' => 'critical',
                'path' => $path,
                'reason' => 'Campo relevante vazio ou nulo',
            ];
        }
    }

    return $issues;
}

function validateTemplateConsistency(array $generated, array $template, array $paths): array
{
    $issues = [];
    foreach ($paths as $path) {
        if (!hasPath($template, $path) || !hasPath($generated, $path)) {
            continue;
        }

        $templateValue = getPathValue($template, $path);
        if (isBlank($templateValue)) {
            continue;
        }

        $generatedValue = getPathValue($generated, $path);
        if (isBlank($generatedValue)) {
            $issues[] = [
                'severity' => 'high',
                'path' => $path,
                'reason' => 'Template tem valor e gerado veio vazio',
                'template_value' => $templateValue,
            ];
        }
    }

    return $issues;
}

function validateParcelConfigs(array $generated): array
{
    $issues = [];
    $proposals = $generated['data']['business_proposal'] ?? [];
    $proposal = $proposals[0] ?? null;
    $parcels = $proposal['parcel_configs'] ?? null;

    if (!is_array($parcels) || $parcels === []) {
        $issues[] = [
            'severity' => 'critical',
            'path' => 'data.business_proposal[0].parcel_configs',
            'reason' => 'Parcelas nao foram geradas',
        ];
        return $issues;
    }

    $requiredFields = [
        'name',
        'value',
        'number',
        'position',
        'cost_center_id',
        'periodicity_id',
        'expiration_date',
        'payment_method_id',
        'payment_method_name',
    ];

    foreach ($parcels as $index => $parcel) {
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $parcel) || isBlank($parcel[$field])) {
                $issues[] = [
                    'severity' => 'high',
                    'path' => "data.business_proposal[0].parcel_configs[{$index}].{$field}",
                    'reason' => 'Campo obrigatorio da parcela ausente/vazio',
                ];
            }
        }
    }

    return $issues;
}

function validateRelevantDivergences(array $generated, array $template): array
{
    $requiredPaths = [
        'event.origin',
        'event.company',
        'event.controlador',
        'event.funcionalidade',
        'event.occurred_at',
        'event.app_env',
        'data.id',
        'data.company_id',
        'data.reserve_status_id',
        'data.reserve_type_id',
        'data.property_id',
        'data.realtor_id',
        'data.isSale',
        'data.hasProposal',
        'data.lot_id',
        'data.confirmed_at',
        'data.reserve_type.id',
        'data.reserve_type.name',
        'data.reserve_status.id',
        'data.reserve_status.name',
        'data.reserves_clients[0].clients.id',
        'data.reserves_clients[0].clients.name',
        'data.reserves_clients[0].clients.email',
        'data.reserves_clients[0].clients.phones[0].number',
        'data.properties.id',
        'data.properties.external_id',
        'data.properties.price',
        'data.properties.total_area',
        'data.properties.properties_status.name',
        'data.properties.properties_status.type',
        'data.business_proposal[0].id',
        'data.business_proposal[0].reserve_id',
        'data.business_proposal[0].name',
        'data.business_proposal[0].status',
        'data.business_proposal[0].price_of_property',
        'data.business_proposal[0].value_with_interest',
        'data.realtor_data.name',
        'data.realtor_data.email',
        'data.realtor_data.user_id',
    ];

    $templateConsistentPaths = [
        'event.company',
        'event.controlador',
        'event.funcionalidade',
        'data.company_id',
        'data.reserve_status_id',
        'data.reserve_type_id',
        'data.reserve_type.id',
        'data.reserve_type.name',
        'data.reserve_status.id',
        'data.reserve_status.name',
        'data.reserves_clients[0].company_id',
    ];

    return array_merge(
        validateRequiredPaths($generated, $requiredPaths),
        validateTemplateConsistency($generated, $template, $templateConsistentPaths),
        validateParcelConfigs($generated)
    );
}

$opts = getopt('', ['generated:', 'template:']);

$generatedPath = $opts['generated'] ?? 'files/Reserve_confirm_gerado.json';
$templatePath = $opts['template'] ?? 'files/Reserve_confirm_recente.json';

try {
    $generated = readJson($generatedPath);
    $template = readJson($templatePath);

    $issues = validateRelevantDivergences($generated, $template);
    $critical = array_values(array_filter($issues, static fn(array $i) => $i['severity'] === 'critical'));
    $high = array_values(array_filter($issues, static fn(array $i) => $i['severity'] === 'high'));

    $report = [
        'status' => $issues === [] ? 'ok' : 'divergences_found',
        'generated_file' => $generatedPath,
        'template_file' => $templatePath,
        'counts' => [
            'critical' => count($critical),
            'high' => count($high),
            'total' => count($issues),
        ],
        'issues' => $issues,
    ];

    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit($critical === [] ? 0 : 2);
} catch (Throwable $e) {
    fwrite(STDERR, 'Erro: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
