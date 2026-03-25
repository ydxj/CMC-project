<template>
  <div class="min-h-screen bg-slate-100 text-slate-800">
    <aside class="fixed inset-y-0 left-0 z-30 hidden w-72 lg:block">
      <RoleSidebar :role="authStore.user?.role" />
    </aside>

    <div class="lg:pl-72">
      <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
          <div class="flex items-center gap-3">
            <button
              type="button"
              class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-100 lg:hidden"
              @click="isSidebarOpen = true"
              aria-label="Open menu"
            >
              <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>
            <h1 class="text-sm font-semibold text-slate-900">Parametres</h1>
          </div>

          <div class="flex items-center gap-3">
            <div class="hidden text-right sm:block">
              <p class="text-sm font-semibold text-slate-800">{{ fullName }}</p>
              <p class="text-xs text-slate-500">{{ roleLabel }}</p>
            </div>
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-sky-600 text-xs font-bold text-white">
              {{ userInitials }}
            </div>
            <button
              @click="handleLogout"
              class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100"
            >
              Logout
            </button>
          </div>
        </div>
      </header>

      <main class="px-4 py-6 sm:px-6 lg:px-8">
        <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-600">Compte</p>
          <h2 class="mt-2 text-2xl font-bold text-slate-900">Parametres utilisateur</h2>
          <p class="mt-2 text-sm text-slate-600">Mettez a jour vos informations et votre mot de passe.</p>
        </section>


        <div v-if="globalMessage" class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">
          {{ globalMessage }}
        </div>

        <div v-if="error" class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">
          {{ error }}
        </div>

        <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
          <h3 class="text-base font-semibold text-slate-900">Profil</h3>

          <form class="mt-4 grid gap-4 md:grid-cols-2" @submit.prevent="saveProfile">
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Nom</label>
              <input v-model="profile.nom" required class="w-full rounded-lg border border-slate-300 px-3 py-2" type="text" />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Prenom</label>
              <input v-model="profile.prenom" required class="w-full rounded-lg border border-slate-300 px-3 py-2" type="text" />
            </div>
            <div class="md:col-span-2">
              <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
              <input v-model="profile.email" required class="w-full rounded-lg border border-slate-300 px-3 py-2" type="email" />
            </div>

            <div class="md:col-span-2">
              <button
                :disabled="savingProfile"
                type="submit"
                class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700 disabled:opacity-60"
              >
                {{ savingProfile ? 'Enregistrement...' : 'Enregistrer le profil' }}
              </button>
            </div>
          </form>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
          <h3 class="text-base font-semibold text-slate-900">Mot de passe</h3>

          <form class="mt-4 grid gap-4 md:grid-cols-2" @submit.prevent="savePassword">
            <div class="md:col-span-2">
              <label class="mb-1 block text-sm font-medium text-slate-700">Nouveau mot de passe</label>
              <input v-model="passwordForm.password" minlength="6" required class="w-full rounded-lg border border-slate-300 px-3 py-2" type="password" />
            </div>

            <div class="md:col-span-2">
              <label class="mb-1 block text-sm font-medium text-slate-700">Confirmer le mot de passe</label>
              <input v-model="passwordForm.confirmPassword" minlength="6" required class="w-full rounded-lg border border-slate-300 px-3 py-2" type="password" />
            </div>

            <div class="md:col-span-2">
              <button
                :disabled="savingPassword"
                type="submit"
                class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-60"
              >
                {{ savingPassword ? 'Mise a jour...' : 'Mettre a jour le mot de passe' }}
              </button>
            </div>
          </form>
        </section>
      </main>
    </div>

    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isSidebarOpen"
        class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-[1px] lg:hidden"
        @click="isSidebarOpen = false"
      />
    </Transition>

    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="-translate-x-full opacity-0"
      enter-to-class="translate-x-0 opacity-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="translate-x-0 opacity-100"
      leave-to-class="-translate-x-full opacity-0"
    >
      <aside v-if="isSidebarOpen" class="fixed left-0 top-0 z-50 h-full w-[86%] max-w-[320px] lg:hidden">
        <div class="flex h-full flex-col overflow-hidden border-r border-slate-200 bg-white shadow-2xl">
          <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
            <p class="text-sm font-semibold text-slate-800">Navigation</p>
            <button
              type="button"
              class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-100"
              @click="isSidebarOpen = false"
              aria-label="Close menu"
            >
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div class="overflow-y-auto">
            <RoleSidebar :role="authStore.user?.role" @navigate="isSidebarOpen = false" />
          </div>
        </div>
      </aside>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '../../composables/useApi'
import { useAuthStore } from '../../stores/auth'

definePageMeta({
  middleware: ['auth'],
})

const router = useRouter()
const api = useApi()
const authStore = useAuthStore()

const isSidebarOpen = ref(false)
const savingProfile = ref(false)
const savingPassword = ref(false)
const error = ref<string | null>(null)
const globalMessage = ref('')

const profile = ref({
  nom: '',
  prenom: '',
  email: '',
})

const passwordForm = ref({
  password: '',
  confirmPassword: '',
})

const fullName = computed(() => {
  if (!authStore.user) return 'Utilisateur'
  return `${authStore.user.nom} ${authStore.user.prenom}`.trim()
})

const roleLabel = computed(() => {
  const role = authStore.user?.role
  if (role === 'DIRECTEUR') return 'Directeur Pedagogique'
  if (role === 'CHEF_POLE') return 'Chef de Pole'
  if (role === 'FORMATEUR') return 'Formateur'
  return role || 'Utilisateur'
})

const userInitials = computed(() => {
  if (!authStore.user) return 'CM'
  const nom = authStore.user.nom?.charAt(0) || ''
  const prenom = authStore.user.prenom?.charAt(0) || ''
  return `${nom}${prenom}`.toUpperCase() || 'CM'
})

const loadMe = async () => {
  error.value = null

  try {
    const response = await api.get<any>('/auth/me')
    const user = response?.user || response?.data?.user || null

    if (!user) {
      throw new Error('Impossible de charger les informations utilisateur')
    }

    profile.value = {
      nom: user.nom || '',
      prenom: user.prenom || '',
      email: user.email || '',
    }
  } catch (err: any) {
    error.value = err?.data?.message || err?.message || 'Erreur lors du chargement du profil'
  }
}

const saveProfile = async () => {
  if (!authStore.user?.id) {
    error.value = 'Utilisateur introuvable'
    return
  }

  savingProfile.value = true
  error.value = null
  globalMessage.value = ''

  try {
    await api.put(`/users/${authStore.user.id}`, {
      nom: profile.value.nom,
      prenom: profile.value.prenom,
      email: profile.value.email,
    })

    await authStore.checkAuth()
    globalMessage.value = 'Profil mis a jour avec succes.'
  } catch (err: any) {
    error.value = err?.data?.message || err?.message || 'Erreur lors de la mise a jour du profil'
  } finally {
    savingProfile.value = false
  }
}

const savePassword = async () => {
  if (!authStore.user?.id) {
    error.value = 'Utilisateur introuvable'
    return
  }

  if (passwordForm.value.password !== passwordForm.value.confirmPassword) {
    error.value = 'La confirmation du mot de passe ne correspond pas.'
    return
  }

  savingPassword.value = true
  error.value = null
  globalMessage.value = ''

  try {
    await api.put(`/users/${authStore.user.id}`, {
      password: passwordForm.value.password,
    })

    passwordForm.value = {
      password: '',
      confirmPassword: '',
    }

    globalMessage.value = 'Mot de passe mis a jour avec succes.'
  } catch (err: any) {
    error.value = err?.data?.message || err?.message || 'Erreur lors de la mise a jour du mot de passe'
  } finally {
    savingPassword.value = false
  }
}

const handleLogout = async () => {
  isSidebarOpen.value = false
  await authStore.logout()
  router.push('/login')
}

onMounted(() => {
  loadMe()
})
</script>

