<script setup>
import { BadgeCheck, RefreshCcw, ShieldCheck, Truck } from '@lucide/vue';

const badges = [
    { icon: ShieldCheck, title: 'Secure payments', text: 'Cash on delivery, bKash, Nagad & protected online payment', color: 'bg-green-100 text-green-700' },
    { icon: Truck, title: 'Fast delivery', text: '1-2 days in Dhaka, 3-5 days nationwide', color: 'bg-blue-100 text-blue-700' },
    { icon: RefreshCcw, title: '7-day replacement', text: 'Damaged or wrong item? We replace it, no questions asked', color: 'bg-amber-100 text-amber-700' },
    { icon: BadgeCheck, title: '100% genuine', text: 'Original products with brand warranty', color: 'bg-purple-100 text-purple-700' },
];
</script>

<template>
    <section class="grid grid-cols-1 gap-2 min-[480px]:grid-cols-2 sm:gap-3 lg:grid-cols-4" aria-label="Why shop with us">
        <div v-for="badge in badges" :key="badge.title" class="flex min-w-0 items-start gap-3 rounded-xl border border-gray-200 bg-white p-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg" :class="badge.color"><component :is="badge.icon" class="h-5 w-5" /></span>
            <div class="min-w-0">
                <p class="text-sm font-bold text-gray-900">{{ badge.title }}</p>
                <p class="mt-0.5 text-xs leading-relaxed text-gray-500">{{ badge.text }}</p>
            </div>
        </div>
    </section>
</template>
