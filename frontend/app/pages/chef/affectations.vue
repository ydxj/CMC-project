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
            <h1 class="text-sm font-semibold text-slate-900">Affectation des formateurs</h1>
          </div>

          <div class="flex items-center gap-3">
            <div class="hidden text-right sm:block">
              <p class="text-sm font-semibold text-slate-800">{{ fullName }}</p>
              <p class="text-xs text-slate-500">{{ authStore.user?.role }}</p>
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
          <h2 class="mt-2 text-2xl font-bold text-slate-900">Affectation des formateurs aux modules</h2>
          <p class="mt-2 text-sm text-slate-600">Assignez des formateurs de votre etablissement aux modules et groupes</p>
        </section>

        <div v-if="actionError" class="mb-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          {{ actionError }}
        </div>
        <div v-if="actionMessage" class="mb-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
          {{ actionMessage }}
        </div>

        <div class="mb-6 flex items-center justify-between gap-3">
          <div class="flex-1">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Rechercher une affectation..."
              class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm placeholder-slate-400 focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
            />
          </div>
          <button
            @click="openModal"
            class="inline-flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-700"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Nouvelle affectation
          </button>
        </div>

        <!-- Assignments List with Pagination -->
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
              <thead class="bg-slate-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Module</th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Formateur</th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Groupe</th>
                  <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 bg-white">
                <tr v-if="paginatedAssignments.length === 0" class="hover:bg-slate-50">
                  <td colspan="4" class="px-6 py-4 text-center text-sm text-slate-500">Aucune affectation</td>
                </tr>
                <tr v-for="asg in paginatedAssignments" :key="asg.id" class="hover:bg-slate-50">
                  <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">{{ asg.module?.nom_module || '-' }}</td>
                  <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                    {{ asg.formateur?.user?.prenom }} {{ asg.formateur?.user?.nom }}
                  </td>
                  <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">{{ asg.groupe?.nom_groupe || '-' }}</td>
                  <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                    <button
                      @click="deleteAffectation(asg.id)"
                      class="text-red-600 hover:text-red-800"
                      title="Supprimer"
                    >
                      <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3H4v2h16V7h-3z" />
                      </svg>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-6 py-4">
            <div class="text-sm text-slate-600">
              Affichage {{ (currentPage - 1) * itemsPerPage + 1 }} à {{ Math.min(currentPage * itemsPerPage, searchedAssignments.length) }} sur {{ searchedAssignments.length }}
            </div>
            <div class="flex gap-2">
              <button
                @click="currentPage--"
                :disabled="currentPage === 1"
                class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-200 disabled:opacity-50"
              >
                Précédent
              </button>
              <div class="flex items-center gap-1">
                <span v-for="page in totalPages" :key="page" :class="[
                  'rounded px-2.5 py-1.5 text-sm font-medium',
                  page === currentPage
                    ? 'bg-sky-600 text-white'
                    : 'border border-slate-300 text-slate-700 hover:bg-slate-100'
                ]" @click="currentPage = page" style="cursor: pointer;">
                  {{ page }}
                </span>
              </div>
              <button
                @click="currentPage++"
                :disabled="currentPage === totalPages"
                class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-200 disabled:opacity-50"
              >
                Suivant
              </button>
            </div>
          </div>
        </div>
      </main>
    </div>

    <!-- Modal Backdrop -->
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="showModal"
        class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-[1px]"
        @click="closeModal"
      />
    </Transition>

    <!-- Modal -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="scale-95 opacity-0"
      enter-to-class="scale-100 opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="scale-100 opacity-100"
      leave-to-class="scale-95 opacity-0"
    >
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="w-full max-w-2xl rounded-2xl border border-slate-200 bg-white shadow-xl">
          <!-- Modal Header -->
          <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <h3 class="text-lg font-bold text-slate-900">Nouvelle affectation</h3>
            <button
              @click="closeModal"
              class="rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700"
            >
              <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Modal Body -->
          <div class="px-6 py-6">
            <div class="grid gap-6 sm:grid-cols-2">
              <div>
                <label class="mb-2 block text-base font-semibold text-slate-700">Secteur</label>
                <select
                  v-model="selectedSecteur"
                  @change="handleSecteurChange(selectedSecteur)"
                  class="w-full rounded-lg border border-slate-300 px-4 py-3 text-base focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
                >
                  <option value="">Choisir un secteur</option>
                  <option v-for="s in secteurs" :key="s.id" :value="s.id">{{ s.nom_secteur }}</option>
                </select>
              </div>

              <div>
                <label class="mb-2 block text-base font-semibold text-slate-700">Filiere</label>
                <select
                  v-model="selectedFiliere"
                  @change="handleFiliereChange(selectedFiliere)"
                  :disabled="!selectedSecteur"
                  class="w-full rounded-lg border border-slate-300 px-4 py-3 text-base focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 disabled:bg-slate-50 disabled:text-slate-500"
                >
                  <option value="">Choisir une filiere</option>
                  <option v-for="f in filieres" :key="f.id" :value="f.id">{{ f.nom_filiere }}</option>
                </select>
              </div>

              <div>
                <label class="mb-2 block text-base font-semibold text-slate-700">Module</label>
                <select
                  v-model="selectedModule"
                  :disabled="!selectedFiliere"
                  class="w-full rounded-lg border border-slate-300 px-4 py-3 text-base focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 disabled:bg-slate-50 disabled:text-slate-500"
                >
                  <option value="">Choisir un module</option>
                  <option v-for="m in modules" :key="m.id" :value="m.id">{{ m.nom_module }}</option>
                </select>
              </div>

              <div>
                <label class="mb-2 block text-base font-semibold text-slate-700">Formateur</label>
                <select
                  v-model="selectedFormateur"
                  class="w-full rounded-lg border border-slate-300 px-4 py-3 text-base focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
                >
                  <option value="">Choisir un formateur</option>
                  <option v-for="f in formateurs" :key="f.id" :value="f.id">{{ f.prenom }} {{ f.nom }}</option>
                </select>
              </div>

              <div>
                <label class="mb-2 block text-base font-semibold text-slate-700">Groupe</label>
                <select
                  v-model="selectedGroupe"
                  :disabled="!selectedFiliere"
                  class="w-full rounded-lg border border-slate-300 px-4 py-3 text-base focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 disabled:bg-slate-50 disabled:text-slate-500"
                >
                  <option value="">Choisir un groupe</option>
                  <option v-for="g in groupes" :key="g.id" :value="g.id">{{ g.nom_groupe }}</option>
                </select>
              </div>
            </div>

            <div v-if="actionError" class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
              {{ actionError }}
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
            <button
              @click="closeModal"
              class="rounded-lg border border-slate-300 px-4 py-2.5 text-base font-semibold text-slate-700 hover:bg-slate-100"
            >
              Annuler
            </button>
            <button
              @click="createAffectation"
              :disabled="!canCreate || createSubmitting"
              class="inline-flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2.5 text-base font-semibold text-white transition hover:bg-sky-700 disabled:opacity-50"
            >
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              {{ createSubmitting ? 'Création...' : 'Créer affectation' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Mobile Sidebar Overlay -->
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
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <RoleSidebar :role="authStore.user?.role" @navigate="isSidebarOpen = false" />
        </div>
      </aside>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../../stores/auth'
import { useApi } from '../../../composables/useApi'

definePageMeta({ middleware: ['auth'] })

interface Filiere {
  id: number
  nom_filiere: string
  id_secteur: number | null
}

interface Secteur {
  id: number
  nom_secteur: string
}

interface Module {
  id: number
  nom_module: string
  id_filiere: number
}

interface Formateur {
  id: number
  nom: string
  prenom: string
}

interface Groupe {
  id: number
  nom_groupe: string
  id_filiere: number
}

interface Assignment {
  id: number
  id_module: number
  id_formateur: number
  id_groupe: number
  id_etablissement: number
  module?: Module & { filiere?: Filiere }
  formateur?: { user?: { nom: string; prenom: string } }
  groupe?: Groupe
}

const api = useApi()
const router = useRouter()
const authStore = useAuthStore()

const isSidebarOpen = ref(false)
const showModal = ref(false)
const secteurs = ref<Secteur[]>([])
const filieres = ref<Filiere[]>([])
const modules = ref<Module[]>([])
const formateurs = ref<Formateur[]>([])
const groupes = ref<Groupe[]>([])
const assignments = ref<Assignment[]>([])

const selectedSecteur = ref<number | ''>('')
const selectedFiliere = ref<number | ''>('')
const selectedModule = ref<number | ''>('')
const selectedFormateur = ref<number | ''>('')
const selectedGroupe = ref<number | ''>('')

const searchQuery = ref('')
const currentPage = ref(1)
const itemsPerPage = 25

const createSubmitting = ref(false)
const actionMessage = ref('')
const actionError = ref('')

const fullName = computed(() => {
  if (!authStore.user) return 'Utilisateur'
  return `${authStore.user.nom} ${authStore.user.prenom}`.trim()
})

const userInitials = computed(() => {
  if (!authStore.user) return 'CF'
  const nom = authStore.user.nom?.charAt(0) || ''
  const prenom = authStore.user.prenom?.charAt(0) || ''
  return `${nom}${prenom}`.toUpperCase() || 'CF'
})

const searchedAssignments = computed(() => {
  if (!searchQuery.value) return assignments.value
  const query = searchQuery.value.toLowerCase()
  return assignments.value.filter(a =>
    (a.module?.nom_module?.toLowerCase().includes(query)) ||
    (a.formateur?.user?.prenom?.toLowerCase().includes(query)) ||
    (a.formateur?.user?.nom?.toLowerCase().includes(query)) ||
    (a.groupe?.nom_groupe?.toLowerCase().includes(query))
  )
})

const totalPages = computed(() => {
  return Math.ceil(searchedAssignments.value.length / itemsPerPage)
})

const paginatedAssignments = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return searchedAssignments.value.slice(start, end)
})

const canCreate = computed(() => {
  return Boolean(selectedModule.value && selectedFormateur.value && selectedGroupe.value)
})

const handleLogout = async () => {
  await authStore.logout()
  await router.push('/login')
}

const openModal = () => {
  showModal.value = true
  actionError.value = ''
}

const closeModal = () => {
  showModal.value = false
  resetSelection()
}

const loadData = async () => {
  try {
    actionError.value = ''
    const response = await api.get<any>('/module-formateur/affectation-form-data')
    const data = response.data || response
    secteurs.value = data.secteurs || []
    formateurs.value = data.formateurs || []
  } catch (err) {
    actionError.value = 'Impossible de charger les donnees de la page.'
    console.error('Error loading affectation data:', err)
  }
}

const loadFilieresBySecteur = async (id_secteur: number) => {
  try {
    const response = await api.get<any>(`/module-formateur/filieres/${id_secteur}`)
    const data = response.data || response
    filieres.value = data.filieres || []
  } catch (err) {
    console.error('Error loading filieres:', err)
  }
}

const loadModulesByFiliere = async (id_filiere: number) => {
  try {
    const response = await api.get<any>(`/module-formateur/modules/${id_filiere}`)
    const data = response.data || response
    modules.value = data.modules || []
  } catch (err) {
    console.error('Error loading modules:', err)
  }
}

const loadGroupesByFiliere = async (id_filiere: number) => {
  try {
    const response = await api.get<any>(`/module-formateur/groupes/${id_filiere}`)
    const data = response.data || response
    groupes.value = data.groupes || []
  } catch (err) {
    console.error('Error loading groupes:', err)
  }
}

const handleSecteurChange = async (id_secteur: number | '') => {
  selectedFiliere.value = ''
  selectedModule.value = ''
  selectedFormateur.value = ''
  selectedGroupe.value = ''

  filieres.value = []
  modules.value = []
  groupes.value = []

  if (id_secteur) {
    await loadFilieresBySecteur(Number(id_secteur))
  }
}

const handleFiliereChange = async (id_filiere: number | '') => {
  selectedModule.value = ''
  selectedFormateur.value = ''
  selectedGroupe.value = ''

  modules.value = []
  groupes.value = []

  if (id_filiere) {
    await loadModulesByFiliere(Number(id_filiere))
    await loadGroupesByFiliere(Number(id_filiere))
  }
}

const loadAssignments = async () => {
  try {
    const response = await api.get<any>('/module-formateur')
    assignments.value = Array.isArray(response) ? response : response.data || []
    currentPage.value = 1
  } catch (err) {
    console.error('Error loading assignments:', err)
  }
}

const createAffectation = async () => {
  if (!canCreate.value) {
    actionError.value = 'Veuillez remplir tous les champs.'
    return
  }

  actionMessage.value = ''
  actionError.value = ''
  createSubmitting.value = true
  try {
    await api.post('/module-formateur', {
      id_module: selectedModule.value,
      id_formateur: selectedFormateur.value,
      id_groupe: selectedGroupe.value,
    })

    await loadAssignments()
    closeModal()
    actionMessage.value = 'Affectation creee avec succes.'
    setTimeout(() => { actionMessage.value = '' }, 3000)
  } catch (err) {
    const apiErr = err as any
    actionError.value = apiErr?.data?.message || apiErr?.message || 'Erreur lors de la creation de l affectation.'
    console.error('Error creating affectation:', err)
  } finally {
    createSubmitting.value = false
  }
}

const deleteAffectation = async (id: number) => {
  if (!confirm('Supprimer cette affectation?')) return

  try {
    await api.delete(`/module-formateur/${id}`)
    await loadAssignments()
    actionMessage.value = 'Affectation supprimee.'
    actionError.value = ''
    setTimeout(() => { actionMessage.value = '' }, 3000)
  } catch (err) {
    actionError.value = 'Suppression impossible pour le moment.'
    console.error('Error deleting affectation:', err)
  }
}

const resetSelection = () => {
  selectedSecteur.value = ''
  selectedFiliere.value = ''
  selectedModule.value = ''
  selectedFormateur.value = ''
  selectedGroupe.value = ''
  filieres.value = []
  modules.value = []
  groupes.value = []
}

onMounted(() => {
  loadData()
  loadAssignments()
})
</script>
