<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SSRFDemoController extends Controller
{

    public function index()
    {
        return view('ssrf_test_page');
    }


    public function fetchUrl(Request $request)
    {
        $url = $request->input('url');
        $method = strtoupper($request->input('method', 'GET'));
        $postData = $request->input('data', []);
        $customHeadersInput = $request->input('headers', []);

        if (!$url) {
            return response()->json(['error' => 'Parâmetro "url" não fornecido.'], 400);
        }
        if (!in_array($method, ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'])) {
            return response()->json(['error' => 'Método HTTP não suportado ou inválido.'], 400);
        }

        try {
            $httpClient = Http::timeout(5);

            if (!empty($customHeadersInput)) {
                $parsedHeaders = [];
                if (is_string($customHeadersInput)) {
                    $lines = explode("\n", trim($customHeadersInput));
                    foreach ($lines as $line) {
                        if (strpos($line, ':') !== false) {
                            list($name, $value) = explode(':', $line, 2);
                            $parsedHeaders[trim($name)] = trim($value);
                        }
                    }
                } elseif (is_array($customHeadersInput)) {
                    $parsedHeaders = $customHeadersInput;
                }

                if (!empty($parsedHeaders)) {
                    $httpClient = $httpClient->withHeaders($parsedHeaders);
                }
            }

            $response = null;

            switch ($method) {
                case 'POST':
                    $response = $httpClient->post($url, $postData);
                    break;
                case 'PUT':
                    $response = $httpClient->put($url, $postData);
                    break;
                case 'PATCH':
                    $response = $httpClient->patch($url, $postData);
                    break;
                case 'DELETE':
                    $response = $httpClient->delete($url, $postData);
                    break;
                default: // GET, HEAD, OPTIONS
                    // ===== CORREÇÃO DEFINITIVA APLICADA AQUI =====
                    // Para GET, ignoramos o campo "Dados" e usamos a URL Alvo exatamente como está.
                    // Chamamos get() SEM o segundo argumento para não interferir na query string original da URL.
                    if ($method === 'GET') {
                        $response = $httpClient->get($url);
                    } else {
                        // Para outros métodos como HEAD, OPTIONS
                        $response = $httpClient->{$method}($url);
                    }
                    break;
                // ===== FIM DA CORREÇÃO =====
            }

            if ($response->successful()) {
                return response($response->body(), 200)
                    ->header('Content-Type', 'application/json');
            } elseif ($response->failed()) {
                return response()->json([
                    'error' => 'Falha ao buscar a URL.',
                    'status_code' => $response->status(),
                    'reason' => $response->reason(),
                    'body' => htmlspecialchars(substr($response->body(), 0, 1000))
                ], $response->status() > 0 ? $response->status() : 500);
            } else {
                return response()->json(['error' => 'Erro desconhecido ao buscar a URL.','url_requested' => $url], 500);
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json(['error' => 'Erro de conexão: ' . $e->getMessage(), 'url_requested' => $url], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro inesperado: ' . $e->getMessage(), 'url_requested' => $url], 500);
        }
    }

    // O método fetchUrlSecure não precisa de alterações, pois ele já faz ->get($urlToFetch) sem o segundo argumento.
    public function fetchUrlSecure(Request $request)
    {
        $urlToFetch = $request->input('url');

        if (!$urlToFetch) {
            return response()->json(['error' => 'Parâmetro "url" não fornecido.'], 400);
        }

        $allowedSchemes = ['http', 'https'];
        $allowedHosts = [
            'www.google.com',
            'jsonplaceholder.typicode.com',
            'httpbin.org',
            'example.com'
        ];

        $parsedUrl = parse_url($urlToFetch);

        if ($parsedUrl === false || empty($parsedUrl['scheme']) || empty($parsedUrl['host'])) {
            return response()->json(['error' => 'URL inválida ou malformada.'], 400);
        }

        $scheme = strtolower($parsedUrl['scheme']);
        $host = strtolower($parsedUrl['host']);

        if (!in_array($scheme, $allowedSchemes)) {
            return response()->json(['error' => "Esquema de URL não permitido: {$scheme}. Apenas HTTP e HTTPS são permitidos."], 403);
        }

        $isAllowedHost = false;
        foreach ($allowedHosts as $allowedHostItem) {
            if ($host === $allowedHostItem || \Illuminate\Support\Str::endsWith($host, '.' . $allowedHostItem)) {
                $isAllowedHost = true;
                break;
            }
        }

        if (!$isAllowedHost) {
            return response()->json(['error' => "Host não permitido: {$host}. O acesso é restrito a domínios pré-aprovados."], 403);
        }

        try {
            $ip = gethostbyname($host);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return response()->json(['error' => "O host '{$host}' resolveu para um endereço IP não público ou reservado ({$ip}). Acesso bloqueado."], 403);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => "Não foi possível validar o IP para o host: {$host}. Detalhe: " . $e->getMessage()], 500);
        }

        try {
            $response = Http::timeout(5)->get($urlToFetch);

            if ($response->successful()) {
                return response($response->body(), 200)
                    ->header('Content-Type', 'application/json');
            } else {
                return response()->json([
                    'error' => 'Falha ao buscar a URL (após validação).',
                    'status_code' => $response->status(),
                    'reason' => $response->reason()
                ], $response->status() > 0 ? $response->status() : 500);
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json(['error' => 'Erro de conexão (após validação): ' . $e->getMessage(), 'url_requested' => $urlToFetch], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro inesperado (após validação): ' . $e->getMessage(), 'url_requested' => $urlToFetch], 500);
        }
    }
}
