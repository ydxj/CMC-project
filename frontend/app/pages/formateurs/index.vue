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
            <h1 class="text-sm font-semibold text-slate-900">Formateurs</h1>
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
          <h2 class="mt-2 text-2xl font-bold text-slate-900">Formateurs</h2>
          <p class="mt-2 text-sm text-slate-600">Liste des formateurs de l'etablissement</p>
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
        </div>

        <div v-if="loading" class="rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm">
          <div class="inline-block h-8 w-8 animate-spin rounded-full border-b-2 border-sky-600"></div>
          <p class="mt-2 text-slate-600">Chargement...</p>
        </div>

        <div v-else-if="error" class="rounded-xl border border-red-200 bg-red-50 p-4">
          <p class="text-red-800">{{ error }}</p>
        </div>

        <div v-else class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
              <thead class="bg-slate-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Matricule</th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Nom complet</th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Email</th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Pole(s)</th>
                  <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 bg-white">
                <tr v-if="filteredFormateurs.length === 0">
                  <td colspan="5" class="px-6 py-8 text-center text-slate-500">Aucun formateur trouve</td>
                </tr>
                <tr v-for="formateur in paginatedFormateurs" :key="formateur.id" class="transition-colors hover:bg-slate-50">
                  <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">{{ formateur.matricule || '-' }}</td>
                  <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">{{ formateur.prenom }} {{ formateur.nom }}</td>
                  <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">{{ formateur.email }}</td>
                  <td class="px-6 py-4 text-sm text-slate-600">
                    <span v-if="getPoleNames(formateur.id).length > 0">{{ getPoleNames(formateur.id).join(', ') }}</span>
                    <span v-else class="text-slate-400 italic">Non assigne</span>
                  </td>
                  <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                    <button @click="viewFormateur(formateur)" class="text-sky-600 hover:text-sky-900" title="Voir">
                      <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
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
import { useApi } from '../../../composables/useApi'
import { useAuthStore } from '../../../stores/auth'

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

interface PoleItem {
  id: number
  nom_pole: string
}

interface PoleFormateurResponse {
  success?: boolean
  data?: Array<{ id?: number; id_user?: number; user?: { id?: number } }>
}

const router = useRouter()
const api = useApi()
const authStore = useAuthStore()

const isSidebarOpen = ref(false)
const loading = ref(true)
const error = ref<string | null>(null)
const searchQuery = ref('')
const formateurs = ref<UserItem[]>([])
const poleByUserId = ref<Record<number, string[]>>({})
const currentPage = ref(1)
const pageSize = 10

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

const filteredFormateurs = computed(() => {
  if (!searchQuery.value.trim()) return formateurs.value

  const query = searchQuery.value.toLowerCase()
  return formateurs.value.filter((formateur) => {
    const poles = getPoleNames(formateur.id).join(' ').toLowerCase()
    return (
      formateur.nom.toLowerCase().includes(query) ||
      formateur.prenom.toLowerCase().includes(query) ||
      formateur.email.toLowerCase().includes(query) ||
      (formateur.matricule || '').toLowerCase().includes(query) ||
      poles.includes(query)
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

const getPoleNames = (userId: number): string[] => {
  return poleByUserId.value[userId] || []
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

const loadFormateurs = async () => {
  loading.value = true
  error.value = null

  try {
    const [usersRes, polesRes] = await Promise.all([
      api.get<UserItem[]>('/users'),
      api.get<PoleItem[]>('/poles'),
    ])

    const users = extractArray<UserItem>(usersRes)
    const poles = extractArray<PoleItem>(polesRes)

    formateurs.value = users.filter((user) => user.role === 'FORMATEUR')

    const poleRequests = poles.map((pole) => api.get<PoleFormateurResponse>(`/poles/${pole.id}/formateurs`))
    const poleResponses = await Promise.all(poleRequests)

    const mapping: Record<number, string[]> = {}

    for (let i = 0; i < poles.length; i += 1) {
      const pole = poles[i]
      const response = poleResponses[i]

      if (!pole || !response) continue

      const entries = extractArray<{ id?: number; id_user?: number; user?: { id?: number } }>(response)
      entries.forEach((entry) => {
        const userId = entry.id_user ?? entry.user?.id
        if (!userId) return

        if (!mapping[userId]) {
          mapping[userId] = []
        }

        if (!mapping[userId].includes(pole.nom_pole)) {
          mapping[userId].push(pole.nom_pole)
        }
      })
    }

    poleByUserId.value = mapping
  } catch (err: any) {
    error.value = err.message || 'Erreur lors du chargement des formateurs'
    console.error('Error loading formateurs:', err)
  } finally {
    loading.value = false
  }
}

const viewFormateur = (formateur: UserItem) => {
  console.log('View formateur:', formateur)
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
