<?php

namespace App\Filament\App\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Filament\App\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab; // ✅ Filament v5
use Illuminate\Database\Eloquent\Builder;
use App\Filament\App\Widgets\OrdersTabsStyleWidget;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getTableQuery(): Builder
    {
        return static::getResource()::getEloquentQuery();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            OrdersTabsStyleWidget::class,
        ];
    }

    public function getTabs(): array
    {
        // ✅ tenant-scoped base query (from OrderResource::getEloquentQuery)
        $base = static::getResource()::getEloquentQuery();

        return [
            'all' => Tab::make('All')
                ->badge(fn() => (clone $base)->count())
                ->badgeColor('gray')
                ->extraAttributes(['class' => 'orders-tab orders-tab--all']),

            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', OrderStatus::Completed->value))
                ->badge(fn() => (clone $base)->where('status', OrderStatus::Completed->value)->count())
                ->badgeColor('success')
                ->extraAttributes(['class' => 'orders-tab orders-tab--completed']),

            'approved' => Tab::make('Approved')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', OrderStatus::Approved->value))
                ->badge(fn() => (clone $base)->where('status', OrderStatus::Approved->value)->count())
                ->badgeColor('info')
                ->extraAttributes(['class' => 'orders-tab orders-tab--approved']),

            'pending' => Tab::make('Pending (COD)')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', OrderStatus::Pending->value))
                ->badge(fn() => (clone $base)->where('status', OrderStatus::Pending->value)->count())
                ->badgeColor('warning')
                ->extraAttributes(['class' => 'orders-tab orders-tab--pending']),

            'on_the_move' => Tab::make('On The Move')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', OrderStatus::OnTheMove->value))
                ->badge(fn() => (clone $base)->where('status', OrderStatus::OnTheMove->value)->count())
                ->badgeColor('primary')
                ->extraAttributes(['class' => 'orders-tab orders-tab--move']),

            'rejected' => Tab::make('Rejected')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', OrderStatus::Rejected->value))
                ->badge(fn() => (clone $base)->where('status', OrderStatus::Rejected->value)->count())
                ->badgeColor('danger')
                ->extraAttributes(['class' => 'orders-tab orders-tab--rejected']),

            'cancelled' => Tab::make('Cancelled')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', OrderStatus::Cancelled->value))
                ->badge(fn() => (clone $base)->where('status', OrderStatus::Cancelled->value)->count())
                ->badgeColor('gray')
                ->extraAttributes(['class' => 'orders-tab orders-tab--cancelled']),

            'returned' => Tab::make('Returned')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', OrderStatus::Returned->value))
                ->badge(fn() => (clone $base)->where('status', OrderStatus::Returned->value)->count())
                ->badgeColor('warning')
                ->extraAttributes(['class' => 'orders-tab orders-tab--returned']),

            'draft' => Tab::make('Draft')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', OrderStatus::Draft->value))
                ->badge(fn() => (clone $base)->where('status', OrderStatus::Draft->value)->count())
                ->badgeColor('gray')
                ->extraAttributes(['class' => 'orders-tab orders-tab--draft']),

            'unprint_awb' => Tab::make('Unprint AWB')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', OrderStatus::UnprintAwb->value))
                ->badge(fn() => (clone $base)->where('status', OrderStatus::UnprintAwb->value)->count())
                ->badgeColor('warning')
                ->extraAttributes(['class' => 'orders-tab orders-tab--unprint']),
        ];
    }
}