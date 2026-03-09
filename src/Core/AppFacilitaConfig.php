<?php

declare(strict_types=1);

namespace R2Soft\AppFacilita\Core;

use InvalidArgumentException;

class AppFacilitaConfig
{
    private string $baseUrl;
    private string $apiInstance;
    private string $apiKey;
    private string $tokenUser;
    private int $timeout;
    private int $connectTimeout;
    private string $userAgent;
    private ?string $proxy;
    private int $retryMax;
    private int $retryBaseMs;

    public function __construct(
        string $baseUrl,
        string $apiInstance,
        string $apiKey,
        string $tokenUser,
        int $timeout = 10,
        ?int $connectTimeout = null,
        ?string $userAgent = null,
        ?string $proxy = null,
        int $retryMax = 3,
        int $retryBaseMs = 250
    ) {
        if ($baseUrl === '') {
            throw new InvalidArgumentException('baseUrl is required');
        }
        if ($apiInstance === '') {
            throw new InvalidArgumentException('apiInstance is required');
        }
        if ($apiKey === '') {
            throw new InvalidArgumentException('apiKey is required');
        }
        if ($tokenUser === '') {
            throw new InvalidArgumentException('tokenUser is required');
        }

        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiInstance = $apiInstance;
        $this->apiKey = $apiKey;
        $this->tokenUser = $tokenUser;
        $this->timeout = $timeout;
        $this->connectTimeout = $connectTimeout ?? $timeout;
        $this->userAgent = $userAgent ?: 'r2-app-facilita/AppFacilitaClient';
        $this->proxy = $proxy;
        $this->retryMax = $retryMax;
        $this->retryBaseMs = $retryBaseMs;
    }

    public static function fromCredentials(array $credentials, array $meta = []): self
    {
        $baseUrl = $credentials['base_url'] ?? $meta['base_url'] ?? null;
        $apiInstance = $credentials['api_instance'] ?? $meta['api_instance'] ?? null;
        $apiKey = $credentials['api_key'] ?? $meta['api_key'] ?? null;
        $tokenUser = $credentials['token_user'] ?? $credentials['token'] ?? $meta['token_user'] ?? null;
        $timeout = $meta['timeout'] ?? 10;
        $connectTimeout = $meta['connect_timeout'] ?? null;
        $userAgent = $meta['user_agent'] ?? null;
        $proxy = $meta['proxy'] ?? null;
        $retryMax = $meta['retry_max'] ?? 3;
        $retryBaseMs = $meta['retry_base_ms'] ?? 250;

        if (empty($baseUrl) || empty($apiInstance) || empty($apiKey) || empty($tokenUser)) {
            throw new InvalidArgumentException('Credenciais AppFacilita incompletas.');
        }

        return new self(
            (string) $baseUrl,
            (string) $apiInstance,
            (string) $apiKey,
            (string) $tokenUser,
            (int) $timeout,
            $connectTimeout !== null ? (int) $connectTimeout : null,
            $userAgent !== null ? (string) $userAgent : null,
            $proxy !== null ? (string) $proxy : null,
            (int) $retryMax,
            (int) $retryBaseMs
        );
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getApiInstance(): string
    {
        return $this->apiInstance;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getTokenUser(): string
    {
        return $this->tokenUser;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getConnectTimeout(): int
    {
        return $this->connectTimeout;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function getProxy(): ?string
    {
        return $this->proxy;
    }

    public function getRetryMax(): int
    {
        return $this->retryMax;
    }

    public function getRetryBaseMs(): int
    {
        return $this->retryBaseMs;
    }
}
