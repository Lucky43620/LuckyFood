<script setup>
import MainLayout                       from '@/Layouts/MainLayout.vue'
import UpdateProfileInformationForm     from '@/Pages/Profile/Partials/UpdateProfileInformationForm.vue'
import UpdatePasswordForm               from '@/Pages/Profile/Partials/UpdatePasswordForm.vue'
import TwoFactorAuthenticationForm      from '@/Pages/Profile/Partials/TwoFactorAuthenticationForm.vue'
import LogoutOtherBrowserSessionsForm   from '@/Pages/Profile/Partials/LogoutOtherBrowserSessionsForm.vue'
import DeleteUserForm                   from '@/Pages/Profile/Partials/DeleteUserForm.vue'

defineProps({
  confirmsTwoFactorAuthentication: Boolean,
  sessions:                        Array,
})
</script>

<template>
  <MainLayout title="Profil">
    <div class="px-6 md:px-7 py-6 max-w-2xl flex flex-col gap-6">

      <!-- Header -->
      <div>
        <p class="text-xs font-semibold tracking-widest uppercase text-neutral-400 mb-1">Compte</p>
        <h1 class="font-display text-[28px] leading-tight tracking-tight text-neutral-900">Profil</h1>
      </div>

      <!-- Update profile info -->
      <div v-if="$page.props.jetstream.canUpdateProfileInformation" class="bg-white rounded-xl shadow-sm p-5">
        <p class="text-[11px] font-semibold tracking-widest uppercase text-neutral-400 mb-5">Informations personnelles</p>
        <UpdateProfileInformationForm :user="$page.props.auth.user" />
      </div>

      <!-- Update password -->
      <div v-if="$page.props.jetstream.canUpdatePassword" class="bg-white rounded-xl shadow-sm p-5">
        <p class="text-[11px] font-semibold tracking-widest uppercase text-neutral-400 mb-5">Mot de passe</p>
        <UpdatePasswordForm />
      </div>

      <!-- 2FA -->
      <div v-if="$page.props.jetstream.canManageTwoFactorAuthentication" class="bg-white rounded-xl shadow-sm p-5">
        <p class="text-[11px] font-semibold tracking-widest uppercase text-neutral-400 mb-5">Double authentification</p>
        <TwoFactorAuthenticationForm :requires-confirmation="confirmsTwoFactorAuthentication" />
      </div>

      <!-- Browser sessions -->
      <div class="bg-white rounded-xl shadow-sm p-5">
        <p class="text-[11px] font-semibold tracking-widest uppercase text-neutral-400 mb-5">Sessions actives</p>
        <LogoutOtherBrowserSessionsForm :sessions="sessions" />
      </div>

      <!-- Delete account -->
      <div v-if="$page.props.jetstream.hasAccountDeletionFeatures" class="bg-white rounded-xl shadow-sm p-5 border border-red-100">
        <p class="text-[11px] font-semibold tracking-widest uppercase text-red-400 mb-5">Zone dangereuse</p>
        <DeleteUserForm />
      </div>

    </div>
  </MainLayout>
</template>
