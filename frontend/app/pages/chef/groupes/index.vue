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
            <h1 class="text-sm font-semibold text-slate-900">Mes Groupes</h1>
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
          <h2 class="mt-2 text-2xl font-bold text-slate-900">Groupes de Mon Pole</h2>
          <p class="mt-2 text-sm text-slate-600">Seuls les groupes lies a vos filieres sont affiches.</p>
        </section>

        <div class="mb-6 flex justify-between gap-3">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Rechercher un groupe..."
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
              Ajouter groupe
            </button>
            <BulkExcelPanel entity="groupes" @imported="loadGroupes" />
          </div>
        </div>

        <div v-if="loading" class="rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm">
          <div class="inline-block h-8 w-8 animate-spin rounded-full border-b-2 border-sky-600"></div>
          <p class="mt-2 text-slate-600">Chargement...</p>
        </div>

        <div v-else-if="error" class="rounded-xl border border-red-200 bg-red-50 p-4">
          <p class="text-red-800">{{ error }}</p>
        </div>

        <div v-else-if="filteredGroupes.length === 0" class="rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm">
          <p class="text-slate-500">Aucun groupe trouve</p>
        </div>

        <div v-else class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
              <thead class="bg-slate-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Nom</th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Annee</th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Niveau</th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Creneau</th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Filiere</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 bg-white">
                <tr v-for="groupe in filteredGroupes" :key="groupe.id" class="hover:bg-slate-50">
                  <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">{{ groupe.nom_groupe }}</td>
                  <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">{{ groupe.annee }}</td>
                  <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">{{ groupe.niveau }}</td>
                  <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">{{ groupe.creneau }}</td>
                  <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">{{ groupe.filiere?.nom_filiere || '-' }}</td>
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
        <div class="w-full max-w-2xl rounded-2xl border border-slate-200 bg-white shadow-2xl">
          <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <h3 class="text-lg font-bold text-slate-900">Ajouter groupe</h3>
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

          <form class="space-y-4 p-6" @submit.prevent="createGroupeFromModal">
            <div v-if="createError" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">
              {{ createError }}
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Nom groupe</label>
              <input v-model="createForm.nom_groupe" required class="w-full rounded-lg border border-slate-300 px-3 py-2" type="text" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Annee</label>
                <input v-model="createForm.annee" required class="w-full rounded-lg border border-slate-300 px-3 py-2" type="text" placeholder="2025/2026" />
              </div>

              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Niveau</label>
                <select v-model="createForm.niveau" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                  <option v-for="item in niveaux" :key="item" :value="item">{{ item }}</option>
                </select>
              </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Creneau</label>
                <select v-model="createForm.crenau" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                  <option value="CDJ">CDJ</option>
                  <option value="CDS">CDS</option>
                </select>
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

            <div class="flex gap-3 border-t border-slate-200 pt-4">
              <button
                type="button"
                class="flex-1 rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                @click="closeCreateModal"
              >
                Annuler
              </button>
              <button
                type="submit"
                class="flex-1 rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="createSubmitting || filieres.length === 0"
              >
                {{ createSubmitting ? 'Creation...' : 'Creer groupe' }}
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
import BulkExcelPanel from '../../../components/BulkExcelPanel.vue'

definePageMeta({ middleware: ['auth'] })

interface FiliereItem {
  id: number
  nom_filiere: string
}

interface GroupeItem {
  id: number
  nom_groupe: string
  annee: string
  niveau: string
  creneau: string
  filiere?: FiliereItem
}

const api = useApi()
const router = useRouter()
const authStore = useAuthStore()

const isSidebarOpen = ref(false)
const loading = ref(true)
const error = ref<string | null>(null)
const searchQuery = ref('')
const groupes = ref<GroupeItem[]>([])
const filieres = ref<FiliereItem[]>([])
const showCreateModal = ref(false)
const createSubmitting = ref(false)
const createError = ref<string | null>(null)

const niveaux = ['TS', 'T', 'Q', 'S', 'FQ', 'BP', 'PC']

const createForm = ref({
  nom_groupe: '',
  annee: '',
  niveau: 'TS',
  crenau: 'CDJ',
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

const filteredGroupes = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  if (!query) return groupes.value

  return groupes.value.filter((groupe) => {
    return (
      groupe.nom_groupe.toLowerCase().includes(query) ||
      groupe.annee.toLowerCase().includes(query) ||
      groupe.niveau.toLowerCase().includes(query) ||
      groupe.creneau.toLowerCase().includes(query) ||
      (groupe.filiere?.nom_filiere || '').toLowerCase().includes(query)
    )
  })
})

const loadGroupes = async (options?: { silent?: boolean }) => {
  if (!options?.silent) {
    loading.value = true
  }
  error.value = null

  try {
    const response = await api.get<any>('/groupes')
    groupes.value = extractArray<GroupeItem>(response)
  } catch (err: any) {
    error.value = err?.data?.message || err?.message || 'Erreur lors du chargement des groupes'
  } finally {
    if (!options?.silent) {
      loading.value = false
    }
  }
}

const loadFilieres = async () => {
  const response = await api.get<any>('/filiers')
  filieres.value = extractArray<FiliereItem>(response)
}

const resetCreateForm = () => {
  createForm.value = {
    nom_groupe: '',
    annee: '',
    niveau: 'TS',
    crenau: 'CDJ',
    id_filiere: '',
  }
  createError.value = null
}

const openCreateModal = async () => {
  createError.value = null
  resetCreateForm()
  showCreateModal.value = true

  try {
    await loadFilieres()
  } catch (err: any) {
    createError.value = err?.data?.message || err?.message || 'Erreur lors du chargement des filieres'
  }
}

const closeCreateModal = () => {
  showCreateModal.value = false
  createError.value = null
}

const createGroupeFromModal = async () => {
  if (!createForm.value.id_filiere) {
    createError.value = 'Selectionnez une filiere'
    return
  }

  createSubmitting.value = true
  createError.value = null

  try {
    await api.post('/groupes', {
      nom_groupe: createForm.value.nom_groupe,
      annee: createForm.value.annee,
      niveau: createForm.value.niveau,
      crenau: createForm.value.crenau,
      id_filiere: createForm.value.id_filiere,
      id_formation: createForm.value.id_filiere,
    })

    await loadGroupes({ silent: true })
    closeCreateModal()
  } catch (err: any) {
    createError.value = err?.data?.message || err?.message || 'Erreur lors de la creation du groupe'
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
  loadGroupes()
})
</script>
