<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Compass, Home, LockKeyhole, ServerCrash, ShoppingBag, Wrench, ArrowLeft, ShieldCheck } from '@lucide/vue';
import SiteLogo from '@/Components/SiteLogo.vue';

// Branded error page for 403 / 404 / 500 / 503. Stands alone (no MainLayout) so it still
// works when the server failed before it could prepare the header / footer data.
const props = defineProps({
    status: { type: Number, default: 404 },
    // True only on a demo site, for a signed-in customer who opened an admin address.
    demoAdminSwitch: { type: Boolean, default: false },
});

const switching = ref(false);

function switchToAdmin() {
    if (switching.value) return;
    switching.value = true;
    router.post('/auth/demo-admin', {}, { onFinish: () => (switching.value = false) });
}

const info = computed(() => ({
    403: { icon: LockKeyhole, title: 'You can\'t open this page', text: 'This page is not available for your account. If you think this is a mistake, please sign in with the right account or contact us.' },
    404: { icon: Compass, title: 'Page not found', text: 'The page you are looking for does not exist or has moved. Check the link, or start again from the home page.' },
    500: { icon: ServerCrash, title: 'Something went wrong', text: 'We hit a problem on our side. Please try again in a moment. Your cart and orders are safe.' },
    503: { icon: Wrench, title: 'Back in a few minutes', text: 'We are doing some quick maintenance. Please check again shortly.' },
}[props.status] ?? { icon: ServerCrash, title: 'Something went wrong', text: 'Please try again in a moment.' }));

function goBack() {
    if (window.history.length > 1) window.history.back();
    else window.location.href = '/';
}
</script>

<template>
    <Head :title="info.title" />

    <div class="flex min-h-dvh flex-col items-center justify-center bg-gray-50 px-4 py-10">
        <a href="/" class="mb-8"><SiteLogo size="lg" /></a>

        <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 text-center shadow-sm sm:p-8">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-green-50 text-green-600">
                <component :is="info.icon" class="h-7 w-7" />
            </span>
            <p class="mt-4 text-sm font-semibold tracking-widest text-green-700">{{ status }}</p>
            <h1 class="mt-1 text-xl font-bold break-words text-gray-900 sm:text-2xl">{{ info.title }}</h1>
            <p class="mt-2 text-sm leading-relaxed text-gray-500">{{ info.text }}</p>

            <button
                v-if="demoAdminSwitch"
                type="button"
                :disabled="switching"
                class="mt-6 flex min-h-11 w-full items-center justify-center gap-2 rounded-full bg-gray-900 px-6 py-2.5 text-sm font-semibold text-white hover:bg-gray-800 disabled:opacity-60"
                @click="switchToAdmin"
            >
                <ShieldCheck class="h-4 w-4" /> Switch to admin
            </button>

            <div class="flex flex-col gap-3 sm:flex-row sm:justify-center" :class="demoAdminSwitch ? 'mt-3' : 'mt-6'">
                <a href="/" class="flex min-h-11 items-center justify-center gap-2 rounded-full bg-green-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-green-700">
                    <Home class="h-4 w-4" /> Go to home
                </a>
                <a href="/products" class="flex min-h-11 items-center justify-center gap-2 rounded-full border-2 border-green-600 px-6 py-2.5 text-sm font-semibold text-green-700 hover:bg-green-50">
                    <ShoppingBag class="h-4 w-4" /> Browse products
                </a>
            </div>
            <button type="button" class="mt-4 inline-flex min-h-10 items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-gray-800" @click="goBack">
                <ArrowLeft class="h-4 w-4" /> Go back
            </button>
        </div>
    </div>
</template>
