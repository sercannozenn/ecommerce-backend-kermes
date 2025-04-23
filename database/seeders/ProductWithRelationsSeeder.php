<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductWithRelationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Kategoriler
        $category1 = Category::create([
                                          'name' => 'Erkek Giyim',
                                          'slug' => 'erkek-giyim',
                                          'is_active' => true,
                                      ]);

        $category2 = Category::create([
                                          'name' => 'Kadın Ayakkabı',
                                          'slug' => 'kadin-ayakkabi',
                                          'is_active' => true,
                                      ]);

        // Markalar
        $brandNike = Brand::create([
                                       'name' => 'Nike',
                                       'slug' => 'nike',
                                       'is_active' => true,
                                   ]);

        $brandAdidas = Brand::create([
                                         'name' => 'Adidas',
                                         'slug' => 'adidas',
                                         'is_active' => true,
                                     ]);

        // Etiketler
        $tag1 = Tag::create([
                                'name' => 'Yeni Sezon',
                                'slug' => 'yeni-sezon',
                            ]);

        $tag2 = Tag::create([
                                'name' => 'İndirimli',
                                'slug' => 'indirimli',
                            ]);

        // Ürün
        $product = Product::create([
                                       'name' => 'Siyah Nike T-Shirt',
                                       'slug' => 'siyah-nike-tshirt',
                                       'short_description' => 'Erkekler için nefes alabilen kumaş.',
                                       'long_description' => 'Spor yaparken maksimum konfor sağlayan, %100 pamuk kumaştan üretilmiş siyah Nike tişört.',
                                       'is_active' => true,
                                       'view_count' => 0,
                                       'stock' => 50,
                                       'stock_alert_limit' => 5,
                                       'keywords' => 'nike, tişört, erkek',
                                       'seo_description' => 'Nike markalı spor tişört erkekler için özel üretim.',
                                       'author' => 'Admin',
                                       'brand_id' => $brandNike->id,
                                   ]);

        // Fiyat
        $product->prices()->create([
                                       'price' => 399.99,
                                       'price_discount' => 349.99,
                                       'updated_by' => 1, // sistemdeki admin user id'si
                                   ]);

        // İlişkiler
        $product->categories()->attach([$category1->id]);
        $product->tags()->attach([$tag1->id, $tag2->id]);
    }
}
