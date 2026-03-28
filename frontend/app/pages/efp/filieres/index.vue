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
            <h1 class="text-sm font-semibold text-slate-900">Filieres</h1>
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
          <h2 class="mt-2 text-2xl font-bold text-slate-900">Filieres</h2>
          <p class="mt-2 text-sm text-slate-600">Liste des filieres de l'etablissement</p>
        </section>

        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div class="relative max-w-md flex-1">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Rechercher une filiere..."
              class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-10 pr-4 text-slate-900 focus:border-transparent focus:ring-2 focus:ring-sky-500"
            >
            <svg class="absolute left-3 top-2.5 h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>

          <div class="flex flex-wrap items-center gap-3">
            <button
              type="button"
              @click="openCreateModal"
              class="inline-flex items-center justify-center rounded-lg bg-sky-600 px-4 py-2 font-medium text-white transition-colors hover:bg-sky-700"
            >
              <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Ajouter filiere
            </button>
            <BulkExcelPanel entity="filiers" @imported="loadFilieres" />
          </div>
        </div>

        <div v-if="loading" class="rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm">
          <div class="inline-block h-8 w-8 animate-spin rounded-full border-b-2 border-sky-600"></div>
          <p class="mt-2 text-slate-600">Chargement...</p>
        </div>

        <div v-else-if="error" class="rounded-xl border border-red-200 bg-red-50 p-4">
          <p class="text-red-800">{{ error }}</p>
        </div>

        <div v-else class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
          <div v-if="displayedFilieres.length === 0" class="p-8 text-center text-slate-500">
            Aucune filiere trouvee
          </div>

          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
              <thead class="bg-slate-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">ID</th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Nom filiere</th>
                  <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 bg-white">
                <tr v-for="filiere in displayedFilieres" :key="filiere.id" class="transition-colors hover:bg-slate-50">
                  <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">{{ filiere.id }}</td>
                  <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">{{ filiere.nom_filiere }}</td>
                  <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                    <div class="flex items-center justify-end gap-3">
                      <button @click="openEditModal(filiere)" class="text-slate-500 hover:text-green-600" title="Modifier">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                      </button>
                      <button @click="deleteFiliere(filiere)" class="text-red-600 hover:text-red-900" title="Supprimer">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="border-t border-slate-200 px-4 py-3 sm:px-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
              <p class="text-sm text-slate-600">
                Affichage {{ paginationStart }}-{{ paginationEnd }} sur {{ totalItems }}
              </p>
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  @click="goToPage(currentPage - 1)"
                  :disabled="currentPage <= 1"
                  class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                >
                  Precedent
                </button>

                <button
                  v-for="pageNumber in visiblePages"
                  :key="pageNumber"
                  type="button"
                  @click="goToPage(pageNumber)"
                  :class="[
                    'rounded-lg px-3 py-1.5 text-sm',
                    pageNumber === currentPage
                      ? 'bg-sky-600 text-white'
                      : 'border border-slate-300 text-slate-700 hover:bg-slate-50'
                  ]"
                >
                  {{ pageNumber }}
                </button>

                <button
                  type="button"
                  @click="goToPage(currentPage + 1)"
                  :disabled="currentPage >= totalPages"
                  class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                >
                  Suivant
                </button>
              </div>
            </div>
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
        class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-[1px] flex items-center justify-center p-4"
        @click.self="closeCreateModal"
      >
        <Transition
          enter-active-class="transition-all duration-300"
          enter-from-class="scale-95 opacity-0"
          enter-to-class="scale-100 opacity-100"
          leave-active-class="transition-all duration-200"
          leave-from-class="scale-100 opacity-100"
          leave-to-class="scale-95 opacity-0"
        >
          <div v-if="showCreateModal" class="bg-white rounded-xl shadow-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 border-b border-slate-200 bg-white px-6 py-4 flex items-center justify-between">
              <h3 class="text-lg font-semibold text-slate-900">Ajouter filiere</h3>
              <button
                @click="closeCreateModal"
                class="text-slate-500 hover:text-slate-700"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <form @submit.prevent="createFiliere" class="p-6 space-y-4">
              <div v-if="createError" class="bg-red-50 border border-red-200 rounded-lg p-3">
                <p class="text-sm text-red-800">{{ createError }}</p>
              </div>

              <div v-if="createSuccess" class="bg-green-50 border border-green-200 rounded-lg p-3">
                <p class="text-sm text-green-800">{{ createSuccess }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                  Nom filiere <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="createForm.nom_filiere"
                  type="text"
                  required
                  maxlength="150"
                  class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 focus:border-transparent focus:ring-2 focus:ring-sky-500"
                  placeholder="Ex: Developpement Digital"
                />
              </div>

              <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <button
                  type="button"
                  @click="closeCreateModal"
                  class="px-4 py-2 border border-slate-300 text-slate-700 font-medium rounded-lg hover:bg-slate-50 transition-colors"
                >
                  Annuler
                </button>
                <button
                  type="submit"
                  :disabled="createSubmitting"
                  class="px-4 py-2 bg-sky-600 text-white font-medium rounded-lg hover:bg-sky-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  <span v-if="createSubmitting" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Création...
                  </span>
                  <span v-else>Créer la filiere</span>
                </button>
              </div>
            </form>
          </div>
        </Transition>
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
        v-if="showEditModal"
        class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-[1px] flex items-center justify-center p-4"
        @click.self="closeEditModal"
      >
        <Transition
          enter-active-class="transition-all duration-300"
          enter-from-class="scale-95 opacity-0"
          enter-to-class="scale-100 opacity-100"
          leave-active-class="transition-all duration-200"
          leave-from-class="scale-100 opacity-100"
          leave-to-class="scale-95 opacity-0"
        >
          <div v-if="showEditModal" class="bg-white rounded-xl shadow-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 border-b border-slate-200 bg-white px-6 py-4 flex items-center justify-between">
              <h3 class="text-lg font-semibold text-slate-900">Modifier filiere</h3>
              <button
                @click="closeEditModal"
                class="text-slate-500 hover:text-slate-700"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <form @submit.prevent="updateFiliere" class="p-6 space-y-4">
              <div v-if="editError" class="bg-red-50 border border-red-200 rounded-lg p-3">
                <p class="text-sm text-red-800">{{ editError }}</p>
              </div>

              <div v-if="editSuccess" class="bg-green-50 border border-green-200 rounded-lg p-3">
                <p class="text-sm text-green-800">{{ editSuccess }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                  Nom filiere <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="editForm.nom_filiere"
                  type="text"
                  required
                  maxlength="150"
                  class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 focus:border-transparent focus:ring-2 focus:ring-sky-500"
                  placeholder="Ex: Developpement Digital"
                />
              </div>

              <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <button
                  type="button"
                  @click="closeEditModal"
                  class="px-4 py-2 border border-slate-300 text-slate-700 font-medium rounded-lg hover:bg-slate-50 transition-colors"
                >
                  Annuler
                </button>
                <button
                  type="submit"
                  :disabled="editSubmitting"
                  class="px-4 py-2 bg-sky-600 text-white font-medium rounded-lg hover:bg-sky-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  <span v-if="editSubmitting" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Mise a jour...
                  </span>
                  <span v-else>Mettre a jour</span>
                </button>
              </div>
            </form>
          </div>
        </Transition>
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
import BulkExcelPanel from '../../../components/BulkExcelPanel.vue'

definePageMeta({
  middleware: ['auth'],
})

interface Pole {
  id: number
  nom_pole: string
}

interface Filiere {
  id: number
  nom_filiere: string
  id_pole: number | null
  pole?: Pole
}

const router = useRouter()
const api = useApi()
const authStore = useAuthStore()

const isSidebarOpen = ref(false)
const filieres = ref<Filiere[]>([])
const loading = ref(true)
const error = ref<string | null>(null)
const searchQuery = ref('')
const totalItems = ref(0)
const totalPages = ref(1)
const currentPage = ref(1)
const PER_PAGE = 10

interface FilieresPaginatedResponse {
  data: Filiere[]
  current_page: number
  last_page: number
  total: number
}

// Create modal state
const showCreateModal = ref(false)
const createSubmitting = ref(false)
const createError = ref<string | null>(null)
const createSuccess = ref<string | null>(null)
const createForm = ref({
  nom_filiere: '',
})

// Edit modal state
const showEditModal = ref(false)
const editSubmitting = ref(false)
const editError = ref<string | null>(null)
const editSuccess = ref<string | null>(null)
const editForm = ref<{
  id: number | null
  nom_filiere: string
}>({
  id: null,
  nom_filiere: '',
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

const displayedFilieres = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  return !query
    ? filieres.value
    : filieres.value.filter((filiere) =>
        filiere.nom_filiere.toLowerCase().includes(query)
      )
})

const paginationStart = computed(() => {
  if (totalItems.value === 0) return 0
  return (currentPage.value - 1) * PER_PAGE + 1
})

const paginationEnd = computed(() => {
  if (totalItems.value === 0) return 0
  return Math.min(currentPage.value * PER_PAGE, totalItems.value)
})

const visiblePages = computed(() => {
  const pages: number[] = []
  const start = Math.max(1, currentPage.value - 2)
  const end = Math.min(totalPages.value, currentPage.value + 2)

  for (let page = start; page <= end; page++) {
    pages.push(page)
  }

  return pages
})

const applyPaginationFromResponse = (response: FilieresPaginatedResponse, fallbackPage: number) => {
  filieres.value = response.data || []
  totalItems.value = Number(response.total) || 0
  totalPages.value = Math.max(Number(response.last_page) || 1, 1)
  currentPage.value = Math.min(Number(response.current_page) || fallbackPage, totalPages.value)
}

const goToPage = async (page: number) => {
  if (page < 1 || page > totalPages.value || page === currentPage.value) return
  currentPage.value = page
  await loadFilieres(page)
}

const loadFilieres = async (page = currentPage.value) => {
  loading.value = true
  error.value = null

  try {
    const response = await api.get<FilieresPaginatedResponse>('/filiers', {
      page,
      per_page: PER_PAGE,
    })
    applyPaginationFromResponse(response, page)
  } catch (err: any) {
    error.value = err.message || 'Erreur lors du chargement des filieres'
    console.error('Error loading filieres:', err)
  } finally {
    loading.value = false
  }
}

watch(searchQuery, async (value, previous) => {
  if (!value.trim() && previous.trim()) {
    await loadFilieres(1)
  }
})

const openCreateModal = () => {
  createForm.value = {
    nom_filiere: '',
  }
  createError.value = null
  createSuccess.value = null
  showCreateModal.value = true
}

const closeCreateModal = () => {
  showCreateModal.value = false
  createError.value = null
  createSuccess.value = null
}

const openEditModal = (filiere: Filiere) => {
  editForm.value = {
    id: filiere.id,
    nom_filiere: filiere.nom_filiere,
  }
  editError.value = null
  editSuccess.value = null
  showEditModal.value = true
}

const closeEditModal = () => {
  showEditModal.value = false
  editError.value = null
  editSuccess.value = null
}

const updateFiliere = async () => {
  editError.value = null
  editSuccess.value = null

  if (!editForm.value.id || !editForm.value.nom_filiere.trim()) {
    editError.value = 'Veuillez remplir tous les champs obligatoires.'
    return
  }

  editSubmitting.value = true

  try {
    const payload = {
      nom_filiere: editForm.value.nom_filiere.trim(),
      id_pole: null,
    }

    const response = await api.put<{ success?: boolean; data?: Partial<Filiere> & { id?: number } }>(
      `/filiers/${editForm.value.id}`,
      payload,
    )

    const responseData = response?.data ?? null
    const updatedId = Number(responseData?.id ?? editForm.value.id)

    filieres.value = filieres.value.map((item) => {
      if (item.id !== updatedId) return item

      return {
        ...item,
        ...responseData,
        nom_filiere: responseData?.nom_filiere ?? payload.nom_filiere,
        id_pole: responseData?.id_pole ?? null,
      }
    })

    editSuccess.value = 'Filiere modifiee avec succes !'
    closeEditModal()
  } catch (err: any) {
    console.error('Error updating filiere:', err)
    if (err.data && err.data.errors) {
      const errors = Object.values(err.data.errors).flat()
      editError.value = errors.join(', ')
    } else {
      editError.value = err.message || 'Erreur lors de la mise a jour de la filiere'
    }
  } finally {
    editSubmitting.value = false
  }
}

const createFiliere = async () => {
  createError.value = null
  createSuccess.value = null

  if (!createForm.value.nom_filiere.trim()) {
    createError.value = 'Veuillez remplir tous les champs obligatoires.'
    return
  }

  createSubmitting.value = true

  try {
    const payload = {
      nom_filiere: createForm.value.nom_filiere.trim(),
      id_pole: null,
    }

    const response = await api.post<{ success?: boolean; data?: Partial<Filiere> & { id?: number } }>('/filiers', payload)

    const responseData = response?.data ?? {}
    const createdFiliere: Filiere = {
      id: Number(responseData.id ?? Date.now()),
      nom_filiere: responseData.nom_filiere ?? payload.nom_filiere,
      id_pole: responseData.id_pole ?? null,
      pole: responseData.pole,
    }

    totalItems.value += 1
    totalPages.value = Math.max(Math.ceil(totalItems.value / PER_PAGE), 1)

    if (currentPage.value === 1) {
      filieres.value = [createdFiliere, ...filieres.value].slice(0, PER_PAGE)
    }

    createSuccess.value = 'Filiere creee avec succes !'
    closeCreateModal()
  } catch (err: any) {
    console.error('Error creating filiere:', err)
    if (err.data && err.data.errors) {
      const errors = Object.values(err.data.errors).flat()
      createError.value = errors.join(', ')
    } else {
      createError.value = err.message || 'Erreur lors de la creation de la filiere'
    }
  } finally {
    createSubmitting.value = false
  }
}

const deleteFiliere = async (filiere: Filiere) => {
  if (!confirm(`Supprimer la filiere "${filiere.nom_filiere}" ?`)) {
    return
  }

  try {
    await api.delete(`/filiers/${filiere.id}`)

    totalItems.value = Math.max(totalItems.value - 1, 0)
    totalPages.value = Math.max(Math.ceil(totalItems.value / PER_PAGE), 1)

    const targetPage = filieres.value.length === 1 && currentPage.value > 1
      ? currentPage.value - 1
      : currentPage.value

    await loadFilieres(targetPage)
  } catch (err: any) {
    alert(err.message || 'Erreur lors de la suppression')
    console.error('Error deleting filiere:', err)
  }
}

const handleLogout = async () => {
  isSidebarOpen.value = false
  await authStore.logout()
  router.push('/login')
}

onMounted(() => {
  loadFilieres(1)
})
</script>


