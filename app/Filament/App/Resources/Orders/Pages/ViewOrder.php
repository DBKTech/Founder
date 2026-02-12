<?php

namespace App\Filament\App\Resources\Orders\Pages;

use App\Filament\App\Resources\Orders\OrderResource;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),

            Action::make('createShipment')
                ->label('Create Shipment')
                ->visible(fn () => $this->record->shipment === null)
                ->action(function () {
                    $shipment = Shipment::create([
                        'order_id' => $this->record->id,
                        'status' => 'pending',
                    ]);

                    ShipmentEvent::create([
                        'shipment_id' => $shipment->id,
                        'status' => 'pending',
                        'description' => 'Shipment created',
                        'occurred_at' => now(),
                    ]);

                    $this->record->refresh();

                    Notification::make()
                        ->title('Shipment created')
                        ->success()
                        ->send();
                }),

            Action::make('setCourierTracking')
                ->label('Set Courier & Tracking')
                ->visible(fn () => $this->record->shipment !== null)
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
                ->action(function (array $data) {
                    $shipment = $this->record->shipment;

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
                ->visible(fn () => $this->record->shipment !== null)
                ->url(fn () => $this->trackingUrl(
                    $this->record->shipment?->courier_code,
                    $this->record->shipment?->tracking_number
                ))
                ->openUrlInNewTab()
                ->disabled(fn () => ! $this->trackingUrl(
                    $this->record->shipment?->courier_code,
                    $this->record->shipment?->tracking_number
                )),

            Action::make('markInTransit')
                ->label('Mark In Transit')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->shipment !== null && $this->record->shipment->status !== 'delivered')
                ->action(function () {
                    $shipment = $this->record->shipment;

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
                ->label('Mark Delivered')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->shipment !== null && $this->record->shipment->status !== 'delivered')
                ->action(function () {
                    $shipment = $this->record->shipment;

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
        ];
    }

    private function trackingUrl(?string $courier, ?string $trackingNo): ?string
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
