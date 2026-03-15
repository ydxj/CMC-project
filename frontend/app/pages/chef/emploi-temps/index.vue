<template>
  <div class="min-h-screen bg-slate-100 text-slate-800" @mouseup="endDrag(); cancelMove()">
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
            <h1 class="text-sm font-semibold text-slate-900">Emploi du Temps du Pole</h1>
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
        <section class="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
          <div class="grid gap-4 lg:grid-cols-[1fr_1fr_2fr]">
            <div>
              <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Vue</p>
              <div class="inline-flex rounded-lg border border-slate-300 bg-slate-50 p-1">
                <button
                  type="button"
                  @click="viewMode = 'formateur'"
                  class="rounded-md px-3 py-1.5 text-xs font-semibold transition"
                  :class="viewMode === 'formateur' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                >
                  Multi formateurs
                </button>
                <button
                  type="button"
                  @click="viewMode = 'groupe'"
                  class="rounded-md px-3 py-1.5 text-xs font-semibold transition"
                  :class="viewMode === 'groupe' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                >
                  Par groupe
                </button>
              </div>
            </div>

            <div v-if="viewMode === 'formateur'" class="block">
              <span class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Formateurs (multi-selection)</span>
              <div class="relative">
                <input
                  v-model="multiFormateurSearch"
                  type="text"
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                  placeholder="Rechercher un formateur..."
                  @focus="showMultiFormateurPicker = true"
                  @input="showMultiFormateurPicker = true"
                  @blur="hideMultiFormateurPicker"
                />
                <div
                  v-if="showMultiFormateurPicker"
                  class="absolute z-30 mt-1 max-h-56 w-full overflow-auto rounded-lg border border-slate-200 bg-white p-1 shadow-lg"
                >
                  <button
                    v-for="f in filteredFormateurOptions"
                    :key="f.id"
                    type="button"
                    class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm hover:bg-slate-100"
                    :class="selectedFormateurIds.includes(f.id) ? 'text-slate-900' : 'text-slate-700'"
                    @mousedown.prevent="toggleFormateurSelection(f.id)"
                  >
                    <span
                      class="inline-flex h-4 w-4 flex-shrink-0 items-center justify-center rounded-full border-2 transition-colors"
                      :style="selectedFormateurIds.includes(f.id)
                        ? { backgroundColor: getFormateurColor(f.id)?.solid || '#64748b', borderColor: getFormateurColor(f.id)?.solid || '#64748b' }
                        : { backgroundColor: 'transparent', borderColor: '#cbd5e1' }"
                    ></span>
                    <span :class="selectedFormateurIds.includes(f.id) ? 'font-semibold' : ''">{{ f.label }}</span>
                  </button>
                  <p v-if="!filteredFormateurOptions.length" class="px-2 py-2 text-xs text-slate-500">Aucun formateur trouve.</p>
                </div>
              </div>
              <div v-if="selectedFormateurIds.length > 0" class="mt-2 flex flex-wrap gap-1.5">
                <span
                  v-for="fId in selectedFormateurIds"
                  :key="`chip-${fId}`"
                  class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold"
                  :style="{ backgroundColor: getFormateurColor(fId)?.light || 'rgba(148,163,184,0.12)', borderColor: getFormateurColor(fId)?.border || '#94a3b8' }"
                >
                  <span class="inline-block h-2 w-2 rounded-full" :style="{ backgroundColor: getFormateurColor(fId)?.solid || '#94a3b8' }"></span>
                  {{ getFormateurLabel(fId) }}
                  <button type="button" class="ml-0.5 font-bold text-slate-500 hover:text-slate-900 focus:outline-none" @mousedown.prevent="removeFormateurSelection(fId)" aria-label="Retirer">&times;</button>
                </span>
              </div>
              <p v-else class="mt-1.5 text-xs text-slate-400">Aucun formateur selectionne. Tapez pour en chercher.</p>
            </div>

            <div v-else class="block">
              <span class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Groupe</span>
              <div class="relative">
                <input
                  v-model="groupeSearch"
                  type="text"
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                  placeholder="Rechercher un groupe..."
                  @focus="showGroupePicker = true"
                  @input="showGroupePicker = true"
                  @blur="hideGroupePicker"
                />
                <div
                  v-if="showGroupePicker"
                  class="absolute z-30 mt-1 max-h-56 w-full overflow-auto rounded-lg border border-slate-200 bg-white p-1 shadow-lg"
                >
                  <button
                    v-for="g in filteredGroupeOptions"
                    :key="g.id"
                    type="button"
                    class="w-full rounded-md px-2 py-1.5 text-left text-sm text-slate-700 hover:bg-slate-100"
                    :class="selectedGroupeId === g.id ? 'bg-slate-100 font-semibold text-slate-900' : ''"
                    @mousedown.prevent="selectGroupeOption(g.id, g.label)"
                  >
                    {{ g.label }}
                  </button>
                  <p v-if="!filteredGroupeOptions.length" class="px-2 py-2 text-xs text-slate-500">Aucun groupe trouve.</p>
                </div>
              </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs text-slate-600">
              Glissez sur les cases vides pour creer une seance. Glissez une seance existante pour la deplacer vers un autre jour/creneau.
            </div>
          </div>
        </section>

        <div class="mb-5 flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-5 py-3 shadow-sm">
          <button
            @click="prevWeek"
            class="flex items-center gap-2 rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"
          >
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Precedente
          </button>
          <div class="text-center">
            <p class="text-sm font-semibold text-slate-900">{{ weekLabel }}</p>
            <button @click="goToCurrentWeek" class="mt-0.5 text-xs text-sky-600 hover:underline">Semaine actuelle</button>
          </div>
          <button
            @click="nextWeek"
            class="flex items-center gap-2 rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"
          >
            Suivante
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>

        <div v-if="error" class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
          {{ error }}
        </div>

        <div v-if="loading" class="rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm">
          <div class="inline-block h-8 w-8 animate-spin rounded-full border-b-2 border-sky-600"></div>
          <p class="mt-2 text-slate-600">Chargement...</p>
        </div>

        <div v-if="viewMode === 'formateur' && selectedFormateurIds.length > 0" class="mb-3 flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 shadow-sm">
          <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Legende:</span>
          <span
            v-for="fId in selectedFormateurIds"
            :key="`legend-${fId}`"
            class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold"
            :style="{ backgroundColor: getFormateurColor(fId)?.light || 'rgba(148,163,184,0.12)', borderColor: getFormateurColor(fId)?.border || '#94a3b8' }"
          >
            <span class="inline-block h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: getFormateurColor(fId)?.solid || '#64748b' }"></span>
            {{ getFormateurLabel(fId) }}
          </span>
        </div>

        <div v-if="!loading" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
          <div class="overflow-x-auto">
            <table class="min-w-full select-none border-separate border-spacing-0" @mouseleave="endDrag(); cancelMove()">
              <thead>
                <tr class="bg-slate-100">
                  <th class="w-32 border-b border-r border-slate-200 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Jour</th>
                  <th
                    v-for="slot in TIME_SLOTS"
                    :key="slot.label"
                    class="border-b border-r border-slate-200 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-600 last:border-r-0"
                  >
                    {{ slot.label }}
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(day, dayIdx) in weekDays"
                  :key="day.name"
                  class="odd:bg-white even:bg-slate-50/20"
                >
                  <td class="border-r border-slate-200 px-4 py-3 align-top">
                    <p class="text-sm font-semibold text-slate-800">{{ day.name }}</p>
                    <p class="mt-0.5 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-500">{{ formatDate(day.date) }}</p>
                  </td>

                  <td
                    v-for="cell in dayCells[dayIdx]"
                    :key="cell.key"
                    :colspan="cell.colspan"
                    class="min-w-[170px] border-r border-slate-200 px-2 py-2 align-top last:border-r-0"
                    @mouseenter="cell.slotIdx !== null ? handleCellEnter(dayIdx, cell.slotIdx) : undefined"
                    @mouseup="cell.slotIdx !== null ? handleCellMouseUp(dayIdx, cell.slotIdx) : undefined"
                  >
                    <div
                      v-if="cell.session"
                      class="rounded-lg border border-slate-200 bg-white p-2.5 text-left shadow-sm"
                      :style="getSessionCardStyle(cell.session)"
                      :class="{ 'cursor-move opacity-70': isMovingSession(cell.session) }"
                      @mousedown.prevent.stop="startMove(cell.session, dayIdx, cell.slotIdx ?? 0)"
                    >
                      <p class="text-xs font-bold text-slate-800">{{ cell.session.module_groupe_formateur.module.nom_module }}</p>
                      <p class="mt-1 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">
                        {{ cell.session.heure_debut.substring(0, 5) }} - {{ cell.session.heure_fin.substring(0, 5) }}
                      </p>
                      <p class="mt-1 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">
                        Duree: {{ getSessionDurationLabel(cell.session) }}
                      </p>
                      <p class="mt-1.5 text-[11px] font-medium text-slate-700">
                        {{ viewMode === 'groupe' ? getFormateurDisplayName(cell.session) : cell.session.module_groupe_formateur.groupe.nom_groupe }}
                      </p>
                      <p v-if="cell.session.salle" class="text-[11px] text-slate-500">{{ cell.session.salle.nom_salle }}</p>
                      <div class="mt-2 flex items-center justify-end gap-2">
                        <button
                          type="button"
                          class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-300 text-slate-600 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                          :disabled="isPastSession(cell.session)"
                          @click.stop="openEditModal(cell.session)"
                          title="Modifier"
                          aria-label="Modifier"
                        >
                          <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 3.487a2.1 2.1 0 113.03 2.908L8.7 18.078 4 19.5l1.34-4.56L16.862 3.487z" />
                          </svg>
                        </button>
                        <button
                          type="button"
                          class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-red-200 text-red-600 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40"
                          :disabled="isPastSession(cell.session)"
                          @click.stop="deleteSession(cell.session)"
                          title="Supprimer"
                          aria-label="Supprimer"
                        >
                          <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-1 12a2 2 0 01-2 2H8a2 2 0 01-2-2L5 7M10 11v6M14 11v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16" />
                          </svg>
                        </button>
                      </div>
                    </div>

                    <div
                      v-else
                      class="h-14 cursor-crosshair rounded-lg border border-dashed transition"
                      :class="isSlotHighlighted(dayIdx, cell.slotIdx!) ? 'border-slate-500 bg-slate-100 ring-1 ring-slate-400' : 'border-slate-300 bg-white hover:border-slate-400 hover:bg-slate-50'"
                      @mousedown.prevent.stop="startDrag(dayIdx, cell.slotIdx!)"
                    >
                      <div
                        v-if="isMoveTarget(dayIdx, cell.slotIdx!)"
                        class="flex h-full items-center justify-center rounded-lg text-[11px] font-semibold"
                        :class="isMoveTargetValid(dayIdx, cell.slotIdx!) ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'"
                      >
                        {{ isMoveTargetValid(dayIdx, cell.slotIdx!) ? 'Deposer ici' : 'Conflit' }}
                      </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
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

    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isModalOpen"
        class="fixed inset-0 z-[1000] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
        @click.self="closeModal"
      >
        <div class="w-full max-w-2xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
          <div class="border-b border-slate-200 px-5 py-4">
            <h3 class="text-lg font-semibold text-slate-900">{{ modalMode === 'create' ? 'Creer une seance' : 'Modifier une seance' }}</h3>
            <p class="mt-1 text-xs text-slate-600">{{ modalDate }} | {{ modalDurationLabel }}</p>
          </div>

          <div class="space-y-4 p-5">
            <div v-if="modalError" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
              {{ modalError }}
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
              <label class="block">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Heure debut</span>
                <input v-model="modalStartTime" type="time" step="1800" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
              </label>
              <label class="block">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Heure fin</span>
                <input v-model="modalEndTime" type="time" step="1800" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
              </label>
            </div>

            <div class="flex flex-wrap gap-2">
              <button class="rounded-full border border-slate-300 px-3 py-1 text-[11px] font-semibold text-slate-600 hover:bg-slate-100" @click="applyPresetDuration(150)">2:30</button>
              <button class="rounded-full border border-slate-300 px-3 py-1 text-[11px] font-semibold text-slate-600 hover:bg-slate-100" @click="applyPresetDuration(300)">5:00</button>
            </div>

            <div class="block">
              <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ viewMode === 'formateur' ? 'Groupe + Module' : 'Formateur + Module' }}</span>
              <div class="relative">
                <input
                  v-model="modalAssignmentSearch"
                  type="text"
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                  placeholder="Rechercher une affectation..."
                  @focus="showModalAssignmentPicker = true"
                  @input="showModalAssignmentPicker = true"
                  @blur="hideModalAssignmentPicker"
                />
                <div
                  v-if="showModalAssignmentPicker"
                  class="absolute z-30 mt-1 max-h-56 w-full overflow-auto rounded-lg border border-slate-200 bg-white p-1 shadow-lg"
                >
                  <button
                    v-for="a in filteredModalAssignments"
                    :key="a.id"
                    type="button"
                    class="w-full rounded-md px-2 py-1.5 text-left text-sm text-slate-700 hover:bg-slate-100"
                    :class="modalAssignmentId === a.id ? 'bg-slate-100 font-semibold text-slate-900' : ''"
                    @mousedown.prevent="selectModalAssignment(a.id)"
                  >
                    {{ assignmentLabel(a, viewMode) }}
                  </button>
                  <p v-if="!filteredModalAssignments.length" class="px-2 py-2 text-xs text-slate-500">Aucune affectation trouvee.</p>
                </div>
              </div>
            </div>

            <div class="block">
              <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Salle</span>
              <div class="relative">
                <input
                  v-model="modalSalleSearch"
                  type="text"
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                  placeholder="Rechercher une salle..."
                  @focus="showModalSallePicker = true"
                  @input="showModalSallePicker = true"
                  @blur="hideModalSallePicker"
                />
                <div
                  v-if="showModalSallePicker"
                  class="absolute z-30 mt-1 max-h-56 w-full overflow-auto rounded-lg border border-slate-200 bg-white p-1 shadow-lg"
                >
                  <button
                    v-for="s in filteredSalles"
                    :key="s.id"
                    type="button"
                    class="w-full rounded-md px-2 py-1.5 text-left text-sm text-slate-700 hover:bg-slate-100"
                    :class="modalSalleId === s.id ? 'bg-slate-100 font-semibold text-slate-900' : ''"
                    @mousedown.prevent="selectModalSalle(s.id)"
                  >
                    {{ s.nom_salle }}
                  </button>
                  <p v-if="!filteredSalles.length" class="px-2 py-2 text-xs text-slate-500">Aucune salle trouvee.</p>
                </div>
              </div>
            </div>
          </div>

          <div class="flex items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-3">
            <button class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700" @click="closeModal">Annuler</button>
            <button
              class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white disabled:opacity-50"
              :disabled="saving"
              @click="saveSession"
            >
              {{ saving ? 'Enregistrement...' : (modalMode === 'create' ? 'Enregistrer' : 'Mettre a jour') }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '../../../../composables/useApi'
import { useAuthStore } from '../../../../stores/auth'

definePageMeta({ middleware: ['auth'] })

type ViewMode = 'formateur' | 'groupe'

interface UserItem {
  id: number
  nom: string
  prenom: string
}

interface FormateurItem {
  id: number
  id_user?: number
  user?: UserItem
}

interface GroupeItem {
  id: number
  nom_groupe: string
}

interface ModuleItem {
  id: number
  nom_module: string
}

interface SalleItem {
  id: number
  nom_salle: string
}

interface Assignment {
  id: number
  id_module: number
  id_groupe: number
  id_formateur: number
  module: ModuleItem
  groupe: GroupeItem
  formateur?: FormateurItem
}

interface Session {
  id: number
  date_seance: string
  heure_debut: string
  heure_fin: string
  id_module_groupe_formateur: number
  id_salle: number
  module_groupe_formateur: Assignment
  salle: SalleItem | null
}

interface DayCell {
  key: string
  colspan: number
  session: Session | null
  slotIdx: number | null
}

const SLOT_BOUNDARIES = ['08:30', '11:00', '13:30', '16:00', '18:30']
const TIME_SLOTS = [
  { label: '8:30 - 11:00', start: SLOT_BOUNDARIES[0] },
  { label: '11:00 - 13:30', start: SLOT_BOUNDARIES[1] },
  { label: '13:30 - 16:00', start: SLOT_BOUNDARIES[2] },
  { label: '16:00 - 18:30', start: SLOT_BOUNDARIES[3] },
]

const DAY_NAMES = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']
const FORMATEUR_COLOR_PALETTE = [
  { bg: 'rgba(14, 165, 233, 0.18)', border: 'rgba(14, 165, 233, 0.55)', solid: '#0ea5e9', light: 'rgba(14, 165, 233, 0.10)' },
  { bg: 'rgba(99, 102, 241, 0.18)', border: 'rgba(99, 102, 241, 0.55)', solid: '#6366f1', light: 'rgba(99, 102, 241, 0.10)' },
  { bg: 'rgba(16, 185, 129, 0.18)', border: 'rgba(16, 185, 129, 0.55)', solid: '#10b981', light: 'rgba(16, 185, 129, 0.10)' },
  { bg: 'rgba(244, 114, 182, 0.18)', border: 'rgba(244, 114, 182, 0.55)', solid: '#f472b6', light: 'rgba(244, 114, 182, 0.10)' },
  { bg: 'rgba(251, 146, 60, 0.18)', border: 'rgba(251, 146, 60, 0.55)', solid: '#fb923c', light: 'rgba(251, 146, 60, 0.10)' },
  { bg: 'rgba(168, 85, 247, 0.18)', border: 'rgba(168, 85, 247, 0.55)', solid: '#a855f7', light: 'rgba(168, 85, 247, 0.10)' },
  { bg: 'rgba(234, 179, 8, 0.18)', border: 'rgba(234, 179, 8, 0.55)', solid: '#eab308', light: 'rgba(234, 179, 8, 0.10)' },
  { bg: 'rgba(20, 184, 166, 0.18)', border: 'rgba(20, 184, 166, 0.55)', solid: '#14b8a6', light: 'rgba(20, 184, 166, 0.10)' },
] as const

const api = useApi()
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const isSidebarOpen = ref(false)
const loading = ref(true)
const saving = ref(false)
const error = ref<string | null>(null)

const viewMode = ref<ViewMode>(route.query.view === 'groupe' ? 'groupe' : 'formateur')
const selectedFormateurIds = ref<number[]>([])
const selectedGroupeId = ref<number | null>(null)
const multiFormateurSearch = ref('')
const groupeSearch = ref('')
const showMultiFormateurPicker = ref(false)
const showGroupePicker = ref(false)

const allAssignments = ref<Assignment[]>([])
const allSchedule = ref<Session[]>([])
const salles = ref<SalleItem[]>([])

const currentWeekStart = ref<Date>(getMonday(new Date()))

const isModalOpen = ref(false)
const modalError = ref<string | null>(null)
const modalMode = ref<'create' | 'edit'>('create')
const editingSessionId = ref<number | null>(null)
const modalDate = ref('')
const modalStartTime = ref('08:30')
const modalEndTime = ref('11:00')
const modalAssignmentId = ref<number | null>(null)
const modalSalleId = ref<number | null>(null)
const modalAssignmentSearch = ref('')
const modalSalleSearch = ref('')
const showModalAssignmentPicker = ref(false)
const showModalSallePicker = ref(false)

const dragDayIdx = ref<number | null>(null)
const dragStartSlot = ref<number | null>(null)
const dragCurrentSlot = ref<number | null>(null)

const movingSession = ref<Session | null>(null)
const moveHoverDayIdx = ref<number | null>(null)
const moveHoverSlotIdx = ref<number | null>(null)

const DAY_START_MINUTES = toMinutes('08:30')
const DAY_END_MINUTES = toMinutes('18:30')

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

function toMinutes(time: string): number {
  const hhmm = time.substring(0, 5)
  const [h = 0, m = 0] = hhmm.split(':').map(Number)
  return h * 60 + m
}

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

function getTodayISO(): string {
  return formatDateISO(new Date())
}

function isPastDate(dateISO: string): boolean {
  return dateISO < getTodayISO()
}

function isPastDay(dayIdx: number): boolean {
  const day = weekDays.value[dayIdx]
  if (!day) return false
  return isPastDate(formatDateISO(day.date))
}

function isPastSession(session: Session): boolean {
  return isPastDate(session.date_seance)
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

const formateurOptions = computed(() => {
  const map = new Map<number, { id: number; label: string }>()
  for (const a of allAssignments.value) {
    const label = a.formateur?.user ? `${a.formateur.user.nom} ${a.formateur.user.prenom}` : `Formateur #${a.id_formateur}`
    map.set(a.id_formateur, { id: a.id_formateur, label })
  }
  return [...map.values()].sort((a, b) => a.label.localeCompare(b.label))
})

const groupeOptions = computed(() => {
  const map = new Map<number, { id: number; label: string }>()
  for (const a of allAssignments.value) {
    map.set(a.id_groupe, { id: a.id_groupe, label: a.groupe?.nom_groupe || `Groupe #${a.id_groupe}` })
  }
  return [...map.values()].sort((a, b) => a.label.localeCompare(b.label))
})

const filteredFormateurOptions = computed(() => {
  const query = multiFormateurSearch.value.trim().toLowerCase()
  const base = !query ? formateurOptions.value : formateurOptions.value.filter((f) => f.label.toLowerCase().includes(query))
  return [...base].sort((a, b) => {
    const aSelected = selectedFormateurIds.value.includes(a.id)
    const bSelected = selectedFormateurIds.value.includes(b.id)
    if (aSelected !== bSelected) return aSelected ? 1 : -1
    return a.label.localeCompare(b.label)
  })
})

const formateurColorMap = computed(() => {
  const map = new Map<number, { bg: string; border: string; solid: string; light: string }>()
  selectedFormateurIds.value.forEach((id, idx) => {
    map.set(id, FORMATEUR_COLOR_PALETTE[idx % FORMATEUR_COLOR_PALETTE.length]!)
  })
  return map
})

const filteredGroupeOptions = computed(() => {
  const query = groupeSearch.value.trim().toLowerCase()
  if (!query) return groupeOptions.value
  return groupeOptions.value.filter((g) => g.label.toLowerCase().includes(query))
})

function toggleFormateurSelection(id: number) {
  const idx = selectedFormateurIds.value.indexOf(id)
  if (idx === -1) {
    selectedFormateurIds.value = [...selectedFormateurIds.value, id]
  } else {
    selectedFormateurIds.value = selectedFormateurIds.value.filter((fId) => fId !== id)
  }
  multiFormateurSearch.value = ''
}

function removeFormateurSelection(id: number) {
  selectedFormateurIds.value = selectedFormateurIds.value.filter((fId) => fId !== id)
}

function selectGroupeOption(id: number, label: string) {
  selectedGroupeId.value = id
  groupeSearch.value = label
  showGroupePicker.value = false
}

function hideMultiFormateurPicker() {
  setTimeout(() => {
    showMultiFormateurPicker.value = false
  }, 120)
}

function hideGroupePicker() {
  setTimeout(() => {
    showGroupePicker.value = false
  }, 120)
}

function selectModalAssignment(assignmentId: number) {
  modalAssignmentId.value = assignmentId
  const selected = modalAssignments.value.find((a) => a.id === assignmentId)
  modalAssignmentSearch.value = selected ? assignmentLabel(selected, viewMode.value) : ''
  showModalAssignmentPicker.value = false
}

function selectModalSalle(salleId: number) {
  modalSalleId.value = salleId
  const selected = salles.value.find((s) => s.id === salleId)
  modalSalleSearch.value = selected?.nom_salle || ''
  showModalSallePicker.value = false
}

function hideModalAssignmentPicker() {
  setTimeout(() => {
    showModalAssignmentPicker.value = false
  }, 120)
}

function hideModalSallePicker() {
  setTimeout(() => {
    showModalSallePicker.value = false
  }, 120)
}

const filteredSchedule = computed(() => {
  if (viewMode.value === 'formateur' && selectedFormateurIds.value.length > 0) {
    const selectedIds = new Set(selectedFormateurIds.value)
    return allSchedule.value.filter((s) => selectedIds.has(s.module_groupe_formateur.id_formateur))
  }

  if (viewMode.value === 'groupe' && selectedGroupeId.value) {
    return allSchedule.value.filter((s) => s.module_groupe_formateur.id_groupe === selectedGroupeId.value)
  }

  return allSchedule.value
})

const modalAssignments = computed(() => {
  if (viewMode.value === 'formateur') {
    if (selectedFormateurIds.value.length === 0) return allAssignments.value
    const selectedIds = new Set(selectedFormateurIds.value)
    return allAssignments.value.filter((a) => selectedIds.has(a.id_formateur))
  }
  return allAssignments.value.filter((a) => a.id_groupe === selectedGroupeId.value)
})

function getFormateurColor(id: number) {
  return formateurColorMap.value.get(id) || null
}

function getFormateurLabel(id: number): string {
  return formateurOptions.value.find((f) => f.id === id)?.label || `Formateur #${id}`
}

function getSessionCardStyle(session: Session): Record<string, string> {
  if (viewMode.value !== 'formateur') return {}
  const color = formateurColorMap.value.get(session.module_groupe_formateur.id_formateur)
  if (!color) return {}
  return {
    backgroundColor: color.bg,
    borderColor: color.border,
  }
}

const filteredModalAssignments = computed(() => {
  const query = modalAssignmentSearch.value.trim().toLowerCase()
  if (!query) return modalAssignments.value

  return modalAssignments.value.filter((a) => assignmentLabel(a, viewMode.value).toLowerCase().includes(query))
})

const filteredSalles = computed(() => {
  const query = modalSalleSearch.value.trim().toLowerCase()
  if (!query) return salles.value
  return salles.value.filter((s) => s.nom_salle.toLowerCase().includes(query))
})

const modalDurationLabel = computed(() => {
  const diff = toMinutes(modalEndTime.value) - toMinutes(modalStartTime.value)
  if (diff <= 0) return 'Duree invalide'
  const h = Math.floor(diff / 60)
  const m = diff % 60
  return m === 0 ? `${h}h` : `${h}h${String(m).padStart(2, '0')}`
})

function assignmentLabel(assignment: Assignment, mode: ViewMode): string {
  if (mode === 'formateur') {
    return `${assignment.groupe?.nom_groupe || '-'} | ${assignment.module?.nom_module || '-'}`
  }

  const formateurName = assignment.formateur?.user
    ? `${assignment.formateur.user.nom} ${assignment.formateur.user.prenom}`
    : `Formateur #${assignment.id_formateur}`

  return `${formateurName} | ${assignment.module?.nom_module || '-'}`
}

function getFormateurDisplayName(session: Session): string {
  const direct = session.module_groupe_formateur.formateur?.user
  if (direct) {
    return `${direct.nom} ${direct.prenom}`.trim()
  }

  const fallback = allAssignments.value.find((a) => a.id === session.id_module_groupe_formateur)
  if (fallback?.formateur?.user) {
    return `${fallback.formateur.user.nom} ${fallback.formateur.user.prenom}`.trim()
  }

  return `Formateur #${session.module_groupe_formateur.id_formateur}`
}

function getSessionDurationLabel(session: Session): string {
  const diff = toMinutes(session.heure_fin) - toMinutes(session.heure_debut)
  const h = Math.floor(diff / 60)
  const m = diff % 60
  if (m === 0) return `${h}h`
  return `${h}h${String(m).padStart(2, '0')}`
}

function overlapsSlot(session: Session, slotIdx: number): boolean {
  const slotStart = toMinutes(SLOT_BOUNDARIES[slotIdx] || '08:30')
  const slotEnd = toMinutes(SLOT_BOUNDARIES[slotIdx + 1] || '18:30')
  const seanceStart = toMinutes(session.heure_debut)
  const seanceEnd = toMinutes(session.heure_fin)
  return seanceStart < slotEnd && seanceEnd > slotStart
}

function getSessionSpan(session: Session): number {
  let span = 0
  for (let i = 0; i < TIME_SLOTS.length; i += 1) {
    if (overlapsSlot(session, i)) span += 1
  }
  return Math.max(1, span)
}

function buildDayCells(dayIdx: number): DayCell[] {
  const dayDate = weekDays.value[dayIdx]?.date
  if (!dayDate) return []

  const dateStr = formatDateISO(dayDate)
  const daySessions = filteredSchedule.value
    .filter((s) => s.date_seance === dateStr)
    .sort((a, b) => toMinutes(a.heure_debut) - toMinutes(b.heure_debut))

  const cells: DayCell[] = []
  let slotIdx = 0

  while (slotIdx < TIME_SLOTS.length) {
    const session = daySessions.find((s) => overlapsSlot(s, slotIdx))

    if (!session) {
      cells.push({ key: `${dayIdx}-${slotIdx}-empty`, colspan: 1, session: null, slotIdx })
      slotIdx += 1
      continue
    }

    const span = getSessionSpan(session)
    cells.push({
      key: `${dayIdx}-${slotIdx}-session-${session.id}`,
      colspan: span,
      session,
      slotIdx,
    })
    slotIdx += span
  }

  return cells
}

const dayCells = computed(() => weekDays.value.map((_, idx) => buildDayCells(idx)))

function isSlotSelected(dayIdx: number, slotIdx: number): boolean {
  if (dragDayIdx.value !== dayIdx || dragStartSlot.value === null || dragCurrentSlot.value === null) {
    return false
  }

  const start = Math.min(dragStartSlot.value, dragCurrentSlot.value)
  const end = Math.max(dragStartSlot.value, dragCurrentSlot.value)
  return slotIdx >= start && slotIdx <= end
}

function isMoveTarget(dayIdx: number, slotIdx: number): boolean {
  return moveHoverDayIdx.value === dayIdx && moveHoverSlotIdx.value === slotIdx
}

function canMoveToTarget(dayIdx: number, slotIdx: number): boolean {
  if (!movingSession.value) return false

  const targetDay = weekDays.value[dayIdx]?.date
  const targetStart = SLOT_BOUNDARIES[slotIdx]
  if (!targetDay || !targetStart) return false

  if (isPastDate(formatDateISO(targetDay))) return false

  const durationMinutes = toMinutes(movingSession.value.heure_fin) - toMinutes(movingSession.value.heure_debut)
  const newStartMin = toMinutes(targetStart)
  const newEndMin = newStartMin + durationMinutes
  if (newEndMin > DAY_END_MINUTES) return false

  const newDate = formatDateISO(targetDay)
  const newStart = minutesToHHMM(newStartMin)
  const newEnd = minutesToHHMM(newEndMin)
  const conflict = getConflictMessage(
    newDate,
    newStart,
    newEnd,
    {
      groupeId: movingSession.value.module_groupe_formateur.id_groupe,
      formateurId: movingSession.value.module_groupe_formateur.id_formateur,
      salleId: movingSession.value.id_salle,
    },
    movingSession.value.id,
  )
  return !conflict
}

function isMoveTargetValid(dayIdx: number, slotIdx: number): boolean {
  return isMoveTarget(dayIdx, slotIdx) && canMoveToTarget(dayIdx, slotIdx)
}

function isSlotHighlighted(dayIdx: number, slotIdx: number): boolean {
  if (movingSession.value) {
    return isMoveTarget(dayIdx, slotIdx)
  }
  return isSlotSelected(dayIdx, slotIdx)
}

function handleCellEnter(dayIdx: number, slotIdx: number) {
  if (movingSession.value) {
    moveHoverDayIdx.value = dayIdx
    moveHoverSlotIdx.value = slotIdx
    return
  }

  updateDrag(dayIdx, slotIdx)
}

function handleCellMouseUp(dayIdx: number, slotIdx: number) {
  if (movingSession.value) {
    endMove(dayIdx, slotIdx)
    return
  }

  endDrag(dayIdx, slotIdx)
}

function isWithinPlannerBounds(start: string, end: string): boolean {
  const startMin = toMinutes(start)
  const endMin = toMinutes(end)
  return startMin >= DAY_START_MINUTES && endMin <= DAY_END_MINUTES
}

function getConflictMessage(
  date: string,
  start: string,
  end: string,
  context: { groupeId?: number | null; formateurId?: number | null; salleId?: number | null },
  ignoreSessionId?: number,
): string | null {
  const startMin = toMinutes(start)
  const endMin = toMinutes(end)
  for (const s of allSchedule.value) {
    if (s.date_seance !== date) continue
    if (ignoreSessionId && s.id === ignoreSessionId) continue
    const sStart = toMinutes(s.heure_debut)
    const sEnd = toMinutes(s.heure_fin)
    if (!(startMin < sEnd && endMin > sStart)) continue

    if (context.groupeId && s.module_groupe_formateur.id_groupe === context.groupeId) {
      return 'Conflit: ce groupe a deja une seance sur ce creneau.'
    }

    if (context.formateurId && s.module_groupe_formateur.id_formateur === context.formateurId) {
      return 'Conflit: ce formateur a deja une seance sur ce creneau.'
    }

    if (context.salleId && s.id_salle === context.salleId) {
      return 'Conflit: cette salle est deja reservee sur ce creneau.'
    }
  }

  return null
}

function startDrag(dayIdx: number, slotIdx: number) {
  if (movingSession.value) return

  if (isPastDay(dayIdx)) {
    error.value = 'Les seances passees sont verrouillees et ne peuvent pas etre modifiees.'
    return
  }

  if (viewMode.value === 'formateur' && selectedFormateurIds.value.length === 0) {
    error.value = 'Choisissez au moins un formateur avant de planifier.'
    return
  }

  if (viewMode.value === 'groupe' && !selectedGroupeId.value) {
    error.value = 'Choisissez un groupe avant de planifier.'
    return
  }

  error.value = null
  dragDayIdx.value = dayIdx
  dragStartSlot.value = slotIdx
  dragCurrentSlot.value = slotIdx
}

function updateDrag(dayIdx: number, slotIdx: number) {
  if (dragDayIdx.value !== dayIdx || dragStartSlot.value === null) return
  dragCurrentSlot.value = slotIdx
}

function endDrag(dayIdx?: number, slotIdx?: number) {
  if (movingSession.value) return

  if (dayIdx !== undefined && dragDayIdx.value !== dayIdx) return
  if (dragStartSlot.value === null || dragDayIdx.value === null) return

  const finalSlot = slotIdx ?? dragCurrentSlot.value
  if (finalSlot === null) {
    dragDayIdx.value = null
    dragStartSlot.value = null
    dragCurrentSlot.value = null
    return
  }

  const startIdx = Math.min(dragStartSlot.value, finalSlot)
  const endIdx = Math.max(dragStartSlot.value, finalSlot) + 1

  const dayDate = weekDays.value[dragDayIdx.value]?.date
  if (!dayDate) {
    dragDayIdx.value = null
    dragStartSlot.value = null
    dragCurrentSlot.value = null
    return
  }

  const dateISO = formatDateISO(dayDate)
  const startTime = SLOT_BOUNDARIES[startIdx] || '08:30'
  const endTime = SLOT_BOUNDARIES[endIdx] || '18:30'

  const draftConflict = getConflictMessage(
    dateISO,
    startTime,
    endTime,
    {
      groupeId: viewMode.value === 'groupe' ? selectedGroupeId.value : null,
      formateurId: null,
    },
  )

  if (draftConflict) {
    error.value = draftConflict
    dragDayIdx.value = null
    dragStartSlot.value = null
    dragCurrentSlot.value = null
    return
  }

  modalDate.value = dateISO
  modalStartTime.value = startTime
  modalEndTime.value = endTime
  modalMode.value = 'create'
  editingSessionId.value = null
  modalAssignmentId.value = null
  modalSalleId.value = null
  modalAssignmentSearch.value = ''
  modalSalleSearch.value = ''
  showModalAssignmentPicker.value = false
  showModalSallePicker.value = false
  modalError.value = null
  isModalOpen.value = true

  dragDayIdx.value = null
  dragStartSlot.value = null
  dragCurrentSlot.value = null
}

function isMovingSession(session: Session): boolean {
  return movingSession.value?.id === session.id
}

function startMove(session: Session, dayIdx: number, slotIdx: number) {
  if (isPastSession(session)) {
    error.value = 'Les seances passees sont verrouillees et ne peuvent pas etre modifiees.'
    return
  }

  error.value = null
  movingSession.value = session
  moveHoverDayIdx.value = dayIdx
  moveHoverSlotIdx.value = slotIdx
  dragDayIdx.value = null
  dragStartSlot.value = null
  dragCurrentSlot.value = null
}

function cancelMove() {
  if (!movingSession.value) return
  movingSession.value = null
  moveHoverDayIdx.value = null
  moveHoverSlotIdx.value = null
}

function minutesToHHMM(totalMinutes: number): string {
  const h = Math.floor(totalMinutes / 60)
  const m = totalMinutes % 60
  return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`
}

async function endMove(dayIdx?: number, slotIdx?: number) {
  if (!movingSession.value) return

  const targetDayIdx = dayIdx ?? moveHoverDayIdx.value
  const targetSlotIdx = slotIdx ?? moveHoverSlotIdx.value

  if (targetDayIdx === null || targetSlotIdx === null) {
    movingSession.value = null
    moveHoverDayIdx.value = null
    moveHoverSlotIdx.value = null
    return
  }

  const targetDay = weekDays.value[targetDayIdx]?.date
  const targetStart = SLOT_BOUNDARIES[targetSlotIdx]
  if (!targetDay || !targetStart) {
    movingSession.value = null
    moveHoverDayIdx.value = null
    moveHoverSlotIdx.value = null
    return
  }

  if (!canMoveToTarget(targetDayIdx, targetSlotIdx)) {
    error.value = 'Deplacement impossible: conflit ou depassement de plage.'
    movingSession.value = null
    moveHoverDayIdx.value = null
    moveHoverSlotIdx.value = null
    return
  }

  const durationMinutes = toMinutes(movingSession.value.heure_fin) - toMinutes(movingSession.value.heure_debut)
  const newStartMin = toMinutes(targetStart)
  const newEndMin = newStartMin + durationMinutes

  const newDate = formatDateISO(targetDay)
  const newStart = minutesToHHMM(newStartMin)
  const newEnd = minutesToHHMM(newEndMin)
  const moveConflict = getConflictMessage(
    newDate,
    newStart,
    newEnd,
    {
      groupeId: movingSession.value.module_groupe_formateur.id_groupe,
      formateurId: movingSession.value.module_groupe_formateur.id_formateur,
      salleId: movingSession.value.id_salle,
    },
    movingSession.value.id,
  )

  if (moveConflict) {
    error.value = moveConflict
    movingSession.value = null
    moveHoverDayIdx.value = null
    moveHoverSlotIdx.value = null
    return
  }

  try {
    await api.put(`/emploi-du-temps/${movingSession.value.id}`, {
      date_seance: newDate,
      heure_debut: newStart,
      heure_fin: newEnd,
    })
    await loadSchedule()
  } catch (err: any) {
    error.value = err?.data?.message || err?.message || 'Erreur lors du deplacement de la seance'
  } finally {
    movingSession.value = null
    moveHoverDayIdx.value = null
    moveHoverSlotIdx.value = null
  }
}

function closeModal() {
  modalError.value = null
  modalMode.value = 'create'
  editingSessionId.value = null
  showModalAssignmentPicker.value = false
  showModalSallePicker.value = false
  isModalOpen.value = false
}

function openEditModal(session: Session) {
  if (isPastSession(session)) {
    error.value = 'Les seances passees sont verrouillees et ne peuvent pas etre modifiees.'
    return
  }

  modalMode.value = 'edit'
  editingSessionId.value = session.id
  modalDate.value = session.date_seance
  modalStartTime.value = session.heure_debut.substring(0, 5)
  modalEndTime.value = session.heure_fin.substring(0, 5)
  modalAssignmentId.value = session.id_module_groupe_formateur
  modalSalleId.value = session.id_salle
  const selectedAssignment = modalAssignments.value.find((a) => a.id === session.id_module_groupe_formateur)
  modalAssignmentSearch.value = selectedAssignment ? assignmentLabel(selectedAssignment, viewMode.value) : ''
  const selectedSalle = salles.value.find((s) => s.id === session.id_salle)
  modalSalleSearch.value = selectedSalle?.nom_salle || ''
  showModalAssignmentPicker.value = false
  showModalSallePicker.value = false
  modalError.value = null
  isModalOpen.value = true
}

async function deleteSession(session: Session) {
  if (isPastSession(session)) {
    error.value = 'Les seances passees sont verrouillees et ne peuvent pas etre modifiees.'
    return
  }

  try {
    await api.delete(`/emploi-du-temps/${session.id}`)
    await loadSchedule()
  } catch (err: any) {
    error.value = err?.data?.message || err?.message || 'Erreur lors de la suppression de la seance'
  }
}

function applyPresetDuration(durationMinutes: number) {
  const nextEnd = toMinutes(modalStartTime.value) + durationMinutes
  const bounded = Math.min(nextEnd, DAY_END_MINUTES)
  const h = Math.floor(bounded / 60)
  const m = bounded % 60
  modalEndTime.value = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`
}

async function saveSession() {
  if (isPastDate(modalDate.value)) {
    modalError.value = 'Les seances passees sont verrouillees et ne peuvent pas etre modifiees.'
    return
  }

  if (!modalAssignmentId.value || !modalSalleId.value) {
    modalError.value = 'Choisissez une affectation et une salle.'
    return
  }

  if (toMinutes(modalEndTime.value) <= toMinutes(modalStartTime.value)) {
    modalError.value = 'Heure fin doit etre apres heure debut.'
    return
  }

  if (!isWithinPlannerBounds(modalStartTime.value, modalEndTime.value)) {
    modalError.value = 'La seance doit etre dans la plage 08:30 - 18:30.'
    return
  }

  const assignment = allAssignments.value.find((a) => a.id === modalAssignmentId.value)
  if (!assignment) {
    modalError.value = 'Affectation invalide.'
    return
  }

  const sessionIdToIgnore = modalMode.value === 'edit' ? editingSessionId.value ?? undefined : undefined
  const modalConflict = getConflictMessage(
    modalDate.value,
    modalStartTime.value,
    modalEndTime.value,
    {
      groupeId: assignment.id_groupe,
      formateurId: assignment.id_formateur,
      salleId: modalSalleId.value,
    },
    sessionIdToIgnore,
  )

  if (modalConflict) {
    modalError.value = modalConflict
    return
  }

  saving.value = true
  modalError.value = null
  error.value = null

  try {
    const payload = {
      date_seance: modalDate.value,
      heure_debut: modalStartTime.value,
      heure_fin: modalEndTime.value,
      id_groupe: assignment.id_groupe,
      id_module: assignment.id_module,
      id_formateur: assignment.id_formateur,
      id_salle: modalSalleId.value,
    }

    if (modalMode.value === 'edit' && editingSessionId.value) {
      await api.put(`/emploi-du-temps/${editingSessionId.value}`, payload)
    } else {
      await api.post('/emploi-du-temps', payload)
    }

    closeModal()
    await loadSchedule()
  } catch (err: any) {
    modalError.value = err?.data?.message || err?.message || 'Erreur lors de la creation de la seance'
  } finally {
    saving.value = false
  }
}

async function loadBaseData() {
  const [assignmentsRes, sallesRes] = await Promise.all([
    api.get<Assignment[]>('/module-formateur'),
    api.get<SalleItem[]>('/salles'),
  ])

  allAssignments.value = Array.isArray(assignmentsRes) ? assignmentsRes : ((assignmentsRes as any)?.data || [])
  salles.value = Array.isArray(sallesRes) ? sallesRes : ((sallesRes as any)?.data || [])

  if (selectedFormateurIds.value.length === 0 && formateurOptions.value.length > 0) {
    selectedFormateurIds.value = [formateurOptions.value[0]!.id]
  }

  if (!selectedGroupeId.value && groupeOptions.value[0]) {
    selectedGroupeId.value = groupeOptions.value[0].id
  }
}

async function loadSchedule() {
  loading.value = true
  error.value = null

  try {
    const params: Record<string, string | number> = {
      start_date: formatDateISO(currentWeekStart.value),
    }

    if (viewMode.value === 'groupe' && selectedGroupeId.value) {
      params.id_groupe = selectedGroupeId.value
    }

    const response = await api.get<{ success: boolean; data: Session[] }>('/emploi-du-temps/weekly', params)
    allSchedule.value = Array.isArray(response.data) ? response.data : []
  } catch (err: any) {
    error.value = err?.data?.message || err?.message || 'Erreur lors du chargement de l emploi du temps'
  } finally {
    loading.value = false
  }
}

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

const handleLogout = async () => {
  isSidebarOpen.value = false
  await authStore.logout()
  router.push('/login')
}

watch(viewMode, () => {
  if (viewMode.value === 'formateur' && selectedFormateurIds.value.length === 0 && formateurOptions.value[0]) {
    selectedFormateurIds.value = [formateurOptions.value[0].id]
  }

  if (viewMode.value === 'groupe' && !selectedGroupeId.value && groupeOptions.value[0]) {
    selectedGroupeId.value = groupeOptions.value[0].id
  }

  router.replace({ query: { ...route.query, view: viewMode.value } })
  loadSchedule()
})

watch(selectedGroupeId, (id) => {
  if (!id) return
  const selected = groupeOptions.value.find((g) => g.id === id)
  if (selected) groupeSearch.value = selected.label
})

watch(modalAssignmentId, (id) => {
  if (!id) {
    modalAssignmentSearch.value = ''
    return
  }
  const selected = modalAssignments.value.find((a) => a.id === id)
  if (selected) modalAssignmentSearch.value = assignmentLabel(selected, viewMode.value)
})

watch(modalSalleId, (id) => {
  if (!id) {
    modalSalleSearch.value = ''
    return
  }
  const selected = salles.value.find((s) => s.id === id)
  if (selected) modalSalleSearch.value = selected.nom_salle
})

watch([selectedFormateurIds, selectedGroupeId, currentWeekStart], () => {
  loadSchedule()
})

onMounted(async () => {
  await loadBaseData()
  await loadSchedule()
})
</script>
