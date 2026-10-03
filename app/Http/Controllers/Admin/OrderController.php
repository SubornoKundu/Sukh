<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CheckoutException;
use App\Services\CourierService;
use App\Services\OrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orders) {}

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:pending,processing,shipped,completed,cancelled'],
            'payment' => ['nullable', 'in:pending,paid,failed,refunded'],
            'method' => ['nullable', 'in:cod,sslcommerz,bkash,nagad'],
        ]);

        $orders = Order::query()
            ->with('user:id,name,email')
            ->withCount('items')
            ->when($filters['q'] ?? null, function ($query, $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';

                $query->where(fn ($q) => $q
                    ->where('order_number', 'like', $like)
                    ->orWhere('shipping_name', 'like', $like)
                    ->orWhere('shipping_phone', 'like', $like)
                    ->orWhereHas('user', fn ($u) => $u->where('email', 'like', $like)));
            })
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['payment'] ?? null, fn ($q, $v) => $q->where('payment_status', $v))
            ->when($filters['method'] ?? null, fn ($q, $v) => $q->where('payment_method', $v))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'filters' => [
                'q' => $filters['q'] ?? '',
                'status' => $filters['status'] ?? '',
                'payment' => $filters['payment'] ?? '',
                'method' => $filters['method'] ?? '',
            ],
        ]);
    }

    public function show(Order $order): Response
    {
        return Inertia::render('Admin/Orders/Show', [
            'order' => $order->load('items.product:id,slug', 'user:id,name,email,phone'),
            'couriers' => collect(CourierService::COURIERS)->map(fn ($c, $key) => ['key' => $key, 'label' => $c['label']])->values(),
            'courierLabel' => CourierService::label($order->courier),
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:processing,shipped,completed,cancelled'],
        ]);

        try {
            $this->orders->updateStatus($order, $data['status']);
        } catch (CheckoutException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Order marked as {$data['status']}.");
    }

    public function updatePayment(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'payment_status' => ['required', 'in:pending,paid,failed,refunded'],
        ]);

        if ($order->status === Order::STATUS_CANCELLED && $data['payment_status'] === Order::PAYMENT_PAID) {
            return back()->with('error', "A cancelled order can't be marked as paid.");
        }

        if ($data['payment_status'] === Order::PAYMENT_PAID && ! $order->stock_deducted) {
            return back()->with('error', 'This order is still waiting for the customer to pay online. It will be marked paid automatically once the payment succeeds.');
        }

        $order->update(['payment_status' => $data['payment_status']]);

        return back()->with('success', "Payment marked as {$data['payment_status']}.");
    }

    /**
     * Printable packing slips (one page per order). Select orders in the list
     * and pass their ids, e.g. /admin/orders/print?ids=3,5,8
     */
    public function print(Request $request): View
    {
        $ids = collect(explode(',', (string) $request->query('ids', '')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->unique()
            ->take(100);

        abort_if($ids->isEmpty(), 404);

        $orders = Order::with(['items', 'user:id,name,email,phone'])
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get();

        abort_if($orders->isEmpty(), 404);

        return view('packing-slip', [
            'orders' => $orders,
            'courierLabels' => collect(CourierService::COURIERS)->map(fn ($c) => $c['label'])->all(),
        ]);
    }

    /**
     * Mock "1-click dispatch": books the order with Pathao or Steadfast and
     * marks it as shipped.
     */
    public function courier(Request $request, Order $order, CourierService $couriers): RedirectResponse
    {
        $data = $request->validate([
            'courier' => ['required', 'in:'.implode(',', array_keys(CourierService::COURIERS))],
        ]);

        if ($order->courier) {
            return back()->with('error', 'This order is already booked with '.CourierService::label($order->courier).'.');
        }

        if (! in_array($order->status, [Order::STATUS_PROCESSING, Order::STATUS_SHIPPED], true)) {
            return back()->with('error', 'Move the order to "Processing" before booking a courier.');
        }

        try {
            if ($order->status === Order::STATUS_PROCESSING) {
                $order = $this->orders->updateStatus($order, Order::STATUS_SHIPPED);
            }

            $booking = $couriers->book($order, $data['courier']);
        } catch (CheckoutException $e) {
            return back()->with('error', $e->getMessage());
        }

        $order->update([
            'courier' => $booking['courier'],
            'tracking_code' => $booking['tracking_code'],
            'courier_booked_at' => now(),
        ]);

        return back()->with('success', 'Booked with '.CourierService::label($booking['courier']).' (demo). Tracking code: '.$booking['tracking_code']
            .($booking['cod_amount'] > 0 ? '. Collect ৳'.number_format($booking['cod_amount']).' on delivery.' : '.'));
    }
}
