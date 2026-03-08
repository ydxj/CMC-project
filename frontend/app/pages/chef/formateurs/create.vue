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
            <h1 class="text-sm font-semibold text-slate-900">Ajouter Formateur</h1>
          </div>

          <div class="flex items-center gap-3">
            <div class="hidden text-right sm:block">
              <p class="text-sm font-semibold text-slate-800">{{ fullName }}</p>
              <p class="text-xs text-slate-500">Chef de Pole</p>
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
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-600">Formulaire</p>
          <h2 class="mt-2 text-2xl font-bold text-slate-900">Creer Un Formateur</h2>
          <p class="mt-2 text-sm text-slate-600">Le formateur sera automatiquement assigne a votre pole.</p>
        </section>

        <div v-if="error" class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">
          {{ error }}
        </div>

        <form class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" @submit.prevent="submitForm">
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Nom</label>
              <input v-model="form.nom" required class="w-full rounded-lg border border-slate-300 px-3 py-2" type="text" />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Prenom</label>
              <input v-model="form.prenom" required class="w-full rounded-lg border border-slate-300 px-3 py-2" type="text" />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
              <input v-model="form.email" required class="w-full rounded-lg border border-slate-300 px-3 py-2" type="email" />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Matricule</label>
              <input v-model="form.matricule" required class="w-full rounded-lg border border-slate-300 px-3 py-2" type="text" />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Mot de passe</label>
              <input v-model="form.password" required minlength="6" class="w-full rounded-lg border border-slate-300 px-3 py-2" type="password" />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Type formateur</label>
              <select v-model="form.type_formateur" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                <option value="FP">FP</option>
                <option value="FV">FV</option>
              </select>
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">MHS</label>
              <input v-model.number="form.MHS" min="0" class="w-full rounded-lg border border-slate-300 px-3 py-2" type="number" />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">NBR semaine</label>
              <input v-model.number="form.NBR_semaine" min="0" class="w-full rounded-lg border border-slate-300 px-3 py-2" type="number" />
            </div>
          </div>

          <div class="mt-6 flex gap-3">
            <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100" @click="router.push('/chef/formateurs')">
              Annuler
            </button>
            <button :disabled="submitting" type="submit" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700 disabled:opacity-60">
              {{ submitting ? 'Creation...' : 'Creer le formateur' }}
            </button>
          </div>
        </form>
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
import { useApi } from '../../../../composables/useApi'
import { useAuthStore } from '../../../../stores/auth'

definePageMeta({ middleware: ['auth'] })

const api = useApi()
const router = useRouter()
const authStore = useAuthStore()

const isSidebarOpen = ref(false)
const submitting = ref(false)
const error = ref<string | null>(null)
const poleId = ref<number | null>(null)

const form = ref({
  nom: '',
  prenom: '',
  email: '',
  matricule: '',
  password: '',
  type_formateur: 'FP',
  MHS: 0,
  NBR_semaine: 0,
})

const fullName = computed(() => {
  if (!authStore.user) return 'Utilisateur'
  return `${authStore.user.nom} ${authStore.user.prenom}`.trim()
})

const userInitials = computed(() => {
  if (!authStore.user) return 'CM'
  const nom = authStore.user.nom?.charAt(0) || ''
  const prenom = authStore.user.prenom?.charAt(0) || ''
  return `${nom}${prenom}`.toUpperCase() || 'CM'
})

const resolveChefPole = async () => {
  const response = await api.get<any>('/my-poles-formateurs')
  if (!response || !(response as any).pole_id) {
    throw new Error('Impossible de trouver votre pole')
  }
  poleId.value = (response as any).pole_id
}

const submitForm = async () => {
  submitting.value = true
  error.value = null

  try {
    if (!poleId.value) {
      await resolveChefPole()
    }

    await api.post('/users', {
      nom: form.value.nom,
      prenom: form.value.prenom,
      email: form.value.email,
      password: form.value.password,
      role: 'FORMATEUR',
      matricule: form.value.matricule,
      type_formateur: form.value.type_formateur,
      MHS: form.value.MHS,
      NBR_semaine: form.value.NBR_semaine,
      id_pole: poleId.value,
    })

    router.push('/chef/formateurs')
  } catch (err: any) {
    error.value = err?.data?.message || err?.message || 'Erreur lors de la creation du formateur'
  } finally {
    submitting.value = false
  }
}

const handleLogout = async () => {
  isSidebarOpen.value = false
  await authStore.logout()
  router.push('/login')
}

onMounted(async () => {
  try {
    await resolveChefPole()
  } catch (err: any) {
    error.value = err?.message || 'Impossible de charger les informations du pole'
  }
})
</script>
