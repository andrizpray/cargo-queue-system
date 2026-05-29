<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            $table->string('cargo_description')->nullable()->after('notes');
            $table->decimal('weight_kg', 10, 2)->nullable()->after('cargo_description');
        });
    }

    public function down(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            $table->dropColumn(['cargo_description', 'weight_kg']);
        });
    }
};
