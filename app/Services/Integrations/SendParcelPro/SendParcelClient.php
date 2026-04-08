<?php

namespace App\Services\Integrations\SendParcelPro;

use Illuminate\Support\Facades\Http;

class SendParcelClient
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.sendparcel.base_url');
        $this->apiKey = config('services.sendparcel.api_key');
    }

    protected function request(string $method, string $endpoint, array $payload = [])
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Accept' => 'application/json',
        ])->$method($this->baseUrl . $endpoint, $payload);
    }

    public function createShipment(array $data)
    {
        return $this->request('post', '/shipments', $data);
    }

    public function trackShipment(string $trackingNumber)
    {
        return $this->request('get', "/track/{$trackingNumber}");
    }

    public function cancelShipment(string $trackingNumber)
    {
        return $this->request('post', "/cancel/{$trackingNumber}");
    }
}