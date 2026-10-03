<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { X } from '@lucide/vue';
import BrandIcon from '@/Components/BrandIcon.vue';
import { routes } from '@/routes';

// A Google One-Tap style prompt for guests. It signs in a demo account; no
// request is made to Google. See DemoLoginController for swapping in the real thing.
const page = usePage();
const siteName = computed(() => page.props.site?.name || 'ShopEase');
const KEY = 'shopease.onetap.dismissed';

const visible = ref(false);
const busy = ref(false);
let timer = null;

const eligible = computed(() => {
    const url = page.url || '';
    return Boolean(page.props.shop?.demoLogin)
        && !page.props.auth?.user
        && !/^\/(login|register|forgot-password|reset-password)/.test(url);
});

function dismissed() {
    try {
        return window.sessionStorage.getItem(KEY) === '1';
    } catch {
        return false;
    }
}

function dismiss() {
    visible.value = false;
    try {
        window.sessionStorage.setItem(KEY, '1');
    } catch {
        // ignore - the prompt just may show again
    }
}

function signIn() {
    busy.value = true;
    router.post(routes.demoLogin(), { provider: 'google' }, {
        onFinish: () => {
            busy.value = false;
            visible.value = false;
        },
    });
}

onMounted(() => {
    if (eligible.value && !dismissed()) {
        timer = window.setTimeout(() => {
            if (eligible.value) visible.value = true;
        }, 2500);
    }
});

onBeforeUnmount(() => window.clearTimeout(timer));
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0 -translate-y-3"
        leave-active-class="transition ease-in duration-150"
        leave-to-class="opacity-0 -translate-y-3"
    >
        <div
            v-if="visible && eligible"
            class="fixed top-3 right-3 left-3 z-[60] rounded-2xl border border-gray-200 bg-white p-4 shadow-2xl sm:top-20 sm:right-4 sm:left-auto sm:w-80"
            role="dialog"
            aria-label="Sign in with Google"
        >
            <button type="button" class="absolute top-2 right-2 flex h-8 w-8 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100" aria-label="Dismiss" @click="dismiss">
                <X class="h-4 w-4" />
            </button>
            <p class="pr-8 text-sm font-semibold text-gray-900">Sign in to {{ siteName }} with Google</p>
            <p class="mt-0.5 text-xs text-gray-500">One tap to start shopping (demo account).</p>
            <button type="button" :disabled="busy" class="mt-3 flex min-h-11 w-full items-center gap-3 rounded-full border border-gray-300 px-4 py-2 text-left text-sm hover:bg-gray-50 disabled:opacity-60" @click="signIn">
                <BrandIcon name="google" class="h-5 w-5 shrink-0" />
                <span class="min-w-0 flex-1 truncate font-medium text-gray-800">{{ busy ? 'Signing in...' : 'Continue as Google Demo User' }}</span>
            </button>
        </div>
    </Transition>
</template>
