<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Heart, ShoppingCart, SlidersHorizontal } from '@lucide/vue';
import { formatPrice } from '@/currency';
import { routes } from '@/routes';
import { addToCart as addToCartRequest } from '@/Composables/cart';

const props = defineProps({
    product: { type: Object, required: true },
});

const page = usePage();
const isAuthed = computed(() => Boolean(page.props.auth?.user));

const hasDiscount = computed(
    () => props.product.discount_price && Number(props.product.discount_price) < Number(props.product.price),
);

const percentOff = computed(() => (hasDiscount.value
    ? Math.round((1 - Number(props.product.discount_price) / Number(props.product.price)) * 100)
    : 0));

const outOfStock = computed(() => Number(props.product.stock) <= 0);
const hasOptions = computed(() => Number(props.product.variants_count ?? 0) > 0);
const lowStock = computed(() => !outOfStock.value
    && Number(props.product.stock) <= Number(page.props.shop?.lowStockThreshold ?? 5));

function addToCart() {
    addToCartRequest({ product_id: props.product.id, quantity: 1 });
}

function addToWishlist() {
    if (!isAuthed.value) {
        router.visit(routes.login());
        return;
    }

    router.post(
        routes.wishlistStore(),
        { product_id: props.product.id },
        { preserveScroll: true, preserveState: true },
    );
}
</script>

<template>
    <div class="group relative flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white transition hover:shadow-lg">
        <Link :href="routes.productShow(product.slug)" class="relative block aspect-square overflow-hidden bg-gray-100">
            <img decoding="async"
                :src="product.image"
                :alt="product.name"
                class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                loading="lazy"
            >
            <span
                v-if="hasDiscount"
                class="absolute top-2 left-2 rounded-full bg-rose-600 px-2 py-0.5 text-xs font-semibold text-white shadow"
            >
                -{{ percentOff }}%
            </span>
            <span
                v-if="lowStock"
                class="absolute bottom-2 left-2 rounded-full bg-amber-500 px-2 py-0.5 text-[11px] font-semibold text-white shadow"
            >
                Only {{ product.stock }} left
            </span>
            <span
                v-if="outOfStock"
                class="absolute inset-0 flex items-center justify-center bg-white/70 text-sm font-semibold text-gray-700 backdrop-blur-[1px]"
            >
                Out of stock
            </span>
        </Link>

        <button
            type="button"
            class="absolute top-2 right-2 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-gray-600 shadow transition hover:bg-white hover:text-rose-600 sm:h-8 sm:w-8"
            title="Add to wishlist"
            aria-label="Add to wishlist"
            @click="addToWishlist"
        >
            <Heart class="h-4.5 w-4.5" />
        </button>

        <div class="flex flex-1 flex-col gap-0.5 p-2.5 sm:p-3">
            <p v-if="product.brand" class="text-[11px] font-medium tracking-wide text-gray-400 uppercase">
                {{ product.brand.name }}
            </p>
            <Link :href="routes.productShow(product.slug)" class="line-clamp-2 text-sm font-semibold break-words text-gray-900 hover:text-green-700">
                {{ product.name }}
            </Link>

            <div class="mt-auto flex items-center justify-between gap-2 pt-2">
                <div class="flex min-w-0 flex-wrap items-baseline gap-x-2">
                    <span class="text-sm font-bold text-gray-900 sm:text-base">
                        {{ formatPrice(hasDiscount ? product.discount_price : product.price) }}
                    </span>
                    <span v-if="hasDiscount" class="text-xs text-gray-400 line-through">
                        {{ formatPrice(product.price) }}
                    </span>
                </div>

                <Link
                    v-if="hasOptions && !outOfStock"
                    :href="routes.productShow(product.slug)"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full sm:h-8 sm:w-8 bg-gray-900 text-white transition hover:bg-gray-700"
                    title="Choose size / colour"
                    aria-label="Choose options"
                >
                    <SlidersHorizontal class="h-4 w-4" />
                </Link>
                <button
                    v-else
                    type="button"
                    :disabled="outOfStock"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full sm:h-8 sm:w-8 bg-green-600 text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:bg-gray-300"
                    title="Add to cart"
                    aria-label="Add to cart"
                    @click="addToCart"
                >
                    <ShoppingCart class="h-4 w-4" />
                </button>
            </div>
        </div>
    </div>
</template>
