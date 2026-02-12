<?php

namespace App\Filament\App\Resources\Orders\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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

                Section::make('Shipment Ops')
                    ->schema([
                        TextEntry::make('shipment.status')
                            ->label('Shipment Status')
                            ->placeholder('-'),

                        TextEntry::make('shipment.courier_code')
                            ->label('Courier')
                            ->placeholder('-'),

                        TextEntry::make('shipment.tracking_number')
                            ->label('Tracking No.')
                            ->copyable()
                            ->copyMessage('Copied!')
                            ->copyMessageDuration(1500)
                            ->placeholder('-'),

                        TextEntry::make('shipment.label_url')
                            ->label('Label URL')
                            ->placeholder('-')
                            ->url(fn ($record) => $record->shipment?->label_url)
                            ->openUrlInNewTab(),

                        RepeatableEntry::make('shipment.events')
                            ->label('Shipment Timeline')
                            ->schema([
                                TextEntry::make('occurred_at')
                                    ->label('Time')
                                    ->dateTime(),

                                TextEntry::make('status')
                                    ->label('Status'),

                                TextEntry::make('description')
                                    ->label('Note')
                                    ->placeholder('-'),
                            ])
                            ->columns(3)
                            ->visible(fn ($record) => (bool) $record->shipment?->exists),
                    ])
                    ->columns(3),
            ]);
    }
}
