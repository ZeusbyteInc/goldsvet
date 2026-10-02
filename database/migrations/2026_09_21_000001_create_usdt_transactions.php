<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('usdt_transactions')) {
            return;
        }
        Schema::create('usdt_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->index();
            $table->unsignedInteger('shop_id')->default(0)->index();
            $table->enum('type', ['deposit', 'withdraw'])->index();
            $table->decimal('amount_usdt', 20, 6)->default(0);
            $table->decimal('amount_local', 20, 2)->default(0);
            $table->decimal('rate', 14, 4)->default(0); // USDT→INR exchange rate at transaction time
            $table->string('txid', 128)->nullable();
            $table->string('address', 64)->default('');
            $table->enum('status', ['pending', 'approved', 'confirmed', 'rejected'])->default('pending')->index();
            $table->string('admin_note', 255)->nullable();
            $table->unsignedInteger('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usdt_transactions');
    }
};
