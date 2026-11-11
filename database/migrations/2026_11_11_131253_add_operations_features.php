<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_areas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('pincode', 10)->nullable();
            $table->decimal('charge', 10, 2)->default(0);
            $table->decimal('min_order', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('restaurant_tables', function (Blueprint $table) {
            $table->id();
            $table->string('name', 40);
            $table->unsignedSmallInteger('seats')->default(4);
            $table->string('token', 40)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->date('spent_on')->index();
            $table->string('category', 40);
            $table->string('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('paid_via', 20)->default('cash');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('favourites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_item_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'menu_item_id']);
        });

        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('points');
            $table->string('description');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('loyalty_points')->default(0)->after('is_active');
        });

        Schema::table('menu_items', function (Blueprint $table) {
            $table->unsignedInteger('stock_qty')->nullable()->after('is_available');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('delivery_area_id')->nullable()->after('landmark')->constrained()->nullOnDelete();
            $table->foreignId('restaurant_table_id')->nullable()->after('delivery_area_id')->constrained()->nullOnDelete();
            $table->unsignedInteger('points_redeemed')->default(0)->after('coupon_code');
            $table->decimal('points_discount', 10, 2)->default(0)->after('points_redeemed');
            $table->unsignedInteger('points_earned')->default(0)->after('points_discount');
            $table->string('invoice_number', 30)->nullable()->unique()->after('order_number');
            $table->string('customer_gstin', 20)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('delivery_area_id');
            $table->dropConstrainedForeignId('restaurant_table_id');
            $table->dropColumn(['points_redeemed', 'points_discount', 'points_earned', 'invoice_number', 'customer_gstin']);
        });

        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropColumn('stock_qty');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('loyalty_points');
        });

        Schema::dropIfExists('loyalty_transactions');
        Schema::dropIfExists('favourites');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('restaurant_tables');
        Schema::dropIfExists('delivery_areas');
    }
};
