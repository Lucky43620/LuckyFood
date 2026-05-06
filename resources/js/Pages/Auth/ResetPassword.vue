<script setup>
import { useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import AppInput from '@/Components/ui/AppInput.vue'
import AppButton from '@/Components/ui/AppButton.vue'
import { Mail, Lock } from 'lucide-vue-next'

const props = defineProps({ email: String, token: String })

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
})

const submit = () => {
    form.post(route('password.update'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
}
</script>

<template>
    <AuthLayout title="Nouveau mot de passe">
        <div class="mb-8">
            <h2 class="font-display text-[28px] leading-tight text-neutral-900">Nouveau mot de passe</h2>
            <p class="mt-1 text-sm text-neutral-500">Choisissez un mot de passe sécurisé.</p>
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-4">
            <AppInput
                v-model="form.email"
                label="Email"
                type="email"
                :icon="Mail"
                :error="form.errors.email"
                autocomplete="username"
                autofocus
            />

            <AppInput
                v-model="form.password"
                label="Nouveau mot de passe"
                type="password"
                placeholder="••••••••"
                :icon="Lock"
                :error="form.errors.password"
                autocomplete="new-password"
            />

            <AppInput
                v-model="form.password_confirmation"
                label="Confirmer le mot de passe"
                type="password"
                placeholder="••••••••"
                :icon="Lock"
                :error="form.errors.password_confirmation"
                autocomplete="new-password"
            />

            <AppButton type="submit" :loading="form.processing" size="lg" class="mt-2 w-full">
                Réinitialiser le mot de passe
            </AppButton>
        </form>
    </AuthLayout>
</template>
