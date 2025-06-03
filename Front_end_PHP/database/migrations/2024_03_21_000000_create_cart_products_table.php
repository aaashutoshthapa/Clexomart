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
        Schema::create('CART_PRODUCTS', function (Blueprint $table) {
            $table->string('cart_id');
            $table->string('product_id');
            $table->integer('product_quantity')->default(1);
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->timestamps();

            $table->primary(['cart_id', 'product_id']);
            $table->foreign('cart_id')->references('cart_id')->on('CART')->onDelete('cascade');
            $table->foreign('product_id')->references('product_id')->on('PRODUCT')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('CART_PRODUCTS');
    }
}; 