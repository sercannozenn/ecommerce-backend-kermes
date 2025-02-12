<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('short_description');
            $table->text('long_description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('view_count')->default(0);
            $table->integer('stock')->default(0);
            $table->integer('stock_alert_limit')->default(10);

            $table->string('keywords')->nullable();
            $table->string('seo_description')->nullable();
            $table->string('author')->nullable();
            $table->timestamps();
        });

        Schema::create('product_publish_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->dateTime('publish_at');
            $table->dateTime('unpublish_at')->nullable();
            $table->foreignId('updated_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('price', 10, 2);
            $table->decimal('price_discount', 10, 2)->nullable();
//            $table->dateTime('discount_start')->nullable();
//            $table->dateTime('discount_end')->nullable();
            $table->foreignId('updated_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('category_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
        });

        Schema::create('product_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('tag_id')->constrained()->onDelete('cascade');
        });

        Schema::create('product_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('tag_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->decimal('discount_fixed', 10, 2)->nullable();
            $table->dateTime('discount_start');
            $table->dateTime('discount_end');
            $table->timestamps();
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('image_path');
            $table->boolean('is_featured')->default(false);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('variant_id')->constrained('products')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_discounts');
        Schema::dropIfExists('product_tag');
        Schema::dropIfExists('category_product');
        Schema::dropIfExists('product_prices');
        Schema::dropIfExists('product_publish_history');
        Schema::dropIfExists('products');
    }
};
