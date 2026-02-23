<?php

namespace App\Filament\App\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Filament\App\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected ?array $statusCounts = null;

    protected function baseQuery(): Builder
    {
        // ✅ This respects tenant scope / resource scopes
        return static::getResource()::getEloquentQuery();
    }

    protected function getStatusCounts(): array
    {
        if ($this->statusCounts !== null) {
            return $this->statusCounts;
        }

        $counts = (clone $this->baseQuery())
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return $this->statusCounts = $counts;
    }

    public function getTabs(): array
    {
        $counts = $this->getStatusCounts();

        $draft = $counts[OrderStatus::Draft->value] ?? 0;
        $pendingPayment = $counts[OrderStatus::PendingPayment->value] ?? 0;

        return [
            'all' => Tab::make('All')
                ->badge(array_sum($counts)),

            'new' => Tab::make('New')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [
                    OrderStatus::Draft->value,
                    OrderStatus::PendingPayment->value,
                ]))
                ->badge($draft + $pendingPayment)
                ->badgeColor('warning'),

            'paid' => Tab::make('Paid')
                ->modifyQueryUsing(fn (Builder $query) =>
                    $query->where('status', OrderStatus::Paid->value)
                )
                ->badge($counts[OrderStatus::Paid->value] ?? 0)
                ->badgeColor('success'),

            'processing' => Tab::make('Processing')
                ->modifyQueryUsing(fn (Builder $query) =>
                    $query->where('status', OrderStatus::Processing->value)
                )
                ->badge($counts[OrderStatus::Processing->value] ?? 0)
                ->badgeColor('info'),

            'shipped' => Tab::make('Shipped')
                ->modifyQueryUsing(fn (Builder $query) =>
                    $query->where('status', OrderStatus::Shipped->value)
                )
                ->badge($counts[OrderStatus::Shipped->value] ?? 0)
                ->badgeColor('primary'),

            'delivered' => Tab::make('Delivered')
                ->modifyQueryUsing(fn (Builder $query) =>
                    $query->where('status', OrderStatus::Delivered->value)
                )
                ->badge($counts[OrderStatus::Delivered->value] ?? 0)
                ->badgeColor('success'),

            'cancelled' => Tab::make('Cancelled')
                ->modifyQueryUsing(fn (Builder $query) =>
                    $query->where('status', OrderStatus::Cancelled->value)
                )
                ->badge($counts[OrderStatus::Cancelled->value] ?? 0)
                ->badgeColor('danger'),
        ];
    }
}