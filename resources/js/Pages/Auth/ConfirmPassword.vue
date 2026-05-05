<script setup>
import { ref }     from 'vue'
import { useForm } from '@inertiajs/vue3'
import AuthLayout  from '@/Layouts/AuthLayout.vue'
import AppInput    from '@/Components/ui/AppInput.vue'
import AppButton   from '@/Components/ui/AppButton.vue'
import { ShieldCheck, Lock } from 'lucide-vue-next'

const form          = useForm({ password: '' })
const passwordInput = ref(null)

const submit = () => {
  form.post(route('password.confirm'), {
    onFinish: () => { form.reset(); passwordInput.value?.focus() },
  })
}
</script>

<template>
  <AuthLayout title="Zone sécurisée">
    <div class="text-center mb-8">
      <div class="w-16 h-16 bg-amber-50 rounded-full flex items-center justify-center mx-auto mb-4">
        <ShieldCheck :size="28" class="text-amber-500" />
      </div>
      <h2 class="font-display text-[26px] leading-tight text-neutral-900 mb-2">Zone sécurisée</h2>
      <p class="text-sm text-neutral-500 leading-relaxed">
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

      <AppButton type="submit" :loading="form.processing" size="lg" class="w-full mt-2">
        Confirmer
      </AppButton>
    </form>
  </AuthLayout>
</template>
