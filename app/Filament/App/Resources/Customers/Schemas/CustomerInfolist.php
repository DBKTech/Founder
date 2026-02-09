<?php

namespace App\Filament\App\Resources\Customers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // tenant_id REMOVED
                TextEntry::make('name'),

                TextEntry::make('phone')
                    ->placeholder('-'),

                TextEntry::make('email')
                    ->label('Email address')
                    ->placeholder('-'),

                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),

                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),

                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
