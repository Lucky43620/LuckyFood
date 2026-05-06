<script setup>
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import AppButton from '@/Components/ui/AppButton.vue'
import { MailCheck, LogOut } from 'lucide-vue-next'

const props = defineProps({ status: String })
const form = useForm({})
const submit = () => form.post(route('verification.send'))
const sent = computed(() => props.status === 'verification-link-sent')
</script>

<template>
    <AuthLayout title="Vérification de l'email">
        <div class="mb-8 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-50">
                <MailCheck :size="28" class="text-green-500" />
            </div>
            <h2 class="mb-2 font-display text-[26px] leading-tight text-neutral-900">Vérifiez votre email</h2>
            <p class="text-sm leading-relaxed text-neutral-500">
                Avant de continuer, cliquez sur le lien de vérification envoyé à votre adresse email. Si vous ne l'avez
                pas reçu, nous pouvons vous en envoyer un nouveau.
            </p>
        </div>

        <div
            v-if="sent"
            class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-2.5 text-center text-sm font-medium text-green-600"
        >
            Un nouveau lien de vérification a été envoyé.
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-3">
            <AppButton type="submit" :loading="form.processing" size="lg" class="w-full">
                Renvoyer l'email de vérification
            </AppButton>

            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="flex h-10 w-full items-center justify-center gap-1.5 rounded-pill px-4 text-sm font-semibold text-neutral-500 transition-colors hover:text-neutral-700"
            >
                <LogOut :size="15" /> Se déconnecter
            </Link>
        </form>
    </AuthLayout>
</template>
