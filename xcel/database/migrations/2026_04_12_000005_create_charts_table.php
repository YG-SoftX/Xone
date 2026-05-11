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
        Schema::create('charts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sheet_id')->constrained()->onDelete('cascade');
            $table->string('chart_type'); // bar, line, pie, area, scatter, doughnut
            $table->string('title');
            $table->string('data_range'); // e.g. "A1:B10"
            $table->integer('position_x');
            $table->integer('position_y');
            $table->integer('width');
            $table->integer('height');
            $table->json('config_json')->nullable(); // chart options
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charts');
    }
};
