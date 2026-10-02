<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Daily Win Limit: per-shop daily win budget (risk_control follow-up).
 *
 * One row per shop per day. shop_id = 0 is the platform default. limit_amount
 * 0 means "no limit" (feature off for that shop); spent accumulates every
 * player win recorded in stat_game. A fresh row appears lazily on the first
 * spin/save of the day, so the budget resets itself at 00:00 server time
 * without a scheduler.
 *
 * The table is also created idempotently by fix_all.php section 28e through
 * raw PDO for deployments that never run artisan migrate — both paths must
 * converge on this exact schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('daily_win_budgets')) {
            return;
        }

        Schema::create('daily_win_budgets', function (Blueprint $table) {
            $table->id();
            // 0 = platform default row; otherwise matches shops.id.
            $table->integer('shop_id')->default(0);
            // Server-date (PHP app timezone), NOT a timestamp: the budget is
            // keyed by calendar day so it rolls over at local midnight.
            $table->date('date');
            $table->decimal('limit_amount', 14, 2)->default(0);
            $table->decimal('spent', 14, 2)->default(0);
            $table->unique(['shop_id', 'date'], 'daily_win_budgets_shop_date_unique');
            $table->index('date', 'daily_win_budgets_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_win_budgets');
    }
};
