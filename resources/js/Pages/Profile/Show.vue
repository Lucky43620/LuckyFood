<script setup>
import { Link } from '@inertiajs/vue3'
import { LogOut } from 'lucide-vue-next'
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

            <!-- Logout -->
            <div class="rounded-xl bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-neutral-400">Session</p>
                        <p class="mt-1 text-sm text-neutral-500">Quitter votre compte sur cet appareil.</p>
                    </div>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="inline-flex h-10 items-center justify-center gap-1.5 rounded-pill bg-neutral-100 px-5 text-sm font-semibold text-neutral-600 transition-colors hover:bg-neutral-200 hover:text-neutral-800"
                    >
                        <LogOut :size="15" /> Se deconnecter
                    </Link>
                </div>
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
