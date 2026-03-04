<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\MarketplaceListing;

class SeedMarketplaceListings extends Command
{
    protected $signature = 'marketplace:seed-listings {--publish}';
    protected $description = 'Create marketplace listings for existing products';

    public function handle(): int
    {
        $publish = (bool) $this->option('publish');

        $count = 0;

        Product::query()->chunk(200, function ($products) use (&$count, $publish) {
            foreach ($products as $product) {
                MarketplaceListing::firstOrCreate(
                    [
                        'tenant_id' => $product->tenant_id,
                        'product_id' => $product->id,
                    ],
                    [
                        'status' => $publish ? 'published' : 'draft',
                        'visibility' => 'tenant',
                        'published_at' => $publish ? now() : null,
                    ]
                );

                $count++;
            }
        });

        $this->info("Seeded/checked listings for {$count} products.");
        return self::SUCCESS;
    }
}