<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import ProductCard from '@/Components/ProductCard.vue';
import TrustBadges from '@/Components/TrustBadges.vue';
import RecentlyViewed from '@/Components/RecentlyViewed.vue';
import { routes } from '@/routes';

const props = defineProps({
    sliders: { type: Array, default: () => [] },
    brands: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    latestProducts: { type: Array, default: () => [] },
    popularProducts: { type: Array, default: () => [] },
    featuredProducts: { type: Array, default: () => [] },
});

const activeSlide = ref(0);
let timer = null;

function goTo(index) {
    activeSlide.value = (index + props.sliders.length) % props.sliders.length;
}

function next() {
    goTo(activeSlide.value + 1);
}

function prev() {
    goTo(activeSlide.value - 1);
}

function resetTimer() {
    window.clearInterval(timer);
    if (props.sliders.length > 1) {
        timer = window.setInterval(next, 5000);
    }
}

onMounted(resetTimer);
onBeforeUnmount(() => window.clearInterval(timer));

function onNavClick(fn) {
    fn();
    resetTimer();
}

const hasSliders = computed(() => props.sliders.length > 0);

// Swipe left/right on touch screens to change slides.
let touchX = null;
let touchY = null;

function onTouchStart(event) {
    const touch = event.touches[0];
    touchX = touch.clientX;
    touchY = touch.clientY;
}

function onTouchEnd(event) {
    if (touchX === null) return;
    const touch = event.changedTouches[0];
    const dx = touch.clientX - touchX;
    const dy = touch.clientY - touchY;
    touchX = null;
    if (props.sliders.length > 1 && Math.abs(dx) > 40 && Math.abs(dx) > Math.abs(dy) * 1.5) {
        onNavClick(dx < 0 ? next : prev);
    }
}
</script>

<template>
    <Head title="Home" />

    <MainLayout>
        <!-- Hero slider -->
        <section v-if="hasSliders" class="mx-auto max-w-7xl px-4 pt-4 sm:px-6 sm:pt-5 lg:px-8">
            <!-- Same width as the rest of the page. Banners are 1600x640 (5:2); keeping that ratio means the text baked into them is never cropped. -->
            <div class="relative aspect-[5/2] w-full overflow-hidden lg:aspect-[7/2] rounded-xl bg-gray-100 sm:rounded-2xl" @touchstart.passive="onTouchStart" @touchend.passive="onTouchEnd">
                <template v-for="(slide, index) in sliders" :key="slide.id">
                    <!-- Banner links may point to another website: a plain <a> for those. -->
                    <a
                        v-if="/^https?:\/\//i.test(slide.link || '')"
                        :href="slide.link"
                        class="absolute inset-0 transition-opacity duration-500"
                        :class="index === activeSlide ? 'opacity-100' : 'pointer-events-none opacity-0'"
                    >
                        <img :src="slide.image" :alt="slide.title" class="h-full w-full object-cover">
                    </a>
                    <Link
                        v-else
                        :href="slide.link || routes.productsIndex()"
                        class="absolute inset-0 transition-opacity duration-500"
                        :class="index === activeSlide ? 'opacity-100' : 'pointer-events-none opacity-0'"
                    >
                        <img :src="slide.image" :alt="slide.title" class="h-full w-full object-cover">
                    </Link>
                </template>

                <template v-if="sliders.length > 1">
                    <button
                        type="button"
                        class="absolute top-1/2 left-2 hidden h-8 w-8 sm:flex -translate-y-1/2 items-center justify-center rounded-full bg-white/80 text-gray-800 shadow hover:bg-white sm:left-3 sm:h-9 sm:w-9"
                        aria-label="Previous slide"
                        @click="onNavClick(prev)"
                    >
                        <ChevronLeft class="h-5 w-5" />
                    </button>
                    <button
                        type="button"
                        class="absolute top-1/2 right-2 hidden h-8 w-8 sm:flex -translate-y-1/2 items-center justify-center rounded-full bg-white/80 text-gray-800 shadow hover:bg-white sm:right-3 sm:h-9 sm:w-9"
                        aria-label="Next slide"
                        @click="onNavClick(next)"
                    >
                        <ChevronRight class="h-5 w-5" />
                    </button>
                    <div class="absolute bottom-1 left-1/2 flex -translate-x-1/2 sm:bottom-3">
                        <button
                            v-for="(slide, index) in sliders"
                            :key="slide.id"
                            type="button"
                            class="flex h-8 items-center px-2"
                            :aria-label="`Go to slide ${index + 1}`"
                            @click="onNavClick(() => goTo(index))"
                        >
                            <span
                                class="block h-2 rounded-full shadow-sm transition-all"
                                :class="index === activeSlide ? 'w-6 bg-white' : 'w-2 bg-white/60'"
                            />
                        </button>
                    </div>
                </template>
            </div>
        </section>

        <div class="mx-auto max-w-7xl px-4 pt-4 pb-3 sm:px-6 sm:pt-5 sm:pb-4 lg:px-8">
            <!-- Brands -->
            <section v-if="brands.length" class="mb-5 sm:mb-7">
                <h2 class="mb-2.5 text-base font-bold text-gray-900 sm:mb-3 sm:text-lg">Shop by Brand</h2>
                <div class="grid grid-cols-3 gap-2 sm:grid-cols-5 sm:gap-3">
                    <Link
                        v-for="brand in brands"
                        :key="brand.id"
                        :href="routes.productsIndex({ brand: brand.slug })"
                        class="flex min-w-0 flex-col items-center gap-1.5 rounded-xl border border-gray-200 bg-white p-2.5 transition hover:border-green-300 hover:shadow-md sm:p-3"
                    >
                        <img loading="lazy" decoding="async" v-if="brand.logo" :src="brand.logo" :alt="brand.name" class="h-9 w-9 rounded-lg object-cover">
                        <span v-else class="flex h-9 w-9 items-center justify-center rounded-lg bg-green-100 text-sm font-bold text-green-700">{{ brand.name.charAt(0).toUpperCase() }}</span>
                        <span class="max-w-full text-center text-xs font-medium break-words text-gray-600">{{ brand.name }}</span>
                    </Link>
                </div>
            </section>

            <!-- Categories -->
            <section v-if="categories.length" class="mb-5 sm:mb-7">
                <h2 class="mb-2.5 text-base font-bold text-gray-900 sm:mb-3 sm:text-lg">Shop by Category</h2>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 sm:gap-3 lg:grid-cols-6">
                    <Link
                        v-for="category in categories"
                        :key="category.id"
                        :href="routes.productsIndex({ category: category.slug })"
                        class="group relative aspect-[3/2] overflow-hidden rounded-xl bg-gray-100"
                    >
                        <img loading="lazy" decoding="async" v-if="category.image" :src="category.image" :alt="category.name" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                        <span v-else class="absolute inset-0 bg-gradient-to-br from-green-100 to-green-200" />
                        <span class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/0" />
                        <span class="absolute right-2 bottom-2 left-3 text-sm font-semibold text-white drop-shadow">{{ category.name }}</span>
                    </Link>
                </div>
            </section>

            <!-- Featured -->
            <section v-if="featuredProducts.length" class="mb-5 sm:mb-7">
                <div class="mb-1.5 flex items-center justify-between sm:mb-2">
                    <h2 class="text-base font-bold text-gray-900 sm:text-lg">Featured Products</h2>
                    <Link :href="routes.productsIndex()" class="inline-block py-1.5 text-sm font-medium text-green-700 hover:underline">View all</Link>
                </div>
                <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 sm:gap-3 md:grid-cols-4">
                    <ProductCard v-for="product in featuredProducts" :key="product.id" :product="product" />
                </div>
            </section>

            <!-- Best sellers -->
            <section v-if="popularProducts.length" class="mb-5 sm:mb-7">
                <div class="mb-1.5 flex items-center justify-between sm:mb-2">
                    <h2 class="text-base font-bold text-gray-900 sm:text-lg">Best Sellers</h2>
                    <Link :href="routes.productsIndex({ sort: 'popular' })" class="inline-block py-1.5 text-sm font-medium text-green-700 hover:underline">View all</Link>
                </div>
                <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 sm:gap-3 md:grid-cols-4">
                    <ProductCard v-for="product in popularProducts" :key="product.id" :product="product" />
                </div>
            </section>

            <!-- Latest -->
            <section v-if="latestProducts.length" class="mb-5 sm:mb-7">
                <div class="mb-1.5 flex items-center justify-between sm:mb-2">
                    <h2 class="text-base font-bold text-gray-900 sm:text-lg">Latest Products</h2>
                    <Link :href="routes.productsIndex()" class="inline-block py-1.5 text-sm font-medium text-green-700 hover:underline">View all</Link>
                </div>
                <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 sm:gap-3 md:grid-cols-4">
                    <ProductCard v-for="product in latestProducts" :key="product.id" :product="product" />
                </div>
            </section>

            <!-- Hidden (no gap) while there is nothing to show -->
            <div class="mb-5 sm:mb-7 empty:hidden">
                <RecentlyViewed />
            </div>

            <TrustBadges />
        </div>
    </MainLayout>
</template>
