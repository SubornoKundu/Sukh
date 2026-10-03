<script setup>
import PasswordInput from '@/Components/PasswordInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthCard from '@/Components/AuthCard.vue';
import { routes } from '@/routes';

const props = defineProps({
    token: { type: String, required: true },
    email: { type: String, default: '' },
});

const form = useForm({ token: props.token, email: props.email, password: '', password_confirmation: '' });

const input = 'w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20 focus:outline-none';
</script>

<template>
    <Head title="Reset password" />

    <AuthCard title="Choose a new password">
        <form class="space-y-4" @submit.prevent="form.post(routes.resetPassword())">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                <input v-model="form.email" placeholder="rakibhossain@gmail.com" type="email" required :class="input">
                <p v-if="form.errors.email" class="mt-1 text-xs text-rose-600">{{ form.errors.email }}</p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">New password</label>
                <PasswordInput v-model="form.password" placeholder="Minimum 8 characters" autocomplete="new-password" required :class="input" />
                <p v-if="form.errors.password" class="mt-1 text-xs text-rose-600">{{ form.errors.password }}</p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Confirm new password</label>
                <PasswordInput v-model="form.password_confirmation" placeholder="Type the password again" autocomplete="new-password" required :class="input" />
            </div>
            <button type="submit" :disabled="form.processing" class="w-full rounded-full bg-green-600 py-2.5 font-semibold text-white hover:bg-green-700 disabled:opacity-60">
                {{ form.processing ? 'Saving...' : 'Reset password' }}
            </button>
        </form>

        <template #footer>
            <Link :href="routes.login()" class="font-semibold text-green-700 hover:underline">Back to sign in</Link>
        </template>
    </AuthCard>
</template>
