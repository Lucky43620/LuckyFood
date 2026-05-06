<script setup>
import MainLayout from '@/Layouts/MainLayout.vue'
import UpdateProfileInformationForm from '@/Pages/Profile/Partials/UpdateProfileInformationForm.vue'
import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm.vue'
import TwoFactorAuthenticationForm from '@/Pages/Profile/Partials/TwoFactorAuthenticationForm.vue'
import LogoutOtherBrowserSessionsForm from '@/Pages/Profile/Partials/LogoutOtherBrowserSessionsForm.vue'
import DeleteUserForm from '@/Pages/Profile/Partials/DeleteUserForm.vue'

defineProps({
    confirmsTwoFactorAuthentication: Boolean,
    sessions: Array,
})
</script>

<template>
    <MainLayout title="Profil">
        <div class="flex max-w-2xl flex-col gap-6 px-6 py-6 md:px-7">
            <!-- Header -->
            <div>
                <p class="mb-1 text-xs font-semibold uppercase tracking-widest text-neutral-400">Compte</p>
                <h1 class="font-display text-[28px] leading-tight tracking-tight text-neutral-900">Profil</h1>
            </div>

            <!-- Update profile info -->
            <div v-if="$page.props.jetstream.canUpdateProfileInformation" class="rounded-xl bg-white p-5 shadow-sm">
                <p class="mb-5 text-[11px] font-semibold uppercase tracking-widest text-neutral-400">
                    Informations personnelles
                </p>
                <UpdateProfileInformationForm :user="$page.props.auth.user" />
            </div>

            <!-- Update password -->
            <div v-if="$page.props.jetstream.canUpdatePassword" class="rounded-xl bg-white p-5 shadow-sm">
                <p class="mb-5 text-[11px] font-semibold uppercase tracking-widest text-neutral-400">Mot de passe</p>
                <UpdatePasswordForm />
            </div>

            <!-- 2FA -->
            <div
                v-if="$page.props.jetstream.canManageTwoFactorAuthentication"
                class="rounded-xl bg-white p-5 shadow-sm"
            >
                <p class="mb-5 text-[11px] font-semibold uppercase tracking-widest text-neutral-400">
                    Double authentification
                </p>
                <TwoFactorAuthenticationForm :requires-confirmation="confirmsTwoFactorAuthentication" />
            </div>

            <!-- Browser sessions -->
            <div class="rounded-xl bg-white p-5 shadow-sm">
                <p class="mb-5 text-[11px] font-semibold uppercase tracking-widest text-neutral-400">
                    Sessions actives
                </p>
                <LogoutOtherBrowserSessionsForm :sessions="sessions" />
            </div>

            <!-- Delete account -->
            <div
                v-if="$page.props.jetstream.hasAccountDeletionFeatures"
                class="rounded-xl border border-red-100 bg-white p-5 shadow-sm"
            >
                <p class="mb-5 text-[11px] font-semibold uppercase tracking-widest text-red-400">Zone dangereuse</p>
                <DeleteUserForm />
            </div>
        </div>
    </MainLayout>
</template>
