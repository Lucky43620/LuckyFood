<script setup>
import { nextTick, ref } from 'vue'
import { useForm }       from '@inertiajs/vue3'
import AuthLayout        from '@/Layouts/AuthLayout.vue'
import AppInput          from '@/Components/ui/AppInput.vue'
import AppButton         from '@/Components/ui/AppButton.vue'
import { ShieldCheck }   from 'lucide-vue-next'

const recovery          = ref(false)
const form              = useForm({ code: '', recovery_code: '' })
const recoveryCodeInput = ref(null)
const codeInput         = ref(null)

const toggleRecovery = async () => {
  recovery.value ^= true
  await nextTick()
  if (recovery.value) {
    recoveryCodeInput.value?.focus()
    form.code = ''
  } else {
    codeInput.value?.focus()
    form.recovery_code = ''
  }
}

const submit = () => form.post(route('two-factor.login'))
</script>

<template>
  <AuthLayout title="Double authentification">
    <div class="text-center mb-8">
      <div class="w-16 h-16 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-4">
        <ShieldCheck :size="28" class="text-green-500" />
      </div>
      <h2 class="font-display text-[24px] leading-tight text-neutral-900 mb-2">Double authentification</h2>
      <p class="text-sm text-neutral-500 leading-relaxed">
        <template v-if="!recovery">
          Entrez le code généré par votre application d'authentification.
        </template>
        <template v-else>
          Entrez l'un de vos codes de récupération d'urgence.
        </template>
      </p>
    </div>

    <form @submit.prevent="submit" class="flex flex-col gap-4">
      <AppInput
        v-if="!recovery"
        ref="codeInput"
        v-model="form.code"
        label="Code d'authentification"
        placeholder="000000"
        inputmode="numeric"
        :error="form.errors.code"
        autocomplete="one-time-code"
        autofocus
      />
      <AppInput
        v-else
        ref="recoveryCodeInput"
        v-model="form.recovery_code"
        label="Code de récupération"
        :error="form.errors.recovery_code"
        autocomplete="one-time-code"
      />

      <div class="flex items-center justify-between">
        <button
          type="button"
          class="text-sm text-green-600 hover:underline"
          @click.prevent="toggleRecovery"
        >
          {{ recovery ? 'Utiliser le code d\'authentification' : 'Utiliser un code de récupération' }}
        </button>
      </div>

      <AppButton type="submit" :loading="form.processing" size="lg" class="w-full mt-2">
        Se connecter
      </AppButton>
    </form>
  </AuthLayout>
</template>
