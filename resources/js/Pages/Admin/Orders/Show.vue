<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, PackageCheck, Printer, Truck } from '@lucide/vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { formatPrice, paymentMethodLabel } from '@/currency';

const props = defineProps({ order: Object, couriers: { type: Array, default: () => [] }, courierLabel: { type: String, default: null } });

const canBook = () => !props.order.courier && ['processing', 'shipped'].includes(props.order.status);

function book(key, label) {
    if (window.confirm(`Book this order with ${label}? (demo -- no real booking is made)`)) {
        router.post(`/admin/orders/${props.order.id}/courier`, { courier: key }, { preserveScroll: true });
    }
}

const labels = { processing: 'Start processing', shipped: 'Mark as shipped', completed: 'Mark as delivered', cancelled: 'Cancel order' };

function setStatus(status) {
    if (status === 'cancelled' && !window.confirm('Cancel this order? Stock will be returned to inventory.')) return;
    router.patch(`/admin/orders/${props.order.id}/status`, { status }, { preserveScroll: true });
}

function setPayment(event) {
    const select = event.target;

    router.patch(`/admin/orders/${props.order.id}/payment`, { payment_status: select.value }, {
        preserveScroll: true,
        // If the server refused the change, snap the dropdown back to the real value.
        onFinish: () => { select.value = props.order.payment_status; },
    });
}

const fmt = (v) => new Date(v).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
</script>

<template>
    <Head :title="`Order ${order.order_number}`" />

    <AdminLayout :title="`Order ${order.order_number}`">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <Link href="/admin/orders" class="flex min-h-10 items-center gap-1 text-sm font-medium text-green-700 hover:underline"><ArrowLeft class="h-4 w-4" /> All orders</Link>
            <div class="flex flex-wrap gap-2">
                <a :href="`/admin/orders/print?ids=${order.id}`" target="_blank" class="flex min-h-10 items-center gap-1.5 rounded-full border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"><PackageCheck class="h-4 w-4" /> Packing slip</a>
                <a :href="`/orders/${order.id}/invoice`" target="_blank" class="flex min-h-10 items-center gap-1.5 rounded-full border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"><Printer class="h-4 w-4" /> Invoice</a>
            </div>
        </div>

        <div class="grid gap-4 sm:gap-6 lg:grid-cols-3">
            <div class="min-w-0 space-y-4 sm:space-y-6 lg:col-span-2">
                <div class="rounded-2xl bg-white p-4 shadow-sm sm:p-5">
                    <h2 class="mb-3 font-bold text-gray-900">Items</h2>
                    <ul class="divide-y divide-gray-100">
                        <li v-for="item in order.items" :key="item.id" class="flex items-center gap-3 py-3 sm:gap-4">
                            <img loading="lazy" decoding="async" :src="item.product_image" :alt="item.product_name" class="h-12 w-12 shrink-0 rounded-lg bg-gray-100 object-cover">
                            <div class="min-w-0 flex-1">
                                <p class="line-clamp-2 text-sm font-semibold break-words text-gray-800">{{ item.product_name }}</p>
                                <p v-if="item.variant_label" class="text-xs text-gray-500">{{ item.variant_label }}</p>
                                <p class="text-xs text-gray-400">{{ item.quantity }} &times; {{ formatPrice(item.price) }}</p>
                            </div>
                            <span class="shrink-0 font-semibold whitespace-nowrap">{{ formatPrice(item.subtotal) }}</span>
                        </li>
                    </ul>
                    <div class="mt-3 space-y-1 border-t border-gray-100 pt-3 text-sm">
                        <div class="flex justify-between text-gray-600"><span>Subtotal</span><span>{{ formatPrice(order.subtotal) }}</span></div>
                        <div v-if="Number(order.discount_amount) > 0" class="flex justify-between text-green-700"><span>Discount ({{ order.coupon_code }})</span><span>-{{ formatPrice(order.discount_amount) }}</span></div>
                        <div class="flex justify-between text-gray-600"><span>Shipping</span><span>{{ formatPrice(order.shipping_fee) }}</span></div>
                        <div class="flex justify-between pt-1 text-base font-bold text-gray-900"><span>Total</span><span>{{ formatPrice(order.total_amount) }}</span></div>
                    </div>
                </div>

                <div class="rounded-2xl bg-white p-4 shadow-sm sm:p-5">
                    <h2 class="mb-3 font-bold text-gray-900">Shipping</h2>
                    <p class="text-sm break-words text-gray-700">{{ order.shipping_name }} &middot; {{ order.shipping_phone }}</p>
                    <p class="text-sm break-words text-gray-500">{{ order.shipping_address }}<span v-if="order.shipping_city">, {{ order.shipping_city }}</span></p>
                    <p v-if="order.notes" class="mt-2 text-sm break-words text-gray-500 italic">"{{ order.notes }}"</p>
                </div>
            </div>

            <div class="min-w-0 space-y-4 sm:space-y-6">
                <div class="rounded-2xl bg-white p-4 shadow-sm sm:p-5">
                    <h2 class="mb-3 font-bold text-gray-900">Status</h2>
                    <div class="mb-4 flex flex-wrap gap-2"><StatusBadge :value="order.status" kind="order" /><StatusBadge :value="order.payment_status" kind="payment" /></div>
                    <div v-if="order.next_statuses.length" class="space-y-2">
                        <button v-for="s in order.next_statuses" :key="s" type="button" class="w-full rounded-full py-2.5 text-sm font-semibold" :class="s === 'cancelled' ? 'border border-rose-200 text-rose-600 hover:bg-rose-50' : 'bg-green-600 text-white hover:bg-green-700'" @click="setStatus(s)">{{ labels[s] }}</button>
                    </div>
                    <p v-else class="text-sm text-gray-400">This order is {{ order.status === 'completed' ? 'delivered' : order.status }} and can't be changed.</p>
                    <p v-if="!order.stock_deducted && order.status === 'pending'" class="mt-3 rounded-lg bg-amber-50 p-2 text-xs text-amber-700">Waiting for online payment. It can be processed once paid.</p>
                </div>

                <div class="rounded-2xl bg-white p-4 shadow-sm sm:p-5">
                    <h2 class="mb-3 flex items-center gap-2 font-bold text-gray-900"><Truck class="h-4 w-4" /> Courier</h2>
                    <template v-if="order.courier">
                        <p class="text-sm font-medium text-gray-800">{{ courierLabel ?? order.courier }}</p>
                        <p class="font-mono text-xs break-all text-gray-500">{{ order.tracking_code }}</p>
                        <p v-if="order.courier_booked_at" class="mt-1 text-xs text-gray-400">Booked {{ fmt(order.courier_booked_at) }} (demo)</p>
                    </template>
                    <template v-else-if="canBook()">
                        <p class="mb-2 text-xs text-gray-500">One-click dispatch (demo): books the parcel and marks the order shipped.</p>
                        <div class="grid gap-2">
                            <button v-for="c in couriers" :key="c.key" type="button" class="min-h-10 rounded-full bg-gray-900 py-2 text-sm font-semibold text-white hover:bg-gray-700" @click="book(c.key, c.label)">Book with {{ c.label }}</button>
                        </div>
                    </template>
                    <p v-else class="text-sm text-gray-400">Move the order to Processing to book a courier.</p>
                </div>

                <div class="rounded-2xl bg-white p-4 shadow-sm sm:p-5">
                    <h2 class="mb-3 font-bold text-gray-900">Payment</h2>
                    <p class="text-sm text-gray-600">{{ paymentMethodLabel(order.payment_method) }}</p>
                    <p v-if="order.transaction_id" class="mb-2 font-mono text-xs break-all text-gray-400">{{ order.transaction_id }}</p>
                    <label class="mt-2 mb-1 block text-xs font-medium text-gray-500">Payment status</label>
                    <select :value="order.payment_status" class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-green-500 focus:outline-none" @change="setPayment">
                        <option value="pending">Pending</option><option value="paid">Paid</option><option value="failed">Failed</option><option value="refunded">Refunded</option>
                    </select>
                </div>

                <div class="rounded-2xl bg-white p-4 text-sm shadow-sm sm:p-5">
                    <h2 class="mb-3 font-bold text-gray-900">Customer</h2>
                    <Link v-if="order.user" :href="`/admin/customers/${order.user.id}`" class="font-medium text-green-700 hover:underline">{{ order.user.name }}</Link>
                    <p class="break-all text-gray-500">{{ order.user?.email }}</p>
                    <p class="mt-3 text-xs text-gray-400">Placed {{ fmt(order.created_at) }}</p>
                    <p v-if="order.delivered_at" class="text-xs text-gray-400">Delivered {{ fmt(order.delivered_at) }}</p>
                    <p v-if="order.cancelled_at" class="text-xs text-gray-400">Cancelled {{ fmt(order.cancelled_at) }}</p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
