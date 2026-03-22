<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('broadcast_messages', function (Blueprint $table): void {
            $table->string('action_url')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('broadcast_messages', function (Blueprint $table): void {
            $table->dropColumn('action_url');
        });
    }
};
