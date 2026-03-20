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
            <h1 class="text-sm font-semibold text-slate-900">Mes Salles & Ateliers</h1>
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
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-600">Gestion</p>
          <h2 class="mt-2 text-2xl font-bold text-slate-900">Liste des salles</h2>
          <p class="mt-2 text-sm text-slate-600">Salles et ateliers de votre pole uniquement.</p>
        </section>

        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Rechercher une salle..."
            class="w-full max-w-md rounded-lg border border-slate-300 px-3 py-2 text-sm"
          />
          <button
            type="button"
            @click="openCreateModal"
            class="inline-flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-700"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Ajouter salle
          </button>
        </div>

        <div v-if="loading" class="rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm">
          <div class="inline-block h-8 w-8 animate-spin rounded-full border-b-2 border-sky-600"></div>
          <p class="mt-2 text-slate-600">Chargement...</p>
        </div>

        <div v-else-if="error" class="rounded-xl border border-red-200 bg-red-50 p-4">
          <p class="text-red-800">{{ error }}</p>
        </div>

        <div v-else-if="filteredSalles.length === 0" class="rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm">
          <p class="text-slate-500">Aucune salle trouvee</p>
        </div>

        <div v-else class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
              <thead class="bg-slate-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Nom salle</th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Type</th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Pole</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 bg-white">
                <tr v-for="salle in filteredSalles" :key="salle.id" class="hover:bg-slate-50">
                  <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">{{ salle.nom_salle }}</td>
                  <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                    <span
                      :class="[
                        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                        salle.type_salle === 'ATELIER' ? 'bg-amber-100 text-amber-800' : 'bg-sky-100 text-sky-800'
                      ]"
                    >
                      {{ salle.type_salle }}
                    </span>
                  </td>
                  <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">{{ salle.pole?.nom_pole || '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
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
        v-if="showCreateModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-[1px]"
        @click.self="closeCreateModal"
      >
        <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white shadow-2xl">
          <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <h3 class="text-lg font-bold text-slate-900">Ajouter salle</h3>
            <button
              type="button"
              class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-100"
              @click="closeCreateModal"
              aria-label="Close"
            >
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form class="space-y-4 p-6" @submit.prevent="createSalle">
            <div v-if="createError" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">
              {{ createError }}
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Nom salle</label>
              <input
                v-model="createForm.nom_salle"
                required
                class="w-full rounded-lg border border-slate-300 px-3 py-2"
                type="text"
              />
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Type</label>
              <select
                v-model="createForm.type_salle"
                required
                class="w-full rounded-lg border border-slate-300 px-3 py-2"
              >
                <option value="SALLE">SALLE</option>
                <option value="ATELIER">ATELIER</option>
              </select>
            </div>

            <div class="flex gap-3 border-t border-slate-200 pt-4">
              <button
                type="button"
                class="flex-1 rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                @click="closeCreateModal"
              >
                Annuler
              </button>
              <button
                type="submit"
                class="flex-1 rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700 disabled:opacity-60"
                :disabled="createSubmitting"
              >
                {{ createSubmitting ? 'Creation...' : 'Creer salle' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>

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

interface PoleItem {
  id: number
  nom_pole: string
}

interface SalleItem {
  id: number
  nom_salle: string
  type_salle: 'SALLE' | 'ATELIER'
  id_pole: number
  pole?: PoleItem
}

const api = useApi()
const router = useRouter()
const authStore = useAuthStore()

const isSidebarOpen = ref(false)
const loading = ref(true)
const error = ref<string | null>(null)
const searchQuery = ref('')
const salles = ref<SalleItem[]>([])
const showCreateModal = ref(false)
const createSubmitting = ref(false)
const createError = ref<string | null>(null)
const poleId = ref<number | null>(null)
const createForm = ref({
  nom_salle: '',
  type_salle: 'SALLE' as 'SALLE' | 'ATELIER',
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

const extractArray = <T>(payload: unknown): T[] => {
  if (Array.isArray(payload)) return payload as T[]
  const maybeData = (payload as { data?: unknown } | null)?.data
  if (Array.isArray(maybeData)) return maybeData as T[]
  return []
}

const filteredSalles = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  if (!query) return salles.value

  return salles.value.filter((salle) => {
    return (
      salle.nom_salle.toLowerCase().includes(query) ||
      salle.type_salle.toLowerCase().includes(query) ||
      (salle.pole?.nom_pole || '').toLowerCase().includes(query)
    )
  })
})

const loadSalles = async () => {
  loading.value = true
  error.value = null

  try {
    const response = await api.get<any>('/salles')
    salles.value = extractArray<SalleItem>(response)
  } catch (err: any) {
    error.value = err?.data?.message || err?.message || 'Erreur lors du chargement des salles'
  } finally {
    loading.value = false
  }
}

const resolveChefPole = async () => {
  const response = await api.get<any>('/my-poles-formateurs')
  const chefPoleId = (response as any)?.pole_id
  if (!chefPoleId) {
    throw new Error('Impossible de trouver votre pole')
  }
  poleId.value = chefPoleId
}

const openCreateModal = () => {
  createForm.value = {
    nom_salle: '',
    type_salle: 'SALLE',
  }
  createError.value = null
  showCreateModal.value = true
}

const closeCreateModal = () => {
  showCreateModal.value = false
  createError.value = null
}

const createSalle = async () => {
  createSubmitting.value = true
  createError.value = null

  try {
    if (!poleId.value) {
      await resolveChefPole()
    }

    await api.post('/salles', {
      nom_salle: createForm.value.nom_salle,
      type_salle: createForm.value.type_salle,
      id_pole: poleId.value,
    })

    await loadSalles()
    closeCreateModal()
  } catch (err: any) {
    createError.value = err?.data?.message || err?.message || 'Erreur lors de la creation de la salle'
  } finally {
    createSubmitting.value = false
  }
}

const handleLogout = async () => {
  isSidebarOpen.value = false
  await authStore.logout()
  router.push('/login')
}

onMounted(() => {
  loadSalles()
})
</script>
