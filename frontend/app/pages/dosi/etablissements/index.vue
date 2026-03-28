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
          <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-600">DOSI</p>
              <h2 class="mt-2 text-2xl font-bold text-slate-900">Nouveaux etablissements</h2>
              <p class="mt-2 text-sm text-slate-600">Affichage pagine a 25 elements par page.</p>
            </div>
            <button
              type="button"
              class="inline-flex items-center justify-center rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700"
              @click="openCreateModal"
            >
              Nouveau etablissement
            </button>
          </div>

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
                  <th class="px-4 py-3 text-left">Ville</th>
                  <th class="px-4 py-3 text-left">Directeur</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 bg-white">
                <tr v-for="e in etablissements" :key="e.id">
                  <td class="px-4 py-3">{{ e.nom_etablissement }}</td>
                  <td class="px-4 py-3">{{ e.type_etablissement }}</td>
                  <td class="px-4 py-3">{{ e.region_name || '-' }}</td>
                  <td class="px-4 py-3">{{ e.city_name || '-' }}</td>
                  <td class="px-4 py-3">
                    <span v-if="e.directeur">{{ e.directeur.prenom }} {{ e.directeur.nom }}</span>
                    <span v-else class="text-slate-500">Non affecte</span>
                  </td>
                </tr>
                <tr v-if="!etablissements.length">
                  <td colspan="5" class="px-4 py-6 text-center text-slate-500">Aucun etablissement</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="total > 0" class="flex flex-col gap-4 border-t border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <p class="text-sm font-medium text-slate-600">
              Affichage {{ from }}-{{ to }} sur {{ total }} etablissements
            </p>

            <div class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white p-2 shadow-sm">
              <button
                type="button"
                class="inline-flex items-center rounded px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-transparent"
                :disabled="currentPage <= 1"
                @click="goToPage(currentPage - 1)"
              >
                Precedent
              </button>

              <span class="px-2 text-sm font-semibold text-slate-700">
                Page {{ currentPage }} / {{ lastPage }}
              </span>

              <button
                type="button"
                class="inline-flex items-center rounded px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-transparent"
                :disabled="currentPage >= lastPage"
                @click="goToPage(currentPage + 1)"
              >
                Suivant
              </button>
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
        <div class="w-full max-w-2xl rounded-2xl border border-slate-200 bg-white shadow-2xl">
          <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <div>
              <h3 class="text-lg font-bold text-slate-900">Creer un etablissement</h3>
              <p class="mt-1 text-xs text-slate-500">Selectionnez une region puis une ville.</p>
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

          <form class="grid gap-4 p-6 md:grid-cols-2" @submit.prevent="createEtablissement">
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
              <div ref="regionDropdownRef" class="relative">
                <button
                  type="button"
                  :disabled="loadingModalOptions"
                  class="flex w-full items-center justify-between rounded-lg border border-slate-300 bg-white px-4 py-2 text-left text-slate-900 disabled:cursor-not-allowed disabled:bg-slate-100"
                  @click="isRegionDropdownOpen = !isRegionDropdownOpen"
                >
                  <span :class="form.region_id ? 'text-slate-900' : 'text-slate-500'">{{ selectedRegionLabel }}</span>
                  <svg class="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 8l4 4 4-4" />
                  </svg>
                </button>

                <div v-if="isRegionDropdownOpen" class="absolute z-20 mt-1 w-full rounded-lg border border-slate-200 bg-white p-2 shadow-xl">
                  <input
                    v-model="regionSearch"
                    type="text"
                    placeholder="Rechercher une region..."
                    class="mb-2 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-transparent focus:ring-2 focus:ring-sky-500"
                  >

                  <div class="max-h-52 overflow-y-auto rounded-md border border-slate-100">
                    <button
                      v-for="region in filteredRegions"
                      :key="region.id"
                      type="button"
                      class="block w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100"
                      :class="form.region_id === region.id ? 'bg-sky-50 text-sky-700' : ''"
                      @click="selectRegion(region.id)"
                    >
                      {{ region.region }}
                    </button>

                    <p v-if="loadingModalOptions" class="px-3 py-2 text-sm text-slate-500">Chargement des regions...</p>
                    <p v-else-if="!filteredRegions.length" class="px-3 py-2 text-sm text-slate-500">Aucune region trouvee.</p>
                  </div>
                </div>
              </div>
            </div>

            <div class="md:col-span-2">
              <label class="mb-1 block text-sm font-medium text-slate-700">Ville</label>
              <div ref="cityDropdownRef" class="relative">
                <button
                  type="button"
                  :disabled="!form.region_id || loadingModalOptions"
                  class="flex w-full items-center justify-between rounded-lg border border-slate-300 bg-white px-4 py-2 text-left text-slate-900 disabled:cursor-not-allowed disabled:bg-slate-100"
                  @click="isCityDropdownOpen = !isCityDropdownOpen"
                >
                  <span :class="form.city_id ? 'text-slate-900' : 'text-slate-500'">{{ selectedCityLabel }}</span>
                  <svg class="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 8l4 4 4-4" />
                  </svg>
                </button>

                <div v-if="isCityDropdownOpen && form.region_id" class="absolute z-20 mt-1 w-full rounded-lg border border-slate-200 bg-white p-2 shadow-xl">
                  <input
                    v-model="citySearch"
                    type="text"
                    placeholder="Rechercher une ville..."
                    class="mb-2 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-transparent focus:ring-2 focus:ring-sky-500"
                  >

                  <div class="max-h-52 overflow-y-auto rounded-md border border-slate-100">
                    <button
                      v-for="city in filteredCities"
                      :key="city.id"
                      type="button"
                      class="block w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100"
                      :class="form.city_id === city.id ? 'bg-sky-50 text-sky-700' : ''"
                      @click="selectCity(city.id)"
                    >
                      {{ city.ville }}
                    </button>

                    <p v-if="!filteredCities.length" class="px-3 py-2 text-sm text-slate-500">Aucune ville trouvee.</p>
                  </div>
                </div>
              </div>
              <p class="mt-1 text-xs text-slate-500">Choisissez d abord une region, puis filtrez dans la liste des villes.</p>
            </div>

            <div class="md:col-span-2">
              <label class="mb-1 block text-sm font-medium text-slate-700">Directeur (optionnel)</label>
              <div ref="directeurDropdownRef" class="relative">
                <button
                  type="button"
                  :disabled="loadingModalOptions"
                  class="flex w-full items-center justify-between rounded-lg border border-slate-300 bg-white px-4 py-2 text-left text-slate-900 disabled:cursor-not-allowed disabled:bg-slate-100"
                  @click="isDirecteurDropdownOpen = !isDirecteurDropdownOpen"
                >
                  <span :class="form.id_directeur ? 'text-slate-900' : 'text-slate-500'">{{ selectedDirecteurLabel }}</span>
                  <svg class="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 8l4 4 4-4" />
                  </svg>
                </button>

                <div v-if="isDirecteurDropdownOpen" class="absolute z-20 mt-1 w-full rounded-lg border border-slate-200 bg-white p-2 shadow-xl">
                  <input
                    v-model="directeurSearch"
                    type="text"
                    placeholder="Rechercher un directeur..."
                    class="mb-2 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-transparent focus:ring-2 focus:ring-sky-500"
                  >

                  <div class="max-h-52 overflow-y-auto rounded-md border border-slate-100">
                    <button
                      type="button"
                      class="block w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100"
                      :class="form.id_directeur === null ? 'bg-sky-50 text-sky-700' : ''"
                      @click="selectDirecteur(null)"
                    >
                      Aucun
                    </button>

                    <button
                      v-for="d in filteredDirecteurs"
                      :key="d.id"
                      type="button"
                      class="block w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100"
                      :class="form.id_directeur === d.id ? 'bg-sky-50 text-sky-700' : ''"
                      @click="selectDirecteur(d.id)"
                    >
                      {{ d.prenom }} {{ d.nom }} - {{ roleLabel(d.role) }}
                    </button>

                    <p v-if="loadingModalOptions" class="px-3 py-2 text-sm text-slate-500">Chargement des directeurs...</p>
                    <p v-else-if="!filteredDirecteurs.length" class="px-3 py-2 text-sm text-slate-500">Aucun directeur trouve.</p>
                  </div>
                </div>
              </div>
              <p class="mt-1 text-xs text-slate-500">Seuls les directeurs non assignes et compatibles sont proposes.</p>
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
                :disabled="submitting"
                class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-50"
              >
                {{ submitting ? 'Creation...' : 'Creer etablissement' }}
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
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
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
  city_id: number
  city_name?: string
  region_id?: number
  region_name?: string
  id_directeur: number | null
  directeur?: Directeur | null
}

interface RegionOption {
  id: number
  region: string
}

interface CityOption {
  id: number
  ville: string
  region: number
}

interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}

const router = useRouter()
const api = useApi()
const authStore = useAuthStore()

const isSidebarOpen = ref(false)
const loading = ref(false)
const loadingModalOptions = ref(false)
const submitting = ref(false)
const error = ref('')
const success = ref('')
const directeurSearch = ref('')
const regionSearch = ref('')
const citySearch = ref('')
const isCreateModalOpen = ref(false)
const isRegionDropdownOpen = ref(false)
const isCityDropdownOpen = ref(false)
const isDirecteurDropdownOpen = ref(false)
const regionDropdownRef = ref<HTMLElement | null>(null)
const cityDropdownRef = ref<HTMLElement | null>(null)
const directeurDropdownRef = ref<HTMLElement | null>(null)

const etablissements = ref<Etablissement[]>([])
const directeurs = ref<Directeur[]>([])
const regions = ref<RegionOption[]>([])
const cities = ref<CityOption[]>([])

const currentPage = ref(1)
const perPage = ref(25)
const lastPage = ref(1)
const total = ref(0)
const from = ref(0)
const to = ref(0)

const form = ref({
  nom_etablissement: '',
  type_etablissement: 'CMC' as 'CMC' | 'EFP',
  region_id: null as number | null,
  city_id: null as number | null,
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

const filteredRegions = computed(() => {
  const query = regionSearch.value.trim().toLowerCase()
  if (!query) return regions.value

  return regions.value.filter((r) => r.region.toLowerCase().includes(query))
})

const filteredCities = computed(() => {
  const query = citySearch.value.trim().toLowerCase()
  if (!query) return cities.value

  return cities.value.filter((c) => c.ville.toLowerCase().includes(query))
})

const selectedCityLabel = computed(() => {
  if (!form.value.city_id) return 'Selectionner une ville'
  const selected = cities.value.find((c) => c.id === form.value.city_id)
  return selected?.ville || 'Selectionner une ville'
})

const selectedRegionLabel = computed(() => {
  if (!form.value.region_id) return 'Selectionner une region'
  const selected = regions.value.find((r) => r.id === form.value.region_id)
  return selected?.region || 'Selectionner une region'
})

const selectedDirecteurLabel = computed(() => {
  if (!form.value.id_directeur) return 'Aucun'
  const selected = compatibleDirecteurs.value.find((d) => d.id === form.value.id_directeur)
  if (!selected) return 'Aucun'
  return `${selected.prenom} ${selected.nom} - ${roleLabel(selected.role)}`
})

const roleLabel = (role: string) => {
  if (role === 'DIRECTEUR_CMC') return 'Directeur CMC'
  if (role === 'DIRECTEUR_EFP') return 'Directeur EFP'
  return role
}

const extractArray = <T>(payload: T[] | { data?: T[] }): T[] => {
  return Array.isArray(payload) ? payload : (payload.data || [])
}

const resetForm = () => {
  form.value = {
    nom_etablissement: '',
    type_etablissement: 'CMC',
    region_id: null,
    city_id: null,
    id_directeur: null,
  }
  directeurSearch.value = ''
  regionSearch.value = ''
  citySearch.value = ''
  cities.value = []
  isRegionDropdownOpen.value = false
  isCityDropdownOpen.value = false
  isDirecteurDropdownOpen.value = false
}

const openCreateModal = async () => {
  resetForm()
  isCreateModalOpen.value = true
  loadingModalOptions.value = true

  try {
    await Promise.all([loadRegions(), loadDirecteurs()])
  } catch (err: any) {
    error.value = err?.data?.message || err?.message || 'Impossible de charger les donnees du formulaire.'
  } finally {
    loadingModalOptions.value = false
  }
}

const closeCreateModal = () => {
  isCreateModalOpen.value = false
  isRegionDropdownOpen.value = false
  isCityDropdownOpen.value = false
  isDirecteurDropdownOpen.value = false
}

const selectRegion = (regionId: number) => {
  form.value.region_id = regionId
  isRegionDropdownOpen.value = false
}

const selectCity = (cityId: number) => {
  form.value.city_id = cityId
  isCityDropdownOpen.value = false
}

const selectDirecteur = (directeurId: number | null) => {
  form.value.id_directeur = directeurId
  isDirecteurDropdownOpen.value = false
}

const handleDocumentClick = (event: MouseEvent) => {
  const target = event.target as Node | null

  if (!target) return

  if (regionDropdownRef.value && !regionDropdownRef.value.contains(target)) {
    isRegionDropdownOpen.value = false
  }

  if (cityDropdownRef.value && !cityDropdownRef.value.contains(target)) {
    isCityDropdownOpen.value = false
  }

  if (directeurDropdownRef.value && !directeurDropdownRef.value.contains(target)) {
    isDirecteurDropdownOpen.value = false
  }
}

const loadRegions = async () => {
  const response = await api.get<RegionOption[] | { data: RegionOption[] }>('/locations/regions')
  regions.value = extractArray(response)
}

const loadDirecteurs = async () => {
  const [cmcResponse, efpResponse] = await Promise.all([
    api.get<Directeur[] | { data: Directeur[] }>('/users', { role: 'DIRECTEUR_CMC' }),
    api.get<Directeur[] | { data: Directeur[] }>('/users', { role: 'DIRECTEUR_EFP' }),
  ])

  const merged = [...extractArray(cmcResponse), ...extractArray(efpResponse)]
  const uniqueById = new Map<number, Directeur>()
  for (const directeur of merged) {
    uniqueById.set(directeur.id, directeur)
  }

  directeurs.value = Array.from(uniqueById.values())
}

const loadCitiesForRegion = async (regionId: number) => {
  const response = await api.get<CityOption[] | { data: CityOption[] }>(`/locations/regions/${regionId}/cities`)
  cities.value = extractArray(response)
}

const loadData = async () => {
  loading.value = true
  error.value = ''

  try {
    const etabResponse = await api.get<PaginatedResponse<Etablissement>>('/etablissements', {
      page: currentPage.value,
      per_page: perPage.value,
    })

    etablissements.value = etabResponse.data || []
    currentPage.value = etabResponse.current_page || 1
    lastPage.value = etabResponse.last_page || 1
    total.value = etabResponse.total || 0
    from.value = etabResponse.from || 0
    to.value = etabResponse.to || 0
  } catch (err: any) {
    error.value = err?.data?.message || err?.message || 'Erreur lors du chargement des donnees.'
  } finally {
    loading.value = false
  }
}

const createEtablissement = async () => {
  if (!form.value.region_id) {
    error.value = 'Veuillez selectionner une region.'
    return
  }

  if (!form.value.city_id) {
    error.value = 'Veuillez selectionner une ville.'
    return
  }

  submitting.value = true
  error.value = ''
  success.value = ''

  try {
    const payload = {
      nom_etablissement: form.value.nom_etablissement,
      type_etablissement: form.value.type_etablissement,
      city_id: form.value.city_id,
      id_directeur: form.value.id_directeur,
    }

    await api.post('/etablissements', payload)
    success.value = 'Etablissement cree avec succes.'

    closeCreateModal()
    resetForm()
    currentPage.value = 1

    await loadData()
  } catch (err: any) {
    error.value = err?.data?.message || err?.message || 'Impossible de creer l etablissement.'
  } finally {
    submitting.value = false
  }
}

const goToPage = async (page: number) => {
  if (page < 1 || page > lastPage.value || page === currentPage.value) return
  currentPage.value = page
  await loadData()
}

const handleLogout = async () => {
  isSidebarOpen.value = false
  await authStore.logout()
  router.push('/login')
}

watch(() => form.value.type_etablissement, () => {
  form.value.id_directeur = null
  directeurSearch.value = ''
  isDirecteurDropdownOpen.value = false
})

watch(() => form.value.region_id, async (regionId) => {
  form.value.city_id = null
  citySearch.value = ''
  cities.value = []
  isCityDropdownOpen.value = false

  if (!regionId) return
  await loadCitiesForRegion(regionId)
})

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

  await loadData()
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleDocumentClick)
})
</script>
