<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import AppInput from '@/Components/ui/AppInput.vue'
import AppButton from '@/Components/ui/AppButton.vue'
import { Mail, Lock } from 'lucide-vue-next'

defineProps({
    canResetPassword: Boolean,
    status: String,
})

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

const submit = () => {
    form.transform((data) => ({ ...data, remember: form.remember ? 'on' : '' })).post(route('login'), {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <AuthLayout title="Connexion">
        <div class="mb-8">
            <h2 class="font-display text-[28px] leading-tight text-neutral-900">Connexion</h2>
            <p class="mt-1 text-sm text-neutral-500">
                Pas encore de compte ?
                <Link :href="route('register')" class="font-semibold text-green-600 hover:underline">S'inscrire</Link>
            </p>
        </div>

        <div
            v-if="status"
            class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-2.5 text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-4">
            <AppInput
                v-model="form.email"
                label="Email"
                type="email"
                placeholder="vous@exemple.com"
                :icon="Mail"
                :error="form.errors.email"
                autocomplete="username"
                autofocus
            />

            <AppInput
                v-model="form.password"
                label="Mot de passe"
                type="password"
                placeholder="••••••••"
                :icon="Lock"
                :error="form.errors.password"
                autocomplete="current-password"
            />

            <div class="flex items-center justify-between">
                <label class="flex cursor-pointer items-center gap-2">
                    <input
                        v-model="form.remember"
                        type="checkbox"
                        class="h-4 w-4 rounded border-neutral-300 text-green-500 focus:ring-green-500"
                    />
                    <span class="text-sm text-neutral-600">Se souvenir de moi</span>
                </label>
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm text-green-600 hover:underline"
                >
                    Mot de passe oublié ?
                </Link>
            </div>

            <AppButton type="submit" :loading="form.processing" size="lg" class="mt-2 w-full"> Se connecter </AppButton>
        </form>
    </AuthLayout>
</template>
