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
            <h1 class="text-sm font-semibold text-slate-900">Assigner Module a Formateur</h1>
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
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-600">Affectation</p>
          <h2 class="mt-2 text-2xl font-bold text-slate-900">Assigner un module</h2>
          <p class="mt-2 text-sm text-slate-600">Selectionnez module, groupe et formateur de votre etablissement.</p>
        </section>

        <div v-if="error" class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">
          {{ error }}
        </div>

        <form class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" @submit.prevent="submitAssignment">
          <div class="grid gap-4 md:grid-cols-3">
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Module</label>
              <div class="relative">
                <input
                  v-model="moduleSearchQuery"
                  type="text"
                  placeholder="Rechercher un module..."
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                  @focus="isModuleDropdownOpen = true"
                  @input="onModuleSearchInput"
                  @blur="closeModuleDropdown"
                />

                <div
                  v-if="isModuleDropdownOpen"
                  class="absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg"
                >
                  <button
                    v-for="moduleItem in filteredModuleOptions"
                    :key="moduleItem.id"
                    type="button"
                    class="block w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100"
                    @mousedown.prevent="selectModule(moduleItem)"
                  >
                    {{ moduleItem.nom_module }}
                  </button>

                  <p v-if="filteredModuleOptions.length === 0" class="px-3 py-2 text-sm text-slate-400">
                    Aucun module trouve
                  </p>
                </div>
              </div>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Groupe</label>
              <div class="relative">
                <input
                  v-model="groupeSearchQuery"
                  type="text"
                  placeholder="Rechercher un groupe..."
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                  @focus="isGroupeDropdownOpen = true"
                  @input="onGroupeSearchInput"
                  @blur="closeGroupeDropdown"
                />

                <div
                  v-if="isGroupeDropdownOpen"
                  class="absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg"
                >
                  <button
                    v-for="groupe in filteredGroupeOptions"
                    :key="groupe.id"
                    type="button"
                    class="block w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100"
                    @mousedown.prevent="selectGroupe(groupe)"
                  >
                    {{ groupe.nom_groupe }}
                  </button>

                  <p v-if="filteredGroupeOptions.length === 0" class="px-3 py-2 text-sm text-slate-400">
                    Aucun groupe trouve
                  </p>
                </div>
              </div>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Formateur</label>
              <div class="relative">
                <input
                  v-model="formateurSearchQuery"
                  type="text"
                  placeholder="Rechercher un formateur..."
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                  @focus="isFormateurDropdownOpen = true"
                  @input="onFormateurSearchInput"
                  @blur="closeFormateurDropdown"
                />

                <div
                  v-if="isFormateurDropdownOpen"
                  class="absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg"
                >
                  <button
                    v-for="formateur in filteredFormateurOptions"
                    :key="formateur.id"
                    type="button"
                    class="block w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100"
                    @mousedown.prevent="selectFormateur(formateur)"
                  >
                    {{ formateur.user?.prenom }} {{ formateur.user?.nom }}
                  </button>

                  <p v-if="filteredFormateurOptions.length === 0" class="px-3 py-2 text-sm text-slate-400">
                    Aucun formateur trouve
                  </p>
                </div>
              </div>
            </div>
          </div>

          <div>
            <button :disabled="submitting" type="submit" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700 disabled:opacity-60">
              {{ submitting ? 'Affectation...' : 'Assigner' }}
            </button>
          </div>
        </form>

        <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
          <h3 class="text-lg font-bold text-slate-900">Affectations existantes</h3>

          <div v-if="loading" class="py-8 text-center">
            <div class="inline-block h-8 w-8 animate-spin rounded-full border-b-2 border-sky-600"></div>
            <p class="mt-2 text-slate-600">Chargement...</p>
          </div>

          <div v-else-if="assignments.length === 0" class="py-8 text-center text-slate-500">
            Aucune affectation trouvee
          </div>

          <div v-else class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
              <thead class="bg-slate-50">
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Module</th>
                  <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Groupe</th>
                  <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Formateur</th>
                  <th class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Action</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 bg-white">
                <tr v-for="item in assignments" :key="item.id">
                  <td class="px-4 py-3 text-sm text-slate-800">{{ item.module?.nom_module || '-' }}</td>
                  <td class="px-4 py-3 text-sm text-slate-800">{{ item.groupe?.nom_groupe || '-' }}</td>
                  <td class="px-4 py-3 text-sm text-slate-800">{{ item.formateur?.user?.prenom }} {{ item.formateur?.user?.nom }}</td>
                  <td class="px-4 py-3 text-right">
                    <button
                      @click="deleteAssignment(item.id)"
                      class="text-sm font-semibold text-red-600 hover:text-red-700"
                    >
                      Supprimer
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
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
import { useApi } from '../../../../composables/useApi'
import { useAuthStore } from '../../../../stores/auth'

definePageMeta({ middleware: ['auth'] })

interface ModuleItem {
  id: number
  nom_module: string
}

interface GroupeItem {
  id: number
  nom_groupe: string
}

interface FormateurItem {
  id: number
  user?: {
    nom?: string
    prenom?: string
  }
}

interface FormateurUserRow {
  id: number
  nom: string
  prenom: string
  formateur_id?: number | null
}

interface AssignmentItem {
  id: number
  module?: { nom_module?: string }
  groupe?: { nom_groupe?: string }
  formateur?: { user?: { nom?: string; prenom?: string } }
}

const api = useApi()
const router = useRouter()
const authStore = useAuthStore()

const isSidebarOpen = ref(false)
const loading = ref(true)
const submitting = ref(false)
const error = ref<string | null>(null)

const modules = ref<ModuleItem[]>([])
const groupes = ref<GroupeItem[]>([])
const formateurs = ref<FormateurItem[]>([])
const assignments = ref<AssignmentItem[]>([])
const moduleSearchQuery = ref('')
const isModuleDropdownOpen = ref(false)
const groupeSearchQuery = ref('')
const isGroupeDropdownOpen = ref(false)
const formateurSearchQuery = ref('')
const isFormateurDropdownOpen = ref(false)

const form = ref({
  id_module: '' as number | '',
  id_groupe: '' as number | '',
  id_formateur: '' as number | '',
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

const filteredModuleOptions = computed(() => {
  const query = moduleSearchQuery.value.trim().toLowerCase()
  if (!query) {
    return modules.value
  }

  return modules.value.filter((moduleItem) => moduleItem.nom_module.toLowerCase().includes(query))
})

const filteredGroupeOptions = computed(() => {
  const query = groupeSearchQuery.value.trim().toLowerCase()
  if (!query) {
    return groupes.value
  }

  return groupes.value.filter((groupe) => groupe.nom_groupe.toLowerCase().includes(query))
})

const filteredFormateurOptions = computed(() => {
  const query = formateurSearchQuery.value.trim().toLowerCase()
  if (!query) {
    return formateurs.value
  }

  return formateurs.value.filter((formateur) => {
    const prenom = formateur.user?.prenom || ''
    const nom = formateur.user?.nom || ''
    const fullName = `${prenom} ${nom}`.toLowerCase().trim()
    return fullName.includes(query)
  })
})

const onModuleSearchInput = () => {
  form.value.id_module = ''
  isModuleDropdownOpen.value = true
}

const selectModule = (moduleItem: ModuleItem) => {
  form.value.id_module = moduleItem.id
  moduleSearchQuery.value = moduleItem.nom_module
  isModuleDropdownOpen.value = false
}

const closeModuleDropdown = () => {
  setTimeout(() => {
    isModuleDropdownOpen.value = false
  }, 120)
}

const onGroupeSearchInput = () => {
  form.value.id_groupe = ''
  isGroupeDropdownOpen.value = true
}

const selectGroupe = (groupe: GroupeItem) => {
  form.value.id_groupe = groupe.id
  groupeSearchQuery.value = groupe.nom_groupe
  isGroupeDropdownOpen.value = false
}

const closeGroupeDropdown = () => {
  setTimeout(() => {
    isGroupeDropdownOpen.value = false
  }, 120)
}

const onFormateurSearchInput = () => {
  form.value.id_formateur = ''
  isFormateurDropdownOpen.value = true
}

const selectFormateur = (formateur: FormateurItem) => {
  form.value.id_formateur = formateur.id
  formateurSearchQuery.value = `${formateur.user?.prenom || ''} ${formateur.user?.nom || ''}`.trim()
  isFormateurDropdownOpen.value = false
}

const closeFormateurDropdown = () => {
  setTimeout(() => {
    isFormateurDropdownOpen.value = false
  }, 120)
}

const loadAll = async () => {
  loading.value = true
  error.value = null

  try {
    const [modulesRes, groupesRes, usersRes, assignmentsRes] = await Promise.all([
      api.get<any>('/modules'),
      api.get<any>('/groupes'),
      api.get<any>('/users', { role: 'FORMATEUR' }),
      api.get<any>('/module-formateur'),
    ])

    modules.value = extractArray<ModuleItem>(modulesRes)
    groupes.value = extractArray<GroupeItem>(groupesRes)
    assignments.value = extractArray<AssignmentItem>(assignmentsRes)
    const formateurUsers = extractArray<FormateurUserRow>(usersRes)
    formateurs.value = formateurUsers
      .filter((user) => !!user.formateur_id)
      .map((user) => ({
        id: Number(user.formateur_id),
        user: {
          nom: user.nom,
          prenom: user.prenom,
        },
      }))
  } catch (err: any) {
    error.value = err?.data?.message || err?.message || 'Erreur lors du chargement des donnees'
  } finally {
    loading.value = false
  }
}

const submitAssignment = async () => {
  if (!form.value.id_module || !form.value.id_groupe || !form.value.id_formateur) {
    error.value = 'Veuillez selectionner module, groupe et formateur'
    return
  }

  submitting.value = true
  error.value = null

  try {
    await api.post('/module-formateur', {
      id_module: form.value.id_module,
      id_groupe: form.value.id_groupe,
      id_formateur: form.value.id_formateur,
    })

    form.value = {
      id_module: '',
      id_groupe: '',
      id_formateur: '',
    }
    moduleSearchQuery.value = ''
    groupeSearchQuery.value = ''
    formateurSearchQuery.value = ''

    await loadAll()
  } catch (err: any) {
    error.value = err?.data?.message || err?.message || 'Erreur lors de l affectation'
  } finally {
    submitting.value = false
  }
}

const deleteAssignment = async (id: number) => {
  try {
    await api.delete(`/module-formateur/${id}`)
    assignments.value = assignments.value.filter((item) => item.id !== id)
  } catch (err: any) {
    error.value = err?.data?.message || err?.message || 'Erreur lors de la suppression'
  }
}

const handleLogout = async () => {
  isSidebarOpen.value = false
  await authStore.logout()
  router.push('/login')
}

onMounted(() => {
  loadAll()
})
</script>
