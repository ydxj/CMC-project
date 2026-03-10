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
            <h1 class="text-sm font-semibold text-slate-900">Mes Formateurs</h1>
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
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-600">Gestion</p>
          <h2 class="mt-2 text-2xl font-bold text-slate-900">Mes Formateurs</h2>
          <p class="mt-2 text-sm text-slate-600">Liste des formateurs assignés à votre pôle</p>
        </section>

        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div class="relative max-w-md flex-1">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Rechercher un formateur..."
              class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-10 pr-4 text-slate-900 focus:border-transparent focus:ring-2 focus:ring-sky-500"
            >
            <svg class="absolute left-3 top-2.5 h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
          <button
            @click="openAddModal"
            class="inline-flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-700"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Mutualiser formateur
          </button>
        </div>

        <div v-if="loading" class="rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm">
          <div class="inline-block h-8 w-8 animate-spin rounded-full border-b-2 border-sky-600"></div>
          <p class="mt-2 text-slate-600">Chargement...</p>
        </div>

        <div v-else-if="error" class="rounded-xl border border-red-200 bg-red-50 p-4">
          <p class="text-red-800">{{ error }}</p>
        </div>

        <div v-else-if="paginatedFormateurs.length === 0" class="rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm">
          <p class="text-slate-500">Aucun formateur assigné à votre pôle</p>
        </div>

        <div v-else class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
              <thead class="bg-slate-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Matricule</th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Nom complet</th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Email</th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Type</th>
                  <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 bg-white">
                <tr v-for="formateur in paginatedFormateurs" :key="formateur.id_user" class="transition-colors hover:bg-slate-50">
                  <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">{{ formateur.user?.matricule || '-' }}</td>
                  <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">{{ formateur.user?.prenom }} {{ formateur.user?.nom }}</td>
                  <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">{{ formateur.user?.email }}</td>
                  <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                    <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                      {{ formateur.type_formateur || '-' }}
                    </span>
                  </td>
                  <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                    <button
                      @click="removeFormateur(formateur.id_user)"
                      class="text-red-600 hover:text-red-900"
                      title="Supprimer"
                    >
                      <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="filteredFormateurs.length > 0" class="flex flex-col gap-4 border-t border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <p class="text-sm font-medium text-slate-600">
              Affichage {{ paginationStart }}-{{ paginationEnd }} sur {{ filteredFormateurs.length }} formateurs
            </p>

            <div class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white p-2 shadow-sm">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-transparent"
                :disabled="currentPage === 1"
                @click="goToPreviousPage"
              >
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15l-5-5 5-5" />
                </svg>
                Precedent
              </button>

              <div class="flex items-center gap-1 border-l border-r border-slate-200 px-2">
                <template v-for="(item, index) in paginationItems" :key="`p-${index}`">
                  <span
                    v-if="item === '...'"
                    class="inline-flex h-8 w-8 items-center justify-center text-sm text-slate-400"
                  >
                    ...
                  </span>
                  <button
                    v-else
                    type="button"
                    class="inline-flex h-8 w-8 items-center justify-center rounded text-sm font-semibold transition"
                    :class="item === currentPage ? 'bg-sky-600 text-white shadow' : 'text-slate-700 hover:bg-slate-100'"
                    @click="goToPage(item)"
                  >
                    {{ item }}
                  </button>
                </template>
              </div>

              <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-transparent"
                :disabled="currentPage === totalPages"
                @click="goToNextPage"
              >
                Suivant
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5l5 5-5 5" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </main>
    </div>

    <!-- Add Formateur Modal -->
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isModalOpen"
        class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-[1px] lg:hidden"
        @click="closeModal"
      />
    </Transition>

    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="scale-95 opacity-0"
      enter-to-class="scale-100 opacity-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="scale-100 opacity-100"
      leave-to-class="scale-95 opacity-0"
    >
      <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white shadow-2xl">
          <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <h3 class="text-lg font-bold text-slate-900">Ajouter un formateur</h3>
            <button
              type="button"
              class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-100"
              @click="closeModal"
              aria-label="Close"
            >
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="p-6">
            <div class="mb-4">
              <label class="mb-2 block text-sm font-medium text-slate-700">Formateur</label>
              <select
                v-model="selectedFormateur"
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:border-transparent focus:ring-2 focus:ring-sky-500"
              >
                <option value="">-- Sélectionner un formateur --</option>
                <option v-for="formateur in availableFormateurs" :key="formateur.id" :value="formateur.id">
                  {{ formateur.prenom }} {{ formateur.nom }} ({{ formateur.email }})
                </option>
              </select>
            </div>

            <div class="flex gap-3">
              <button
                type="button"
                class="flex-1 rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                @click="closeModal"
              >
                Annuler
              </button>
              <button
                type="button"
                class="flex-1 rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="!selectedFormateur || isAdding"
                @click="addFormateur"
              >
                {{ isAdding ? 'Ajout en cours...' : 'Ajouter' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Sidebar -->
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
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '../../../../composables/useApi'
import { useAuthStore } from '../../../../stores/auth'

definePageMeta({
  middleware: ['auth']
})

interface UserItem {
  id: number
  nom: string
  prenom: string
  email: string
  role: string
  matricule?: string
}

interface Formateur {
  id_user: number
  type_formateur: string
  MHS: number
  NBR_semaine: number
  user: UserItem
}

const router = useRouter()
const api = useApi()
const authStore = useAuthStore()

const isSidebarOpen = ref(false)
const isModalOpen = ref(false)
const loading = ref(true)
const error = ref<string | null>(null)
const searchQuery = ref('')
const formateurs = ref<Formateur[]>([])
const allFormateurs = ref<UserItem[]>([])
const selectedFormateur = ref<number | string>('')
const isAdding = ref(false)
const userPoleId = ref<number | null>(null)
const currentPage = ref(1)
const pageSize = 10

const userPole = computed(() => {
  const user = authStore.user
  if (!user) return null
  
  // The pole ID should be available from the authenticated user
  // We'll need to fetch the pole where the user is the chef
  return null
})

const fullName = computed(() => {
  if (!authStore.user) return 'Utilisateur'
  return `${authStore.user.nom} ${authStore.user.prenom}`.trim()
})

const roleLabel = computed(() => {
  const role = authStore.user?.role
  if (role === 'CHEF_POLE') return 'Chef de Pole'
  return role || 'Utilisateur'
})

const userInitials = computed(() => {
  if (!authStore.user) return 'CM'
  const nom = authStore.user.nom?.charAt(0) || ''
  const prenom = authStore.user.prenom?.charAt(0) || ''
  return `${nom}${prenom}`.toUpperCase() || 'CM'
})

const filteredFormateurs = computed(() => {
  if (!searchQuery.value.trim()) return formateurs.value

  const query = searchQuery.value.toLowerCase()
  return formateurs.value.filter((formateur) => {
    return (
      formateur.user?.nom.toLowerCase().includes(query) ||
      formateur.user?.prenom.toLowerCase().includes(query) ||
      formateur.user?.email.toLowerCase().includes(query) ||
      (formateur.user?.matricule || '').toLowerCase().includes(query)
    )
  })
})

const totalPages = computed(() => {
  const total = Math.ceil(filteredFormateurs.value.length / pageSize)
  return total > 0 ? total : 1
})

const paginatedFormateurs = computed(() => {
  const start = (currentPage.value - 1) * pageSize
  const end = start + pageSize
  return filteredFormateurs.value.slice(start, end)
})

const paginationStart = computed(() => {
  if (filteredFormateurs.value.length === 0) return 0
  return (currentPage.value - 1) * pageSize + 1
})

const paginationEnd = computed(() => {
  if (filteredFormateurs.value.length === 0) return 0
  return Math.min(currentPage.value * pageSize, filteredFormateurs.value.length)
})

const paginationItems = computed<(number | '...')[]>(() => {
  const total = totalPages.value
  const current = currentPage.value

  if (total <= 7) {
    return Array.from({ length: total }, (_, i) => i + 1)
  }

  const items: (number | '...')[] = [1]

  if (current > 3) {
    items.push('...')
  }

  const start = Math.max(2, current - 1)
  const end = Math.min(total - 1, current + 1)

  for (let i = start; i <= end; i += 1) {
    items.push(i)
  }

  if (current < total - 2) {
    items.push('...')
  }

  items.push(total)
  return items
})

const availableFormateurs = computed(() => {
  const assignedIds = new Set(formateurs.value.map(f => f.id_user))
  return allFormateurs.value.filter(f => !assignedIds.has(f.id))
})

const goToPage = (page: number) => {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
}

const goToPreviousPage = () => {
  goToPage(currentPage.value - 1)
}

const goToNextPage = () => {
  goToPage(currentPage.value + 1)
}

watch(searchQuery, () => {
  currentPage.value = 1
})

watch(filteredFormateurs, () => {
  if (currentPage.value > totalPages.value) {
    currentPage.value = totalPages.value
  }
})

const extractArray = <T>(payload: unknown): T[] => {
  if (Array.isArray(payload)) {
    return payload as T[]
  }

  const maybeData = (payload as { data?: unknown } | null)?.data
  if (Array.isArray(maybeData)) {
    return maybeData as T[]
  }

  return []
}

const loadFormateurs = async () => {
  loading.value = true
  error.value = null

  try {
    // Get formateurs for the chef's pole using dedicated endpoint
    const response = await api.get<any>('/my-poles-formateurs')
    
    if (!response || !(response as any).data) {
      error.value = 'Error loading formateurs'
      return
    }

    // Store the pole ID for future operations
    userPoleId.value = (response as any).pole_id
    const poleFormateurs = extractArray<Formateur>(response)
    formateurs.value = poleFormateurs

    // Load all formateurs from the system for the add modal
    const usersRes = await api.get<UserItem[]>('/users')
    const users = extractArray<UserItem>(usersRes)
    allFormateurs.value = users.filter((u: UserItem) => u.role === 'FORMATEUR')
  } catch (err: any) {
    error.value = err.message || 'Erreur lors du chargement des formateurs'
    console.error('Error loading formateurs:', err)
  } finally {
    loading.value = false
  }
}

const openAddModal = () => {
  isModalOpen.value = true
  selectedFormateur.value = ''
}

const closeModal = () => {
  isModalOpen.value = false
  selectedFormateur.value = ''
}

const addFormateur = async () => {
  if (!selectedFormateur.value) return

  isAdding.value = true
  error.value = null

  try {
    if (!userPoleId.value) {
      error.value = 'No pole ID available'
      isAdding.value = false
      return
    }

    // Add formateur to pole
    await api.post(`/poles/${userPoleId.value}/formateurs`, {
      id_user: selectedFormateur.value,
    })

    // Reload formateurs
    await loadFormateurs()
    closeModal()
  } catch (err: any) {
    error.value = err.message || 'Erreur lors de l\'ajout du formateur'
    console.error('Error adding formateur:', err)
  } finally {
    isAdding.value = false
  }
}

const removeFormateur = async (userId: number) => {
  if (!confirm('Êtes-vous sûr de vouloir supprimer ce formateur?')) return

  error.value = null

  try {
    if (!userPoleId.value) {
      error.value = 'No pole ID available'
      return
    }

    // Remove formateur from pole using query parameter
    await api.delete(`/poles/${userPoleId.value}/formateurs?id_user=${userId}`)

    // Reload formateurs
    await loadFormateurs()
  } catch (err: any) {
    error.value = err.message || 'Erreur lors de la suppression du formateur'
    console.error('Error removing formateur:', err)
  }
}

const handleLogout = async () => {
  isSidebarOpen.value = false
  await authStore.logout()
  router.push('/login')
}

onMounted(() => {
  loadFormateurs()
})
</script>
