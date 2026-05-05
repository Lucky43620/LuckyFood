<script setup>
import { computed }           from 'vue'
import { Link, useForm }      from '@inertiajs/vue3'
import AuthLayout             from '@/Layouts/AuthLayout.vue'
import AppButton              from '@/Components/ui/AppButton.vue'
import { MailCheck, LogOut }  from 'lucide-vue-next'

const props  = defineProps({ status: String })
const form   = useForm({})
const submit = () => form.post(route('verification.send'))
const sent   = computed(() => props.status === 'verification-link-sent')
</script>

<template>
  <AuthLayout title="Vérification de l'email">
    <div class="text-center mb-8">
      <div class="w-16 h-16 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-4">
        <MailCheck :size="28" class="text-green-500" />
      </div>
      <h2 class="font-display text-[26px] leading-tight text-neutral-900 mb-2">Vérifiez votre email</h2>
      <p class="text-sm text-neutral-500 leading-relaxed">
        Avant de continuer, cliquez sur le lien de vérification envoyé à votre adresse email.
        Si vous ne l'avez pas reçu, nous pouvons vous en envoyer un nouveau.
      </p>
    </div>

    <div v-if="sent" class="mb-4 text-sm font-medium text-green-600 bg-green-50 border border-green-200 rounded-lg px-4 py-2.5 text-center">
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
        class="flex items-center justify-center gap-1.5 w-full h-10 px-4 text-sm font-semibold text-neutral-500 hover:text-neutral-700 transition-colors rounded-pill"
      >
        <LogOut :size="15" /> Se déconnecter
      </Link>
    </form>
  </AuthLayout>
</template>
