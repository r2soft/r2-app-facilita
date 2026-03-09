<?php

declare(strict_types=1);

namespace R2Soft\AppFacilita\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use R2Soft\AppFacilita\Clients\AppFacilitaClient;
use R2Soft\AppFacilita\Core\AppFacilitaConfig;
use R2Soft\AppFacilita\Exceptions\AppFacilitaValidationException;

class AppFacilitaClientTest extends TestCase
{
    public function testRequestReturnsDecodedBody(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['ok' => true])),
        ]);
        $handler = HandlerStack::create($mock);
        $http = new Client(['handler' => $handler, 'base_uri' => 'https://api.example.test/']);

        $config = new AppFacilitaConfig('https://api.example.test', 'inst', 'key', 'token');
        $client = new AppFacilitaClient($config, $http);

        $result = $client->request('GET', '/platform/v1/teams');
        $this->assertSame(['ok' => true], $result);
    }

    public function testRequestThrowsValidationException(): void
    {
        $mock = new MockHandler([
            new Response(422, [], json_encode(['message' => 'Erro de validacao'])),
        ]);
        $handler = HandlerStack::create($mock);
        $http = new Client(['handler' => $handler, 'base_uri' => 'https://api.example.test/']);

        $config = new AppFacilitaConfig('https://api.example.test', 'inst', 'key', 'token');
        $client = new AppFacilitaClient($config, $http);

        $this->expectException(AppFacilitaValidationException::class);
        $client->request('GET', '/platform/v1/custom-field');
    }
}
