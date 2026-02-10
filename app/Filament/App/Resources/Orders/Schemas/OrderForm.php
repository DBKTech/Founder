<?php

namespace App\Filament\App\Resources\Orders\Schemas;

use App\Models\Customer;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // tenant_id REMOVED (auto-set)

                Select::make('customer_id')
                    ->label('Customer')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->options(function () {
                        $tenantId = auth()->user()?->tenant_id;

                        return Customer::query()
                            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
                            ->orderBy('name')
                            ->pluck('name', 'id');
                    }),

                TextInput::make('order_no')
                    ->label('Order No')
                    ->required()
                    ->maxLength(50),

                Select::make('status')
                    ->required()
                    ->options([
                        'draft' => 'Draft',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('draft'),

                TextInput::make('total')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->minValue(0),

                DateTimePicker::make('ordered_at')
                    ->label('Ordered At'),
            ]);
    }
}
