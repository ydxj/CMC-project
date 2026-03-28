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
            <h1 class="text-sm font-semibold text-slate-900">Nouveaux directeurs</h1>
          </div>

          <button
            @click="handleLogout"
            class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100"
          >
            Logout
          </button>
        </div>
      </header>

      <main class="px-4 py-6 sm:px-6 lg:px-8">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
          <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-600">DOSI</p>
              <h2 class="mt-2 text-2xl font-bold text-slate-900">Gestion des directeurs</h2>
              <p class="mt-2 text-sm text-slate-600">Creation claire et affectation automatique selon le type d etablissement.</p>
            </div>

            <button
              type="button"
              class="inline-flex items-center justify-center rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700"
              @click="openCreateModal"
            >
              Nouveau directeur
            </button>
          </div>

          <div class="mt-5 grid gap-3 sm:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
              <p class="text-xs uppercase tracking-wide text-slate-500">Directeurs affectes</p>
              <p class="mt-1 text-2xl font-bold text-slate-900">{{ totalDirecteursCount }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
              <p class="text-xs uppercase tracking-wide text-slate-500">Etablissements sans directeur</p>
              <p class="mt-1 text-2xl font-bold text-slate-900">{{ availableEtablissements.length }}</p>
            </div>
          </div>

          <p v-if="error" class="mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ error }}</p>
          <p v-if="success" class="mt-4 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700">{{ success }}</p>
        </section>

        <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-200 px-5 py-4">
            <h3 class="text-sm font-semibold text-slate-900">Directeurs affectes</h3>
          </div>

          <div v-if="loadingPage" class="p-6 text-sm text-slate-600">Chargement...</div>
          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
              <thead class="bg-slate-50 text-slate-600">
                <tr>
                  <th class="px-4 py-3 text-left">Nom</th>
                  <th class="px-4 py-3 text-left">Email</th>
                  <th class="px-4 py-3 text-left">Role</th>
                  <th class="px-4 py-3 text-left">Etablissement</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 bg-white">
                <tr v-for="d in assignedDirecteurs" :key="d.id">
                  <td class="px-4 py-3">{{ d.prenom }} {{ d.nom }}</td>
                  <td class="px-4 py-3">{{ d.email }}</td>
                  <td class="px-4 py-3">
                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">{{ roleLabel(d.role) }}</span>
                  </td>
                  <td class="px-4 py-3">{{ etablissementNameById[d.id_etablissement || 0] || 'Non affecte' }}</td>
                </tr>
                <tr v-if="!assignedDirecteurs.length">
                  <td colspan="4" class="px-4 py-8 text-center text-slate-500">Aucun directeur affecte</td>
                </tr>
              </tbody>
            </table>

            <!-- Pagination Controls -->
            <div class="border-t border-slate-200 bg-slate-50 px-4 py-3 flex items-center justify-between text-sm">
              <div class="text-slate-600">
                Page {{ dirteursCurrentPage }} / {{ totalDirecteursPages }}
              </div>
              <div class="flex gap-2">
                <button
                  type="button"
                  @click="gotoPreviousDirecteurPage"
                  :disabled="dirteursCurrentPage <= 1 || loadingPage"
                  class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50"
                >
                  Precedent
                </button>
                <button
                  type="button"
                  @click="gotoNextDirecteurPage"
                  :disabled="dirteursCurrentPage >= totalDirecteursPages || loadingPage"
                  class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50"
                >
                  Suivant
                </button>
              </div>
            </div>
          </div>
        </section>
      </main>
    </div>

    <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-opacity duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="isCreateModalOpen" class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-[1px]" @click="closeCreateModal" />
    </Transition>

    <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="scale-95 opacity-0" enter-to-class="scale-100 opacity-100" leave-active-class="transition duration-200 ease-in" leave-from-class="scale-100 opacity-100" leave-to-class="scale-95 opacity-0">
      <div v-if="isCreateModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="w-full max-w-3xl rounded-2xl border border-slate-200 bg-white shadow-2xl">
          <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <div>
              <h3 class="text-lg font-bold text-slate-900">Creer un directeur</h3>
              <p class="mt-1 text-xs text-slate-500">Le role est defini automatiquement selon le type d etablissement.</p>
            </div>
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

          <form class="grid gap-4 p-6 md:grid-cols-2" @submit.prevent="createDirecteur">
            <div class="md:col-span-2" ref="etablissementDropdownRef">
              <label class="mb-1 block text-sm font-medium text-slate-700">Etablissement</label>
              <div class="relative">
                <button
                  type="button"
                  :disabled="loadingModal"
                  class="flex w-full items-center justify-between rounded-lg border border-slate-300 bg-white px-4 py-2 text-left text-slate-900 disabled:cursor-not-allowed disabled:bg-slate-100"
                  @click="isEtablissementDropdownOpen = !isEtablissementDropdownOpen"
                >
                  <span :class="selectedEtablissement ? 'text-slate-900' : 'text-slate-500'">{{ selectedEtablissementLabel }}</span>
                  <svg class="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 8l4 4 4-4" />
                  </svg>
                </button>

                <div v-if="isEtablissementDropdownOpen" class="absolute z-20 mt-1 w-full rounded-lg border border-slate-200 bg-white p-2 shadow-xl">
                  <input
                    v-model="etablissementSearch"
                    type="text"
                    placeholder="Rechercher un etablissement..."
                    class="mb-2 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-transparent focus:ring-2 focus:ring-sky-500"
                  >

                  <div class="max-h-52 overflow-y-auto rounded-md border border-slate-100">
                    <button
                      v-for="e in filteredAvailableEtablissements"
                      :key="e.id"
                      type="button"
                      class="block w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100"
                      :class="form.id_etablissement === e.id ? 'bg-sky-50 text-sky-700' : ''"
                      @click="selectEtablissement(e.id)"
                    >
                      {{ e.nom_etablissement }} ({{ e.type_etablissement }})
                    </button>

                    <p v-if="loadingModal" class="px-3 py-2 text-sm text-slate-500">Chargement...</p>
                    <p v-else-if="!filteredAvailableEtablissements.length" class="px-3 py-2 text-sm text-slate-500">Aucun etablissement disponible.</p>
                  </div>
                </div>
              </div>
              <p class="mt-1 text-xs text-slate-500">Seuls les etablissements sans directeur sont proposes.</p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Matricule</label>
              <input v-model="form.matricule" required type="text" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 focus:border-transparent focus:ring-2 focus:ring-sky-500">
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Role applique</label>
              <input :value="resolvedRoleLabel" disabled type="text" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2 text-slate-700">
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Nom</label>
              <input v-model="form.nom" required type="text" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 focus:border-transparent focus:ring-2 focus:ring-sky-500">
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Prenom</label>
              <input v-model="form.prenom" required type="text" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 focus:border-transparent focus:ring-2 focus:ring-sky-500">
            </div>

            <div class="md:col-span-2">
              <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
              <input v-model="form.email" required type="email" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 focus:border-transparent focus:ring-2 focus:ring-sky-500">
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Mot de passe</label>
              <input v-model="form.password" required minlength="6" type="password" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 focus:border-transparent focus:ring-2 focus:ring-sky-500">
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Confirmer mot de passe</label>
              <input v-model="form.password_confirmation" required minlength="6" type="password" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 focus:border-transparent focus:ring-2 focus:ring-sky-500">
              <p v-if="passwordMismatch" class="mt-1 text-xs text-red-600">Les mots de passe ne correspondent pas.</p>
            </div>

            <div class="md:col-span-2 flex items-center justify-end gap-3 pt-2">
              <button
                type="button"
                class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"
                @click="closeCreateModal"
              >
                Annuler
              </button>
              <button
                type="submit"
                :disabled="submitting || passwordMismatch || !selectedEtablissement"
                class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-50"
              >
                {{ submitting ? 'Creation...' : 'Creer et affecter directeur' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>

    <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-opacity duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="isSidebarOpen" class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden" @click="isSidebarOpen = false" />
    </Transition>

    <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="-translate-x-full opacity-0" enter-to-class="translate-x-0 opacity-100" leave-active-class="transition duration-200 ease-in" leave-from-class="translate-x-0 opacity-100" leave-to-class="-translate-x-full opacity-0">
      <aside v-if="isSidebarOpen" class="fixed left-0 top-0 z-50 h-full w-[86%] max-w-[320px] lg:hidden">
        <div class="h-full overflow-y-auto border-r border-slate-200 bg-white shadow-2xl">
          <RoleSidebar :role="authStore.user?.role" @navigate="isSidebarOpen = false" />
        </div>
      </aside>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '../../../../composables/useApi'
import { useAuthStore } from '../../../../stores/auth'

definePageMeta({ middleware: ['auth'] })

interface DirecteurItem {
  id: number
  nom: string
  prenom: string
  email: string
  role: string
  id_etablissement: number | null
}

interface EtablissementItem {
  id: number
  nom_etablissement: string
  type_etablissement: 'CMC' | 'EFP'
  id_directeur: number | null
}

const router = useRouter()
const api = useApi()
const authStore = useAuthStore()

const isSidebarOpen = ref(false)
const isCreateModalOpen = ref(false)
const isEtablissementDropdownOpen = ref(false)

const loadingPage = ref(false)
const loadingModal = ref(false)
const submitting = ref(false)
const error = ref('')
const success = ref('')

const etablissementSearch = ref('')
const etablissementDropdownRef = ref<HTMLElement | null>(null)

const directeurs = ref<DirecteurItem[]>([])
const etablissements = ref<EtablissementItem[]>([])

const dirteursCurrentPage = ref(1)
const totalDirecteursPages = ref(1)
const totalDirecteursCount = ref(0)
const directeurPageSize = 25

const form = ref({
  matricule: '',
  nom: '',
  prenom: '',
  email: '',
  password: '',
  password_confirmation: '',
  id_etablissement: 0,
})

const extractArray = <T>(payload: T[] | { data?: T[] }): T[] => {
  return Array.isArray(payload) ? payload : (payload.data || [])
}

const roleLabel = (role: string) => {
  if (role === 'DIRECTEUR_CMC') return 'Directeur CMC'
  if (role === 'DIRECTEUR_EFP') return 'Directeur EFP'
  return role
}

const etablissementNameById = computed<Record<number, string>>(() => {
  return etablissements.value.reduce((acc, etablissement) => {
    acc[etablissement.id] = etablissement.nom_etablissement
    return acc
  }, {} as Record<number, string>)
})

const assignedDirecteurs = computed(() => {
  return directeurs.value.filter((d) => (d.role === 'DIRECTEUR_CMC' || d.role === 'DIRECTEUR_EFP') && !!d.id_etablissement)
})

const availableEtablissements = computed(() => {
  return etablissements.value.filter((e) => !e.id_directeur)
})

const filteredAvailableEtablissements = computed(() => {
  const query = etablissementSearch.value.trim().toLowerCase()
  if (!query) return availableEtablissements.value

  return availableEtablissements.value.filter((e) => e.nom_etablissement.toLowerCase().includes(query))
})

const selectedEtablissement = computed(() => {
  return etablissements.value.find((e) => e.id === form.value.id_etablissement)
})

const selectedEtablissementLabel = computed(() => {
  if (!selectedEtablissement.value) return 'Selectionner un etablissement'
  return `${selectedEtablissement.value.nom_etablissement} (${selectedEtablissement.value.type_etablissement})`
})

const resolvedRole = computed(() => {
  if (!selectedEtablissement.value) return ''
  return selectedEtablissement.value.type_etablissement === 'CMC' ? 'DIRECTEUR_CMC' : 'DIRECTEUR_EFP'
})

const resolvedRoleLabel = computed(() => {
  if (resolvedRole.value === 'DIRECTEUR_CMC') return 'Directeur CMC'
  if (resolvedRole.value === 'DIRECTEUR_EFP') return 'Directeur EFP'
  return 'Choisissez un etablissement'
})

const passwordMismatch = computed(() => {
  return form.value.password !== '' && form.value.password_confirmation !== '' && form.value.password !== form.value.password_confirmation
})

const resetForm = () => {
  form.value = {
    matricule: '',
    nom: '',
    prenom: '',
    email: '',
    password: '',
    password_confirmation: '',
    id_etablissement: 0,
  }

  etablissementSearch.value = ''
  isEtablissementDropdownOpen.value = false
}

const loadDirecteursOnly = async (page = 1) => {
  const [cmcResponse, efpResponse] = await Promise.all([
    api.get<DirecteurItem[] | { data: DirecteurItem[]; last_page?: number; total?: number }>('/users', { role: 'DIRECTEUR_CMC', page, per_page: directeurPageSize }),
    api.get<DirecteurItem[] | { data: DirecteurItem[]; last_page?: number; total?: number }>('/users', { role: 'DIRECTEUR_EFP', page, per_page: directeurPageSize }),
  ])

  const cmcData = cmcResponse as any
  const efpData = efpResponse as any
  
  const cmcDirecteurs = Array.isArray(cmcResponse) ? cmcResponse : (cmcData.data || [])
  const efpDirecteurs = Array.isArray(efpResponse) ? efpResponse : (efpData.data || [])
  
  const merged = [...cmcDirecteurs, ...efpDirecteurs]
  const mapById = new Map<number, DirecteurItem>()

  for (const directeur of merged) {
    mapById.set(directeur.id, directeur)
  }

  directeurs.value = Array.from(mapById.values())
  
  // Get last_page and total from either response (both should have the same pagination)
  const lastPage = Math.max(cmcData?.last_page || 1, efpData?.last_page || 1)
  const total = Math.max(cmcData?.total || 0, efpData?.total || 0)
  
  totalDirecteursPages.value = lastPage || 1
  totalDirecteursCount.value = total || 0
  dirteursCurrentPage.value = page
}

const loadEtablissements = async () => {
  const etabResponse = await api.get<EtablissementItem[] | { data: EtablissementItem[] }>('/etablissements')
  etablissements.value = extractArray(etabResponse)
}

const loadPageData = async () => {
  loadingPage.value = true
  error.value = ''

  try {
    await Promise.all([loadDirecteursOnly(1), loadEtablissements()])
  } catch (err: any) {
    error.value = err?.data?.message || err?.message || 'Erreur lors du chargement des donnees.'
  } finally {
    loadingPage.value = false
  }
}

const openCreateModal = async () => {
  resetForm()
  isCreateModalOpen.value = true
  loadingModal.value = true

  try {
    await loadEtablissements()
  } catch (err: any) {
    error.value = err?.data?.message || err?.message || 'Impossible de charger les etablissements.'
  } finally {
    loadingModal.value = false
  }
}

const closeCreateModal = () => {
  isCreateModalOpen.value = false
  isEtablissementDropdownOpen.value = false
}

const selectEtablissement = (id: number) => {
  form.value.id_etablissement = id
  isEtablissementDropdownOpen.value = false
}

const createDirecteur = async () => {
  if (!selectedEtablissement.value) {
    error.value = 'Veuillez selectionner un etablissement.'
    return
  }

  submitting.value = true
  error.value = ''
  success.value = ''

  try {
    const createResponse = await api.post<{ data?: { id?: number }; id?: number }>('/users', {
      matricule: form.value.matricule,
      nom: form.value.nom,
      prenom: form.value.prenom,
      email: form.value.email,
      password: form.value.password,
      role: resolvedRole.value,
      id_etablissement: selectedEtablissement.value.id,
    })

    const createdId = createResponse?.data?.id || createResponse?.id
    if (!createdId) {
      throw new Error('Creation du directeur reussie mais id utilisateur introuvable.')
    }

    await api.put(`/etablissements/${selectedEtablissement.value.id}`, {
      id_directeur: createdId,
    })

    success.value = 'Directeur cree et affecte avec succes.'
    closeCreateModal()
    resetForm()

    await loadPageData()
  } catch (err: any) {
    error.value = err?.data?.message || err?.message || 'Impossible de creer ce directeur.'
  } finally {
    submitting.value = false
  }
}

const goToDirecteurPage = async (page: number) => {
  if (page < 1 || page > totalDirecteursPages.value) return
  loadingPage.value = true
  error.value = ''
  
  try {
    await loadDirecteursOnly(page)
  } catch (err: any) {
    error.value = err?.data?.message || err?.message || 'Erreur lors du chargement de la page.'
  } finally {
    loadingPage.value = false
  }
}

const gotoPreviousDirecteurPage = async () => {
  await goToDirecteurPage(dirteursCurrentPage.value - 1)
}

const gotoNextDirecteurPage = async () => {
  await goToDirecteurPage(dirteursCurrentPage.value + 1)
}

const handleDocumentClick = (event: MouseEvent) => {
  const target = event.target as Node | null
  if (!target || !etablissementDropdownRef.value) return

  if (!etablissementDropdownRef.value.contains(target)) {
    isEtablissementDropdownOpen.value = false
  }
}

const handleLogout = async () => {
  isSidebarOpen.value = false
  await authStore.logout()
  router.push('/login')
}

onMounted(async () => {
  document.addEventListener('click', handleDocumentClick)

  authStore.initializeAuth()
  if (!authStore.isAuthenticated) {
    router.push('/login')
    return
  }

  await authStore.checkAuth()
  if (authStore.user?.role !== 'DOSI') {
    router.push('/dashboard')
    return
  }

  await loadPageData()
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleDocumentClick)
})
</script>
