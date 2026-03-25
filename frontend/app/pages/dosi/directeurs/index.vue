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
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-600">DOSI</p>
          <h2 class="mt-2 text-2xl font-bold text-slate-900">Creer un directeur et l affecter</h2>
          <p class="mt-2 text-sm text-slate-600">Chaque etablissement peut avoir un seul directeur.</p>

          <form class="mt-6 grid gap-4 md:grid-cols-2" @submit.prevent="createDirecteur">
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Matricule</label>
              <input v-model="form.matricule" required type="text" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 focus:border-transparent focus:ring-2 focus:ring-sky-500">
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Etablissement</label>
              <input
                v-model="etablissementSearch"
                type="text"
                placeholder="Rechercher un etablissement..."
                class="mb-2 w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 focus:border-transparent focus:ring-2 focus:ring-sky-500"
              >
              <select
                v-model.number="form.id_etablissement"
                required
                class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 focus:border-transparent focus:ring-2 focus:ring-sky-500"
              >
                <option :value="0" disabled>Selectionner</option>
                <option v-for="e in filteredAvailableEtablissements" :key="e.id" :value="e.id">
                  {{ e.nom_etablissement }} ({{ e.type_etablissement }})
                </option>
              </select>
              <p class="mt-1 text-xs text-slate-500">Tapez pour filtrer. Seuls les etablissements sans directeur sont affiches.</p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Nom</label>
              <input v-model="form.nom" required type="text" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 focus:border-transparent focus:ring-2 focus:ring-sky-500">
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Prenom</label>
              <input v-model="form.prenom" required type="text" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 focus:border-transparent focus:ring-2 focus:ring-sky-500">
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
              <input v-model="form.email" required type="email" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 focus:border-transparent focus:ring-2 focus:ring-sky-500">
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Role applique automatiquement</label>
              <input :value="resolvedRoleLabel" disabled type="text" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2 text-slate-700">
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
                type="submit"
                :disabled="submitting || passwordMismatch || !selectedEtablissement"
                class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-50"
              >
                {{ submitting ? 'Creation...' : 'Creer et affecter directeur' }}
              </button>
            </div>
          </form>

          <p v-if="error" class="mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ error }}</p>
          <p v-if="success" class="mt-4 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700">{{ success }}</p>
        </section>

        <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-200 px-5 py-4">
            <h3 class="text-sm font-semibold text-slate-900">Directeurs affectes</h3>
          </div>

          <div v-if="loading" class="p-6 text-sm text-slate-600">Chargement...</div>
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
                  <td class="px-4 py-3">{{ d.role }}</td>
                  <td class="px-4 py-3">{{ etablissementNameById[d.id_etablissement || 0] || 'Non affecte' }}</td>
                </tr>
                <tr v-if="!assignedDirecteurs.length">
                  <td colspan="4" class="px-4 py-6 text-center text-slate-500">Aucun directeur affecte</td>
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
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '../../../../composables/useApi'
import { useAuthStore } from '../../../../stores/auth'

definePageMeta({ middleware: ['auth'] })

interface UserItem {
  id: number
  nom: string
  prenom: string
  email: string
  role: string
  id_etablissement: number | null
}

interface Etablissement {
  id: number
  nom_etablissement: string
  type_etablissement: 'CMC' | 'EFP'
  id_directeur: number | null
}

const router = useRouter()
const api = useApi()
const authStore = useAuthStore()

const isSidebarOpen = ref(false)
const loading = ref(false)
const submitting = ref(false)
const error = ref('')
const success = ref('')
const etablissementSearch = ref('')

const users = ref<UserItem[]>([])
const etablissements = ref<Etablissement[]>([])

const form = ref({
  matricule: '',
  nom: '',
  prenom: '',
  email: '',
  password: '',
  password_confirmation: '',
  id_etablissement: 0,
})

const selectedEtablissement = computed(() => etablissements.value.find((e) => e.id === form.value.id_etablissement))

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

const availableEtablissements = computed(() => etablissements.value.filter((e) => !e.id_directeur))

const filteredAvailableEtablissements = computed(() => {
  const query = etablissementSearch.value.trim().toLowerCase()
  if (!query) return availableEtablissements.value

  return availableEtablissements.value.filter((e) => {
    return e.nom_etablissement.toLowerCase().includes(query)
  })
})

const assignedDirecteurs = computed(() => users.value.filter((u) => (u.role === 'DIRECTEUR_CMC' || u.role === 'DIRECTEUR_EFP') && !!u.id_etablissement))

const etablissementNameById = computed<Record<number, string>>(() => {
  return etablissements.value.reduce((acc, e) => {
    acc[e.id] = e.nom_etablissement
    return acc
  }, {} as Record<number, string>)
})

const extractArray = <T>(payload: T[] | { data?: T[] }): T[] => {
  return Array.isArray(payload) ? payload : (payload.data || [])
}

const loadData = async () => {
  loading.value = true
  error.value = ''

  try {
    const [usersResponse, etabResponse] = await Promise.all([
      api.get<UserItem[] | { data: UserItem[] }>('/users'),
      api.get<Etablissement[] | { data: Etablissement[] }>('/etablissements'),
    ])

    users.value = extractArray(usersResponse)
    etablissements.value = extractArray(etabResponse)
  } catch (err: any) {
    error.value = err?.data?.message || err?.message || 'Erreur lors du chargement des donnees.'
  } finally {
    loading.value = false
  }
}

const createDirecteur = async () => {
  if (!selectedEtablissement.value) return

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

    await loadData()
  } catch (err: any) {
    error.value = err?.data?.message || err?.message || 'Impossible de creer ce directeur.'
  } finally {
    submitting.value = false
  }
}

const handleLogout = async () => {
  isSidebarOpen.value = false
  await authStore.logout()
  router.push('/login')
}

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
