<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'count_deposit')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('count_deposit', 20, 4)->default(0);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'count_deposit')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('count_deposit');
            });
        }
    }
};
