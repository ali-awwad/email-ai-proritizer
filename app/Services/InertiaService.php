<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class InertiaService
{
    public static function render(string $component, array $props = []): Response|JsonResponse
    {
        $page = [
            'component' => $component,
            'props' => $props,
            'url' => request()->url(),
            'version' => '1.0',
        ];

        if (request()->header('X-Inertia')) {
            return response()->json($page);
        }

        return response()->view('app', [
            'page' => $page,
        ]);
    }
}