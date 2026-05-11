<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for YG Xcel module (Spreadsheet Service)
     */
    public function up(): void
    {
        // Workbooks
        if (!Schema::hasTable('xcel_workbooks')) {
            Schema::create('xcel_workbooks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('uuid')->unique();
                $table->boolean('is_published')->default(false);
                $table->json('theme')->nullable();
                $table->json('settings')->nullable();
                $table->integer('sheet_count')->default(1);
                $table->integer('total_cells')->default(0);
                $table->bigInteger('size_bytes')->default(0);
                $table->timestamp('last_edited_at')->nullable();
                $table->timestamps();
                
                $table->index(['user_id', 'is_published']);
                $table->index('uuid');
            });
        }

        // Sheets
        if (!Schema::hasTable('xcel_sheets')) {
            Schema::create('xcel_sheets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workbook_id')->constrained('xcel_workbooks')->onDelete('cascade');
                $table->string('name');
                $table->integer('order')->default(0);
                $table->integer('row_count')->default(100);
                $table->integer('column_count')->default(26);
                $table->json('frozen_rows')->nullable();
                $table->json('frozen_columns')->nullable();
                $table->json('hidden_rows')->nullable();
                $table->json('hidden_columns')->nullable();
                $table->json('filters')->nullable();
                $table->json('sort_order')->nullable();
                $table->timestamps();
                
                $table->index(['workbook_id', 'order']);
            });
        }

        // Cells
        if (!Schema::hasTable('xcel_cells')) {
            Schema::create('xcel_cells', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sheet_id')->constrained('xcel_sheets')->onDelete('cascade');
                $table->integer('row');
                $table->integer('column');
                $table->string('cell_reference');
                $table->text('value')->nullable();
                $table->text('display_value')->nullable();
                $table->text('formula')->nullable();
                $table->enum('data_type', ['text', 'number', 'date', 'time', 'datetime', 'boolean', 'formula', 'error'])->default('text');
                $table->json('format')->nullable();
                $table->json('validation')->nullable();
                $table->json('conditional_formatting')->nullable();
                $table->text('comment')->nullable();
                $table->foreignId('comment_user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('last_modified_at')->nullable();
                $table->timestamps();
                
                $table->unique(['sheet_id', 'row', 'column']);
                $table->index('cell_reference');
            });
        }

        // Formulas
        if (!Schema::hasTable('xcel_formulas')) {
            Schema::create('xcel_formulas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cell_id')->constrained('xcel_cells')->onDelete('cascade');
                $table->text('formula_string');
                $table->json('dependencies')->nullable();
                $table->json('precedents')->nullable();
                $table->text('parsed_formula')->nullable();
                $table->text('result')->nullable();
                $table->boolean('is_valid')->default(true);
                $table->text('error_message')->nullable();
                $table->timestamps();
            });
        }

        // Charts
        if (!Schema::hasTable('xcel_charts')) {
            Schema::create('xcel_charts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sheet_id')->constrained('xcel_sheets')->onDelete('cascade');
                $table->string('title');
                $table->enum('type', ['bar', 'column', 'line', 'area', 'pie', 'doughnut', 'scatter', 'bubble', 'radar', 'combo']);
                $table->json('data_range');
                $table->json('series')->nullable();
                $table->json('axes')->nullable();
                $table->json('legend')->nullable();
                $table->json('colors')->nullable();
                $table->json('position');
                $table->boolean('is_3d')->default(false);
                $table->json('options')->nullable();
                $table->timestamps();
            });
        }

        // Pivot tables
        if (!Schema::hasTable('xcel_pivot_tables')) {
            Schema::create('xcel_pivot_tables', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sheet_id')->constrained('xcel_sheets')->onDelete('cascade');
                $table->string('name');
                $table->json('source_range');
                $table->json('rows')->nullable();
                $table->json('columns')->nullable();
                $table->json('values')->nullable();
                $table->json('filters')->nullable();
                $table->json('layout')->nullable();
                $table->json('position');
                $table->timestamps();
            });
        }

        // Collaborators
        if (!Schema::hasTable('xcel_collaborators')) {
            Schema::create('xcel_collaborators', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workbook_id')->constrained('xcel_workbooks')->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->enum('role', ['owner', 'editor', 'commenter', 'viewer'])->default('viewer');
                $table->timestamp('last_active_at')->nullable();
                $table->timestamps();
                
                $table->unique(['workbook_id', 'user_id']);
            });
        }

        // Edit sessions
        if (!Schema::hasTable('xcel_edit_sessions')) {
            Schema::create('xcel_edit_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workbook_id')->constrained('xcel_workbooks')->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('sheet_id')->nullable()->constrained('xcel_sheets')->onDelete('set null');
                $table->json('active_cells')->nullable();
                $table->timestamp('started_at');
                $table->timestamp('last_activity_at');
                $table->timestamp('ended_at')->nullable();
            });
        }

        // Revisions
        if (!Schema::hasTable('xcel_revisions')) {
            Schema::create('xcel_revisions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workbook_id')->constrained('xcel_workbooks')->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('revision_number');
                $table->text('change_summary')->nullable();
                $table->json('changes_snapshot')->nullable();
                $table->bigInteger('size_bytes');
                $table->timestamps();
            });
        }

        // Templates
        if (!Schema::hasTable('xcel_templates')) {
            Schema::create('xcel_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('category');
                $table->json('workbook_structure');
                $table->json('sample_data')->nullable();
                $table->json('formulas')->nullable();
                $table->boolean('is_public')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->integer('usage_count')->default(0);
                $table->timestamps();
            });
        }

        // Named ranges
        if (!Schema::hasTable('xcel_named_ranges')) {
            Schema::create('xcel_named_ranges', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workbook_id')->constrained('xcel_workbooks')->onDelete('cascade');
                $table->string('name');
                $table->string('scope');
                $table->text('reference');
                $table->text('comment')->nullable();
                $table->timestamps();
                
                $table->unique(['workbook_id', 'name']);
            });
        }

        // Macros
        if (!Schema::hasTable('xcel_macros')) {
            Schema::create('xcel_macros', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workbook_id')->constrained('xcel_workbooks')->onDelete('cascade');
                $table->string('name');
                $table->text('description')->nullable();
                $table->longText('script');
                $table->json('triggers')->nullable();
                $table->boolean('is_enabled')->default(true);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('xcel_macros');
        Schema::dropIfExists('xcel_named_ranges');
        Schema::dropIfExists('xcel_templates');
        Schema::dropIfExists('xcel_revisions');
        Schema::dropIfExists('xcel_edit_sessions');
        Schema::dropIfExists('xcel_collaborators');
        Schema::dropIfExists('xcel_pivot_tables');
        Schema::dropIfExists('xcel_charts');
        Schema::dropIfExists('xcel_formulas');
        Schema::dropIfExists('xcel_cells');
        Schema::dropIfExists('xcel_sheets');
        Schema::dropIfExists('xcel_workbooks');
    }
};
