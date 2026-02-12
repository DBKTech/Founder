<?php

namespace App\Filament\App\Resources\Orders\Tables;

use App\Models\Shipment;
use App\Models\ShipmentEvent;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_no')
                    ->label('Order No')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->searchable(),

                // ✅ Shipment Ops columns
                TextColumn::make('shipment.status')
                    ->label('Shipment')
                    ->badge()
                    ->placeholder('-'),

                TextColumn::make('shipment.courier_code')
                    ->label('Courier')
                    ->placeholder('-'),

                TextColumn::make('shipment.tracking_number')
                    ->label('Tracking')
                    ->placeholder('-')
                    ->copyable()
                    ->copyMessage('Copied!')
                    ->copyMessageDuration(1500),

                TextColumn::make('total')
                    ->money('MYR')
                    ->sortable(),

                TextColumn::make('ordered_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                // ✅ Ops actions on list (no need open view one-by-one)
                Action::make('createShipment')
                    ->label('Create Shipment')
                    ->visible(fn (Model $record) => $record->shipment === null)
                    ->action(function (Model $record) {
                        $shipment = Shipment::create([
                            'order_id' => $record->id,
                            'status' => 'pending',
                        ]);

                        ShipmentEvent::create([
                            'shipment_id' => $shipment->id,
                            'status' => 'pending',
                            'description' => 'Shipment created',
                            'occurred_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Shipment created')
                            ->success()
                            ->send();
                    }),

                Action::make('setTracking')
                    ->label('Set Tracking')
                    ->visible(fn (Model $record) => $record->shipment !== null)
                    ->form([
                        Select::make('courier_code')
                            ->label('Courier')
                            ->options([
                                'poslaju' => 'Pos Laju',
                                'jnt' => 'J&T',
                                'dhl' => 'DHL',
                                'gdex' => 'GDEX',
                            ])
                            ->required(),

                        TextInput::make('tracking_number')
                            ->label('Tracking Number')
                            ->required(),
                    ])
                    ->action(function (Model $record, array $data) {
                        $shipment = $record->shipment;

                        $shipment->update([
                            'courier_code' => $data['courier_code'],
                            'tracking_number' => $data['tracking_number'],
                        ]);

                        ShipmentEvent::create([
                            'shipment_id' => $shipment->id,
                            'status' => $shipment->status,
                            'description' => 'Courier/Tracking updated',
                            'occurred_at' => now(),
                            'payload' => $data,
                        ]);

                        Notification::make()
                            ->title('Tracking updated')
                            ->success()
                            ->send();
                    }),

                Action::make('openTracking')
                    ->label('Open Tracking')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->visible(fn (Model $record) => filled($record->shipment?->tracking_number))
                    ->url(fn (Model $record) => self::trackingUrl(
                        $record->shipment?->courier_code,
                        $record->shipment?->tracking_number
                    ))
                    ->openUrlInNewTab()
                    ->disabled(fn (Model $record) => ! self::trackingUrl(
                        $record->shipment?->courier_code,
                        $record->shipment?->tracking_number
                    )),

                Action::make('markInTransit')
                    ->label('In Transit')
                    ->requiresConfirmation()
                    ->visible(fn (Model $record) => $record->shipment !== null && $record->shipment->status !== 'delivered')
                    ->action(function (Model $record) {
                        $shipment = $record->shipment;

                        $shipment->update([
                            'status' => 'in_transit',
                            'shipped_at' => now(),
                        ]);

                        ShipmentEvent::create([
                            'shipment_id' => $shipment->id,
                            'status' => 'in_transit',
                            'description' => 'Marked as in transit (manual)',
                            'occurred_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Marked in transit')
                            ->success()
                            ->send();
                    }),

                Action::make('markDelivered')
                    ->label('Delivered')
                    ->requiresConfirmation()
                    ->visible(fn (Model $record) => $record->shipment !== null && $record->shipment->status !== 'delivered')
                    ->action(function (Model $record) {
                        $shipment = $record->shipment;

                        $shipment->update([
                            'status' => 'delivered',
                            'delivered_at' => now(),
                        ]);

                        ShipmentEvent::create([
                            'shipment_id' => $shipment->id,
                            'status' => 'delivered',
                            'description' => 'Marked as delivered (manual)',
                            'occurred_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Marked delivered')
                            ->success()
                            ->send();
                    }),

                // Existing actions
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function trackingUrl(?string $courier, ?string $trackingNo): ?string
    {
        if (! $courier || ! $trackingNo) {
            return null;
        }

        return match ($courier) {
            'poslaju' => 'https://www.pos.com.my/track-trace/?track-trace-number=' . urlencode($trackingNo),
            'jnt'     => 'https://www.jtexpress.my/tracking/' . urlencode($trackingNo),
            'gdex'    => 'https://www.gdexpress.com/track/?consignmentno=' . urlencode($trackingNo),
            'dhl'     => 'https://www.dhl.com/my-en/home/tracking.html?tracking-id=' . urlencode($trackingNo),
            default   => null,
        };
    }
}
