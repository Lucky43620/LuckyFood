<script setup>
import { ref }     from 'vue'
import { useForm } from '@inertiajs/vue3'
import DialogModal from '@/Components/DialogModal.vue'
import AppInput    from '@/Components/ui/AppInput.vue'
import AppButton   from '@/Components/ui/AppButton.vue'
import { Trash2 }  from 'lucide-vue-next'

const confirmingDeletion = ref(false)
const form               = useForm({ password: '' })

const confirm = () => { confirmingDeletion.value = true }

const submit = () => {
  form.delete(route('current-user.destroy'), {
    preserveScroll: true,
    onSuccess:      () => closeModal(),
    onFinish:       () => form.reset(),
  })
}

const closeModal = () => { confirmingDeletion.value = false; form.reset() }
</script>

<template>
  <div class="flex flex-col gap-4">
    <p class="text-sm text-neutral-500 leading-relaxed">
      Une fois votre compte supprimé, toutes ses ressources et données seront définitivement effacées.
      Avant de supprimer votre compte, téléchargez toute donnée que vous souhaitez conserver.
    </p>

    <div>
      <AppButton variant="danger" @click="confirm">
        <Trash2 :size="14" /> Supprimer mon compte
      </AppButton>
    </div>

    <!-- Confirmation modal -->
    <DialogModal :show="confirmingDeletion" @close="closeModal">
      <template #title>Supprimer le compte</template>
      <template #content>
        <p class="text-sm text-neutral-600 mb-4">
          Êtes-vous sûr de vouloir supprimer votre compte ? Cette action est irréversible.
          Entrez votre mot de passe pour confirmer.
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
          <AppButton variant="danger" :loading="form.processing" @click="submit">
            Supprimer définitivement
          </AppButton>
        </div>
      </template>
    </DialogModal>
  </div>
</template>
