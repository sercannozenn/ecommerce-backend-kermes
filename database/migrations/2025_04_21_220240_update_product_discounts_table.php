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
        $foreignColumns = [
            'product_id'  => 'product_discounts_product_id_foreign',
            'category_id' => 'product_discounts_category_id_foreign',
            'tag_id'      => 'product_discounts_tag_id_foreign',
            'user_id'     => 'product_discounts_user_id_foreign',
        ];

        foreach ($foreignColumns as $column => $foreignKey) {
            if (Schema::hasColumn('product_discounts', $column)) {
                try {
                    Schema::table('product_discounts', function (Blueprint $table) use ($column) {
                        $table->dropForeign([$column]);
                    });
                } catch (\Throwable $e) {
                    logger()->warning("Foreign key already removed or not found: $foreignKey — " . $e->getMessage());
                }

                Schema::table('product_discounts', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }

        Schema::table('product_discounts', function (Blueprint $table) {
            $table->string('name')->after('id');
            $table->string('target_type')->after('name');
            $table->string('discount_type')->after('target_type');
            $table->decimal('discount_amount', 10, 2)->after('discount_type');
            $table->unsignedTinyInteger('priority')->default(0)->after('discount_amount');
            $table->boolean('is_active')->default(true)->after('priority');
            $table->string('description')->nullable()->after('is_active');
        });

        // discount_start / discount_end type değişikliği (doctrine/dbal gerektirir)
        Schema::table('product_discounts', function (Blueprint $table) {
            $table->dateTime('discount_start')->change();
            $table->dateTime('discount_end')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_discounts', function (Blueprint $table) {
            $table->dropColumn([
                                   'name',
                                   'target_type',
                                   'discount_type',
                                   'discount_amount',
                                   'priority',
                                   'is_active',
                                   'description'
                               ]);
        });
    }
};
