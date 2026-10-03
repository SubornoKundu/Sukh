<?php

namespace App\Services;

use App\Models\Order;

/**
 * Mock courier booking (Pathao / Steadfast). It only generates a fake
 * consignment id so the "1-click dispatch" flow can be demoed end to end.
 * To go live, replace book() with a real HTTP call to the courier's API and
 * return the consignment id the courier gives you.
 */
class CourierService
{
    /** @var array<string, array{label: string, prefix: string}> */
    public const COURIERS = [
        'pathao' => ['label' => 'Pathao', 'prefix' => 'PTH'],
        'steadfast' => ['label' => 'Steadfast', 'prefix' => 'SFC'],
    ];

    /**
     * @return array{courier: string, tracking_code: string, cod_amount: float}
     */
    public function book(Order $order, string $courier): array
    {
        $meta = self::COURIERS[$courier] ?? throw new CheckoutException('Unknown courier.');

        return [
            'courier' => $courier,
            'tracking_code' => $meta['prefix'].'-'.now()->format('ymd').'-'.strtoupper(substr(bin2hex(random_bytes(4)), 0, 6)),
            // Cash to collect from the customer at the door.
            'cod_amount' => $order->isPaid() ? 0.0 : (float) $order->total_amount,
        ];
    }

    public static function label(?string $courier): ?string
    {
        return $courier ? (self::COURIERS[$courier]['label'] ?? ucfirst($courier)) : null;
    }
}
