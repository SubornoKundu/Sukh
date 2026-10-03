<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { BadgeCheck, CreditCard, ShieldCheck, Smartphone } from '@lucide/vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import { formatPrice } from '@/currency';
import { routes } from '@/routes';

const props = defineProps({
    order: { type: Object, required: true },
    session: { type: Object, required: true },
});

const methods = [
    { id: 'card', label: 'Credit / Debit Card', hint: 'Visa, Mastercard, Amex', color: 'bg-blue-600', icon: CreditCard },
    { id: 'bkash', label: 'bKash', hint: 'Mobile wallet', color: 'bg-pink-600', icon: Smartphone },
    { id: 'nagad', label: 'Nagad', hint: 'Mobile wallet', color: 'bg-orange-600', icon: Smartphone },
    { id: 'rocket', label: 'Rocket', hint: 'Mobile wallet', color: 'bg-purple-600', icon: Smartphone },
];

const selected = ref('card');
const processing = ref(false);

function pay() {
    processing.value = true;
    router.post(
        routes.paymentSuccess(props.order.id),
        { method: selected.value },
        { onFinish: () => (processing.value = false) },
    );
}

function cancel() {
    router.post(routes.paymentFail(props.order.id));
}
</script>

<template>
    <Head title="Complete Payment" />
    <FlashMessages />

    <div class="flex min-h-dvh items-center justify-center bg-gray-100 px-4 py-6 sm:py-10">
        <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-xl">
            <div class="bg-gray-900 px-6 py-4 text-white">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-bold tracking-wide">SSLCommerz</span>
                    <span class="rounded-full bg-amber-400/90 px-2 py-0.5 text-[10px] font-bold text-amber-950">DEMO MODE</span>
                </div>
                <p class="mt-1 text-xs text-gray-300">No real transaction is made -- this simulates the checkout flow for the assignment.</p>
            </div>

            <div class="space-y-5 p-4 sm:p-6">
                <div class="rounded-xl bg-gray-50 p-4">
                    <div class="flex justify-between gap-3 text-sm text-gray-500">
                        <span>Order</span>
                        <span class="font-mono break-all">{{ order.order_number }}</span>
                    </div>
                    <div class="mt-1 flex items-baseline justify-between">
                        <span class="text-sm text-gray-500">Amount payable</span>
                        <span class="text-xl font-bold text-gray-900 sm:text-2xl">{{ formatPrice(session.amount) }}</span>
                    </div>
                </div>

                <div>
                    <p class="mb-2 text-sm font-semibold text-gray-700">Choose a payment method</p>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <button
                            v-for="method in methods"
                            :key="method.id"
                            type="button"
                            class="flex items-center gap-2 rounded-xl border-2 p-3 text-left transition"
                            :class="selected === method.id ? 'border-green-600 bg-green-50' : 'border-gray-200 hover:border-gray-300'"
                            @click="selected = method.id"
                        >
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white" :class="method.color">
                                <component :is="method.icon" class="h-4 w-4" />
                            </span>
                            <span>
                                <span class="block text-xs font-semibold text-gray-800">{{ method.label }}</span>
                                <span class="block text-[11px] text-gray-400">{{ method.hint }}</span>
                            </span>
                            <BadgeCheck v-if="selected === method.id" class="ml-auto h-4 w-4 shrink-0 text-green-600" />
                        </button>
                    </div>
                </div>

                <button
                    type="button"
                    :disabled="processing"
                    class="w-full rounded-full bg-green-600 py-3 font-semibold text-white hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-60"
                    @click="pay"
                >
                    {{ processing ? 'Processing...' : `Pay ${formatPrice(session.amount)} Now` }}
                </button>
                <button type="button" class="w-full py-2 text-center text-sm text-gray-400 hover:text-gray-600" @click="cancel">
                    Cancel payment
                </button>

                <p class="flex items-center justify-center gap-1 text-center text-[11px] text-gray-400">
                    <ShieldCheck class="h-3.5 w-3.5" /> Simulated gateway for demo purposes only
                </p>
            </div>
        </div>
    </div>
</template>
