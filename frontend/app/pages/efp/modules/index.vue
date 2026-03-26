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
            <h1 class="text-sm font-semibold text-slate-900">Mes Modules</h1>
          </div>

          <div class="flex items-center gap-3">
            <div class="hidden text-right sm:block">
              <p class="text-sm font-semibold text-slate-800">{{ fullName }}</p>
              <p class="text-xs text-slate-500">Directeur EFP</p>
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
          <h2 class="mt-2 text-2xl font-bold text-slate-900">Modules de l'etablissement</h2>
          <p class="mt-2 text-sm text-slate-600">Les modules relies aux filieres de votre etablissement sont affiches.</p>
        </section>

        <div class="mb-6 flex justify-between gap-3">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Rechercher un module..."
            class="w-full max-w-md rounded-lg border border-slate-300 px-3 py-2 text-sm"
          />
          <div class="flex flex-wrap items-center gap-3">
            <button
              @click="openCreateModal"
              class="inline-flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-700"
            >
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Ajouter module
            </button>
            <BulkExcelPanel entity="modules" @imported="loadModules" />
          </div>
        </div>

        <div v-if="loading" class="rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm">
          <div class="inline-block h-8 w-8 animate-spin rounded-full border-b-2 border-sky-600"></div>
          <p class="mt-2 text-slate-600">Chargement...</p>
        </div>

        <div v-else-if="error" class="rounded-xl border border-red-200 bg-red-50 p-4">
          <p class="text-red-800">{{ error }}</p>
        </div>

        <div v-else-if="filteredModules.length === 0" class="rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm">
          <p class="text-slate-500">Aucun module trouve</p>
        </div>

        <div v-else class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
              <thead class="bg-slate-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Nom module</th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Volume horaire</th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Filiere</th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Formateurs affectes</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 bg-white">
                <tr v-for="moduleItem in filteredModules" :key="moduleItem.id" class="hover:bg-slate-50">
                  <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">{{ moduleItem.nom_module }}</td>
                  <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">{{ moduleItem.volume_horaire }}</td>
                  <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">{{ moduleItem.filiere?.nom_filiere || '-' }}</td>
                  <td class="px-6 py-4 text-sm text-slate-700">
                    <span v-if="getAssignedFormateurs(moduleItem.id).length === 0" class="text-slate-400">Aucun</span>
                    <div v-else class="flex flex-wrap gap-1.5">
                      <span
                        v-for="name in getAssignedFormateurs(moduleItem.id)"
                        :key="`${moduleItem.id}-${name}`"
                        class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-1 text-xs font-medium text-sky-700"
                      >
                        {{ name }}
                      </span>
                    </div>
                  </td>
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
              <h3 class="text-lg font-semibold text-slate-900">Creer Un Module</h3>
              <button @click="closeCreateModal" class="text-slate-500 hover:text-slate-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <form @submit.prevent="createModule" class="p-6 space-y-4">
              <div v-if="createError" class="bg-red-50 border border-red-200 rounded-lg p-3">
                <p class="text-sm text-red-800">{{ createError }}</p>
              </div>

              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Nom module</label>
                <input v-model="createForm.nom_module" required class="w-full rounded-lg border border-slate-300 px-3 py-2" type="text" />
              </div>

              <div class="grid gap-4 sm:grid-cols-2">
                <div>
                  <label class="mb-1 block text-sm font-medium text-slate-700">Volume horaire</label>
                  <input v-model.number="createForm.volume_horaire" required min="1" class="w-full rounded-lg border border-slate-300 px-3 py-2" type="number" />
                </div>
                <div>
                  <label class="mb-1 block text-sm font-medium text-slate-700">Filiere</label>
                  <select v-model="createForm.id_filiere" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option disabled value="">Selectionner une filiere</option>
                    <option v-for="filiere in filieres" :key="filiere.id" :value="filiere.id">
                      {{ filiere.nom_filiere }}
                    </option>
                  </select>
                </div>
              </div>

              <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <button type="button" @click="closeCreateModal" class="px-4 py-2 border border-slate-300 text-slate-700 font-medium rounded-lg hover:bg-slate-50 transition-colors">
                  Annuler
                </button>
                <button :disabled="createSubmitting" type="submit" class="px-4 py-2 bg-sky-600 text-white font-medium rounded-lg hover:bg-sky-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                  {{ createSubmitting ? 'Creation...' : 'Creer module' }}
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
import BulkExcelPanel from '../../../components/BulkExcelPanel.vue'

definePageMeta({ middleware: ['auth'] })

interface FiliereItem {
  id: number
  nom_filiere: string
}

interface ModuleItem {
  id: number
  nom_module: string
  volume_horaire: number
  filiere?: FiliereItem
}

interface AssignmentItem {
  id: number
  id_module: number
  formateur?: {
    id?: number
    user?: {
      nom?: string
      prenom?: string
    }
  }
}

const api = useApi()
const router = useRouter()
const authStore = useAuthStore()

const isSidebarOpen = ref(false)
const loading = ref(true)
const error = ref<string | null>(null)
const searchQuery = ref('')
const modules = ref<ModuleItem[]>([])
const assignments = ref<AssignmentItem[]>([])
const filieres = ref<FiliereItem[]>([])
const showCreateModal = ref(false)
const createSubmitting = ref(false)
const createError = ref<string | null>(null)
const createForm = ref({
  nom_module: '',
  volume_horaire: 1,
  id_filiere: '' as number | '',
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

const filteredModules = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  if (!query) return modules.value

  return modules.value.filter((moduleItem) => {
    return (
      moduleItem.nom_module.toLowerCase().includes(query) ||
      String(moduleItem.volume_horaire).includes(query) ||
      (moduleItem.filiere?.nom_filiere || '').toLowerCase().includes(query)
    )
  })
})

const getAssignedFormateurs = (moduleId: number) => {
  const names = assignments.value
    .filter((assignment) => assignment.id_module === moduleId)
    .map((assignment) => {
      const prenom = assignment.formateur?.user?.prenom || ''
      const nom = assignment.formateur?.user?.nom || ''
      const fullName = `${prenom} ${nom}`.trim()
      return fullName || null
    })
    .filter((name): name is string => !!name)

  return Array.from(new Set(names))
}

const loadModules = async () => {
  loading.value = true
  error.value = null

  try {
    const [modulesRes, assignmentsRes] = await Promise.all([
      api.get<any>('/modules'),
      api.get<any>('/module-formateur'),
    ])

    modules.value = extractArray<ModuleItem>(modulesRes)
    assignments.value = extractArray<AssignmentItem>(assignmentsRes)
  } catch (err: any) {
    error.value = err?.data?.message || err?.message || 'Erreur lors du chargement des modules'
  } finally {
    loading.value = false
  }
}

const loadFilieres = async () => {
  try {
    const response = await api.get<any>('/filiers')
    filieres.value = extractArray<FiliereItem>(response)
  } catch (err: any) {
    console.error('Error loading filieres:', err)
  }
}

const openCreateModal = () => {
  createError.value = null
  createForm.value = {
    nom_module: '',
    volume_horaire: 1,
    id_filiere: '',
  }
  showCreateModal.value = true
}

const closeCreateModal = () => {
  showCreateModal.value = false
  createError.value = null
}

const createModule = async () => {
  if (!createForm.value.id_filiere) {
    createError.value = 'Selectionnez une filiere'
    return
  }

  createSubmitting.value = true
  createError.value = null

  try {
    await api.post('/modules', {
      nom_module: createForm.value.nom_module,
      volume_horaire: createForm.value.volume_horaire,
      id_filiere: createForm.value.id_filiere,
      id_formation: createForm.value.id_filiere,
    })

    closeCreateModal()
    await loadModules()
  } catch (err: any) {
    createError.value = err?.data?.message || err?.message || 'Erreur lors de la creation du module'
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
  loadModules()
  loadFilieres()
})
</script>
