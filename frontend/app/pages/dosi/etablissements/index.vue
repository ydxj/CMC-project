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
            <h1 class="text-sm font-semibold text-slate-900">Nouveaux etablissements</h1>
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
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-600">DOSI</p>
          <h2 class="mt-2 text-2xl font-bold text-slate-900">Creer un etablissement</h2>
          <p class="mt-2 text-sm text-slate-600">Associez un directeur compatible au type d'etablissement si necessaire.</p>

          <form class="mt-6 grid gap-4 md:grid-cols-2" @submit.prevent="createEtablissement">
            <div class="md:col-span-2">
              <label class="mb-1 block text-sm font-medium text-slate-700">Nom etablissement</label>
              <input
                v-model="form.nom_etablissement"
                type="text"
                required
                class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 focus:border-transparent focus:ring-2 focus:ring-sky-500"
              >
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Type</label>
              <select
                v-model="form.type_etablissement"
                required
                class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 focus:border-transparent focus:ring-2 focus:ring-sky-500"
              >
                <option value="CMC">CMC</option>
                <option value="EFP">EFP</option>
              </select>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Region</label>
              <input
                v-model="form.region"
                type="text"
                required
                class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 focus:border-transparent focus:ring-2 focus:ring-sky-500"
              >
            </div>

            <div class="md:col-span-2">
              <label class="mb-1 block text-sm font-medium text-slate-700">Directeur (optionnel)</label>
              <input
                v-model="directeurSearch"
                type="text"
                placeholder="Rechercher un directeur..."
                class="mb-2 w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 focus:border-transparent focus:ring-2 focus:ring-sky-500"
              >
              <select
                v-model="form.id_directeur"
                class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 focus:border-transparent focus:ring-2 focus:ring-sky-500"
              >
                <option :value="null">Aucun</option>
                <option v-for="d in filteredDirecteurs" :key="d.id" :value="d.id">
                  {{ d.prenom }} {{ d.nom }} - {{ roleLabel(d.role) }}
                </option>
              </select>
              <p class="mt-1 text-xs text-slate-500">Tapez pour filtrer. Seuls les directeurs non assignes et compatibles sont proposes.</p>
            </div>

            <div class="md:col-span-2 flex items-center justify-end gap-3 pt-2">
              <button
                type="submit"
                :disabled="submitting"
                class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-50"
              >
                {{ submitting ? 'Creation...' : 'Creer etablissement' }}
              </button>
            </div>
          </form>

          <p v-if="error" class="mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ error }}</p>
          <p v-if="success" class="mt-4 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700">{{ success }}</p>
        </section>

        <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-200 px-5 py-4">
            <h3 class="text-sm font-semibold text-slate-900">Liste des etablissements</h3>
          </div>

          <div v-if="loading" class="p-6 text-sm text-slate-600">Chargement...</div>
          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
              <thead class="bg-slate-50 text-slate-600">
                <tr>
                  <th class="px-4 py-3 text-left">Nom</th>
                  <th class="px-4 py-3 text-left">Type</th>
                  <th class="px-4 py-3 text-left">Region</th>
                  <th class="px-4 py-3 text-left">Directeur</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 bg-white">
                <tr v-for="e in etablissements" :key="e.id">
                  <td class="px-4 py-3">{{ e.nom_etablissement }}</td>
                  <td class="px-4 py-3">{{ e.type_etablissement }}</td>
                  <td class="px-4 py-3">{{ e.region }}</td>
                  <td class="px-4 py-3">
                    <span v-if="e.directeur">{{ e.directeur.prenom }} {{ e.directeur.nom }}</span>
                    <span v-else class="text-slate-500">Non affecte</span>
                  </td>
                </tr>
                <tr v-if="!etablissements.length">
                  <td colspan="4" class="px-4 py-6 text-center text-slate-500">Aucun etablissement</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </main>
    </div>

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
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '../../../../composables/useApi'
import { useAuthStore } from '../../../../stores/auth'

definePageMeta({ middleware: ['auth'] })

interface Directeur {
  id: number
  nom: string
  prenom: string
  role: string
  id_etablissement: number | null
}

interface Etablissement {
  id: number
  nom_etablissement: string
  type_etablissement: 'CMC' | 'EFP'
  region: string
  id_directeur: number | null
  directeur?: Directeur | null
}

const router = useRouter()
const api = useApi()
const authStore = useAuthStore()

const isSidebarOpen = ref(false)
const loading = ref(false)
const submitting = ref(false)
const error = ref('')
const success = ref('')
const directeurSearch = ref('')

const etablissements = ref<Etablissement[]>([])
const directeurs = ref<Directeur[]>([])

const form = ref({
  nom_etablissement: '',
  type_etablissement: 'CMC' as 'CMC' | 'EFP',
  region: '',
  id_directeur: null as number | null,
})

const compatibleDirecteurs = computed(() => {
  const expectedRole = form.value.type_etablissement === 'CMC' ? 'DIRECTEUR_CMC' : 'DIRECTEUR_EFP'
  return directeurs.value.filter((d) => d.role === expectedRole && !d.id_etablissement)
})

const filteredDirecteurs = computed(() => {
  const query = directeurSearch.value.trim().toLowerCase()
  if (!query) return compatibleDirecteurs.value

  return compatibleDirecteurs.value.filter((d) => {
    const fullName = `${d.prenom} ${d.nom}`.toLowerCase()
    return fullName.includes(query)
  })
})

const roleLabel = (role: string) => {
  if (role === 'DIRECTEUR_CMC') return 'Directeur CMC'
  if (role === 'DIRECTEUR_EFP') return 'Directeur EFP'
  return role
}

const extractArray = <T>(payload: T[] | { data?: T[] }): T[] => {
  return Array.isArray(payload) ? payload : (payload.data || [])
}

const loadData = async () => {
  loading.value = true
  error.value = ''

  try {
    const [etabResponse, usersResponse] = await Promise.all([
      api.get<Etablissement[] | { data: Etablissement[] }>('/etablissements'),
      api.get<Directeur[] | { data: Directeur[] }>('/users'),
    ])

    etablissements.value = extractArray(etabResponse)
    const users = extractArray(usersResponse)
    directeurs.value = users.filter((u) => u.role === 'DIRECTEUR_CMC' || u.role === 'DIRECTEUR_EFP')
  } catch (err: any) {
    error.value = err?.data?.message || err?.message || 'Erreur lors du chargement des donnees.'
  } finally {
    loading.value = false
  }
}

const createEtablissement = async () => {
  submitting.value = true
  error.value = ''
  success.value = ''

  try {
    const payload = {
      nom_etablissement: form.value.nom_etablissement,
      type_etablissement: form.value.type_etablissement,
      region: form.value.region,
      id_directeur: form.value.id_directeur,
    }

    await api.post('/etablissements', payload)
    success.value = 'Etablissement cree avec succes.'

    form.value = {
      nom_etablissement: '',
      type_etablissement: 'CMC',
      region: '',
      id_directeur: null,
    }
    directeurSearch.value = ''

    await loadData()
  } catch (err: any) {
    error.value = err?.data?.message || err?.message || 'Impossible de creer l etablissement.'
  } finally {
    submitting.value = false
  }
}

const handleLogout = async () => {
  isSidebarOpen.value = false
  await authStore.logout()
  router.push('/login')
}

watch(() => form.value.type_etablissement, () => {
  form.value.id_directeur = null
  directeurSearch.value = ''
})

onMounted(async () => {
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

  await loadData()
})
</script>
