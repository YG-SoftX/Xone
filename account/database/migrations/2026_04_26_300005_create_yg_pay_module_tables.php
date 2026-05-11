<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for YG Pay module (Payment System)
     */
    public function up(): void
    {
        // API Products (central services being sold)
        if (!Schema::hasTable('api_products')) {
            Schema::create('api_products', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();           // 'sso', 'mail', 'drive', 'pay', 'ai'
                $table->string('display_name');
                $table->text('description')->nullable();
                $table->string('icon')->nullable();
                $table->decimal('base_price', 10, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Pricing Plans (subscription tiers)
        if (!Schema::hasTable('pricing_plans')) {
            Schema::create('pricing_plans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->nullable()->constrained('api_products')->onDelete('cascade');
                $table->string('name');                      // 'Free', 'Pro', 'Enterprise'
                $table->string('slug')->unique();
                $table->decimal('monthly_price', 10, 2)->default(0);
                $table->json('features')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Digital wallets
        if (!Schema::hasTable('pay_wallets')) {
            Schema::create('pay_wallets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('wallet_number')->unique(); // e.g., YGW-XXXX-XXXX-XXXX
                $table->decimal('balance', 15, 2)->default(0.00);
                $table->string('currency')->default('USD');
                $table->enum('status', ['active', 'frozen', 'closed'])->default('active');
                $table->json('limits')->nullable(); // Daily/monthly transaction limits
                $table->timestamps();
                
                $table->index('user_id');
            });
        }

        // Payment cards
        if (!Schema::hasTable('pay_cards')) {
            Schema::create('pay_cards', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('card_token'); // Tokenized card number from provider
                $table->string('last_four');
                $table->string('brand'); // visa, mastercard, amex
                $table->integer('exp_month');
                $table->integer('exp_year');
                $table->string('cardholder_name');
                $table->boolean('is_default')->default(false);
                $table->boolean('is_verified')->default(false);
                $table->timestamps();
                
                $table->index('user_id');
            });
        }

        // Transactions
        if (!Schema::hasTable('pay_transactions')) {
            Schema::create('pay_transactions', function (Blueprint $table) {
                $table->id();
                $table->string('transaction_id')->unique(); // txn_xxxxx
                $table->foreignId('wallet_id')->constrained('pay_wallets')->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->enum('type', ['payment', 'refund', 'transfer', 'withdrawal', 'deposit', 'fee']);
                $table->decimal('amount', 15, 2);
                $table->string('currency')->default('USD');
                $table->enum('status', ['pending', 'completed', 'failed', 'refunded', 'disputed']);
                $table->string('provider')->nullable(); // stripe, paypal, razorpay
                $table->string('provider_transaction_id')->nullable();
                $table->foreignId('recipient_wallet_id')->nullable()->constrained('pay_wallets')->onDelete('set null');
                $table->string('recipient_email')->nullable();
                $table->text('description')->nullable();
                $table->json('metadata')->nullable();
                $table->string('error_message')->nullable();
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();
                
                $table->index(['user_id', 'created_at']);
                $table->index('status');
                $table->index('transaction_id');
            });
        }

        // Merchants
        if (!Schema::hasTable('pay_merchants')) {
            Schema::create('pay_merchants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('business_name');
                $table->string('business_type'); // retail, service, online
                $table->string('tax_id')->nullable();
                $table->string('website')->nullable();
                $table->text('address')->nullable();
                $table->string('country');
                $table->string('phone');
                $table->string('email');
                $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
                $table->json('bank_details')->nullable();
                $table->decimal('commission_rate', 5, 2)->default(2.9); // Percentage
                $table->timestamps();
                
                $table->index('user_id');
            });
        }

        // Invoices
        if (!Schema::hasTable('pay_invoices')) {
            Schema::create('pay_invoices', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_number')->unique(); // INV-2026-0001
                $table->foreignId('merchant_id')->constrained('pay_merchants')->onDelete('cascade');
                $table->foreignId('customer_id')->nullable()->constrained('users')->onDelete('set null');
                $table->string('customer_email');
                $table->string('customer_name');
                $table->decimal('subtotal', 15, 2);
                $table->decimal('tax_amount', 15, 2)->default(0.00);
                $table->decimal('total_amount', 15, 2);
                $table->string('currency')->default('USD');
                $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])->default('draft');
                $table->text('description')->nullable();
                $table->json('line_items')->nullable(); // Array of items
                $table->timestamp('due_date')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
                
                $table->index(['merchant_id', 'created_at']);
                $table->index('status');
            });
        }

        // Subscriptions
        if (!Schema::hasTable('pay_subscriptions')) {
            Schema::create('pay_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('plan_id')->nullable()->constrained('pricing_plans')->onDelete('set null');
                $table->string('provider_subscription_id')->nullable();
                $table->string('provider'); // stripe, paypal
                $table->enum('status', ['active', 'cancelled', 'expired', 'past_due'])->default('active');
                $table->decimal('amount', 15, 2);
                $table->string('currency')->default('USD');
                $table->string('interval'); // month, year
                $table->integer('interval_count')->default(1);
                $table->timestamp('current_period_start');
                $table->timestamp('current_period_end');
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();
                
                $table->index(['user_id', 'status']);
            });
        }

        // Webhooks (for payment provider callbacks)
        if (!Schema::hasTable('pay_webhooks')) {
            Schema::create('pay_webhooks', function (Blueprint $table) {
                $table->id();
                $table->string('webhook_id')->unique();
                $table->string('provider'); // stripe, paypal, razorpay
                $table->string('event_type'); // payment_intent.succeeded, charge.refunded
                $table->json('payload');
                $table->string('signature');
                $table->boolean('processed')->default(false);
                $table->timestamp('processed_at')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();
                
                $table->index(['provider', 'processed']);
            });
        }

        // Disputes/Chargebacks
        if (!Schema::hasTable('pay_disputes')) {
            Schema::create('pay_disputes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('transaction_id')->constrained('pay_transactions')->onDelete('cascade');
                $table->string('provider_dispute_id');
                $table->decimal('amount', 15, 2);
                $table->string('reason'); // fraud, product_not_received, etc.
                $table->enum('status', ['needs_response', 'under_review', 'won', 'lost'])->default('needs_response');
                $table->text('evidence')->nullable();
                $table->timestamp('due_date');
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
                
                $table->index('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pay_disputes');
        Schema::dropIfExists('pay_webhooks');
        Schema::dropIfExists('pay_subscriptions');
        Schema::dropIfExists('pay_invoices');
        Schema::dropIfExists('pay_merchants');
        Schema::dropIfExists('pay_transactions');
        Schema::dropIfExists('pay_cards');
        Schema::dropIfExists('pay_wallets');
        Schema::dropIfExists('pricing_plans');
        Schema::dropIfExists('api_products');
    }
};
