<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone', 20);
            $table->string('notification_type', 50); // queue_created, queue_status_changed, queue_reminder
            $table->string('channel', 20)->default('whatsapp'); // whatsapp, sms
            $table->string('status', 20)->default('pending'); // pending, sent, delivered, failed
            $table->string('message', 500);
            $table->json('metadata')->nullable(); // Additional data like queue_id, old_status, new_status
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'notification_type']);
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
