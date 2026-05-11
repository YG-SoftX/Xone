<?php
class Plans {
    public static function all(): array {
        return [
            'free' => [
                'name'        => 'Free',
                'price_month' => 0,
                'price_year'  => 0,
                'api_calls'   => 100,       // per day
                'models'      => 1,
                'train_steps' => 5000,
                'pages'       => 10,
                'features'    => ['1 model', '100 API calls/day', '10 pages/crawl', 'Community support'],
                'badge'       => '',
            ],
            'starter' => [
                'name'        => 'Starter',
                'price_month' => 9,
                'price_year'  => 79,
                'api_calls'   => 2000,
                'models'      => 3,
                'train_steps' => 20000,
                'pages'       => 50,
                'features'    => ['3 models', '2,000 API calls/day', '50 pages/crawl', 'Email support'],
                'badge'       => 'popular',
            ],
            'pro' => [
                'name'        => 'Pro',
                'price_month' => 29,
                'price_year'  => 249,
                'api_calls'   => 20000,
                'models'      => 10,
                'train_steps' => 50000,
                'pages'       => 200,
                'features'    => ['10 models', '20,000 API calls/day', '200 pages/crawl', 'Priority support', 'Webhook events'],
                'badge'       => '',
            ],
            'enterprise' => [
                'name'        => 'Enterprise',
                'price_month' => 99,
                'price_year'  => 899,
                'api_calls'   => PHP_INT_MAX,
                'models'      => PHP_INT_MAX,
                'train_steps' => PHP_INT_MAX,
                'pages'       => PHP_INT_MAX,
                'features'    => ['Unlimited models', 'Unlimited API calls', 'Unlimited crawl', 'SLA support', 'Custom deployment'],
                'badge'       => '',
            ],
        ];
    }

    public static function get(string $plan): array {
        return self::all()[$plan] ?? self::all()['free'];
    }

    public static function canDo(array $sub, string $action, int $value = 1): bool {
        $plan = self::get($sub['plan'] ?? 'free');
        return match($action) {
            'api_call'   => ($sub['calls_today'] ?? 0) + $value <= $plan['api_calls'],
            'add_model'  => ($sub['model_count'] ?? 0) + $value <= $plan['models'],
            'train_step' => $value <= $plan['train_steps'],
            'crawl_page' => $value <= $plan['pages'],
            default      => false,
        };
    }
}
