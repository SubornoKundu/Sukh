<?php

namespace App\Services;

use App\Models\Order;

/**
 * A self-contained DEMO of an SSLCommerz-style checkout.
 *
 * SSLCommerz (https://www.sslcommerz.com) is the payment gateway named in
 * the assignment brief. Wiring up a real merchant account needs sandbox
 * credentials this project doesn't have, so this class fakes the parts of
 * the flow a real integration would perform:
 *
 *   1. initiate()  -- a real gateway would return a redirect URL to its
 *      hosted payment page. Here, App\Http\Controllers\Payment\
 *      SslCommerzController::show() renders that page itself
 *      (resources/js/Pages/Payment/Gateway.vue), styled after
 *      SSLCommerz's sandbox checkout.
 *   2. complete()  -- a real gateway calls your IPN/success URL with a
 *      signed payload after the customer pays. Here, clicking "Pay Now"
 *      on the demo page hits that same success route directly.
 *
 * To go live: install `sslcommerz/sslcommerz-php`, replace the body of
 * initiate()/complete() with real API calls, and point config/services.php
 * at real store credentials. Nothing outside this class needs to change.
 */
class SslCommerzDemoGateway
{
    /**
     * "Initiate" a payment session for the given order. A real gateway
     * integration would call out to SSLCommerz here and return the
     * `GatewayPageURL` it responds with; the demo simply hands back the
     * in-app route that renders the mock checkout page.
     *
     * @return array{order_id: int, amount: string, currency: string, redirect_url: string}
     */
    public function initiate(Order $order): array
    {
        return [
            'order_id' => $order->id,
            'amount' => number_format((float) $order->total_amount, 2, '.', ''),
            'currency' => 'BDT',
            'redirect_url' => route('payment.show', $order),
        ];
    }

    /**
     * Mark an order as paid via the chosen demo method (card / bKash /
     * Nagad / Rocket) and generate a fake-but-plausible transaction ID,
     * in the same shape SSLCommerz's `val_id` / `tran_id` would take.
     */
    public function complete(Order $order, string $method): string
    {
        $transactionId = strtoupper('DEMO'.now()->format('ymdHis').bin2hex(random_bytes(3)));

        $order->update([
            'payment_status' => Order::PAYMENT_PAID,
            'status' => Order::STATUS_PROCESSING,
            'payment_method' => $method,
            'transaction_id' => $transactionId,
        ]);

        return $transactionId;
    }

    /**
     * Mark an order's payment as failed / cancelled by the customer.
     */
    public function fail(Order $order): void
    {
        $order->update([
            'payment_status' => Order::PAYMENT_FAILED,
        ]);
    }
}
