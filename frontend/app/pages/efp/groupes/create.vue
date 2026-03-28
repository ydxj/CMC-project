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
            <h1 class="text-sm font-semibold text-slate-900">Ajouter Groupe</h1>
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
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-600">Formulaire</p>
          <h2 class="mt-2 text-2xl font-bold text-slate-900">Creer Un Groupe</h2>
          <p class="mt-2 text-sm text-slate-600">Le groupe sera ajoute a une filiere de votre etablissement.</p>
        </section>

        <div v-if="error" class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">
          {{ error }}
        </div>

        <form class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" @submit.prevent="submitForm">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Nom groupe</label>
            <input v-model="form.nom_groupe" required class="w-full rounded-lg border border-slate-300 px-3 py-2" type="text" />
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Annee</label>
              <input v-model="form.annee" required class="w-full rounded-lg border border-slate-300 px-3 py-2" type="text" placeholder="2025/2026" />
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Niveau</label>
              <select v-model="form.niveau" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                <option v-for="item in niveaux" :key="item" :value="item">{{ item }}</option>
              </select>
            </div>
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Creneau</label>
              <select v-model="form.crenau" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                <option value="CDJ">CDJ</option>
                <option value="CDS">CDS</option>
              </select>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Filiere</label>
              <select v-model="form.id_filiere" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                <option disabled value="">Selectionner une filiere</option>
                <option v-for="filiere in filieres" :key="filiere.id" :value="filiere.id">
                  {{ filiere.nom_filiere }}
                </option>
              </select>
            </div>
          </div>

          <div class="mt-6 flex gap-3">
            <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100" @click="router.push('/efp/groupes')">
              Annuler
            </button>
            <button :disabled="submitting || filieres.length === 0" type="submit" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700 disabled:opacity-60">
              {{ submitting ? 'Creation...' : 'Creer groupe' }}
            </button>
          </div>
        </form>
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

interface FiliereItem {
  id: number
  nom_filiere: string
}

const api = useApi()
const router = useRouter()
const authStore = useAuthStore()

const isSidebarOpen = ref(false)
const submitting = ref(false)
const error = ref<string | null>(null)
const filieres = ref<FiliereItem[]>([])

const niveaux = ['TS', 'T', 'Q', 'S', 'FQ', 'BP', 'PC']

const form = ref({
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

const loadFilieres = async () => {
  try {
    const response = await api.get<any>('/filiers')
    filieres.value = extractArray<FiliereItem>(response)
  } catch (err: any) {
    error.value = err?.data?.message || err?.message || 'Erreur lors du chargement des filieres'
  }
}

const submitForm = async () => {
  if (!form.value.id_filiere) {
    error.value = 'Selectionnez une filiere'
    return
  }

  submitting.value = true
  error.value = null

  try {
    await api.post('/groupes', {
      nom_groupe: form.value.nom_groupe,
      annee: form.value.annee,
      niveau: form.value.niveau,
      crenau: form.value.crenau,
      id_filiere: form.value.id_filiere,
      id_formation: form.value.id_filiere,
    })

    router.push('/efp/groupes')
  } catch (err: any) {
    error.value = err?.data?.message || err?.message || 'Erreur lors de la creation du groupe'
  } finally {
    submitting.value = false
  }
}

const handleLogout = async () => {
  isSidebarOpen.value = false
  await authStore.logout()
  router.push('/login')
}

onMounted(() => {
  loadFilieres()
})
</script>
