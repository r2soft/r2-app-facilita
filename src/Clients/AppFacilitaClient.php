<?php

declare(strict_types=1);

namespace R2Soft\AppFacilita\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use R2Soft\AppFacilita\Core\AppFacilitaConfig;
use R2Soft\AppFacilita\Exceptions\AppFacilitaAuthException;
use R2Soft\AppFacilita\Exceptions\AppFacilitaException;
use R2Soft\AppFacilita\Exceptions\AppFacilitaRateLimitException;
use R2Soft\AppFacilita\Exceptions\AppFacilitaServerException;
use R2Soft\AppFacilita\Exceptions\AppFacilitaValidationException;

class AppFacilitaClient
{
    private AppFacilitaConfig $config;
    private ClientInterface $httpClient;
    private ?LoggerInterface $logger;

    public function __construct(
        AppFacilitaConfig $config,
        ?ClientInterface $httpClient = null,
        ?LoggerInterface $logger = null,
        ?HandlerStack $handlerStack = null
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->httpClient = $httpClient ?: $this->buildHttpClient($handlerStack);
    }

    public function uploadArquivo(string $filePath, string $name): array
    {
        if (!is_file($filePath)) {
            throw new AppFacilitaValidationException('Arquivo nao encontrado para upload.');
        }

        return $this->request('POST', '/platform/v1/files', [
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => fopen($filePath, 'r'),
                    'filename' => basename($filePath),
                ],
                [
                    'name' => 'name',
                    'contents' => $name,
                ],
            ],
        ]);
    }

    public function listarCamposPersonalizados(string $type): array
    {
        return $this->request('GET', '/platform/v1/custom-field', [
            'query' => ['type' => $type],
        ]);
    }

    public function listarCamposPersonalizadosFormatado(string $type, int $id): array
    {
        return $this->request('GET', '/platform/v1/custom-field/simple', [
            'query' => ['type' => $type, 'id' => $id],
        ]);
    }

    public function atualizarCampoRepeaterNegocio(array $payload): array
    {
        $this->requireFields($payload, ['type', 'fieldable_id', 'repeater_row_id', 'slug', 'slug-repeater', 'value']);

        return $this->request('PUT', '/platform/v1/custom-field/repeater', [
            'json' => $payload,
        ]);
    }

    public function atualizarCampoPersonalizado(array $payload): array
    {
        $this->requireFields($payload, ['type', 'fieldable_id', 'slug', 'value']);

        return $this->request('PUT', '/platform/v1/custom-field', [
            'json' => $payload,
        ]);
    }

    public function atualizarCliente(int $customerId, array $payload): array
    {
        return $this->request('PUT', '/platform/v1/customer', [
            'query' => ['customer_id' => $customerId],
            'json' => $payload,
        ]);
    }

    public function buscarCliente(string $query): array
    {
        return $this->request('GET', '/platform/v1/customer/search', [
            'query' => ['q' => $query],
        ]);
    }

    public function criarCliente(array $payload): array
    {
        $this->requireFields($payload, ['name']);

        return $this->request('POST', '/platform/v1/customer', [
            'json' => $payload,
        ]);
    }

    public function disponibilidade(int $availabilityId, ?int $perPage = null, ?int $page = null): array
    {
        $query = ['availability_id' => $availabilityId];
        if ($perPage !== null) {
            $query['per_page'] = $perPage;
        }
        if ($page !== null) {
            $query['page'] = $page;
        }

        return $this->request('GET', '/platform/v1/availability', [
            'query' => $query,
        ]);
    }

    public function listarEmpreendimentos(?int $perPage = null, ?int $page = null): array
    {
        $query = [];
        if ($perPage !== null) {
            $query['per_page'] = $perPage;
        }
        if ($page !== null) {
            $query['page'] = $page;
        }

        return $this->request('GET', '/platform/v1/products', [
            'query' => $query,
        ]);
    }

    public function visualizarEmpreendimento(int $productId): array
    {
        return $this->request('GET', '/platform/v1/products/show', [
            'query' => ['product_id' => $productId],
        ]);
    }

    public function listarEquipes(): array
    {
        return $this->request('GET', '/platform/v1/teams');
    }

    public function listarGerentesEquipe(int $teamId): array
    {
        return $this->request('GET', '/platform/v1/managers', [
            'query' => ['team_id' => $teamId],
        ]);
    }

    public function listarSupervisoresEquipe(int $teamId): array
    {
        return $this->request('GET', '/platform/v1/supervisors', [
            'query' => ['team_id' => $teamId],
        ]);
    }

    public function listarCorretoresGerente(int $managerId): array
    {
        return $this->request('GET', '/platform/v1/brokers', [
            'query' => ['manager_id' => $managerId],
        ]);
    }

    public function enviarLeadFila(int $queue, array $payload): array
    {
        $this->requireFields($payload, ['customer', 'deal']);

        return $this->request('POST', '/platform/v1/lead', [
            'query' => ['queue' => $queue],
            'json' => $payload,
        ]);
    }

    public function redistribuirNegocio(array $payload): array
    {
        $this->requireFields($payload, ['deal_id', 'redistribute_lead_id']);

        return $this->request('PUT', '/platform/v1/leads/redistribute', [
            'json' => $payload,
        ]);
    }

    public function listarEtapasStatus(): array
    {
        return $this->request('GET', '/platform/v1/funnel');
    }

    public function moverNegocio(int $dealId, array $payload): array
    {
        $this->requireFields($payload, ['funnel_stage_id']);

        return $this->request('PUT', '/platform/v1/funnel/move', [
            'query' => ['deal_id' => $dealId],
            'json' => $payload,
        ]);
    }

    public function listarFunis(?int $page = null, ?string $sort = null): array
    {
        $query = [];
        if ($page !== null) {
            $query['page'] = $page;
        }
        if ($sort !== null) {
            $query['sort'] = $sort;
        }

        return $this->request('GET', '/platform/v1/funnels', [
            'query' => $query,
        ]);
    }

    public function listarNegocios(?int $page = null, ?int $perPage = null, ?string $sort = null): array
    {
        $query = [];
        if ($page !== null) {
            $query['page'] = $page;
        }
        if ($perPage !== null) {
            $query['per_page'] = $perPage;
        }
        if ($sort !== null) {
            $query['sort'] = $sort;
        }

        return $this->request('GET', '/platform/v1/deals', [
            'query' => $query,
        ]);
    }

    public function atualizarCamposPersonalizadosNegocio(int $dealId, int $groupFieldId, array $payload): array
    {
        return $this->request('PUT', '/platform/v1/lead/customfield', [
            'query' => ['deal_id' => $dealId, 'group_field_id' => $groupFieldId],
            'json' => $payload,
        ]);
    }

    public function atualizarNegocio(int $dealId, array $payload): array
    {
        return $this->request('PUT', '/platform/v1/deal', [
            'query' => ['deal_id' => $dealId],
            'json' => $payload,
        ]);
    }

    public function alterarResponsavelNegocio(int $dealId, int $teamId, int $managerId, int $brokerId): array
    {
        return $this->request('PUT', '/platform/v1/funnel/changeowner', [
            'query' => [
                'deal_id' => $dealId,
                'team_id' => $teamId,
                'manager_id' => $managerId,
                'broker_id' => $brokerId,
            ],
        ]);
    }

    public function buscarNegocio(string $query): array
    {
        return $this->request('GET', '/platform/v1/deal/search', [
            'query' => ['q' => $query],
        ]);
    }

    public function criarNegocioComCliente(array $payload, ?string $idempotencyKey = null): array
    {
        $this->requireFields($payload, ['customer', 'deal']);
        return $this->request('POST', '/platform/v1/funnel/deal', [
            'json' => $payload,
            'headers' => $this->idempotencyHeader($idempotencyKey),
        ]);
    }

    public function criarNegocioParaClienteExistente(array $payload, ?string $idempotencyKey = null): array
    {
        $this->requireFields($payload, ['deal']);
        return $this->request('POST', '/platform/v1/funnel/deal', [
            'json' => $payload,
            'headers' => $this->idempotencyHeader($idempotencyKey),
        ]);
    }

    public function criarHistoricoNegocio(array $payload): array
    {
        $this->requireFields($payload, ['type', 'message', 'deal_id']);
        return $this->request('POST', '/platform/v1/deal/historical', [
            'json' => $payload,
        ]);
    }

    public function listarNegociosOciosidade(int $start, int $length, int $idleRule): array
    {
        return $this->request('GET', '/platform/v1/dealsByIdleness', [
            'query' => [
                'start' => $start,
                'length' => $length,
                'idle_rule' => $idleRule,
            ],
        ]);
    }

    public function consultarNegocio(int $dealId): array
    {
        return $this->request('GET', '/platform/v1/deal', [
            'query' => ['deal_id' => $dealId],
        ]);
    }

    public function listarOciosidades(): array
    {
        return $this->request('GET', '/platform/v1/idles');
    }

    public function criarReserva(array $payload, ?string $idempotencyKey = null): array
    {
        $this->requireFields($payload, ['unity_id', 'reserved_user_id', 'product_id', 'deal_id', 'expire_time']);
        return $this->request('POST', '/platform/v1/reserves', [
            'json' => $payload,
            'headers' => $this->idempotencyHeader($idempotencyKey),
        ]);
    }

    public function visualizarReservasUnidade(int $unitId, ?int $perPage = null, ?int $page = null): array
    {
        $query = ['unit_id' => $unitId];
        if ($perPage !== null) {
            $query['per_page'] = $perPage;
        }
        if ($page !== null) {
            $query['page'] = $page;
        }

        return $this->request('GET', '/platform/v1/reserves/show', [
            'query' => $query,
        ]);
    }

    public function renovarReserva(int $id, string $expireTime): array
    {
        return $this->request('PUT', '/platform/v1/reserves', [
            'query' => ['id' => $id],
            'json' => ['id' => $id, 'status' => 'reserved', 'expire_time' => $expireTime],
        ]);
    }

    public function cancelarReserva(int $id, string $reason): array
    {
        return $this->request('PUT', '/platform/v1/reserves', [
            'query' => ['id' => $id],
            'json' => ['id' => $id, 'status' => 'canceled', 'reason' => $reason],
        ]);
    }

    public function listarReservas(array $payload): array
    {
        return $this->request('POST', '/platform/v1/datatable/reserves', [
            'json' => $payload,
        ]);
    }

    public function criarSimulacao(int $dealId, array $payload): array
    {
        return $this->request('POST', '/platform/v1/simulation', [
            'query' => ['deal_id' => $dealId],
            'json' => $payload,
        ]);
    }

    public function visualizarSimulacao(int $dealId): array
    {
        return $this->request('GET', '/platform/v1/simulation', [
            'query' => ['deal_id' => $dealId],
        ]);
    }

    public function listarTabelasVendas(int $availabilityId): array
    {
        return $this->request('GET', '/platform/v1/salestable', [
            'query' => ['availability_id' => $availabilityId],
        ]);
    }

    public function visualizarFluxoTabelaVendas(int $salesTableId): array
    {
        return $this->request('GET', '/platform/v1/salestable/flow', [
            'query' => ['sales_table_id' => $salesTableId],
        ]);
    }

    public function listarTarefas(string $type, int $start, int $length, ?string $forDateRange = null): array
    {
        $query = [
            'type' => $type,
            'start' => $start,
            'length' => $length,
        ];
        if ($forDateRange !== null) {
            $query['fordate'] = $forDateRange;
        }

        return $this->request('GET', '/platform/v1/tasks', [
            'query' => $query,
        ]);
    }

    public function atualizarTarefa(int $taskId, array $payload): array
    {
        return $this->request('PUT', '/platform/v1/tasks', [
            'query' => ['task_id' => $taskId],
            'json' => $payload,
        ]);
    }

    public function criarTarefa(array $payload): array
    {
        $this->requireFields($payload, ['type', 'title', 'for_date']);
        return $this->request('POST', '/platform/v1/tasks', [
            'json' => $payload,
        ]);
    }

    public function listarUsuarios(?int $perPage = null, ?int $page = null): array
    {
        $query = [];
        if ($perPage !== null) {
            $query['per_page'] = $perPage;
        }
        if ($page !== null) {
            $query['page'] = $page;
        }

        return $this->request('GET', '/platform/v1/users/all', [
            'query' => $query,
        ]);
    }

    public function cadastrarWebhook(array $payload): array
    {
        $this->requireFields($payload, ['description', 'url', 'event']);
        return $this->request('POST', '/platform/v1/webhook', [
            'json' => $payload,
        ]);
    }

    public function request(string $method, string $path, array $options = []): array
    {
        $method = strtoupper($method);
        $url = $this->normalizePath($path);

        if (isset($options['json']) && !isset($options['headers']['Content-Type'])) {
            $options['headers']['Content-Type'] = 'application/json';
        }

        $options['headers'] = $this->buildHeaders($options['headers'] ?? []);
        $options['timeout'] = $this->config->getTimeout();
        $options['connect_timeout'] = $this->config->getConnectTimeout();
        $options['http_errors'] = false;

        try {
            $response = $this->httpClient->request($method, $url, $options);
        } catch (ConnectException $e) {
            throw new AppFacilitaServerException('Falha de conexao com AppFacilita.', null, $path, $method);
        } catch (RequestException $e) {
            throw new AppFacilitaServerException('Erro HTTP ao chamar AppFacilita.', null, $path, $method);
        }

        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        $requestId = $this->extractRequestId($response);

        if ($status < 200 || $status >= 300) {
            $this->handleError($response, $body, $method, $path, $requestId);
        }

        return $this->decodeBody($body);
    }

    protected function buildHttpClient(?HandlerStack $stack = null): ClientInterface
    {
        $stack = $stack ?: HandlerStack::create();
        $stack->push($this->createRetryMiddleware());

        $options = [
            'base_uri' => $this->config->getBaseUrl() . '/',
            'handler' => $stack,
        ];

        $proxy = $this->config->getProxy();
        if (!empty($proxy)) {
            $options['proxy'] = $proxy;
        }

        return new Client($options);
    }

    private function buildHeaders(array $extra = []): array
    {
        $headers = [
            'api-instance' => $this->config->getApiInstance(),
            'api-key' => $this->config->getApiKey(),
            'token-user' => $this->config->getTokenUser(),
            'Accept' => 'application/json',
            'User-Agent' => $this->config->getUserAgent(),
        ];

        foreach ($extra as $key => $value) {
            $headers[$key] = $value;
        }

        return $headers;
    }

    protected function createRetryMiddleware(): callable
    {
        $maxRetries = $this->config->getRetryMax();
        $baseMs = $this->config->getRetryBaseMs();

        return Middleware::retry(
            function ($retries, $request, $response = null, $exception = null) use ($maxRetries) {
                if ($retries >= $maxRetries) {
                    return false;
                }

                if ($exception instanceof ConnectException) {
                    return true;
                }

                if ($response instanceof ResponseInterface) {
                    $status = $response->getStatusCode();
                    return in_array($status, [429, 502, 503, 504], true);
                }

                return false;
            },
            function ($retries) use ($baseMs) {
                $jitter = random_int(0, 100);
                return ($baseMs * (2 ** $retries)) + $jitter;
            }
        );
    }

    private function handleError(ResponseInterface $response, string $body, string $method, string $path, ?string $requestId): void
    {
        $status = $response->getStatusCode();
        $decoded = $this->decodeBody($body);
        $message = $this->extractErrorMessage($decoded) ?: 'Erro na API AppFacilita.';

        if (in_array($status, [401, 403], true)) {
            throw new AppFacilitaAuthException($message, $status, $path, $method, $requestId, $decoded);
        }
        if ($status === 422) {
            throw new AppFacilitaValidationException($message, $status, $path, $method, $requestId, $decoded);
        }
        if ($status === 429) {
            throw new AppFacilitaRateLimitException($message, $status, $path, $method, $requestId, $decoded);
        }
        if ($status >= 500) {
            throw new AppFacilitaServerException($message, $status, $path, $method, $requestId, $decoded);
        }

        throw new AppFacilitaException($message, $status, $path, $method, $requestId, $decoded);
    }

    private function extractErrorMessage(array $decoded): ?string
    {
        if (isset($decoded['message']) && is_string($decoded['message'])) {
            return $decoded['message'];
        }
        if (isset($decoded['error']) && is_string($decoded['error'])) {
            return $decoded['error'];
        }
        if (isset($decoded['errors']) && is_array($decoded['errors'])) {
            return json_encode($decoded['errors']);
        }
        return null;
    }

    private function decodeBody(string $body): array
    {
        if ($body === '') {
            return [];
        }
        $decoded = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }
        return ['raw' => $body];
    }

    private function normalizePath(string $path): string
    {
        if (stripos($path, 'http') === 0) {
            return $path;
        }
        return ltrim($path, '/');
    }

    private function extractRequestId(ResponseInterface $response): ?string
    {
        foreach (['x-request-id', 'request-id', 'x-correlation-id'] as $header) {
            if ($response->hasHeader($header)) {
                return $response->getHeaderLine($header);
            }
        }
        return null;
    }

    private function requireFields(array $payload, array $fields): void
    {
        $missing = [];
        foreach ($fields as $field) {
            if (!array_key_exists($field, $payload) || $payload[$field] === null || $payload[$field] === '') {
                $missing[] = $field;
            }
        }
        if (!empty($missing)) {
            throw new AppFacilitaValidationException('Campos obrigatorios ausentes: ' . implode(', ', $missing));
        }
    }

    private function sanitizeBody($body)
    {
        if (is_string($body)) {
            $decoded = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $body = $decoded;
            } else {
                return $this->truncate($body, 2000);
            }
        }

        if (is_array($body)) {
            $body = $this->maskSensitive($body);
        }

        return $body;
    }

    private function sanitizeHeaders(array $headers): array
    {
        $masked = [];
        foreach ($headers as $key => $value) {
            $lower = strtolower((string) $key);
            if (strpos($lower, 'token') !== false || strpos($lower, 'api-key') !== false || strpos($lower, 'authorization') !== false) {
                $masked[$key] = '***';
                continue;
            }
            $masked[$key] = $value;
        }
        return $masked;
    }

    private function maskSensitive(array $data): array
    {
        $masked = [];
        foreach ($data as $key => $value) {
            $lower = strtolower((string) $key);
            if (strpos($lower, 'token') !== false || strpos($lower, 'api-key') !== false || strpos($lower, 'authorization') !== false || strpos($lower, 'password') !== false) {
                $masked[$key] = '***';
                continue;
            }

            if (is_array($value)) {
                $masked[$key] = $this->maskSensitive($value);
            } else {
                $masked[$key] = $value;
            }
        }
        return $masked;
    }

    private function truncate(string $value, int $limit): string
    {
        if (strlen($value) <= $limit) {
            return $value;
        }
        return substr($value, 0, $limit) . '...';
    }

    private function idempotencyHeader(?string $key): array
    {
        if (empty($key)) {
            return [];
        }

        return ['Idempotency-Key' => $key];
    }
}
