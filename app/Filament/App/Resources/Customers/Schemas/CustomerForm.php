<?php

namespace App\Filament\App\Resources\Customers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // tenant_id REMOVED
                TextInput::make('name')
                    ->required()
                    ->maxLength(120),

                TextInput::make('phone')
                    ->tel()
                    ->maxLength(30),

                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->maxLength(120),

                Textarea::make('notes')
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }
}
