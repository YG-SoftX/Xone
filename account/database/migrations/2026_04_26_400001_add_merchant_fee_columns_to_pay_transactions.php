<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pay_transactions', function (Blueprint $table) {
            // 'p2p' = free personal transfer, 'merchant' = business payment (fee applies)
            $table->string('payment_type')->default('p2p')->after('type');
            $table->decimal('fee', 10, 4)->default(0)->after('amount');
            $table->decimal('fee_rate', 5, 4)->default(0)->after('fee'); // 0.015 = 1.5%
            $table->string('merchant_id')->nullable()->after('fee_rate'); // recipient's merchant ID if applicable
        });
    }

    public function down(): void
    {
        Schema::table('pay_transactions', function (Blueprint $table) {
            $table->dropColumn(['payment_type', 'fee', 'fee_rate', 'merchant_id']);
        });
    }
};
