<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import BrandIcon from '@/Components/BrandIcon.vue';
import { ShieldCheck, User } from '@lucide/vue';
import { routes } from '@/routes';

// One-click demo access for visitors trying the site. Controlled by SHOP_DEMO_LOGIN.
const page = usePage();
const enabled = computed(() => Boolean(page.props.shop?.demoLogin));
const busy = ref('');

function signIn(provider) {
    busy.value = provider;
    router.post(routes.demoLogin(), { provider }, { onFinish: () => (busy.value = '') });
}
</script>

<template>
    <div v-if="enabled" class="mt-5">
        <div class="relative my-4 flex items-center">
            <span class="h-px flex-1 bg-gray-200" />
            <span class="px-3 text-xs font-medium text-gray-400 uppercase">or explore the demo</span>
            <span class="h-px flex-1 bg-gray-200" />
        </div>
        <button type="button" :disabled="busy !== ''" class="mb-2 flex min-h-11 w-full items-center justify-center gap-2.5 rounded-full border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-60" @click="signIn('google')">
            <BrandIcon name="google" class="h-5 w-5 shrink-0" /> {{ busy === 'google' ? 'Signing in...' : 'Continue with Google (demo)' }}
        </button>
        <div class="grid gap-2 min-[400px]:grid-cols-2">
            <button type="button" :disabled="busy !== ''" class="flex min-h-11 items-center justify-center gap-2 rounded-full bg-green-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-60" @click="signIn('customer')">
                <User class="h-4 w-4 shrink-0" /> {{ busy === 'customer' ? 'Signing in...' : 'Customer' }}
            </button>
            <button type="button" :disabled="busy !== ''" class="flex min-h-11 items-center justify-center gap-2 rounded-full bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-gray-700 disabled:opacity-60" @click="signIn('admin')">
                <ShieldCheck class="h-4 w-4 shrink-0" /> {{ busy === 'admin' ? 'Signing in...' : 'Admin' }}
            </button>
        </div>
        <p class="mt-2 text-center text-xs text-gray-400">Demo only: no real Google sign-in. Customer / Admin sign in the test accounts, no password needed.</p>
    </div>
</template>
