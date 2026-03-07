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
            <h1 class="text-sm font-semibold text-slate-900">Ajouter filiere</h1>
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
        <div class="mx-auto max-w-3xl">
          <div class="mb-6">
            <div class="mb-4 flex items-center gap-3">
              <NuxtLink
                to="/filieres"
                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-100"
              >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
              </NuxtLink>
              <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-600">Nouveau</p>
                <h2 class="text-2xl font-bold text-slate-900">Ajouter une filiere</h2>
              </div>
            </div>
            <p class="text-sm text-slate-600">Creer une nouvelle filiere</p>
          </div>

          <form @submit.prevent="handleSubmit" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div v-if="errorMessage" class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">
              <p class="text-sm text-red-800">{{ errorMessage }}</p>
            </div>

            <div v-if="successMessage" class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4">
              <p class="text-sm text-green-800">{{ successMessage }}</p>
            </div>

            <div class="space-y-6">
              <div>
                <label for="id_pole" class="mb-1 block text-sm font-medium text-slate-700">
                  Pole <span class="text-red-500">*</span>
                </label>
                <select
                  id="id_pole"
                  v-model.number="form.id_pole"
                  required
                  class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 focus:border-transparent focus:ring-2 focus:ring-sky-500"
                >
                  <option :value="null">Selectionner un pole</option>
                  <option v-for="pole in poles" :key="pole.id" :value="pole.id">
                    {{ pole.nom_pole }}
                  </option>
                </select>
              </div>

              <div>
                <label for="nom_filiere" class="mb-1 block text-sm font-medium text-slate-700">
                  Nom filiere <span class="text-red-500">*</span>
                </label>
                <input
                  id="nom_filiere"
                  v-model="form.nom_filiere"
                  type="text"
                  required
                  maxlength="150"
                  class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 focus:border-transparent focus:ring-2 focus:ring-sky-500"
                  placeholder="Ex: Developpement Digital"
                />
              </div>
            </div>

            <div class="mt-8 flex items-center justify-end gap-4">
              <NuxtLink
                to="/filieres"
                class="rounded-lg border border-slate-300 px-6 py-2 font-medium text-slate-700 transition-colors hover:bg-slate-50"
              >
                Annuler
              </NuxtLink>
              <button
                type="submit"
                :disabled="submitting"
                class="rounded-lg bg-sky-600 px-6 py-2 font-medium text-white transition-colors hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-50"
              >
                <span v-if="submitting" class="flex items-center">
                  <svg class="-ml-1 mr-2 h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Creation en cours...
                </span>
                <span v-else>Creer la filiere</span>
              </button>
            </div>
          </form>
        </div>
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
      <aside
        v-if="isSidebarOpen"
        class="fixed left-0 top-0 z-50 h-full w-[86%] max-w-[320px] lg:hidden"
      >
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
import { useApi } from '../../../composables/useApi'
import { useAuthStore } from '../../../stores/auth'

definePageMeta({
  middleware: ['auth']
})

interface Pole {
  id: number
  nom_pole: string
}

const router = useRouter()
const api = useApi()
const authStore = useAuthStore()

const isSidebarOpen = ref(false)
const submitting = ref(false)
const errorMessage = ref<string | null>(null)
const successMessage = ref<string | null>(null)
const poles = ref<Pole[]>([])

const form = ref({
  nom_filiere: '',
  id_pole: null as number | null,
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

const loadPoles = async () => {
  try {
    const response = await api.get<Pole[] | { data: Pole[] }>('/poles')
    poles.value = Array.isArray(response) ? response : ((response as any).data || [])
  } catch (err: any) {
    console.error('Error loading poles:', err)
  }
}

const handleSubmit = async () => {
  errorMessage.value = null
  successMessage.value = null

  if (!form.value.nom_filiere.trim() || form.value.id_pole === null) {
    errorMessage.value = 'Veuillez remplir tous les champs obligatoires.'
    return
  }

  submitting.value = true

  try {
    await api.post('/filiers', {
      nom_filiere: form.value.nom_filiere.trim(),
      id_pole: form.value.id_pole,
    })

    successMessage.value = 'Filiere creee avec succes !'

    form.value = {
      nom_filiere: '',
      id_pole: null,
    }

    setTimeout(() => {
      router.push('/filieres')
    }, 1500)
  } catch (err: any) {
    console.error('Error creating filiere:', err)
    if (err.data && err.data.errors) {
      const errors = Object.values(err.data.errors).flat()
      errorMessage.value = errors.join(', ')
    } else {
      errorMessage.value = err.message || 'Erreur lors de la creation de la filiere'
    }
  } finally {
    submitting.value = false
  }
}

const handleLogout = async () => {
  isSidebarOpen.value = false
  await authStore.logout()
  router.push('/login')
}

onMounted(() => {
  loadPoles()
})
</script>
