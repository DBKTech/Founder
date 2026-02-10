<?php

namespace App\Filament\App\Resources\Orders\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // tenant_id REMOVED (tak perlu show dekat seller)

                TextEntry::make('order_no')
                    ->label('Order No'),

                TextEntry::make('customer.name')
                    ->label('Customer')
                    ->placeholder('-'),

                TextEntry::make('status')
                    ->label('Status')
                    ->placeholder('-'),

                TextEntry::make('total')
                    ->label('Total')
                    ->money('MYR')
                    ->placeholder('-'),

                TextEntry::make('ordered_at')
                    ->label('Ordered At')
                    ->dateTime()
                    ->placeholder('-'),

                TextEntry::make('created_at')
                    ->label('Created at')
                    ->dateTime()
                    ->placeholder('-'),

                TextEntry::make('updated_at')
                    ->label('Updated at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
