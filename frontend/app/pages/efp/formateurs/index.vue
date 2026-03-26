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
          <p class="mt-2 text-sm text-slate-600">{{ isPoleScoped ? 'Formateurs de votre pôle' : 'Liste des formateurs de l\'etablissement' }}</p>
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
                  <th v-if="!isPoleScoped" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Pole(s)</th>
                  <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 bg-white">
                <tr v-if="paginatedFormateurs.length === 0">
                  <td :colspan="isPoleScoped ? 4 : 5" class="px-6 py-8 text-center text-slate-500">Aucun formateur trouve</td>
                </tr>
                <tr v-for="(item, idx) in paginatedFormateurs" :key="idx" class="transition-colors hover:bg-slate-50">
                  <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">
                    {{ isPoleScoped ? (item as FormateurItem).user?.matricule : (item as UserItem).matricule || '-' }}
                  </td>
                  <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">
                    {{ isPoleScoped ? `${(item as FormateurItem).user?.prenom} ${(item as FormateurItem).user?.nom}` : `${(item as UserItem).prenom} ${(item as UserItem).nom}` }}
                  </td>
                  <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                    {{ isPoleScoped ? (item as FormateurItem).user?.email : (item as UserItem).email }}
                  </td>
                  <td v-if="!isPoleScoped" class="px-6 py-4 text-sm text-slate-600">
                    <span v-if="getPoleNames((item as UserItem).id).length > 0">{{ getPoleNames((item as UserItem).id).join(', ') }}</span>
                    <span v-else class="text-slate-400 italic">Non assigne</span>
                  </td>
                  <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                    <button
                      type="button"
                      @click="viewFormateur(isPoleScoped ? (item as FormateurItem).user : (item as UserItem))"
                      class="text-sky-600 hover:text-sky-900"
                      title="Voir"
                    >
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

          <div v-if="(isPoleScoped ? totalCount : filteredFormateurs.length) > 0" class="flex flex-col gap-4 border-t border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <p class="text-sm font-medium text-slate-600">
              {{ paginationDisplay }}
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
        v-if="viewedFormateur"
        class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-[1px]"
        @click="closeFormateurView"
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
      <div v-if="viewedFormateur" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white shadow-2xl">
          <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <h3 class="text-lg font-bold text-slate-900">Details formateur</h3>
            <button
              type="button"
              class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-100"
              @click="closeFormateurView"
              aria-label="Close"
            >
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="space-y-4 p-6">
            <div>
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nom complet</p>
              <p class="mt-1 text-sm font-semibold text-slate-900">{{ viewedFormateur?.prenom }} {{ viewedFormateur?.nom }}</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Matricule</p>
                <p class="mt-1 text-sm text-slate-800">{{ viewedFormateur?.matricule || '-' }}</p>
              </div>
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Role</p>
                <p class="mt-1 text-sm text-slate-800">{{ roleLabelFromValue(viewedFormateur?.role) }}</p>
              </div>
            </div>

            <div>
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Email</p>
              <p class="mt-1 break-all text-sm text-slate-800">{{ viewedFormateur?.email || '-' }}</p>
            </div>

            <div v-if="viewedFormateurPoleNames.length > 0">
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pole(s)</p>
              <p class="mt-1 text-sm text-slate-800">{{ viewedFormateurPoleNames.join(', ') }}</p>
            </div>

            <div class="flex justify-end pt-2">
              <button
                type="button"
                class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                @click="closeFormateurView"
              >
                Fermer
              </button>
            </div>
          </div>
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
  middleware: ['auth'],
})

interface UserItem {
  id: number
  nom: string
  prenom: string
  email: string
  role: string
  matricule?: string
  pole_names?: string[]
}

interface PoleItem {
  id: number
  nom_pole: string
  id_chef?: number
}

interface FormateurItem {
  id_user: number
  type_formateur: string
  MHS: number
  NBR_semaine: number
  user: UserItem
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
const formateurs = ref<UserItem[] | FormateurItem[]>([])
const poleByUserId = ref<Record<number, string[]>>({})
const currentPage = ref(1)
const pageSize = 10
const totalCount = ref(0)
const viewedFormateur = ref<UserItem | null>(null)
const isFormateur = computed(() => authStore.user?.role === 'FORMATEUR')
const isChefPole = computed(() => authStore.user?.role === 'CHEF_POLE')
const isDirecteur = computed(() => authStore.user?.role === 'DIRECTEUR_EFP' || authStore.user?.role === 'DIRECTEUR')
const isPoleScoped = computed(() => isFormateur.value || isChefPole.value)
const isBackendPaginated = computed(() => isPoleScoped.value || isDirecteur.value)
const userPoleId = ref<number | null>(null)

const fullName = computed(() => {
  if (!authStore.user) return 'Utilisateur'
  return `${authStore.user.nom} ${authStore.user.prenom}`.trim()
})

const roleLabel = computed(() => {
  return roleLabelFromValue(authStore.user?.role)
})

const roleLabelFromValue = (role?: string | null) => {
  if (role === 'DIRECTEUR_EFP' || role === 'DIRECTEUR') return 'Directeur Pedagogique'
  if (role === 'CHEF_POLE') return 'Chef de Pole'
  if (role === 'FORMATEUR') return 'Formateur'
  return role || 'Utilisateur'
}

const userInitials = computed(() => {
  if (!authStore.user) return 'CM'
  const nom = authStore.user.nom?.charAt(0) || ''
  const prenom = authStore.user.prenom?.charAt(0) || ''
  return `${nom}${prenom}`.toUpperCase() || 'CM'
})

const filteredFormateurs = computed(() => {
  if (isDirecteur.value) {
    return formateurs.value
  }

  if (!searchQuery.value.trim()) return formateurs.value

  const query = searchQuery.value.toLowerCase()
  
  if (isPoleScoped.value) {
    // For formateur viewing their own pole members
    return (formateurs.value as FormateurItem[]).filter((item: any) => {
      const user = item.user || item
      return (
        user.nom?.toLowerCase().includes(query) ||
        user.prenom?.toLowerCase().includes(query) ||
        user.email?.toLowerCase().includes(query) ||
        (user.matricule || '').toLowerCase().includes(query)
      )
    })
  } else {
    // For admin/director viewing all formateurs
    return (formateurs.value as UserItem[]).filter((formateur) => {
      const poles = getPoleNames(formateur.id).join(' ').toLowerCase()
      return (
        formateur.nom.toLowerCase().includes(query) ||
        formateur.prenom.toLowerCase().includes(query) ||
        formateur.email.toLowerCase().includes(query) ||
        (formateur.matricule || '').toLowerCase().includes(query) ||
        poles.includes(query)
      )
    })
  }
})

const totalPages = computed(() => {
  if (isBackendPaginated.value) {
    const total = Math.ceil(totalCount.value / pageSize)
    return total > 0 ? total : 1
  } else {
    // Frontend pagination for admin/director
    const total = Math.ceil(filteredFormateurs.value.length / pageSize)
    return total > 0 ? total : 1
  }
})

const paginatedFormateurs = computed(() => {
  if (isBackendPaginated.value) {
    // Already paginated from backend
    return formateurs.value
  } else {
    // Frontend pagination
    const start = (currentPage.value - 1) * pageSize
    const end = start + pageSize
    return filteredFormateurs.value.slice(start, end)
  }
})

const paginationStart = computed(() => {
  if (isBackendPaginated.value) {
    if (totalCount.value === 0) return 0
    return (currentPage.value - 1) * pageSize + 1
  } else {
    if (filteredFormateurs.value.length === 0) return 0
    return (currentPage.value - 1) * pageSize + 1
  }
})

const paginationEnd = computed(() => {
  if (isBackendPaginated.value) {
    if (totalCount.value === 0) return 0
    return Math.min(currentPage.value * pageSize, totalCount.value)
  } else {
    if (filteredFormateurs.value.length === 0) return 0
    return Math.min(currentPage.value * pageSize, filteredFormateurs.value.length)
  }
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

const paginationDisplay = computed(() => {
  if (isBackendPaginated.value) {
    return `Affichage ${paginationStart.value}-${paginationEnd.value} sur ${totalCount.value} formateurs`
  } else {
    return `Affichage ${paginationStart.value}-${paginationEnd.value} sur ${filteredFormateurs.value.length} formateurs`
  }
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
  if (isDirecteur.value) {
    if (currentPage.value !== 1) {
      currentPage.value = 1
      return
    }

    loadFormateurs()
    return
  }

  currentPage.value = 1
})

watch(filteredFormateurs, () => {
  if (currentPage.value > totalPages.value) {
    currentPage.value = totalPages.value
  }
})

watch(currentPage, async () => {
  if (isBackendPaginated.value) {
    // Reload data when page changes in backend-paginated views.
    await loadFormateurs()
  }
})

const getPoleNames = (userId: number): string[] => {
  return poleByUserId.value[userId] || []
}

const viewedFormateurPoleNames = computed(() => {
  if (!viewedFormateur.value?.id) return []
  return getPoleNames(viewedFormateur.value.id)
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
    if (isFormateur.value) {
      // Resolve current formateur pole, then fetch pole formateurs using the generic pole endpoint.
      const polesRes = await api.get<PoleItem[]>('/poles')
      const poles = extractArray<PoleItem>(polesRes)
      const currentUserId = authStore.user?.id

      userPoleId.value = null

      for (const pole of poles) {
        const poleRes = await api.get<any>(`/poles/${pole.id}/formateurs`)
        const entries = extractArray<FormateurItem>(poleRes)
        const belongsToPole = entries.some((entry) => entry.id_user === currentUserId)
        if (belongsToPole) {
          userPoleId.value = pole.id
          break
        }
      }

      if (!userPoleId.value) {
        formateurs.value = []
        totalCount.value = 0
        return
      }

      const offset = (currentPage.value - 1) * pageSize
      const pagedResponse = await api.get<any>(`/poles/${userPoleId.value}/formateurs`, {
        limit: pageSize,
        offset,
      })
      formateurs.value = extractArray<FormateurItem>(pagedResponse)

      const allResponse = await api.get<any>(`/poles/${userPoleId.value}/formateurs`)
      totalCount.value = extractArray<FormateurItem>(allResponse).length
    } else if (isChefPole.value) {
      const offset = (currentPage.value - 1) * pageSize
      const response = await api.get<any>('/my-poles-formateurs', {
        limit: pageSize,
        offset,
      })

      if (response && (response as any).pole_id) {
        userPoleId.value = (response as any).pole_id
      }

      formateurs.value = extractArray<FormateurItem>(response)

      const allResponse = await api.get<any>('/my-poles-formateurs')
      totalCount.value = extractArray<FormateurItem>(allResponse).length
    } else if (isDirecteur.value) {
      const offset = (currentPage.value - 1) * pageSize
      const response = await api.get<any>('/users', {
        role: 'FORMATEUR',
        q: searchQuery.value.trim(),
        limit: pageSize,
        offset,
      })

      const rows = extractArray<UserItem>(response)
      formateurs.value = rows
      totalCount.value = Number((response as any)?.total || rows.length)

      const mapping: Record<number, string[]> = {}
      rows.forEach((row) => {
        mapping[row.id] = Array.isArray(row.pole_names) ? row.pole_names : []
      })
      poleByUserId.value = mapping
    } else {
      // Fallback for non-pole-scoped, non-directeur roles.
      const usersRes = await api.get<UserItem[]>('/users')
      const users = extractArray<UserItem>(usersRes)
      formateurs.value = users.filter((user) => user.role === 'FORMATEUR')
      totalCount.value = filteredFormateurs.value.length
      poleByUserId.value = {}
    }
  } catch (err: any) {
    error.value = err.message || 'Erreur lors du chargement des formateurs'
    console.error('Error loading formateurs:', err)
  } finally {
    loading.value = false
  }
}

const viewFormateur = (formateur?: UserItem | null) => {
  if (!formateur) return
  viewedFormateur.value = formateur
}

const closeFormateurView = () => {
  viewedFormateur.value = null
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


