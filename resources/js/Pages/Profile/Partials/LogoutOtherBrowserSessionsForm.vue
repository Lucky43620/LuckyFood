<script setup>
import { ref }       from 'vue'
import { useForm }   from '@inertiajs/vue3'
import DialogModal   from '@/Components/DialogModal.vue'
import AppInput      from '@/Components/ui/AppInput.vue'
import AppButton     from '@/Components/ui/AppButton.vue'
import { Monitor, Smartphone, CheckCircle2 } from 'lucide-vue-next'

defineProps({ sessions: Array })

const confirmingLogout = ref(false)
const form             = useForm({ password: '' })

const confirm = () => { confirmingLogout.value = true }

const submit = () => {
  form.delete(route('other-browser-sessions.destroy'), {
    preserveScroll: true,
    onSuccess:      () => closeModal(),
    onFinish:       () => form.reset(),
  })
}

const closeModal = () => { confirmingLogout.value = false; form.reset() }
</script>

<template>
  <div class="flex flex-col gap-4">
    <p class="text-sm text-neutral-500 leading-relaxed">
      Si nécessaire, vous pouvez vous déconnecter de toutes vos autres sessions sur d'autres navigateurs et appareils.
    </p>

    <!-- Sessions list -->
    <div v-if="sessions.length" class="flex flex-col gap-3">
      <div
        v-for="(s, i) in sessions"
        :key="i"
        class="flex items-center gap-3 p-3 bg-neutral-50 rounded-lg"
      >
        <div class="w-8 h-8 bg-white rounded-md flex items-center justify-center shadow-sm shrink-0">
          <Monitor v-if="s.agent.is_desktop" :size="16" class="text-neutral-500" />
          <Smartphone v-else :size="16" class="text-neutral-500" />
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-semibold text-neutral-700">
            {{ s.agent.platform ?? 'Inconnu' }} — {{ s.agent.browser ?? 'Inconnu' }}
          </p>
          <p class="text-xs text-neutral-400">
            {{ s.ip_address }} ·
            <span v-if="s.is_current_device" class="text-green-600 font-semibold">Cet appareil</span>
            <span v-else>Dernière activité {{ s.last_active }}</span>
          </p>
        </div>
      </div>
    </div>

    <div class="flex items-center gap-3">
      <AppButton variant="secondary" @click="confirm">
        Déconnecter les autres sessions
      </AppButton>
      <Transition name="fade">
        <span v-if="form.recentlySuccessful" class="flex items-center gap-1 text-xs font-semibold text-green-600">
          <CheckCircle2 :size="14" /> Terminé
        </span>
      </Transition>
    </div>

    <!-- Confirmation modal -->
    <DialogModal :show="confirmingLogout" @close="closeModal">
      <template #title>Déconnecter les autres sessions</template>
      <template #content>
        <p class="text-sm text-neutral-600 mb-4">
          Confirmez votre mot de passe pour vous déconnecter de tous vos autres appareils.
        </p>
        <AppInput
          v-model="form.password"
          label="Mot de passe"
          type="password"
          placeholder="••••••••"
          :error="form.errors.password"
          autocomplete="current-password"
          @keyup.enter="submit"
        />
      </template>
      <template #footer>
        <div class="flex justify-end gap-2">
          <AppButton variant="ghost" @click="closeModal">Annuler</AppButton>
          <AppButton :loading="form.processing" @click="submit">
            Se déconnecter
          </AppButton>
        </div>
      </template>
    </DialogModal>
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }
</style>
