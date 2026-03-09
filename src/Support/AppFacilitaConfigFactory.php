<?php

declare(strict_types=1);

namespace R2Soft\AppFacilita\Support;

use R2Soft\AppFacilita\Core\AppFacilitaConfig;

class AppFacilitaConfigFactory
{
    public static function fromEnv(string $prefix = 'APP_FACILITA_'): AppFacilitaConfig
    {
        $baseUrl = getenv($prefix . 'BASE_URL');
        $apiInstance = getenv($prefix . 'API_INSTANCE');
        $apiKey = getenv($prefix . 'API_KEY');
        $tokenUser = getenv($prefix . 'TOKEN');
        $timeout = getenv($prefix . 'TIMEOUT');
        $connectTimeout = getenv($prefix . 'CONNECT_TIMEOUT');
        $userAgent = getenv($prefix . 'USER_AGENT');
        $proxy = getenv($prefix . 'PROXY');
        $retryMax = getenv($prefix . 'RETRY_MAX');
        $retryBaseMs = getenv($prefix . 'RETRY_BASE_MS');

        return new AppFacilitaConfig(
            (string) $baseUrl,
            (string) $apiInstance,
            (string) $apiKey,
            (string) $tokenUser,
            $timeout ? (int) $timeout : 10,
            $connectTimeout ? (int) $connectTimeout : null,
            $userAgent ?: null,
            $proxy ?: null,
            $retryMax ? (int) $retryMax : 3,
            $retryBaseMs ? (int) $retryBaseMs : 250
        );
    }
}
