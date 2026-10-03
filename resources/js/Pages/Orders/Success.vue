<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle2, Package, ShoppingBag } from '@lucide/vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import OrderTracker from '@/Components/OrderTracker.vue';
import { formatPrice, paymentMethodLabel } from '@/currency';
import { routes } from '@/routes';

defineProps({
    order: { type: Object, required: true },
});
</script>

<template>
    <Head title="Order placed" />

    <MainLayout>
        <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 sm:py-12">
            <div class="rounded-3xl border border-green-200 bg-white p-5 text-center shadow-sm sm:p-8">
                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
                    <CheckCircle2 class="h-9 w-9 text-green-600" />
                </span>
                <h1 class="mt-4 text-xl font-bold text-gray-900 sm:text-2xl">Thank you! Your order is placed.</h1>
                <p class="mt-1 text-sm text-gray-500">We emailed your confirmation. Order number</p>
                <p class="mt-1 font-mono text-base font-bold break-all text-green-700 sm:text-lg">{{ order.order_number }}</p>

                <div class="mt-6 text-left">
                    <OrderTracker :steps="order.tracking_steps" />
                </div>

                <dl class="mt-6 space-y-2 rounded-2xl bg-gray-50 p-4 text-left text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">Payment</dt><dd class="font-medium text-gray-800">{{ paymentMethodLabel(order.payment_method) }}</dd></div>
                    <div v-if="order.transaction_id" class="flex justify-between gap-3"><dt class="text-gray-500">Transaction ID</dt><dd class="font-mono break-all text-gray-800">{{ order.transaction_id }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">Deliver to</dt><dd class="min-w-0 text-right break-words text-gray-800">{{ order.shipping_name }}, {{ order.shipping_address }}</dd></div>
                    <div class="flex justify-between gap-3 border-t border-gray-200 pt-2 text-base font-bold text-gray-900"><dt>Total</dt><dd>{{ formatPrice(order.total_amount) }}</dd></div>
                </dl>
                <p v-if="order.payment_method === 'cod'" class="mt-3 text-xs text-amber-700">Cash on delivery -- please keep {{ formatPrice(order.total_amount) }} ready.</p>
                <p v-else-if="order.payment_status !== 'paid'" class="mt-3 text-xs text-amber-700">We'll verify your payment shortly and update the order.</p>

                <ul class="mt-5 divide-y divide-gray-100 text-left">
                    <li v-for="item in order.items" :key="item.id" class="flex items-center gap-3 py-2.5">
                        <img loading="lazy" decoding="async" :src="item.product_image" :alt="item.product_name" class="h-12 w-12 shrink-0 rounded-lg bg-gray-100 object-cover">
                        <div class="min-w-0 flex-1">
                            <p class="line-clamp-2 text-sm font-medium break-words text-gray-800">{{ item.product_name }}</p>
                            <p class="text-xs text-gray-400"><span v-if="item.variant_label">{{ item.variant_label }} &middot; </span>Qty {{ item.quantity }}</p>
                        </div>
                        <span class="shrink-0 text-sm font-semibold whitespace-nowrap">{{ formatPrice(item.subtotal) }}</span>
                    </li>
                </ul>

                <div class="mt-6 grid gap-2 sm:grid-cols-2">
                    <Link :href="routes.orderShow(order.id)" class="flex min-h-11 items-center justify-center gap-2 rounded-full bg-green-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-green-700">
                        <Package class="h-4 w-4" /> Track my order
                    </Link>
                    <Link :href="routes.productsIndex()" class="flex min-h-11 items-center justify-center gap-2 rounded-full border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        <ShoppingBag class="h-4 w-4" /> Continue shopping
                    </Link>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
