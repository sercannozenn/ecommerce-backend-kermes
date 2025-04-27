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
        Schema::table('product_price_histories', function (Blueprint $table) {
            $table->unsignedBigInteger('updated_by')->nullable()->after('valid_until');
            $table->string('reason', 255)->nullable()->after('updated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_price_histories', function (Blueprint $table) {
            $table->dropColumn([
                'updated_by',
                'reason'
                               ]);
        });
    }
};
