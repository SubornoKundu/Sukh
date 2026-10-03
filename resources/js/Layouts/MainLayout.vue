<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    ChevronDown, Clock, Heart, LayoutDashboard, LogOut, MapPin, Menu, Package, Phone, ShoppingCart, User, X, Mail,
} from '@lucide/vue';
import { routes } from '@/routes';
import FlashMessages from '@/Components/FlashMessages.vue';
import CartDrawer from '@/Components/CartDrawer.vue';
import LiveSearch from '@/Components/LiveSearch.vue';
import SiteLogo from '@/Components/SiteLogo.vue';
import BrandIcon from '@/Components/BrandIcon.vue';
import GoogleOneTap from '@/Components/GoogleOneTap.vue';
import { openCartDrawer } from '@/Composables/cart';

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const cartCount = computed(() => page.props.cartCount ?? 0);
const wishlistCount = computed(() => page.props.wishlistCount ?? 0);
const shop = computed(() => page.props.shop ?? {});
const categories = computed(() => page.props.navCategories ?? []);
const site = computed(() => page.props.site ?? {});
const siteName = computed(() => site.value.name || 'ShopEase');

const contact = computed(() => shop.value.contact ?? {});
const social = computed(() => Object.entries(shop.value.social ?? {}).filter(([, url]) => url));
const whatsappUrl = computed(() => {
    const number = String(shop.value.whatsapp ?? '').replace(/\D/g, '');
    return number ? `https://wa.me/${number}?text=${encodeURIComponent(`Hi ${siteName.value}, I need some help with an order / product.`)}` : null;
});
const telHref = computed(() => `tel:${String(contact.value.phone ?? '').replace(/[^+\d]/g, '')}`);

const mobileMenuOpen = ref(false);
const openCategory = ref(null);      // desktop dropdown
const mobileCategory = ref(null);    // mobile accordion
const accountOpen = ref(false);
const navRoot = ref(null);
const accountRoot = ref(null);

// Close every popup when the page changes.
watch(() => page.url, () => {
    mobileMenuOpen.value = false;
    openCategory.value = null;
    accountOpen.value = false;
});

function toggleCategory(id) {
    openCategory.value = openCategory.value === id ? null : id;
}

// Hover opens the dropdown on mouse devices; touch devices use tap.
function hoverCategory(id) {
    if (window.matchMedia('(hover: hover)').matches) openCategory.value = id;
}

function onOutside(event) {
    if (navRoot.value && !navRoot.value.contains(event.target)) openCategory.value = null;
    if (accountRoot.value && !accountRoot.value.contains(event.target)) accountOpen.value = false;
}

function onKey(event) {
    if (event.key === 'Escape') {
        openCategory.value = null;
        accountOpen.value = false;
    }
}

onMounted(() => {
    document.addEventListener('pointerdown', onOutside);
    window.addEventListener('keydown', onKey);
});

onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', onOutside);
    window.removeEventListener('keydown', onKey);
});

function logout() {
    router.post(routes.logout());
}

const year = new Date().getFullYear();

const quickLinks = computed(() => [
    { label: 'Home', href: routes.home() },
    { label: 'Shop all products', href: routes.productsIndex() },
    { label: 'Best sellers', href: routes.productsIndex({ sort: 'popular' }) },
    { label: 'My cart', href: routes.cartIndex() },
    { label: 'Wishlist', href: routes.wishlistIndex() },
    { label: 'My orders', href: routes.ordersIndex() },
    { label: 'Address book', href: routes.addressesIndex() },
    { label: 'Track an order', href: routes.trackOrder() },
]);

// Help & policy pages (footer).
const helpLinks = [
    { label: 'About us', href: routes.page('about') },
    { label: 'Contact us', href: routes.page('contact') },
    { label: 'Returns & refunds', href: routes.page('returns') },
    { label: 'Privacy policy', href: routes.page('privacy') },
    { label: 'Terms & conditions', href: routes.page('terms') },
];
</script>

<template>
    <div class="flex min-h-dvh flex-col bg-gray-50">
        <FlashMessages />
        <CartDrawer />
        <GoogleOneTap />

        <div v-if="site.announcement" class="bg-green-700 px-4 py-2 text-center text-xs leading-snug font-medium text-white sm:text-sm">
            <a v-if="site.announcement.link" :href="site.announcement.link" class="block py-1 break-words underline-offset-2 hover:underline">{{ site.announcement.text }} &rarr;</a>
            <span v-else class="break-words">{{ site.announcement.text }}</span>
        </div>

        <header class="sticky top-0 z-40 border-b border-gray-200 bg-white/95 backdrop-blur">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between gap-3 lg:gap-6">
                    <Link :href="routes.home()" class="flex min-w-0 shrink items-center" :aria-label="siteName">
                        <SiteLogo size="md" hide-name-below />
                    </Link>

                    <div class="hidden max-w-xl flex-1 lg:block">
                        <LiveSearch />
                    </div>

                    <div class="flex items-center gap-0.5 sm:gap-1.5">
                        <a
                            v-if="whatsappUrl"
                            :href="whatsappUrl"
                            target="_blank"
                            rel="noopener"
                            class="flex h-10 w-10 items-center justify-center rounded-full text-[#25D366] hover:bg-green-50"
                            title="Chat on WhatsApp"
                            aria-label="Chat on WhatsApp"
                        >
                            <BrandIcon name="whatsapp" class="h-6 w-6" />
                        </a>

                        <Link :href="routes.wishlistIndex()" class="relative flex h-10 w-10 items-center justify-center rounded-full text-gray-600 hover:bg-gray-100" title="Wishlist" aria-label="Wishlist">
                            <Heart class="h-5 w-5" />
                            <span v-if="wishlistCount > 0" class="absolute top-0 right-0 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-600 px-1 text-[10px] leading-none font-bold text-white">
                                {{ wishlistCount > 99 ? '99+' : wishlistCount }}
                            </span>
                        </Link>

                        <button type="button" class="relative flex h-10 w-10 items-center justify-center rounded-full text-gray-600 hover:bg-gray-100" title="Cart" :aria-label="`Open cart, ${cartCount} items`" @click="openCartDrawer">
                            <ShoppingCart class="h-5 w-5" />
                            <span v-if="cartCount > 0" :key="cartCount" class="absolute top-0 right-0 flex h-4 min-w-4 animate-[pop_0.3s_ease-out] items-center justify-center rounded-full bg-green-600 px-1 text-[10px] leading-none font-bold text-white">
                                {{ cartCount > 99 ? '99+' : cartCount }}
                            </span>
                        </button>

                        <!-- Desktop account -->
                        <div v-if="user" ref="accountRoot" class="relative ml-1 hidden lg:block">
                            <button type="button" class="flex h-10 items-center gap-2 rounded-full border border-gray-200 py-1 pr-3 pl-1 hover:bg-gray-50" :aria-expanded="accountOpen" @click="accountOpen = !accountOpen">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-green-600 text-sm font-bold text-white uppercase">{{ user.name?.charAt(0) }}</span>
                                <span class="max-w-24 truncate text-sm font-medium text-gray-700">{{ user.name?.split(' ')[0] }}</span>
                                <ChevronDown class="h-4 w-4 text-gray-400" />
                            </button>
                            <div v-if="accountOpen" class="absolute top-full right-0 z-50 mt-2 w-56 rounded-xl border border-gray-200 bg-white p-1.5 shadow-xl">
                                <Link v-if="user.role === 'admin'" :href="routes.admin()" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-semibold text-gray-900 hover:bg-gray-50"><LayoutDashboard class="h-4 w-4" /> Admin panel</Link>
                                <Link :href="routes.ordersIndex()" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"><Package class="h-4 w-4" /> My orders</Link>
                                <Link :href="routes.addressesIndex()" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"><MapPin class="h-4 w-4" /> Address book</Link>
                                <Link :href="routes.wishlistIndex()" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"><Heart class="h-4 w-4" /> Wishlist</Link>
                                <Link :href="routes.profileEdit()" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"><User class="h-4 w-4" /> Profile</Link>
                                <button type="button" class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50" @click="logout"><LogOut class="h-4 w-4" /> Log out</button>
                            </div>
                        </div>
                        <template v-else>
                            <Link :href="routes.login()" class="hidden shrink-0 rounded-full px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100 lg:block">Login</Link>
                            <Link :href="routes.register()" class="hidden shrink-0 rounded-full bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700 lg:block">Sign up</Link>
                        </template>

                        <button type="button" class="flex h-10 w-10 items-center justify-center rounded-full text-gray-600 hover:bg-gray-100 lg:hidden" :aria-expanded="mobileMenuOpen" aria-label="Menu" @click="mobileMenuOpen = !mobileMenuOpen">
                            <component :is="mobileMenuOpen ? X : Menu" class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <!-- Phones & tablets: search always visible under the logo row -->
                <div class="pb-3 lg:hidden">
                    <LiveSearch />
                </div>
            </div>

            <!-- Desktop category bar with sub-category dropdowns -->
            <nav ref="navRoot" class="hidden border-t border-gray-100 lg:block" aria-label="Categories">
                <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-x-1 px-4 text-sm font-medium text-gray-600 sm:px-6 lg:px-8">
                    <Link :href="routes.home()" class="rounded-lg px-3 py-2.5 hover:text-green-700">Home</Link>
                    <Link :href="routes.productsIndex()" class="rounded-lg px-3 py-2.5 hover:text-green-700">All products</Link>
                    <div
                        v-for="category in categories"
                        :key="category.id"
                        class="relative"
                        @mouseenter="category.children?.length && hoverCategory(category.id)"
                        @mouseleave="openCategory = openCategory === category.id ? null : openCategory"
                    >
                        <template v-if="category.children?.length">
                            <button type="button" class="flex items-center gap-1 rounded-lg px-3 py-2.5 hover:text-green-700" :class="openCategory === category.id ? 'text-green-700' : ''" :aria-expanded="openCategory === category.id" @click="toggleCategory(category.id)">
                                {{ category.name }} <ChevronDown class="h-3.5 w-3.5 transition" :class="openCategory === category.id ? 'rotate-180' : ''" />
                            </button>
                            <div v-if="openCategory === category.id" class="absolute top-full left-0 z-50 min-w-52 rounded-xl border border-gray-200 bg-white p-1.5 shadow-xl">
                                <Link :href="routes.productsIndex({ category: category.slug })" class="block rounded-lg px-3 py-2 font-semibold text-gray-900 hover:bg-green-50 hover:text-green-700">All {{ category.name }}</Link>
                                <Link v-for="child in category.children" :key="child.id" :href="routes.productsIndex({ category: child.slug })" class="block rounded-lg px-3 py-2 hover:bg-green-50 hover:text-green-700">{{ child.name }}</Link>
                            </div>
                        </template>
                        <Link v-else :href="routes.productsIndex({ category: category.slug })" class="block rounded-lg px-3 py-2.5 hover:text-green-700">{{ category.name }}</Link>
                    </div>
                </div>
            </nav>

            <!-- Mobile / tablet menu -->
            <div v-if="mobileMenuOpen" class="max-h-[calc(100dvh-8.5rem)] overflow-y-auto overscroll-contain border-t border-gray-100 lg:hidden">
                <nav class="mx-auto max-w-7xl space-y-1 px-4 py-3 text-sm font-medium text-gray-700 sm:px-6">
                    <Link :href="routes.home()" class="block rounded-lg px-3 py-2.5 hover:bg-gray-100">Home</Link>
                    <Link :href="routes.productsIndex()" class="block rounded-lg px-3 py-2.5 hover:bg-gray-100">All products</Link>

                    <p v-if="categories.length" class="px-3 pt-3 pb-1 text-xs font-semibold tracking-wide text-gray-400 uppercase">Categories</p>
                    <div v-for="category in categories" :key="category.id">
                        <div class="flex items-stretch">
                            <Link :href="routes.productsIndex({ category: category.slug })" class="min-w-0 flex-1 rounded-lg px-3 py-2.5 hover:bg-gray-100">{{ category.name }}</Link>
                            <button v-if="category.children?.length" type="button" class="flex w-11 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100" :aria-expanded="mobileCategory === category.id" :aria-label="`Show ${category.name} sub-categories`" @click="mobileCategory = mobileCategory === category.id ? null : category.id">
                                <ChevronDown class="h-4 w-4 transition" :class="mobileCategory === category.id ? 'rotate-180' : ''" />
                            </button>
                        </div>
                        <div v-if="mobileCategory === category.id" class="mb-1 ml-3 border-l border-gray-200 pl-2">
                            <Link v-for="child in category.children" :key="child.id" :href="routes.productsIndex({ category: child.slug })" class="block rounded-lg px-3 py-2 text-gray-600 hover:bg-gray-100">{{ child.name }}</Link>
                        </div>
                    </div>

                    <p class="px-3 pt-3 pb-1 text-xs font-semibold tracking-wide text-gray-400 uppercase">Account</p>
                    <template v-if="user">
                        <Link v-if="user.role === 'admin'" :href="routes.admin()" class="block rounded-lg bg-gray-900 px-3 py-2.5 text-white">Admin panel</Link>
                        <Link :href="routes.ordersIndex()" class="block rounded-lg px-3 py-2.5 hover:bg-gray-100">My orders</Link>
                        <Link :href="routes.addressesIndex()" class="block rounded-lg px-3 py-2.5 hover:bg-gray-100">Address book</Link>
                        <Link :href="routes.wishlistIndex()" class="block rounded-lg px-3 py-2.5 hover:bg-gray-100">Wishlist</Link>
                        <Link :href="routes.profileEdit()" class="block rounded-lg px-3 py-2.5 hover:bg-gray-100">Profile</Link>
                        <button type="button" class="block w-full rounded-lg px-3 py-2.5 text-left text-rose-600 hover:bg-rose-50" @click="logout">Log out</button>
                    </template>
                    <template v-else>
                        <Link :href="routes.login()" class="block rounded-lg px-3 py-2.5 hover:bg-gray-100">Login</Link>
                        <Link :href="routes.register()" class="block rounded-lg bg-green-600 px-3 py-2.5 text-center font-semibold text-white">Sign up</Link>
                    </template>
                </nav>
            </div>
        </header>

        <main class="flex-1">
            <slot />
        </main>

        <footer class="mt-6 sm:mt-8 border-t border-gray-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-6 sm:gap-8 min-[560px]:grid-cols-2 lg:grid-cols-4">
                    <div class="min-w-0">
                        <SiteLogo size="md" />
                        <p v-if="site.tagline" class="mt-3 text-sm leading-relaxed break-words text-gray-500">{{ site.tagline }}</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <a v-for="[name, url] in social" :key="name" :href="url" target="_blank" rel="noopener" class="flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 text-gray-600 hover:border-green-300 hover:bg-green-50 hover:text-green-700" :aria-label="name">
                                <BrandIcon :name="name" class="h-[18px] w-[18px]" />
                            </a>
                        </div>
                    </div>

                    <div class="min-w-0">
                        <h3 class="text-sm font-bold text-gray-900">Quick links</h3>
                        <ul class="mt-3 space-y-2 text-sm text-gray-500">
                            <li v-for="link in quickLinks" :key="link.label"><Link :href="link.href" class="hover:text-green-700">{{ link.label }}</Link></li>
                        </ul>
                    </div>

                    <div class="min-w-0">
                        <h3 class="text-sm font-bold text-gray-900">Shop by category</h3>
                        <ul class="mt-3 space-y-2 text-sm text-gray-500">
                            <li v-for="category in categories.slice(0, 7)" :key="category.id"><Link :href="routes.productsIndex({ category: category.slug })" class="hover:text-green-700">{{ category.name }}</Link></li>
                            <li v-if="!categories.length"><Link :href="routes.productsIndex()" class="hover:text-green-700">All products</Link></li>
                        </ul>
                    </div>

                    <div class="min-w-0">
                        <h3 class="text-sm font-bold text-gray-900">Contact us</h3>
                        <ul class="mt-3 space-y-3 text-sm text-gray-500">
                            <li v-if="contact.address" class="flex gap-2.5"><MapPin class="mt-0.5 h-4 w-4 shrink-0 text-green-600" /> <span class="min-w-0 break-words">{{ contact.address }}</span></li>
                            <li v-if="contact.phone" class="flex gap-2.5"><Phone class="mt-0.5 h-4 w-4 shrink-0 text-green-600" /> <a :href="telHref" class="inline-block py-1.5 hover:text-green-700">{{ contact.phone }}</a></li>
                            <li v-if="contact.email" class="flex gap-2.5"><Mail class="mt-0.5 h-4 w-4 shrink-0 text-green-600" /> <a :href="`mailto:${contact.email}`" class="inline-block min-w-0 py-1.5 break-all hover:text-green-700">{{ contact.email }}</a></li>
                            <li v-if="contact.hours" class="flex gap-2.5"><Clock class="mt-0.5 h-4 w-4 shrink-0 text-green-600" /> <span>{{ contact.hours }}</span></li>
                            <li v-if="whatsappUrl"><a :href="whatsappUrl" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-full bg-[#25D366] px-4 py-2 text-sm font-semibold text-white hover:opacity-90"><BrandIcon name="whatsapp" class="h-4 w-4" /> Chat on WhatsApp</a></li>
                        </ul>
                    </div>
                </div>

                <div class="mt-6 flex flex-col flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-6 text-center text-xs text-gray-400 sm:flex-row sm:text-left">
                    <p class="break-words">{{ site.footerText || `© ${year} ${siteName}. All rights reserved.` }}</p>
                    <nav class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1" aria-label="Help and policies">
                        <Link v-for="link in helpLinks" :key="link.label" :href="link.href" class="inline-block py-2.5 hover:text-green-700">{{ link.label }}</Link>
                    </nav>
                    <p>We accept: Cash on delivery &middot; bKash &middot; Nagad &middot; Cards (SSLCommerz demo)</p>
                </div>
            </div>
        </footer>
    </div>
</template>

<style>
@keyframes pop {
    0% { transform: scale(0.6); }
    60% { transform: scale(1.25); }
    100% { transform: scale(1); }
}
</style>
