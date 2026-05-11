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
        Schema::create('cells', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sheet_id')->constrained()->onDelete('cascade');
            $table->string('cell_address'); // e.g. "A1", "B12"
            $table->integer('row');
            $table->integer('column');
            $table->text('value')->nullable();
            $table->text('formula')->nullable();
            $table->text('computed_value')->nullable();
            $table->string('data_type')->default('text'); // text, number, date, boolean, formula
            $table->json('format_json')->nullable(); // font, color, alignment, borders, number format
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['sheet_id', 'cell_address']);
            $table->index(['sheet_id', 'row', 'column']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cells');
    }
};
