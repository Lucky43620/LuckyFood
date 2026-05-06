<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import AppInput from '@/Components/ui/AppInput.vue'
import AppButton from '@/Components/ui/AppButton.vue'
import { Mail } from 'lucide-vue-next'

defineProps({ status: String })

const form = useForm({ email: '' })
const submit = () => form.post(route('password.email'))
</script>

<template>
    <AuthLayout title="Mot de passe oublié">
        <div class="mb-8">
            <h2 class="font-display text-[28px] leading-tight text-neutral-900">Mot de passe oublié</h2>
            <p class="mt-2 text-sm leading-relaxed text-neutral-500">
                Renseignez votre adresse email et nous vous enverrons un lien pour réinitialiser votre mot de passe.
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

            <AppButton type="submit" :loading="form.processing" size="lg" class="mt-2 w-full">
                Envoyer le lien de réinitialisation
            </AppButton>

            <p class="text-center text-sm text-neutral-500">
                <Link :href="route('login')" class="text-green-600 hover:underline">Retour à la connexion</Link>
            </p>
        </form>
    </AuthLayout>
</template>
