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
        Schema::create('calendar_event_scheduled_notifications', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('calendar_event_id')->unsigned();
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->longText('exception')->nullable();
            $table->timestamp('scheduled_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_event_scheduled_notifications');
    }
};
