<script setup>
import { ref }         from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import AppInput        from '@/Components/ui/AppInput.vue'
import AppButton       from '@/Components/ui/AppButton.vue'
import { Camera, Trash2, CheckCircle2 } from 'lucide-vue-next'

const props = defineProps({ user: Object })

const REGION_OPTIONS = [
  { value: 'FR', label: 'France' },
  { value: 'BE', label: 'Belgique' },
  { value: 'CH', label: 'Suisse' },
  { value: 'CA', label: 'Canada' },
  { value: 'US', label: 'États-Unis' },
  { value: 'GB', label: 'Royaume-Uni' },
  { value: 'DE', label: 'Allemagne' },
  { value: 'ES', label: 'Espagne' },
  { value: 'IT', label: 'Italie' },
]

const LANGUAGE_OPTIONS = [
  { value: 'fr', label: 'Français' },
  { value: 'en', label: 'Anglais' },
  { value: 'de', label: 'Allemand' },
  { value: 'es', label: 'Espagnol' },
  { value: 'it', label: 'Italien' },
  { value: 'nl', label: 'Néerlandais' },
]

const form = useForm({
  _method:            'PUT',
  name:               props.user.name,
  email:              props.user.email,
  photo:              null,
  fatsecret_region:   props.user.fatsecret_region   ?? 'FR',
  fatsecret_language: props.user.fatsecret_language ?? 'fr',
})

const verificationLinkSent = ref(false)
const photoPreview         = ref(null)
const photoInput           = ref(null)

const submit = () => {
  if (photoInput.value) form.photo = photoInput.value.files[0]
  form.post(route('user-profile-information.update'), {
    errorBag:       'updateProfileInformation',
    preserveScroll: true,
    onSuccess:      () => clearPhotoInput(),
  })
}

const selectPhoto = () => photoInput.value.click()

const updatePreview = () => {
  const file = photoInput.value.files[0]
  if (!file) return
  const reader = new FileReader()
  reader.onload = (e) => { photoPreview.value = e.target.result }
  reader.readAsDataURL(file)
}

const deletePhoto = () => {
  router.delete(route('current-user-photo.destroy'), {
    preserveScroll: true,
    onSuccess: () => { photoPreview.value = null; clearPhotoInput() },
  })
}

const clearPhotoInput = () => {
  if (photoInput.value?.value) photoInput.value.value = null
}

const sendVerification = () => { verificationLinkSent.value = true }
</script>

<template>
  <form @submit.prevent="submit" class="flex flex-col gap-4">

    <!-- Photo -->
    <div v-if="$page.props.jetstream.managesProfilePhotos" class="flex items-center gap-4">
      <input ref="photoInput" type="file" class="hidden" accept="image/*" @change="updatePreview" />

      <div class="relative shrink-0">
        <img
          v-if="!photoPreview"
          :src="user.profile_photo_url"
          :alt="user.name"
          class="w-16 h-16 rounded-full object-cover bg-neutral-100"
        />
        <div
          v-else
          class="w-16 h-16 rounded-full bg-cover bg-center bg-no-repeat"
          :style="`background-image: url('${photoPreview}')`"
        />
      </div>

      <div class="flex gap-2">
        <button
          type="button"
          @click="selectPhoto"
          class="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-600 hover:text-neutral-800 bg-neutral-100 hover:bg-neutral-200 px-3 h-8 rounded-md transition-colors"
        >
          <Camera :size="13" /> Changer la photo
        </button>
        <button
          v-if="user.profile_photo_path"
          type="button"
          @click="deletePhoto"
          class="inline-flex items-center gap-1.5 text-xs font-semibold text-red-500 hover:text-red-600 bg-red-50 hover:bg-red-100 px-3 h-8 rounded-md transition-colors"
        >
          <Trash2 :size="13" /> Supprimer
        </button>
      </div>
      <p v-if="form.errors.photo" class="text-xs text-red-500">{{ form.errors.photo }}</p>
    </div>

    <!-- Name -->
    <AppInput v-model="form.name" label="Nom" :error="form.errors.name" autocomplete="name" />

    <!-- Email -->
    <div class="flex flex-col gap-1.5">
      <AppInput v-model="form.email" label="Email" type="email" :error="form.errors.email" autocomplete="username" />
      <div v-if="$page.props.jetstream.hasEmailVerification && user.email_verified_at === null" class="mt-0.5">
        <p class="text-xs text-amber-600">
          Votre adresse email n'est pas vérifiée.
          <Link
            :href="route('verification.send')"
            method="post"
            as="button"
            class="font-semibold hover:underline"
            @click.prevent="sendVerification"
          >
            Renvoyer l'email de vérification
          </Link>
        </p>
        <p v-if="verificationLinkSent" class="text-xs text-green-600 mt-1">
          Un nouveau lien a été envoyé à votre adresse email.
        </p>
      </div>
    </div>

    <!-- FatSecret region & language -->
    <div class="grid grid-cols-2 gap-4">
      <div class="flex flex-col gap-1.5">
        <label class="text-sm font-semibold text-neutral-700">Région FatSecret</label>
        <select
          v-model="form.fatsecret_region"
          class="h-11 bg-white border border-neutral-200 rounded-md text-[14px] text-neutral-800 px-3 focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-400 transition-all"
        >
          <option v-for="r in REGION_OPTIONS" :key="r.value" :value="r.value">{{ r.label }}</option>
        </select>
        <p v-if="form.errors.fatsecret_region" class="text-xs text-red-500">{{ form.errors.fatsecret_region }}</p>
      </div>
      <div class="flex flex-col gap-1.5">
        <label class="text-sm font-semibold text-neutral-700">Langue FatSecret</label>
        <select
          v-model="form.fatsecret_language"
          class="h-11 bg-white border border-neutral-200 rounded-md text-[14px] text-neutral-800 px-3 focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-400 transition-all"
        >
          <option v-for="l in LANGUAGE_OPTIONS" :key="l.value" :value="l.value">{{ l.label }}</option>
        </select>
        <p v-if="form.errors.fatsecret_language" class="text-xs text-red-500">{{ form.errors.fatsecret_language }}</p>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center gap-3 pt-2">
      <AppButton type="submit" :loading="form.processing">Enregistrer</AppButton>
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
