<?php

namespace Database\Seeders;

use App\Models\BillingAccount;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class PaymentTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create test user
        $user = User::first() ?? User::factory()->create([
            'email' => 'test@ygxone.com',
            'password' => bcrypt('password'),
        ]);

        // Create billing account
        $billingAccount = BillingAccount::firstOrCreate(
            ['user_id' => $user->id],
            [
                'company_name' => 'Test Company',
                'status' => 'active',
                'current_balance' => 0,
                'total_spent' => 0,
            ]
        );

        echo "Created billing account: {$billingAccount->billing_id}\n";

        // Create sample transactions
        $transactions = [
            [
                'provider' => 'stripe',
                'provider_transaction_id' => 'pi_test_123456',
                'type' => 'payment',
                'amount' => 99.99,
                'currency' => 'USD',
                'status' => 'completed',
                'payment_method_type' => 'card',
                'payment_method_details' => [
                    'last_four' => '4242',
                    'brand' => 'visa',
                    'exp_month' => 12,
                    'exp_year' => 2027,
                ],
                'processed_at' => now()->subDays(5),
            ],
            [
                'provider' => 'paypal',
                'provider_transaction_id' => 'ORDER-TEST-789',
                'type' => 'payment',
                'amount' => 49.99,
                'currency' => 'USD',
                'status' => 'completed',
                'payment_method_type' => 'paypal_account',
                'payment_method_details' => [
                    'email' => 'buyer@example.com',
                ],
                'processed_at' => now()->subDays(3),
            ],
            [
                'provider' => 'razorpay',
                'provider_transaction_id' => 'pay_test_abc123',
                'type' => 'payment',
                'amount' => 5000.00,
                'currency' => 'INR',
                'status' => 'completed',
                'payment_method_type' => 'upi',
                'payment_method_details' => [
                    'vpa' => 'user@upi',
                ],
                'processed_at' => now()->subDays(2),
            ],
            [
                'provider' => 'stripe',
                'provider_transaction_id' => 'pi_test_failed',
                'type' => 'payment',
                'amount' => 29.99,
                'currency' => 'USD',
                'status' => 'failed',
                'error_message' => 'Your card was declined.',
                'payment_method_type' => 'card',
                'payment_method_details' => [
                    'last_four' => '0002',
                    'brand' => 'mastercard',
                ],
                'processed_at' => now()->subDay(),
            ],
            [
                'provider' => 'stripe',
                'provider_transaction_id' => 'pi_test_pending',
                'type' => 'payment',
                'amount' => 199.99,
                'currency' => 'USD',
                'status' => 'pending',
                'payment_method_type' => 'card',
                'payment_method_details' => [
                    'last_four' => '1234',
                    'brand' => 'visa',
                ],
                'processed_at' => null,
            ],
        ];

        foreach ($transactions as $data) {
            $transaction = PaymentTransaction::create(array_merge($data, [
                'billing_account_id' => $billingAccount->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Test)',
                'metadata' => ['seeded' => true],
            ]));

            echo "Created transaction: {$transaction->transaction_id} ({$data['status']})\n";
        }

        echo "\n✅ Payment test data seeded successfully!\n";
        echo "Total transactions: " . PaymentTransaction::where('billing_account_id', $billingAccount->id)->count() . "\n";
    }
}
