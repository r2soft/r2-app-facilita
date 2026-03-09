<?php

declare(strict_types=1);

use R2Soft\AppFacilita\Clients\AppFacilitaClient;

$client = app(AppFacilitaClient::class);

$negocio = $client->consultarNegocio(1234);

// Alternativa com Facade:
// use AppFacilita;
// $negocio = AppFacilita::consultarNegocio(1234);
