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
        Schema::create('sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spreadsheet_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->integer('order_index')->default(0);
            $table->integer('row_count')->default(1000);
            $table->integer('column_count')->default(26);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sheets');
    }
};
