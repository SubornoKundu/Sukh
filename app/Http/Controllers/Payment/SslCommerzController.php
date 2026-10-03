<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CheckoutException;
use App\Services\OrderService;
use App\Services\SslCommerzDemoGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SslCommerzController extends Controller
{
    public function __construct(
        protected SslCommerzDemoGateway $gateway,
        protected OrderService $orders,
    ) {}

    /**
     * Render the mock SSLCommerz checkout page for an order awaiting payment.
     */
    public function show(Request $request, Order $order): Response|RedirectResponse
    {
        $this->authorizeOwner($request, $order);

        if (! $order->awaitingPayment()) {
            return redirect()->route('orders.show', $order);
        }

        return Inertia::render('Payment/Gateway', [
            'order' => $order->load('items'),
            'session' => $this->gateway->initiate($order),
        ]);
    }

    /**
     * Demo "Pay Now": confirms the order (stock is taken at this moment),
     * marks it paid and clears the purchased items from the cart.
     */
    public function success(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOwner($request, $order);

        if ($order->isPaid()) {
            return redirect()->route('orders.show', $order);
        }

        $data = $request->validate([
            'method' => ['required', 'string', 'in:card,bkash,nagad,rocket'],
        ]);

        try {
            $this->orders->payOnline($order, $data['method']);
        } catch (CheckoutException $e) {
            // Stock ran out while the customer was on the payment page.
            if ($order->refresh()->awaitingPayment()) {
                $this->orders->cancel($order);
            }

            return redirect()->route('cart.index')
                ->with('error', $e->getMessage().' Your order was cancelled and you were not charged.');
        }

        return redirect()->route('orders.success', $order);
    }

    /**
     * Demo "Cancel payment": the order stays pending so the customer can
     * retry. Ignored for orders that are already paid.
     */
    public function fail(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOwner($request, $order);

        if (! $order->awaitingPayment()) {
            return redirect()->route('orders.show', $order);
        }

        $this->gateway->fail($order);

        return redirect()->route('payment.show', $order)
            ->with('error', 'Payment was not completed. You can try again below.');
    }

    protected function authorizeOwner(Request $request, Order $order): void
    {
        abort_if($order->user_id !== $request->user()->id, 403);
    }
}
