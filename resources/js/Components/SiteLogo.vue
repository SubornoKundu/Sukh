<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Store } from '@lucide/vue';

// The brand mark used in the header, footer, login card and admin sidebar.
// Uses the uploaded logo when there is one, otherwise an icon + the site name.
const props = defineProps({
    size: { type: String, default: 'md' },        // sm | md | lg
    dark: { type: Boolean, default: false },       // on a dark background (admin sidebar)
    hideNameBelow: { type: Boolean, default: false }, // hide the text name on very narrow phones
    suffix: { type: String, default: '' },         // e.g. "Admin"
});

const site = computed(() => usePage().props.site ?? {});
const name = computed(() => site.value.name || 'ShopEase');

// "ShopEase" -> Shop + Ease, "My Shop" -> My + Shop, "Acme" -> Acme
const parts = computed(() => {
    const n = name.value.trim();
    const camel = n.match(/^([A-Z][a-z0-9]+)([A-Z][A-Za-z0-9]*)$/);
    if (camel) return [camel[1], camel[2]];
    const i = n.indexOf(' ');
    if (i > 0) return [n.slice(0, i + 1), n.slice(i + 1)];
    return [n, ''];
});

const box = computed(() => ({ sm: 'h-8 w-8 rounded-lg', md: 'h-9 w-9 rounded-xl', lg: 'h-10 w-10 rounded-xl' }[props.size]));
const icon = computed(() => (props.size === 'sm' ? 'h-4 w-4' : 'h-5 w-5'));
const logoH = computed(() => ({ sm: 'h-8 max-w-[9rem]', md: 'h-9 max-w-[11rem] sm:h-10', lg: 'h-12 max-w-[14rem]' }[props.size]));
const text = computed(() => (props.size === 'lg' ? 'text-xl' : 'text-lg'));
</script>

<template>
    <span class="flex min-w-0 items-center gap-2">
        <template v-if="site.logo">
            <img :src="site.logo" :alt="name" class="w-auto shrink-0 object-contain" :class="logoH">
            <span v-if="suffix" class="text-xs font-normal" :class="dark ? 'text-gray-400' : 'text-gray-500'">{{ suffix }}</span>
        </template>
        <template v-else>
            <span class="flex shrink-0 items-center justify-center bg-green-600 text-white" :class="box"><Store :class="icon" /></span>
            <span class="min-w-0 truncate font-bold tracking-tight" :class="[text, dark ? 'text-white' : 'text-gray-900', hideNameBelow ? 'hidden min-[375px]:inline' : '']">
                {{ parts[0] }}<span v-if="parts[1]" :class="dark ? 'text-green-400' : 'text-green-600'">{{ parts[1] }}</span>
                <span v-if="suffix" class="ml-1 text-xs font-normal" :class="dark ? 'text-gray-400' : 'text-gray-500'">{{ suffix }}</span>
            </span>
        </template>
    </span>
</template>
