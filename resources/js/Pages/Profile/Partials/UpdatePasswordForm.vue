<script setup>
import { useForm }      from '@inertiajs/vue3'
import AppInput         from '@/Components/ui/AppInput.vue'
import AppButton        from '@/Components/ui/AppButton.vue'
import { CheckCircle2 } from 'lucide-vue-next'

const form = useForm({
  current_password:      '',
  password:              '',
  password_confirmation: '',
})

const submit = () => {
  form.put(route('user-password.update'), {
    errorBag:       'updatePassword',
    preserveScroll: true,
    onSuccess:      () => form.reset(),
    onError:        () => {
      if (form.errors.password)          form.reset('password', 'password_confirmation')
      if (form.errors.current_password)  form.reset('current_password')
    },
  })
}
</script>

<template>
  <form @submit.prevent="submit" class="flex flex-col gap-4">
    <AppInput
      v-model="form.current_password"
      label="Mot de passe actuel"
      type="password"
      placeholder="••••••••"
      :error="form.errors.current_password"
      autocomplete="current-password"
    />
    <AppInput
      v-model="form.password"
      label="Nouveau mot de passe"
      type="password"
      placeholder="••••••••"
      :error="form.errors.password"
      autocomplete="new-password"
    />
    <AppInput
      v-model="form.password_confirmation"
      label="Confirmer le nouveau mot de passe"
      type="password"
      placeholder="••••••••"
      :error="form.errors.password_confirmation"
      autocomplete="new-password"
    />

    <div class="flex items-center gap-3 pt-2">
      <AppButton type="submit" :loading="form.processing">Mettre à jour</AppButton>
      <Transition name="fade">
        <span v-if="form.recentlySuccessful" class="flex items-center gap-1 text-xs font-semibold text-green-600">
          <CheckCircle2 :size="14" /> Enregistré
        </span>
      </Transition>
    </div>
  </form>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }
</style>
