<?php

declare(strict_types=1);

namespace R2Soft\AppFacilita\Tests;

use PHPUnit\Framework\TestCase;
use R2Soft\AppFacilita\Core\AppFacilitaConfig;

class AppFacilitaConfigTest extends TestCase
{
    public function testFromCredentialsBuildsConfig(): void
    {
        $config = AppFacilitaConfig::fromCredentials([
            'base_url' => 'https://api.example.test',
            'api_instance' => 'inst',
            'api_key' => 'key',
            'token_user' => 'token',
        ], [
            'timeout' => 20,
            'retry_max' => 5,
        ]);

        $this->assertSame('https://api.example.test', $config->getBaseUrl());
        $this->assertSame('inst', $config->getApiInstance());
        $this->assertSame('key', $config->getApiKey());
        $this->assertSame('token', $config->getTokenUser());
        $this->assertSame(20, $config->getTimeout());
        $this->assertSame(5, $config->getRetryMax());
    }
}
