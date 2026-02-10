<?php

namespace App\Filament\App\Resources\Orders\Pages;

use App\Filament\App\Resources\Orders\OrderResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $tenantId = auth()->user()?->tenant_id;
        if (!$tenantId)
            abort(403);

        $data['tenant_id'] = $tenantId;

        if (empty($data['order_no'])) {
            $data['order_no'] = 'ORD-' . now()->format('Ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        }

        return static::getModel()::create($data);
    }
}
