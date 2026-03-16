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
            <h1 class="text-sm font-semibold text-slate-900">Suivi des Heures Formateur</h1>
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
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-600">Chef de pole</p>
          <h2 class="mt-2 text-2xl font-bold text-slate-900">Suivi des heures par module</h2>
          <p class="mt-2 text-sm text-slate-600">Selectionnez un formateur de votre pole pour consulter ses modules et ses heures planifiees.</p>

          <div class="mt-4 grid gap-3 sm:max-w-xl">
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Formateur</label>
            <div class="relative">
              <input
                v-model="formateurSearch"
                type="text"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                placeholder="Rechercher un formateur..."
                @focus="showFormateurPicker = true"
                @input="showFormateurPicker = true"
                @blur="hideFormateurPicker"
              />
              <div
                v-if="showFormateurPicker"
                class="absolute z-30 mt-1 max-h-56 w-full overflow-auto rounded-lg border border-slate-200 bg-white p-1 shadow-lg"
              >
                <button
                  v-for="f in filteredFormateurs"
                  :key="f.id"
                  type="button"
                  class="w-full rounded-md px-2 py-1.5 text-left text-sm hover:bg-slate-100"
                  :class="selectedFormateurId === f.id ? 'bg-slate-100 font-semibold text-slate-900' : 'text-slate-700'"
                  @mousedown.prevent="selectFormateur(f)"
                >
                  {{ f.label }}
                </button>
                <p v-if="!filteredFormateurs.length" class="px-2 py-2 text-xs text-slate-500">Aucun formateur trouve.</p>
              </div>
            </div>
          </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
          <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div class="text-xs text-slate-500">
              <span v-if="selectedFormateurLabel">Formateur: <span class="font-semibold text-slate-700">{{ selectedFormateurLabel }}</span></span>
              <span v-else>Selectionnez un formateur pour voir ses heures.</span>
            </div>
            <div class="flex items-center gap-2 text-xs" v-if="selectedFormateurId">
              <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-700">Modules: {{ moduleHours.length }}</span>
              <span class="rounded-full bg-sky-100 px-2.5 py-1 font-semibold text-sky-700">Total planifie: {{ formatMinutes(totalPlannedMinutes) }}</span>
            </div>
          </div>

          <div v-if="loadingFormateurs" class="py-4 text-center text-sm text-slate-500">Chargement des formateurs...</div>
          <div v-else-if="hoursLoading" class="py-4 text-center text-sm text-slate-500">Chargement des heures...</div>
          <div v-else-if="error" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">{{ error }}</div>
          <div v-else-if="!selectedFormateurId" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-500">
            Aucun formateur selectionne.
          </div>
          <div v-else-if="moduleHours.length === 0" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-500">
            Aucun module assigne pour ce formateur.
          </div>
          <div v-else class="overflow-x-auto">
            <table class="min-w-full border-collapse text-xs">
              <thead>
                <tr class="bg-slate-50">
                  <th class="border border-slate-200 px-3 py-2 text-left font-semibold uppercase tracking-wide text-slate-500">Module</th>
                  <th class="border border-slate-200 px-3 py-2 text-left font-semibold uppercase tracking-wide text-slate-500">Groupe</th>
                  <th class="border border-slate-200 px-3 py-2 text-right font-semibold uppercase tracking-wide text-slate-500">Volume Module</th>
                  <th class="border border-slate-200 px-3 py-2 text-right font-semibold uppercase tracking-wide text-slate-500">Planifie</th>
                  <th class="border border-slate-200 px-3 py-2 text-right font-semibold uppercase tracking-wide text-slate-500">Reste</th>
                  <th class="border border-slate-200 px-3 py-2 text-right font-semibold uppercase tracking-wide text-slate-500">Depassement</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in moduleHours" :key="row.assignment_id" class="odd:bg-white even:bg-slate-50/30">
                  <td class="border border-slate-200 px-3 py-2 text-slate-700">{{ row.nom_module }}</td>
                  <td class="border border-slate-200 px-3 py-2 text-slate-600">{{ row.nom_groupe }}</td>
                  <td class="border border-slate-200 px-3 py-2 text-right font-semibold text-slate-700">{{ formatMinutes(row.volume_minutes) }}</td>
                  <td class="border border-slate-200 px-3 py-2 text-right font-semibold text-sky-700">{{ formatMinutes(row.planned_minutes) }}</td>
                  <td class="border border-slate-200 px-3 py-2 text-right" :class="row.remaining_minutes === 0 ? 'font-semibold text-amber-700' : 'text-emerald-700'">
                    {{ formatMinutes(row.remaining_minutes) }}
                  </td>
                  <td class="border border-slate-200 px-3 py-2 text-right" :class="row.exceeded_minutes > 0 ? 'font-semibold text-red-700' : 'text-slate-400'">
                    {{ row.exceeded_minutes > 0 ? formatMinutes(row.exceeded_minutes) : '-' }}
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
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '../../../../composables/useApi'
import { useAuthStore } from '../../../../stores/auth'

definePageMeta({ middleware: ['auth'] })

interface FormateurItem {
  id: number
  user?: {
    nom?: string
    prenom?: string
  }
}

interface FormateurOption {
  id: number
  label: string
}

interface ModuleHoursRow {
  assignment_id: number
  module_id: number
  nom_module: string
  groupe_id: number
  nom_groupe: string
  volume_horaire: number
  volume_minutes: number
  planned_minutes: number
  planned_hours: number
  remaining_minutes: number
  remaining_hours: number
  exceeded_minutes: number
  exceeded_hours: number
}

const api = useApi()
const router = useRouter()
const authStore = useAuthStore()

const isSidebarOpen = ref(false)
const loadingFormateurs = ref(false)
const hoursLoading = ref(false)
const error = ref<string | null>(null)

const formateurs = ref<FormateurOption[]>([])
const selectedFormateurId = ref<number | null>(null)
const formateurSearch = ref('')
const showFormateurPicker = ref(false)
const moduleHours = ref<ModuleHoursRow[]>([])

const totalPlannedMinutes = computed(() => moduleHours.value.reduce((sum, row) => sum + row.planned_minutes, 0))

const selectedFormateurLabel = computed(() => {
  if (!selectedFormateurId.value) return ''
  return formateurs.value.find((f) => f.id === selectedFormateurId.value)?.label || ''
})

const filteredFormateurs = computed(() => {
  const query = formateurSearch.value.trim().toLowerCase()
  const base = !query ? formateurs.value : formateurs.value.filter((f) => f.label.toLowerCase().includes(query))
  return base
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

function formatMinutes(totalMinutes: number): string {
  const safe = Math.max(0, Number(totalMinutes) || 0)
  const h = Math.floor(safe / 60)
  const m = safe % 60
  return m === 0 ? `${h}h` : `${h}h${String(m).padStart(2, '0')}`
}

function selectFormateur(option: FormateurOption) {
  selectedFormateurId.value = option.id
  formateurSearch.value = option.label
  showFormateurPicker.value = false
}

function hideFormateurPicker() {
  setTimeout(() => {
    showFormateurPicker.value = false
  }, 120)
}

const loadFormateurs = async () => {
  loadingFormateurs.value = true
  error.value = null

  try {
    const response = await api.get<any>('/my-poles-formateurs')
    const raw = extractArray<FormateurItem>(response)
    formateurs.value = raw
      .map((f) => {
        const nom = f.user?.nom || ''
        const prenom = f.user?.prenom || ''
        return {
          id: f.id,
          label: `${nom} ${prenom}`.trim() || `Formateur #${f.id}`,
        }
      })
      .sort((a, b) => a.label.localeCompare(b.label, 'fr'))

    if (!selectedFormateurId.value && formateurs.value[0]) {
      selectedFormateurId.value = formateurs.value[0].id
      formateurSearch.value = formateurs.value[0].label
    }
  } catch (err: any) {
    error.value = err?.data?.message || err?.message || 'Erreur lors du chargement des formateurs'
  } finally {
    loadingFormateurs.value = false
  }
}

const loadModuleHours = async () => {
  if (!selectedFormateurId.value) {
    moduleHours.value = []
    return
  }

  hoursLoading.value = true
  error.value = null

  try {
    const response = await api.get<{ success: boolean; data: ModuleHoursRow[] }>(`/formateur/${selectedFormateurId.value}/module-hours`)
    moduleHours.value = Array.isArray(response.data) ? response.data : []
  } catch (err: any) {
    moduleHours.value = []
    error.value = err?.data?.message || err?.message || 'Erreur lors du chargement des heures modules'
  } finally {
    hoursLoading.value = false
  }
}

const handleLogout = async () => {
  isSidebarOpen.value = false
  await authStore.logout()
  router.push('/login')
}

watch(selectedFormateurId, () => {
  loadModuleHours()
})

onMounted(async () => {
  await loadFormateurs()
  await loadModuleHours()
})
</script>
