<?php

declare(strict_types=1);

namespace R2Soft\AppFacilita\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

class AppFacilita extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'app-facilita';
    }
}
