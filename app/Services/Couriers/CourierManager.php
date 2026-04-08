<?php

namespace App\Services\Couriers;

use App\Contracts\CourierProviderInterface;
use InvalidArgumentException;

class CourierManager
{
    public function provider(?string $courierCode = null): CourierProviderInterface
    {
        $courierCode = $courierCode ?: config('couriers.default');

        return match ($courierCode) {
            'sendparcelpro' => $this->sendParcelProProvider(),
            default => throw new InvalidArgumentException("Unsupported courier provider [{$courierCode}]"),
        };
    }

    protected function sendParcelProProvider(): CourierProviderInterface
    {
        $driver = config('couriers.sendparcelpro.driver', 'fake');

        return match ($driver) {
            'fake' => app(FakeSendParcelProvider::class),
            // 'real' => app(RealSendParcelProvider::class),
            default => throw new InvalidArgumentException("Unsupported SendParcelPro driver [{$driver}]"),
        };
    }
}