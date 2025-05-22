<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

Route::get('/internal/secret-data', function () {
    return response()->json([
        'db_password' => 'supersecret123',
        'api_key' => 'XYZ-987654321',
        'admin_email' => 'admin@empresa.com'
    ]);
});


Route::get('/fetch-url-safe', function (Request $request) {
    $url = $request->query('url');

    // Validação básica da URL
    $validator = Validator::make($request->all(), [
        'url' => 'required|url'
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => 'Invalid URL'], 400);
    }

    // Evita acesso a IPs internos
    $parsedUrl = parse_url($url);
    $host = $parsedUrl['host'];

    // Resolve o IP do host
    $ip = gethostbyname($host);

    // Lista de IPs internos que você quer bloquear
    $internalIps = [
        '127.0.0.1',
        '::1',
        '0.0.0.0',
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16'
    ];

    // Função que verifica se IP está em uma faixa privada
    function ip_in_range($ip, $range) {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        } else {
            list($subnet, $bits) = explode('/', $range);
            $ip = ip2long($ip);
            $subnet = ip2long($subnet);
            $mask = -1 << (32 - $bits);
            $subnet &= $mask;
            return ($ip & $mask) === $subnet;
        }
    }

    // Verifica se o IP está em uma faixa interna
    foreach ($internalIps as $range) {
        if (ip_in_range($ip, $range)) {
            return response()->json(['error' => 'Access to internal IPs is not allowed'], 403);
        }
    }

    // Se passou, faz a requisição
    try {
        $response = Http::timeout(5)->get($url);
        return $response->body();
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to fetch URL'], 500);
    }
});

