<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use R2Soft\AppFacilita\Clients\AppFacilitaClient;
use R2Soft\AppFacilita\Core\AppFacilitaConfig;

$config = new AppFacilitaConfig(
    'https://api.appfacilita.exemplo',
    'sua-instancia',
    'sua-api-key',
    'seu-token'
);

$client = new AppFacilitaClient($config);

$response = $client->listarEquipes();
var_dump($response);
