<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->boolean('notify_admin_on_queue_created')->default(true);
            $table->boolean('notify_driver_on_status_changed')->default(true);
            $table->boolean('send_reminders')->default(true);
            $table->integer('reminder_minutes_before')->default(10); // Minutes before estimated time
            $table->string('admin_phone', 20)->nullable();
            $table->json('driver_notification_preferences')->nullable(); // Custom preferences per driver type
            $table->timestamps();

            $table->unique('location_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_notification_settings');
    }
};
