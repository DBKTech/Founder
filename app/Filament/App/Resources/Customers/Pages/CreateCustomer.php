<?php

namespace App\Filament\App\Resources\Customers\Pages;

use App\Filament\App\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $tenantId = auth()->user()?->tenant_id;

        if (! $tenantId) {
            abort(403, 'Tenant context not resolved for this user.');
        }

        $data['tenant_id'] = $tenantId;

        return static::getModel()::create($data);
    }
}
