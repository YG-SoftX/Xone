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
        if (!Schema::hasTable('sheets')) {
            Schema::create('sheets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('spreadsheet_id')->constrained()->onDelete('cascade');
                $table->string('name');
                $table->integer('order_index')->default(0);
                $table->integer('row_count')->default(1000);
                $table->integer('column_count')->default(26);
                $table->timestamps();
            });

            // Add the circular foreign key to spreadsheets table
            Schema::table('spreadsheets', function (Blueprint $table) {
                $table->foreign('default_sheet_id')->references('id')->on('sheets')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sheets');
    }
};
