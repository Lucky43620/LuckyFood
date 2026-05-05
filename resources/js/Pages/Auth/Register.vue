<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import AuthLayout  from '@/Layouts/AuthLayout.vue'
import AppInput    from '@/Components/ui/AppInput.vue'
import AppButton   from '@/Components/ui/AppButton.vue'
import { User, Mail, Lock } from 'lucide-vue-next'

const form = useForm({
  name:                  '',
  email:                 '',
  password:              '',
  password_confirmation: '',
  terms:                 false,
})

const submit = () => {
  form.post(route('register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>

<template>
  <AuthLayout title="Créer un compte">
    <div class="mb-8">
      <h2 class="font-display text-[28px] leading-tight text-neutral-900">Créer un compte</h2>
      <p class="text-sm text-neutral-500 mt-1">
        Déjà inscrit ?
        <Link :href="route('login')" class="text-green-600 font-semibold hover:underline">Se connecter</Link>
      </p>
    </div>

    <form @submit.prevent="submit" class="flex flex-col gap-4">
      <AppInput
        v-model="form.name"
        label="Nom"
        placeholder="Jean Dupont"
        :icon="User"
        :error="form.errors.name"
        autocomplete="name"
        autofocus
      />

      <AppInput
        v-model="form.email"
        label="Email"
        type="email"
        placeholder="vous@exemple.com"
        :icon="Mail"
        :error="form.errors.email"
        autocomplete="username"
      />

      <AppInput
        v-model="form.password"
        label="Mot de passe"
        type="password"
        placeholder="••••••••"
        :icon="Lock"
        :error="form.errors.password"
        autocomplete="new-password"
      />

      <AppInput
        v-model="form.password_confirmation"
        label="Confirmer le mot de passe"
        type="password"
        placeholder="••••••••"
        :icon="Lock"
        :error="form.errors.password_confirmation"
        autocomplete="new-password"
      />

      <div v-if="$page.props.jetstream.hasTermsAndPrivacyPolicyFeature">
        <label class="flex items-start gap-2 cursor-pointer">
          <input
            v-model="form.terms"
            type="checkbox"
            class="w-4 h-4 mt-0.5 rounded border-neutral-300 text-green-500 focus:ring-green-500"
            required
          />
          <span class="text-sm text-neutral-600">
            J'accepte les
            <a :href="route('terms.show')" target="_blank" class="text-green-600 hover:underline">Conditions d'utilisation</a>
            et la
            <a :href="route('policy.show')" target="_blank" class="text-green-600 hover:underline">Politique de confidentialité</a>
          </span>
        </label>
        <p v-if="form.errors.terms" class="text-xs text-red-500 mt-1">{{ form.errors.terms }}</p>
      </div>

      <AppButton type="submit" :loading="form.processing" size="lg" class="w-full mt-2">
        Créer mon compte
      </AppButton>
    </form>
  </AuthLayout>
</template>
