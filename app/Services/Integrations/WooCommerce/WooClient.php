<?php

namespace App\Services\Integrations\WooCommerce;

use App\Models\Integration;
use App\Models\IntegrationLog;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class WooClient
{
    public function __construct(protected Integration $integration)
    {
    }

    protected function baseUrl(): string
    {
        return rtrim($this->integration->store_url, '/');
    }

    protected function request(string $method, string $endpoint, array $query = []): Response
    {
        $url = $this->baseUrl() . '/wp-json/wc/v3/' . ltrim($endpoint, '/');

        $query = array_merge($query, [
            'consumer_key' => $this->integration->api_key,
            'consumer_secret' => $this->integration->api_secret,
        ]);

        $request = Http::acceptJson()->timeout(30);

        if (app()->environment('local')) {
            $request = $request->withoutVerifying();
        }
        
        $response = $request->$method($url, $query);

        IntegrationLog::create([
            'integration_id' => $this->integration->id,
            'direction' => 'outbound',
            'event_type' => 'woo_api_' . $method,
            'request_url' => $url,
            'request_headers' => json_encode([
                'Accept' => 'application/json',
            ]),
            'request_body' => json_encode($query),
            'response_code' => $response->status(),
            'response_body' => $response->body(),
            'status' => $response->successful() ? 'success' : 'failed',
        ]);

        return $response;
    }

    public function testConnection(): array
    {
        $response = $this->request('get', 'orders', [
            'per_page' => 1,
            'orderby' => 'date',
            'order' => 'desc',
        ]);

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'data' => $response->json(),
            'raw_body' => $response->body(),
            'message' => $response->successful()
                ? 'WooCommerce connection successful.'
                : ($response->body() ?: 'WooCommerce connection failed.'),
        ];
    }

    public function getOrders(array $params = []): array
    {
        $response = $this->request('get', 'orders', $params);

        if (!$response->successful()) {
            throw new \Exception(
                'Woo getOrders failed. HTTP ' . $response->status() . ' | Body: ' . $response->body()
            );
        }

        return $response->json();
    }

    public function getOrder(int|string $wooOrderId): array
    {
        $response = $this->request('get', 'orders/' . $wooOrderId);

        if (!$response->successful()) {
            throw new \Exception(
                'Woo getOrder failed. HTTP ' . $response->status() . ' | Body: ' . $response->body()
            );
        }

        return $response->json();
    }
}