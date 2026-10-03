<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthCard from '@/Components/AuthCard.vue';
import { routes } from '@/routes';

const form = useForm({ email: '' });
</script>

<template>
    <Head title="Forgot password" />

    <AuthCard title="Forgot your password?" subtitle="Enter your email and we'll send you a link to choose a new one.">
        <form class="space-y-4" @submit.prevent="form.post(routes.forgotPassword())">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                <input v-model="form.email" placeholder="rakibhossain@gmail.com" type="email" autofocus required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 focus:outline-none">
                <p v-if="form.errors.email" class="mt-1 text-xs text-rose-600">{{ form.errors.email }}</p>
            </div>
            <button type="submit" :disabled="form.processing" class="w-full rounded-full bg-green-600 py-2.5 font-semibold text-white hover:bg-green-700 disabled:opacity-60">
                {{ form.processing ? 'Sending...' : 'Send reset link' }}
            </button>
        </form>

        <template #footer>
            <Link :href="routes.login()" class="font-semibold text-green-700 hover:underline">Back to sign in</Link>
        </template>
    </AuthCard>
</template>
