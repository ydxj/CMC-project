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
            <h1 class="text-sm font-semibold text-slate-900">Mon Emploi du Temps</h1>
          </div>

          <div class="flex items-center gap-3">
            <div class="hidden text-right sm:block">
              <p class="text-sm font-semibold text-slate-800">{{ fullName }}</p>
              <p class="text-xs text-slate-500">Formateur</p>
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

        <!-- Week navigation -->
        <div class="mb-5 flex items-center justify-between rounded-xl border border-slate-200 bg-white px-5 py-3 shadow-sm">
          <button
            @click="prevWeek"
            class="flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-40"
          >
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Précédente
          </button>
          <div class="text-center">
            <p class="text-sm font-semibold text-slate-900">{{ weekLabel }}</p>
            <button
              @click="goToCurrentWeek"
              class="mt-0.5 text-xs text-sky-600 hover:underline"
            >
              Semaine actuelle
            </button>
          </div>
          <button
            @click="nextWeek"
            class="flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Suivante
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>

        <section class="mb-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
          <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div>
              <p class="text-sm font-semibold text-slate-900">Suivi des Heures par Module</p>
              <p class="text-xs text-slate-500">Heures planifiees dans l'emploi du temps comparees au volume du module.</p>
            </div>
            <div class="flex items-center gap-2 text-xs">
              <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-700">Modules: {{ moduleHours.length }}</span>
              <span class="rounded-full bg-sky-100 px-2.5 py-1 font-semibold text-sky-700">Total planifie: {{ formatMinutes(totalPlannedMinutes) }}</span>
            </div>
          </div>

          <div v-if="hoursLoading" class="py-4 text-center text-sm text-slate-500">Chargement des heures...</div>
          <div v-else-if="hoursError" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">{{ hoursError }}</div>
          <div v-else-if="moduleHours.length === 0" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-500">
            Aucun module assigne.
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

        <!-- Loading -->
        <div v-if="loading" class="rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm">
          <div class="inline-block h-8 w-8 animate-spin rounded-full border-b-2 border-sky-600"></div>
          <p class="mt-2 text-slate-600">Chargement...</p>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="rounded-xl border border-red-200 bg-red-50 p-4">
          <p class="text-red-800">{{ error }}</p>
        </div>

        <!-- Timetable -->
        <div v-else class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
          <div class="overflow-x-auto">
            <table class="min-w-full border-collapse">
              <thead>
                <tr class="bg-slate-50">
                  <th class="w-28 border-b border-r border-slate-200 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Jour
                  </th>
                  <th
                    v-for="slot in TIME_SLOTS"
                    :key="slot.label"
                    class="border-b border-r border-slate-200 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500 last:border-r-0"
                  >
                    {{ slot.label }}
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(day, dayIdx) in weekDays"
                  :key="day.name"
                  class="border-b border-slate-100 last:border-b-0"
                  :class="{ 'bg-sky-50/40': isToday(day.date) }"
                >
                  <td class="border-r border-slate-200 px-4 py-3">
                    <p class="text-sm font-semibold text-slate-800">{{ day.name }}</p>
                    <p class="text-xs text-slate-400">{{ formatDate(day.date) }}</p>
                    <span
                      v-if="isToday(day.date)"
                      class="mt-1 inline-block rounded-full bg-sky-600 px-2 py-0.5 text-[10px] font-semibold text-white"
                    >
                      Aujourd'hui
                    </span>
                  </td>
                  <td
                    v-for="cell in dayCells[dayIdx]"
                    :key="cell.key"
                    :colspan="cell.colspan"
                    class="border-r border-slate-200 px-3 py-3 align-top last:border-r-0"
                    :class="{ 'min-w-[160px]': true }"
                  >
                    <div
                      v-if="cell.session"
                      class="rounded-lg border border-sky-200 bg-sky-50 p-2.5 text-left"
                    >
                      <p class="text-xs font-bold text-sky-800 leading-snug">
                        {{ cell.session.module_groupe_formateur.module.nom_module }}
                      </p>
                      <p class="mt-0.5 text-[11px] text-slate-500">
                        {{ cell.session.heure_debut.substring(0, 5) }} - {{ cell.session.heure_fin.substring(0, 5) }}
                      </p>
                      <div class="mt-1.5 flex flex-wrap gap-1.5">
                        <span class="inline-flex items-center gap-1 rounded-md bg-sky-100 px-2 py-0.5 text-[11px] font-medium text-sky-700">
                          <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 21v-1.5a3.5 3.5 0 00-3.5-3.5h-7A3.5 3.5 0 003 19.5V21M10 12a4 4 0 100-8 4 4 0 000 8" />
                          </svg>
                          {{ cell.session.module_groupe_formateur.groupe.nom_groupe }}
                        </span>
                        <span
                          v-if="cell.session.salle"
                          class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600"
                        >
                          <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 21h16M7 21V5a2 2 0 012-2h6a2 2 0 012 2v16" />
                          </svg>
                          {{ cell.session.salle.nom_salle }}
                        </span>
                      </div>
                    </div>
                    <div v-else class="h-4"></div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Legend -->
          <div class="border-t border-slate-200 bg-slate-50 px-5 py-3">
            <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500">
              <div class="flex items-center gap-1.5">
                <div class="h-3 w-3 rounded border border-sky-200 bg-sky-50"></div>
                <span>Séance planifiée</span>
              </div>
              <div class="flex items-center gap-1.5">
                <div class="h-3 w-3 rounded bg-sky-600"></div>
                <span>Aujourd'hui</span>
              </div>
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

interface GroupeItem {
  id: number
  nom_groupe: string
}

interface ModuleItem {
  id: number
  nom_module: string
  volume_horaire?: number | string
}

interface SalleItem {
  id: number
  nom_salle: string
}

interface ModuleGroupeFormateur {
  id: number
  id_module: number
  id_groupe: number
  id_formateur: number
  groupe: GroupeItem
  module: ModuleItem
}

interface Session {
  id: number
  date_seance: string
  heure_debut: string
  heure_fin: string
  id_module_groupe_formateur: number
  id_salle: number
  module_groupe_formateur: ModuleGroupeFormateur
  salle: SalleItem | null
}

interface DayCell {
  key: string
  colspan: number
  session: Session | null
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

const TIME_SLOTS = [
  { label: '8:30 - 11:00', start: '08:30' },
  { label: '11:00 - 13:30', start: '11:00' },
  { label: '13:30 - 16:00', start: '13:30' },
  { label: '16:00 - 18:30', start: '16:00' },
]

const DAY_NAMES = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']

const api = useApi()
const router = useRouter()
const authStore = useAuthStore()

const isSidebarOpen = ref(false)
const loading = ref(true)
const error = ref<string | null>(null)
const schedule = ref<Session[]>([])
const moduleHours = ref<ModuleHoursRow[]>([])
const hoursLoading = ref(false)
const hoursError = ref<string | null>(null)
const currentWeekStart = ref<Date>(getMonday(new Date()))

function getMonday(date: Date): Date {
  const d = new Date(date)
  d.setHours(0, 0, 0, 0)
  const day = d.getDay()
  const diff = day === 0 ? -6 : 1 - day
  d.setDate(d.getDate() + diff)
  return d
}

function formatDate(date: Date): string {
  return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' })
}

function formatDateISO(date: Date): string {
  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const d = String(date.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
}

function isToday(date: Date): boolean {
  const today = new Date()
  return (
    date.getFullYear() === today.getFullYear() &&
    date.getMonth() === today.getMonth() &&
    date.getDate() === today.getDate()
  )
}

const weekDays = computed(() =>
  DAY_NAMES.map((name, idx) => {
    const d = new Date(currentWeekStart.value)
    d.setDate(d.getDate() + idx)
    return { name, date: d }
  })
)

const weekLabel = computed(() => {
  const start = weekDays.value[0]!.date
  const end = weekDays.value[5]!.date
  const opts: Intl.DateTimeFormatOptions = { day: 'numeric', month: 'long', year: 'numeric' }
  return `Semaine du ${start.toLocaleDateString('fr-FR', opts)} au ${end.toLocaleDateString('fr-FR', opts)}`
})

const totalPlannedMinutes = computed(() => moduleHours.value.reduce((sum, row) => sum + row.planned_minutes, 0))

function toMinutes(time: string): number {
  const hhmm = time.substring(0, 5)
  const [h = 0, m = 0] = hhmm.split(':').map(Number)
  return h * 60 + m
}

function formatMinutes(totalMinutes: number): string {
  const safe = Math.max(0, Number(totalMinutes) || 0)
  const h = Math.floor(safe / 60)
  const m = safe % 60
  return m === 0 ? `${h}h` : `${h}h${String(m).padStart(2, '0')}`
}

function overlapsSlot(session: Session, slotIdx: number): boolean {
  const slot = TIME_SLOTS[slotIdx]
  const nextSlot = TIME_SLOTS[slotIdx + 1]
  if (!slot) return false

  const slotStart = toMinutes(slot.start)
  const slotEnd = nextSlot ? toMinutes(nextSlot.start) : toMinutes('18:30')
  const seanceStart = toMinutes(session.heure_debut)
  const seanceEnd = toMinutes(session.heure_fin)
  return seanceStart < slotEnd && seanceEnd > slotStart
}

function buildDayCells(dayIdx: number): DayCell[] {
  const dayDate = weekDays.value[dayIdx]?.date
  if (!dayDate) return []

  const dateStr = formatDateISO(dayDate)
  const daySessions = schedule.value
    .filter((s) => s.date_seance === dateStr)
    .sort((a, b) => toMinutes(a.heure_debut) - toMinutes(b.heure_debut))

  const cells: DayCell[] = []
  let slotIdx = 0

  while (slotIdx < TIME_SLOTS.length) {
    const session = daySessions.find((s) => overlapsSlot(s, slotIdx))

    if (!session) {
      cells.push({ key: `${dayIdx}-${slotIdx}-empty`, colspan: 1, session: null })
      slotIdx += 1
      continue
    }

    let endIdxExclusive = slotIdx
    while (endIdxExclusive < TIME_SLOTS.length && overlapsSlot(session, endIdxExclusive)) {
      endIdxExclusive += 1
    }

    cells.push({
      key: `${dayIdx}-${slotIdx}-session-${session.id}`,
      colspan: Math.max(1, endIdxExclusive - slotIdx),
      session,
    })
    slotIdx = endIdxExclusive
  }

  return cells
}

const dayCells = computed(() => weekDays.value.map((_, idx) => buildDayCells(idx)))

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

function prevWeek() {
  const d = new Date(currentWeekStart.value)
  d.setDate(d.getDate() - 7)
  currentWeekStart.value = d
}

function nextWeek() {
  const d = new Date(currentWeekStart.value)
  d.setDate(d.getDate() + 7)
  currentWeekStart.value = d
}

function goToCurrentWeek() {
  currentWeekStart.value = getMonday(new Date())
}

async function loadSchedule() {
  loading.value = true
  error.value = null
  try {
    const startDate = formatDateISO(currentWeekStart.value)
    const response = await api.get<{ success: boolean; data: Session[] }>(
      '/me/emploi-du-temps',
      { start_date: startDate }
    )
    schedule.value = Array.isArray(response.data) ? response.data : []
  } catch (err: any) {
    error.value = err?.data?.message || err?.message || 'Erreur lors du chargement de l\'emploi du temps'
  } finally {
    loading.value = false
  }
}

async function loadModuleHours() {
  hoursLoading.value = true
  hoursError.value = null

  try {
    const response = await api.get<{ success: boolean; data: ModuleHoursRow[] }>('/me/module-hours')
    moduleHours.value = Array.isArray(response.data) ? response.data : []
  } catch (err: any) {
    hoursError.value = err?.data?.message || err?.message || 'Erreur lors du chargement des heures modules'
  } finally {
    hoursLoading.value = false
  }
}

const handleLogout = async () => {
  isSidebarOpen.value = false
  await authStore.logout()
  router.push('/login')
}

watch(currentWeekStart, () => loadSchedule())

onMounted(() => {
  loadModuleHours()
  loadSchedule()
})
</script>
