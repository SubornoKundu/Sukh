<script setup>
import { computed, ref } from 'vue';
import { useForm, Head, router, usePage } from '@inertiajs/vue3';
import { Banknote, CreditCard, MapPin, Smartphone, Tag, X } from '@lucide/vue';
import { formatPrice } from '@/currency';
import { routes } from '@/routes';
import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({
    cartItems: { type: Array, required: true },
    totals: { type: Object, required: true },
    coupon: { type: Object, default: null },
    defaults: { type: Object, default: () => ({}) },
    addresses: { type: Array, default: () => [] },
    manualPayments: { type: Object, default: () => ({}) },
});

const page = usePage();
const freeOver = page.props.shop?.freeShippingOver ?? 0;

const form = useForm({
    shipping_name: props.defaults.shipping_name ?? '',
    shipping_phone: props.defaults.shipping_phone ?? '',
    shipping_address: props.defaults.shipping_address ?? '',
    shipping_city: props.defaults.shipping_city ?? '',
    notes: '',
    payment_method: 'cod',
    transaction_id: '',
    save_address: false,
});

const isManual = computed(() => ['bkash', 'nagad'].includes(form.payment_method));
const manual = computed(() => props.manualPayments[form.payment_method] ?? null);

function useAddress(a) {
    form.shipping_name = a.name;
    form.shipping_phone = a.phone;
    form.shipping_address = a.address;
    form.shipping_city = a.city ?? '';
}

const methods = [
    { value: 'cod', label: 'Cash on Delivery', hint: 'Pay in cash when your order arrives.', icon: Banknote },
    { value: 'bkash', label: 'bKash', hint: 'Send money, then enter the Transaction ID.', icon: Smartphone },
    { value: 'nagad', label: 'Nagad', hint: 'Send money, then enter the Transaction ID.', icon: Smartphone },
    { value: 'online', label: 'Pay Online', hint: 'Card / mobile banking gateway (demo).', icon: CreditCard },
];

const couponCode = ref('');
const couponBusy = ref(false);

function submit() {
    form.post(routes.checkoutStore());
}

function applyCoupon() {
    if (!couponCode.value.trim()) return;
    router.post(routes.couponApply(), { code: couponCode.value }, {
        preserveScroll: true,
        preserveState: true,
        onStart: () => (couponBusy.value = true),
        // Keep the typed code when it was rejected so it can be corrected.
        onSuccess: (page) => { if (!page.props.flash?.error) couponCode.value = ''; },
        onFinish: () => { couponBusy.value = false; },
    });
}

function removeCoupon() {
    router.delete(routes.couponRemove(), { preserveScroll: true, preserveState: true });
}

const input = 'w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 focus:outline-none';
</script>

<template>
    <Head title="Checkout" />

    <MainLayout>
        <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
            <h1 class="mb-5 text-xl font-bold text-gray-900 sm:mb-6 sm:text-2xl">Checkout</h1>

            <div class="grid grid-cols-1 gap-6 sm:gap-8 lg:grid-cols-3">
                <form class="min-w-0 space-y-4 sm:space-y-5 lg:col-span-2" @submit.prevent="submit">
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-6">
                        <h2 class="mb-4 font-bold text-gray-900">Delivery Details</h2>

                        <div v-if="addresses.length" class="mb-4 flex flex-wrap gap-2">
                            <button v-for="a in addresses" :key="a.id" type="button" class="flex min-h-10 max-w-full min-w-0 items-center gap-1.5 rounded-full border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 hover:border-green-500 hover:text-green-700" @click="useAddress(a)">
                                <MapPin class="h-3.5 w-3.5 shrink-0" /> <span class="shrink-0">{{ a.label }} &middot;</span> <span class="min-w-0 truncate">{{ a.address }}</span>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Full Name</label>
                                <input v-model="form.shipping_name" placeholder="Rakib Hossain" type="text" autocomplete="name" :class="input">
                                <p v-if="form.errors.shipping_name" class="mt-1 text-xs text-rose-600">{{ form.errors.shipping_name }}</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Phone Number</label>
                                <input v-model="form.shipping_phone" placeholder="01712345678" type="tel" inputmode="tel" autocomplete="tel" :class="input">
                                <p v-if="form.errors.shipping_phone" class="mt-1 text-xs text-rose-600">{{ form.errors.shipping_phone }}</p>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-sm font-medium text-gray-700">Street Address</label>
                                <input v-model="form.shipping_address" placeholder="House 12, Road 5, Dhanmondi" type="text" autocomplete="street-address" :class="input">
                                <p v-if="form.errors.shipping_address" class="mt-1 text-xs text-rose-600">{{ form.errors.shipping_address }}</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">City (optional)</label>
                                <input v-model="form.shipping_city" placeholder="Dhaka" type="text" autocomplete="address-level2" :class="input">
                                <p v-if="form.errors.shipping_city" class="mt-1 text-xs text-rose-600">{{ form.errors.shipping_city }}</p>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-sm font-medium text-gray-700">Order Notes (optional)</label>
                                <textarea v-model="form.notes" placeholder="Call before delivery, near Dhanmondi Lake gate" rows="2" :class="input" />
                                <p v-if="form.errors.notes" class="mt-1 text-xs text-rose-600">{{ form.errors.notes }}</p>
                            </div>
                            <label class="flex items-center gap-2 text-sm text-gray-600 sm:col-span-2">
                                <input v-model="form.save_address" type="checkbox" class="rounded border-gray-300 text-green-600"> Save this address for next time
                            </label>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-6">
                        <h2 class="mb-4 font-bold text-gray-900">Payment Method</h2>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label v-for="m in methods" :key="m.value" class="flex min-h-14 cursor-pointer items-start gap-3 rounded-xl border p-3 transition sm:p-4" :class="form.payment_method === m.value ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-gray-300'">
                                <input v-model="form.payment_method" type="radio" :value="m.value" class="mt-1 text-green-600">
                                <span class="min-w-0">
                                    <span class="flex items-center gap-1.5 font-semibold text-gray-900"><component :is="m.icon" class="h-4 w-4 shrink-0" /> {{ m.label }}</span>
                                    <span class="block text-xs text-gray-500">{{ m.hint }}</span>
                                </span>
                            </label>
                        </div>
                        <div v-if="isManual && manual" class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 sm:p-4">
                            <p>Send <strong>{{ formatPrice(totals.total) }}</strong> to <strong class="break-all">{{ manual.number }}</strong> ({{ manual.type }}) via {{ form.payment_method === 'bkash' ? 'bKash' : 'Nagad' }}, then enter the Transaction ID below.</p>
                            <input v-model="form.transaction_id" placeholder="Transaction ID (e.g. 8N7A6D5E4C)" type="text" :class="[input, 'mt-3 uppercase']">
                            <p v-if="form.errors.transaction_id" class="mt-1 text-xs text-rose-600">{{ form.errors.transaction_id }}</p>
                            <p class="mt-2 text-xs text-amber-700">We verify the payment manually; your order is confirmed right away.</p>
                        </div>
                        <p v-if="form.errors.payment_method" class="mt-2 text-xs text-rose-600">{{ form.errors.payment_method }}</p>
                    </div>

                    <div class="sticky bottom-0 z-20 -mx-4 border-t border-gray-200 bg-white/95 px-4 py-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] backdrop-blur sm:static sm:mx-0 sm:border-0 sm:bg-transparent sm:p-0 sm:backdrop-blur-none">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full rounded-full bg-green-600 py-3 font-semibold text-white hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {{ form.processing ? 'Placing order...' : (form.payment_method === 'online' ? `Continue to Payment -- ${formatPrice(totals.total)}` : `Place Order -- ${formatPrice(totals.total)}`) }}
                    </button>
                    </div>
                </form>

                <div class="h-fit rounded-2xl border border-gray-200 bg-white p-4 sm:p-6 lg:sticky lg:top-24">
                    <h2 class="mb-4 font-bold text-gray-900">Order Summary</h2>
                    <ul class="mb-4 space-y-3 lg:max-h-64 lg:overflow-y-auto">
                        <li v-for="item in cartItems" :key="item.id" class="flex items-center gap-3">
                            <img loading="lazy" decoding="async" :src="item.product.image" :alt="item.product.name" class="h-12 w-12 shrink-0 rounded-lg bg-gray-100 object-cover">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-gray-800">{{ item.product.name }}</p>
                                <p class="truncate text-xs text-gray-400"><span v-if="item.variant_label">{{ item.variant_label }} &middot; </span>Qty {{ item.quantity }}</p>
                            </div>
                            <span class="text-sm font-semibold whitespace-nowrap text-gray-800">{{ formatPrice(item.line_total) }}</span>
                        </li>
                    </ul>

                    <div class="mb-4 border-t border-gray-100 pt-4">
                        <div v-if="coupon" class="flex items-center justify-between gap-2 rounded-lg bg-green-50 px-3 py-2 text-sm text-green-800">
                            <span class="flex min-w-0 items-center gap-1.5 break-words"><Tag class="h-4 w-4" /> <strong>{{ coupon.code }}</strong> &middot; {{ coupon.label }}</span>
                            <button type="button" class="-m-2 flex h-10 w-10 shrink-0 items-center justify-center text-green-700 hover:text-green-900" aria-label="Remove coupon" title="Remove coupon" @click="removeCoupon"><X class="h-4 w-4" /></button>
                        </div>
                        <form v-else class="flex gap-2" @submit.prevent="applyCoupon">
                            <input v-model="couponCode" placeholder="Coupon code (e.g. EID20)" type="text" class="w-full min-w-0 rounded-lg border border-gray-200 px-3 py-2 text-sm uppercase focus:border-green-500 focus:outline-none">
                            <button type="submit" :disabled="couponBusy" class="shrink-0 rounded-lg bg-gray-900 px-4 text-sm font-semibold text-white disabled:opacity-60">Apply</button>
                        </form>
                    </div>

                    <div class="space-y-1 border-t border-gray-100 pt-4 text-sm">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal</span>
                            <span>{{ formatPrice(totals.subtotal) }}</span>
                        </div>
                        <div v-if="totals.discount > 0" class="flex justify-between text-green-700">
                            <span>Discount</span>
                            <span>-{{ formatPrice(totals.discount) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Shipping</span>
                            <span>{{ totals.shipping > 0 ? formatPrice(totals.shipping) : 'Free' }}</span>
                        </div>
                        <p v-if="totals.shipping > 0 && freeOver > 0" class="text-xs text-gray-400">Free shipping on orders over {{ formatPrice(freeOver) }}.</p>
                        <div class="flex justify-between pt-2 text-base font-bold text-gray-900">
                            <span>Total</span>
                            <span>{{ formatPrice(totals.total) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
