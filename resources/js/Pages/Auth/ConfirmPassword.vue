<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import AppInput from '@/Components/ui/AppInput.vue'
import AppButton from '@/Components/ui/AppButton.vue'
import { ShieldCheck, Lock } from 'lucide-vue-next'

const form = useForm({ password: '' })
const passwordInput = ref(null)

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => {
            form.reset()
            passwordInput.value?.focus()
        },
    })
}
</script>

<template>
    <AuthLayout title="Zone sécurisée">
        <div class="mb-8 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-amber-50">
                <ShieldCheck :size="28" class="text-amber-500" />
            </div>
            <h2 class="mb-2 font-display text-[26px] leading-tight text-neutral-900">Zone sécurisée</h2>
            <p class="text-sm leading-relaxed text-neutral-500">
                Confirmez votre mot de passe pour accéder à cette section de l'application.
            </p>
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-4">
            <AppInput
                ref="passwordInput"
                v-model="form.password"
                label="Mot de passe"
                type="password"
                placeholder="••••••••"
                :icon="Lock"
                :error="form.errors.password"
                autocomplete="current-password"
                autofocus
            />

            <AppButton type="submit" :loading="form.processing" size="lg" class="mt-2 w-full"> Confirmer </AppButton>
        </form>
    </AuthLayout>
</template>
