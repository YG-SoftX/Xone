<?php

namespace YGAI\Payments\Gateways;

class YGPay {
    private $clientId;
    private $checkoutUrl = "https://pay.ygxone.com/checkout";

    public function __construct($clientId) {
        $this->clientId = $clientId;
    }

    /**
     * Initiate Payment and Redirect to YG Pay Checkout
     */
    public function process($amount, $currency, $orderId, $returnUrl, $cancelUrl) {
        $params = [
            'client_id'     => $this->clientId,
            'amount'        => $amount,
            'currency'      => $currency,
            'return_url'    => $returnUrl . "?order_id=" . $orderId,
            'cancel_url'    => $cancelUrl . "?order_id=" . $orderId,
            'custom'        => $orderId
        ];

        $queryString = http_build_query($params);
        $redirectUrl = $this->checkoutUrl . "?" . $queryString;

        header("Location: " . $redirectUrl);
        exit();
    }

    /**
     * Verify Callback from YG Pay
     */
    public function verify($request) {
        if (isset($request['status']) && $request['status'] === 'success') {
            return [
                'status' => true,
                'trx_id' => $request['trx_id'] ?? null,
                'order_id' => $request['custom'] ?? null
            ];
        }
        return ['status' => false];
    }
}
