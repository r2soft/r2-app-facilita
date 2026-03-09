# r2-app-facilita

Pacote PHP com cliente e utilitarios para integracao com a API AppFacilita, mais DTOs de webhook. O nucleo eh agnostico de framework, com camada opcional de integracao para Laravel.

## Requisitos

- PHP >= 8.0
- ext-json

## Instalacao

Via Composer:

```bash
composer require r2soft/app-facilita
```

## Uso em PHP puro

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use R2Soft\AppFacilita\Clients\AppFacilitaClient;
use R2Soft\AppFacilita\Core\AppFacilitaConfig;

$config = new AppFacilitaConfig(
    'https://api.appfacilita.exemplo',
    'sua-instancia',
    'sua-api-key',
    'seu-token'
);

$client = new AppFacilitaClient($config);

$negocio = $client->consultarNegocio(1234);
```

Opcionalmente, usando variaveis de ambiente:

```php
use R2Soft\AppFacilita\Support\AppFacilitaConfigFactory;

$config = AppFacilitaConfigFactory::fromEnv();
$client = new AppFacilitaClient($config);
```

## Uso em Laravel

1. Instale via Composer.
2. (Opcional) Publique o config:

```bash
php artisan vendor:publish --tag=config --provider="R2\\AppFacilita\\Laravel\\Providers\\AppFacilitaServiceProvider"
```

3. Configure as variaveis de ambiente:

- `APP_FACILITA_BASE_URL`
- `APP_FACILITA_API_INSTANCE`
- `APP_FACILITA_API_KEY`
- `APP_FACILITA_TOKEN`
- opcionais: `APP_FACILITA_TIMEOUT`, `APP_FACILITA_CONNECT_TIMEOUT`, `APP_FACILITA_USER_AGENT`, `APP_FACILITA_PROXY`, `APP_FACILITA_RETRY_MAX`, `APP_FACILITA_RETRY_BASE_MS`

4. Uso via container:

```php
use R2Soft\AppFacilita\Clients\AppFacilitaClient;

$client = app(AppFacilitaClient::class);
$negocio = $client->consultarNegocio(1234);
```

5. Uso via Facade (opcional):

```php
use AppFacilita;

$negocio = AppFacilita::consultarNegocio(1234);
```

## DTOs

Os DTOs de webhook agora estao em `R2Soft\AppFacilita\DTOs`.

## Configuracao

```php
new AppFacilitaConfig(
    baseUrl: 'https://api.appfacilita.exemplo',
    apiInstance: 'instancia',
    apiKey: 'api-key',
    tokenUser: 'token',
    timeout: 10,
    connectTimeout: 10,
    userAgent: 'r2-app-facilita/AppFacilitaClient',
    proxy: null,
    retryMax: 3,
    retryBaseMs: 250
);
```

## Limitações e extensões

- O cliente HTTP usa Guzzle por padrao. Se precisar trocar, injete um `ClientInterface`.
- A camada Laravel e opcional e nao eh dependida pelo nucleo.

## Exemplos

Veja `examples/php-puro.php` e `examples/laravel.php`.

## Testes

```bash
composer test
```
