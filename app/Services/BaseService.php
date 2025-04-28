<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Throwable;

abstract class BaseService
{
    /**
     * @throws Throwable
     */
    protected function transaction(callable $callback)
    {
        return DB::transaction(function () use ($callback) {
            return call_user_func($callback);
        });
    }
}
