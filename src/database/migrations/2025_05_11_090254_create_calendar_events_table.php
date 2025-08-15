<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('calendar_id')->unsigned();
            $table->string('title')->default('No title');
            $table->longText('description')->nullable();
            $table->date('original_start_date');
            $table->date('original_end_date')->nullable();
            $table->time('original_start_time');
            $table->time('original_end_time');
            $table->timestamp('utc_start_timestamp');
            $table->timestamp('utc_end_timestamp');
            $table->string('timezone')->default('UTC');
            $table->string('utc_offset')->default('+00:00');
            $table->boolean('is_whole_day')->default(false);
            $table->boolean('is_infinite')->default(false);
            $table->longText('rrule')->default(null);
            $table->json('meta_data')->default(new Expression('(JSON_ARRAY())'));
            $table->json('reminders')->default(new Expression('(JSON_ARRAY())'));
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
