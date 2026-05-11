<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class PaymentReceipt extends Mailable
{
    protected $payload;
    public function __construct($payload = []) { $this->payload = $payload; }
    public function build() { return $this->view("emails.payment_receipt"); }
}
