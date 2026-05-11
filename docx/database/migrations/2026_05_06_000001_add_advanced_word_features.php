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
        // Add advanced formatting fields to documents table
        Schema::table('documents', function (Blueprint $table) {
            $table->json('page_setup')->nullable()->after('content_json'); // margins, size, orientation
            $table->json('headers_footers')->nullable()->after('page_setup'); // header/footer content
            $table->json('styles')->nullable()->after('headers_footers'); // custom styles
            $table->json('track_changes')->nullable()->after('styles'); // tracked changes data
        });

        // Create document sections table for complex layouts
        Schema::create('document_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->integer('section_order')->default(1);
            $table->json('page_setup')->nullable(); // section-specific page setup
            $table->json('headers_footers')->nullable();
            $table->text('content')->nullable();
            $table->timestamps();
            
            $table->index(['document_id', 'section_order']);
        });

        // Create bookmarks table
        Schema::create('document_bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->integer('position'); // character offset
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->unique(['document_id', 'name']);
        });

        // Create footnotes/endnotes table
        Schema::create('document_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['footnote', 'endnote']);
            $table->integer('reference_position'); // where note is referenced
            $table->text('content');
            $table->integer('number'); // display number
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['document_id', 'type']);
        });

        // Create table styles
        Schema::create('table_styles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->json('properties'); // borders, shading, etc.
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            
            $table->index('document_id');
        });

        // Create shapes/drawings table
        Schema::create('document_shapes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->string('type'); // rectangle, circle, arrow, line, textbox, etc.
            $table->json('properties'); // position, size, color, rotation, etc.
            $table->text('content')->nullable(); // for textboxes
            $table->integer('z_index')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index('document_id');
        });

        // Create charts table
        Schema::create('document_charts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->string('chart_type'); // bar, line, pie, scatter, area
            $table->json('data'); // chart data
            $table->json('options'); // titles, labels, colors, etc.
            $table->json('position'); // placement in document
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index('document_id');
        });

        // Enhance document_shares for real-time collaboration
        Schema::table('document_shares', function (Blueprint $table) {
            $table->boolean('can_download')->default(true)->after('permission');
            $table->boolean('can_print')->default(true)->after('can_download');
            $table->boolean('can_copy')->default(true)->after('can_print');
            $table->timestamp('expires_at')->nullable()->after('can_copy');
            $table->string('password_hash')->nullable()->after('expires_at');
            $table->json('allowed_domains')->nullable()->after('password_hash');
        });

        // Create document presence tracking for real-time collaboration
        Schema::create('document_presence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('session_id');
            $table->json('cursor_position')->nullable(); // {from: 123, to: 145}
            $table->json('selection')->nullable();
            $table->timestamp('last_active')->useCurrent();
            $table->timestamps();
            
            $table->unique(['document_id', 'user_id', 'session_id']);
            $table->index(['document_id', 'last_active']);
        });

        // Create collaborative editing operations log
        Schema::create('document_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('session_id');
            $table->string('operation_type'); // insert, delete, format, etc.
            $table->json('operation_data'); // detailed operation info
            $table->integer('version'); // for conflict resolution
            $table->timestamp('applied_at')->useCurrent();
            
            $table->index(['document_id', 'version']);
            $table->index(['document_id', 'applied_at']);
        });

        // Create document comparison snapshots
        Schema::create('document_comparisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->foreignId('compare_with_id')->constrained('documents')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->json('differences'); // detected changes
            $table->text('summary')->nullable();
            $table->timestamps();
            
            $table->index(['document_id', 'compare_with_id']);
        });

        // Create macros/automation scripts
        Schema::create('document_macros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('script'); // JavaScript code
            $table->json('triggers')->nullable(); // when to run
            $table->boolean('is_enabled')->default(true);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->index('document_id');
        });

        // Create auto-correct entries
        Schema::create('auto_correct_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('trigger_text'); // what user types
            $table->string('replacement_text'); // what it becomes
            $table->boolean('is_global')->default(false); // user-specific or global
            $table->timestamps();
            
            $table->unique(['user_id', 'trigger_text']);
        });

        // Create quick parts/building blocks
        Schema::create('quick_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // null = system
            $table->string('name');
            $table->string('category'); // headers, footers, text boxes, etc.
            $table->text('content');
            $table->json('metadata')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            
            $table->index(['user_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quick_parts');
        Schema::dropIfExists('auto_correct_entries');
        Schema::dropIfExists('document_macros');
        Schema::dropIfExists('document_comparisons');
        Schema::dropIfExists('document_operations');
        Schema::dropIfExists('document_presence');
        
        Schema::table('document_shares', function (Blueprint $table) {
            $table->dropColumn(['can_download', 'can_print', 'can_copy', 'expires_at', 'password_hash', 'allowed_domains']);
        });
        
        Schema::dropIfExists('document_charts');
        Schema::dropIfExists('document_shapes');
        Schema::dropIfExists('table_styles');
        Schema::dropIfExists('document_notes');
        Schema::dropIfExists('document_bookmarks');
        Schema::dropIfExists('document_sections');
        
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['page_setup', 'headers_footers', 'styles', 'track_changes']);
        });
    }
};
