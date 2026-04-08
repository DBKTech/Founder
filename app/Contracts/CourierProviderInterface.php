<?php

namespace App\Contracts;

use App\Models\Order;
use App\Models\Shipment;

interface CourierProviderInterface
{
    public function createShipment(Order $order): array;

    public function cancelShipment(Shipment $shipment): array;

    public function trackShipment(Shipment $shipment): array;

    public function getLabel(Shipment $shipment): ?string;
}