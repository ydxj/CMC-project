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
          <h1 class="text-sm font-semibold text-slate-900">Dashboard</h1>
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
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-600">Dashboard</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">{{ greetingTitle }}</h2>
            <!-- <p class="mt-2 max-w-3xl text-sm text-slate-600">
              Pilot CMC planning with role-based actions and clear access to poles, salles, filieres, formateurs and emploi du temps.
            </p> -->
        </section>

        <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <article
            v-for="card in statCards"
            :key="card.key"
            class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
          >
            <template v-if="loadingStats">
              <div class="animate-pulse">
                <div class="h-3 w-24 rounded bg-slate-200"></div>
                <div class="mt-3 h-8 w-16 rounded bg-slate-200"></div>
                <div class="mt-2 h-3 w-32 rounded bg-slate-200"></div>
              </div>
            </template>
            <template v-else>
              <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
              <p
                class="mt-3 text-slate-900"
                :class="typeof card.value === 'number' ? 'text-2xl font-bold' : 'text-sm font-semibold leading-6'"
              >
                {{ card.value }}
              </p>
              <p class="mt-1 text-xs text-slate-500">{{ card.description }}</p>
            </template>
          </article>
        </section>

        <section
          v-if="isFormateur"
          class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"
        >
          <h3 class="text-base font-semibold text-slate-900">Mon Apercu</h3>
          <p class="mt-1 text-sm text-slate-600">
            Vos modules, groupes et poles assignes a partir des donnees API.
          </p>

          <div class="mt-4 grid gap-4 sm:grid-cols-3">
            <div>
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Mes Modules</p>
              <ul class="mt-2 space-y-1 text-sm text-slate-700">
                <li v-for="name in formateurOverview.modules" :key="`m-${name}`">{{ name }}</li>
                <li v-if="formateurOverview.modules.length === 0" class="text-slate-400">Aucun module assigne</li>
              </ul>
            </div>

            <div>
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Mes Groupes</p>
              <ul class="mt-2 space-y-1 text-sm text-slate-700">
                <li v-for="name in formateurOverview.groupes" :key="`g-${name}`">{{ name }}</li>
                <li v-if="formateurOverview.groupes.length === 0" class="text-slate-400">Aucun groupe assigne</li>
              </ul>
            </div>

            <div>
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Mon Pole</p>
              <ul class="mt-2 space-y-1 text-sm text-slate-700">
                <li v-for="name in formateurOverview.poles" :key="`p-${name}`">{{ name }}</li>
                <li v-if="formateurOverview.poles.length === 0" class="text-slate-400">Aucun pole assigne</li>
              </ul>
            </div>
          </div>
        </section>

        <!-- <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h3 class="text-base font-semibold text-slate-900">Access Policy Summary</h3>
            <p class="mt-1 text-sm text-slate-600">
              Directeur has global management rights, Chef de Pole manages his pole, and Formateur has personal views only.
            </p>

            <div class="mt-4 overflow-x-auto">
              <table class="min-w-full text-left text-sm">
                <thead>
                  <tr class="border-b border-slate-200 text-slate-500">
                    <th class="px-3 py-2 font-semibold">Section</th>
                    <th class="px-3 py-2 font-semibold">Directeur</th>
                    <th class="px-3 py-2 font-semibold">Chef de Pole</th>
                    <th class="px-3 py-2 font-semibold">Formateur</th>
                  </tr>
                </thead>
                <tbody class="text-slate-700">
                  <tr class="border-b border-slate-100">
                    <td class="px-3 py-2">Utilisateurs</td>
                    <td class="px-3 py-2">Full CRUD</td>
                    <td class="px-3 py-2">No access</td>
                    <td class="px-3 py-2">No access</td>
                  </tr>
                  <tr class="border-b border-slate-100">
                    <td class="px-3 py-2">Poles</td>
                    <td class="px-3 py-2">Full CRUD</td>
                    <td class="px-3 py-2">View own pole</td>
                    <td class="px-3 py-2">No access</td>
                  </tr>
                  <tr class="border-b border-slate-100">
                    <td class="px-3 py-2">Modules / Groupes</td>
                    <td class="px-3 py-2">View all</td>
                    <td class="px-3 py-2">CRUD + assign</td>
                    <td class="px-3 py-2">Own only</td>
                  </tr>
                  <tr>
                    <td class="px-3 py-2">Emploi du Temps</td>
                    <td class="px-3 py-2">Global</td>
                    <td class="px-3 py-2">Pole + create</td>
                    <td class="px-3 py-2">Own only</td>
                  </tr>
                </tbody>
              </table>
            </div>
        </section> -->
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
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { useApi } from '../../composables/useApi'

interface UserItem {
  id: number
  role: string
}

interface PoleItem {
  id: number
  id_chef?: number | null
  nom_pole?: string
}

interface SalleItem {
  id: number
  id_pole?: number | null
}

interface FiliereItem {
  id: number
  id_pole?: number | null
}

interface ModuleItem {
  id: number
  id_filiere?: number | null
  nom_module?: string
}

interface FormateurItem {
  id: number
  id_user?: number
  user?: { id?: number }
}

interface GroupeItem {
  id: number
  nom_groupe?: string
}

interface ModuleAssignment {
  id?: number
  module?: ModuleItem
  groupe?: GroupeItem
}

const authStore = useAuthStore()
const router = useRouter()
const api = useApi()
const isSidebarOpen = ref(false)
const loadingStats = ref(true)

const stats = reactive({
  etablissements: '--' as string | number,
  directeurs: '--' as string | number,
  poles: '--' as string | number,
  salles: '--' as string | number,
  formateurs: '--' as string | number,
  modules: '--' as string | number,
  groupes: '--' as string | number,
  assignments: '--' as string | number,
})

const formateurOverview = reactive({
  modules: [] as string[],
  groupes: [] as string[],
  poles: [] as string[],
})

const isFormateur = computed(() => authStore.user?.role === 'FORMATEUR')
const isChefPole = computed(() => authStore.user?.role === 'CHEF_POLE')
const isDosi = computed(() => authStore.user?.role === 'DOSI')

const fullName = computed(() => {
  if (!authStore.user) return 'Utilisateur'
  return `${authStore.user.nom} ${authStore.user.prenom}`.trim()
})

const roleLabel = computed(() => {
  const role = authStore.user?.role
  if (role === 'DOSI') return 'DOSI'
  if (role === 'DIRECTEUR' || role === 'DIRECTEUR_CMC') return 'Directeur CMC'
  if (role === 'DIRECTEUR_EFP') return 'Directeur EFP'
  if (role === 'CHEF_POLE') return 'Chef de Pole'
  if (role === 'FORMATEUR') return 'Formateur'
  return role || 'Utilisateur'
})

const greetingTitle = computed(() => {
  if (!authStore.user) return 'Welcome to CMC platform'
  return `Welcome, ${authStore.user.prenom}`
})

const userInitials = computed(() => {
  if (!authStore.user) return 'CM'
  const nom = authStore.user.nom?.charAt(0) || ''
  const prenom = authStore.user.prenom?.charAt(0) || ''
  return `${nom}${prenom}`.toUpperCase() || 'CM'
})

const handleLogout = async () => {
  isSidebarOpen.value = false
  await authStore.logout()
  router.push('/login')
}

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

const getUniqueCount = (items: Array<number | undefined | null>) => {
  return new Set(items.filter((item): item is number => typeof item === 'number')).size
}

const statCards = computed(() => {
  if (isDosi.value) {
    return [
      { key: 'etablissements', label: 'Etablissements', value: stats.etablissements, description: 'Nombre total d etablissements' },
      { key: 'directeurs', label: 'Directeurs', value: stats.directeurs, description: 'Nombre total de directeurs' },
    ]
  }

  if (isFormateur.value) {
    return [
      { key: 'modules', label: 'Mes Modules', value: stats.modules, description: 'Modules assignes' },
      { key: 'groupes', label: 'Mes Groupes', value: stats.groupes, description: 'Groupes enseignes' },
      { key: 'poles', label: 'Mon Pole', value: stats.poles, description: 'Nom du pole d appartenance' },
      { key: 'assignments', label: 'Seances', value: stats.assignments, description: 'Affectations actives' },
    ]
  }

  if (isChefPole.value) {
    return [
      { key: 'poles', label: 'Mon Pole', value: stats.poles, description: 'Pole(s) que vous gerez' },
      { key: 'salles', label: 'Salles / Ateliers', value: stats.salles, description: 'Available learning spaces' },
      { key: 'formateurs', label: 'Formateurs', value: stats.formateurs, description: 'Assigned teachers' },
      { key: 'modules', label: 'Modules', value: stats.modules, description: 'Courses and units' },
    ]
  }

  return [
    { key: 'poles', label: 'Poles', value: stats.poles, description: 'Training departments' },
    { key: 'salles', label: 'Salles / Ateliers', value: stats.salles, description: 'Available learning spaces' },
    { key: 'formateurs', label: 'Formateurs', value: stats.formateurs, description: 'Assigned teachers' },
    { key: 'modules', label: 'Modules', value: stats.modules, description: 'Courses and units' },
  ]
})

const loadDashboardStats = async () => {
  if (!authStore.user) return

  loadingStats.value = true

  try {
    const response = await api.get<any>('/dashboard/stats')
    const data = response.stats || response.data?.stats || response

    stats.etablissements = data.etablissements ?? '--'
    stats.directeurs = data.directeurs ?? '--'
    stats.poles = data.poles ?? '--'
    stats.salles = data.salles ?? '--'
    stats.formateurs = data.formateurs ?? '--'
    stats.modules = data.modules ?? '--'
    stats.groupes = data.groupes ?? '--'
    stats.assignments = data.assignments ?? '--'

    // For formateur, also load overview data
    if (authStore.user?.role === 'FORMATEUR' && response.overview) {
      formateurOverview.modules = response.overview.modules || []
      formateurOverview.groupes = response.overview.groupes || []
      formateurOverview.poles = response.overview.poles || []
    }
  } catch (error) {
    console.error('Dashboard stats error:', error)
  } finally {
    loadingStats.value = false
  }
}

onMounted(async () => {
  authStore.initializeAuth()

  if (!authStore.isAuthenticated) {
    router.push('/login')
    return
  }

  // Refresh user profile from backend to keep session coherent across refresh.
  await authStore.checkAuth()

  if (!authStore.isAuthenticated) {
    router.push('/login')
    return
  }

  await loadDashboardStats()
})
</script>
