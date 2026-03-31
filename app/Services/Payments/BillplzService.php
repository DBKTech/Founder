<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;

class BillplzService
{
    public function createBill(array $data): array
    {
        $payload = [
            'collection_id' => config('services.billplz.collection_id'),
            'email' => $data['email'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'name' => $data['name'],
            'amount' => (int) round(($data['amount'] ?? 0) * 100),
            'callback_url' => $data['callback_url'],
            'redirect_url' => $data['redirect_url'] ?? null,
            'description' => $data['description'],
            'reference_2_label' => 'Order No',
            'reference_2' => $data['order_no'],
        ];

        // For direct gateway flow (example: card), Billplz expects Bank Code
        if (! empty($data['payment_channel'])) {
            $payload['reference_1_label'] = 'Bank Code';
            $payload['reference_1'] = $data['payment_channel'];
        }

        $http = Http::withBasicAuth(config('services.billplz.api_key'), '');

        if (app()->environment('local')) {
            $http = $http->withoutVerifying();
        }

        $response = $http
            ->asForm()
            ->post(config('services.billplz.base_url') . '/bills', $payload);

        $response->throw();

        return $response->json();
    }

    public function getBill(string $billId): array
    {
        $http = Http::withBasicAuth(config('services.billplz.api_key'), '');

        if (app()->environment('local')) {
            $http = $http->withoutVerifying();
        }

        $response = $http
            ->get(config('services.billplz.base_url') . "/bills/{$billId}");

        $response->throw();

        return $response->json();
    }

    public function getPaymentGateways(): array
    {
        $http = Http::withBasicAuth(config('services.billplz.api_key'), '');

        if (app()->environment('local')) {
            $http = $http->withoutVerifying();
        }

        $response = $http->get('https://www.billplz.com/api/v4/payment_gateways');

        $response->throw();

        return $response->json();
    }

    public function getPaymentChannelForMethod(string $method): ?string
    {
        return match ($method) {
            'card' => config('services.billplz.card_gateway_code', 'BP-BILLPLZ1'),
            'fpx' => null,
            default => null,
        };
    }

    public function buildCheckoutUrl(array $bill, ?string $paymentChannel = null): string
    {
        $url = $bill['url'] ?? '';

        if (! $url) {
            return $url;
        }

        // Only append auto_submit when using a specific channel like card
        if (! $paymentChannel) {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . 'auto_submit=true';
    }

    public function isGatewayActive(string $gatewayCode): bool
    {
        $gateways = $this->getPaymentGateways();

        foreach ($gateways as $gateway) {
            if (
                ($gateway['code'] ?? null) === $gatewayCode
                && (bool) ($gateway['active'] ?? false) === true
            ) {
                return true;
            }
        }

        return false;
    }
}