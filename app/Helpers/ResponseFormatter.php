<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

trait ResponseFormatter
{
    public function success($data = null, int $statusCode = 200, bool $wrapData = true): JsonResponse
    {
        $response = [];

        if ($wrapData) {
            $response['data'] = $data;
        } else {
            $response = array_merge($response, (array) $data);
        }

        if (app()->environment('local')) {
            $response['debug'] = [
                'memory_usage' => memory_get_usage(true) . ' bytes',
                'laravel_version' => app()->version(),
                'query_count' => count(DB::getQueryLog()),
                'duration' => round((microtime(true) - LARAVEL_START) * 1000) . ' ms',
            ];
        }

        return response()->json($response, $statusCode);
    }

    public function error(int $statusCode = 400, $errors = null): JsonResponse
    {
        $response = [
            'errors' => $errors,
        ];

        if (app()->environment('local')) {
            $response['debug'] = [
                'memory_usage' => memory_get_usage(true) . ' bytes',
                'laravel_version' => app()->version(),
                'query_count' => count(DB::getQueryLog()),
                'duration' => round((microtime(true) - LARAVEL_START) * 1000) . ' ms',
            ];
        }

        return response()->json($response, $statusCode);
    }
}
