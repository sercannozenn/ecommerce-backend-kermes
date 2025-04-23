<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductPrice;
use App\Models\Tag;
use App\Services\ProductServices\ProductPriceHistoryService;
use Illuminate\Database\Seeder;

class ProductStructureSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Kategori, Marka, Etiket Oluştur
        $category = Category::firstOrCreate(['name' => 'Elektronik'], ['slug' => 'elektronik']);
        $brand    = Brand::firstOrCreate(['name' => 'MarkaX'], ['slug' => 'marka-x']);
        $tag      = Tag::firstOrCreate(['name' => 'Yeni Sezon'], ['slug' => 'yeni-sezon']);

        // 2. Ürün oluştur
        $product = Product::create([
                                       'name'               => 'Test Telefon',
                                       'slug'               => 'test-telefon',
                                       'brand_id'           => $brand->id,
                                       'short_description'  => 'Kısa açıklama burada',
                                       'long_description'   => 'Uzun açıklama burada',
                                       'is_active'          => true,
                                       'view_count'         => 0,
                                       'stock'              => 100,
                                       'stock_alert_limit'  => 5,
                                       'keywords'           => 'telefon,teknoloji',
                                       'seo_description'    => 'Test telefon ürünü SEO açıklaması',
                                       'author'             => 'Seeder',
                                   ]);

        $product->categories()->sync([$category->id]);
        $product->tags()->sync([$tag->id]);

        // 3. Fiyat oluştur
        $price = ProductPrice::create([
                                          'product_id'     => $product->id,
                                          'price'          => 1500,
                                          'price_discount' => null,
                                          'updated_by'     => 1
                                      ]);

        // 4. Fiyat geçmişi oluştur
        app(ProductPriceHistoryService::class)->createHistory($product, $price);
    }
}
