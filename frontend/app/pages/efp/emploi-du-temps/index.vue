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
            <h1 class="text-sm font-semibold text-slate-900">Emploi du Temps</h1>
          </div>

          <div class="flex items-center gap-3">
            <div class="hidden text-right sm:block">
              <p class="text-sm font-semibold text-slate-800">{{ fullName }}</p>
              <p class="text-xs text-slate-500">Directeur</p>
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
        <!-- Filters row -->
        <section class="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
          <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-[auto_1fr_1fr]">
            <!-- View Mode Toggle -->
            <div>
              <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Vue</p>
              <div class="inline-flex rounded-lg border border-slate-300 bg-slate-50 p-1">
                <button
                  type="button"
                  @click="setViewMode('general')"
                  class="rounded-md px-3 py-1.5 text-xs font-semibold transition"
                  :class="viewMode === 'general' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                >
                  Planning general
                </button>
                <button
                  type="button"
                  @click="setViewMode('duo')"
                  class="rounded-md px-3 py-1.5 text-xs font-semibold transition"
                  :class="viewMode === 'duo' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                >
                  Multi Formateurs
                </button>
                <button
                  type="button"
                  @click="setViewMode('formateur')"
                  class="rounded-md px-3 py-1.5 text-xs font-semibold transition"
                  :class="viewMode === 'formateur' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                >
                  Formateur
                </button>
              </div>
            </div>

            <div v-if="viewMode === 'general'" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs text-slate-600">
              Le planning general affiche toutes les seances de votre etablissement EFP.
            </div>

            <!-- Multi-select formateurs -->
            <div v-else-if="viewMode === 'duo'" class="sm:col-span-2 lg:col-span-2">
              <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Formateurs selectionnés</span>
                <span
                  v-if="selectedCompareFormateurIds.length > 0"
                  class="inline-flex items-center justify-center h-5 min-w-[20px] rounded-full bg-sky-100 px-1.5 text-[11px] font-bold text-sky-700"
                >{{ selectedCompareFormateurIds.length }}</span>
              </div>

              <!-- Trigger / combobox row -->
              <div class="relative">
                <div
                  class="flex min-h-[42px] w-full cursor-text flex-wrap items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3 py-1.5 shadow-sm transition focus-within:border-sky-500 focus-within:ring-2 focus-within:ring-sky-500/20"
                  @click="showMultiFormateurPicker = true"
                >
                  <!-- Selected chips inside the input box -->
                  <span
                    v-for="fId in selectedCompareFormateurIds"
                    :key="`chip-${fId}`"
                    class="inline-flex items-center gap-1.5 rounded-full border py-0.5 pl-2 pr-1 text-[11px] font-semibold shadow-sm"
                    :style="{ backgroundColor: getFormateurColor(fId)?.light || 'rgba(148,163,184,0.12)', borderColor: getFormateurColor(fId)?.border || '#94a3b8', color: getFormateurColor(fId)?.solid || '#64748b' }"
                  >
                    <span class="inline-block h-2 w-2 rounded-full flex-shrink-0" :style="{ backgroundColor: getFormateurColor(fId)?.solid || '#94a3b8' }"></span>
                    {{ getFormateurLabel(fId) }}
                    <button
                      type="button"
                      class="ml-0.5 flex h-3.5 w-3.5 flex-shrink-0 items-center justify-center rounded-full text-current opacity-60 transition hover:opacity-100 focus:outline-none"
                      @mousedown.prevent="removeFormateurSelection(fId)"
                      aria-label="Retirer"
                    >
                      <svg viewBox="0 0 10 10" class="h-2.5 w-2.5" fill="currentColor"><path d="M6.414 5l2.293-2.293a1 1 0 00-1.414-1.414L5 3.586 2.707 1.293A1 1 0 001.293 2.707L3.586 5 1.293 7.293a1 1 0 001.414 1.414L5 6.414l2.293 2.293a1 1 0 001.414-1.414z"/></svg>
                    </button>
                  </span>

                  <!-- Search input -->
                  <input
                    v-model="multiFormateurSearch"
                    type="text"
                    class="min-w-[120px] flex-1 border-none bg-transparent py-0.5 text-sm text-slate-700 placeholder-slate-400 outline-none"
                    :placeholder="selectedCompareFormateurIds.length === 0 ? 'Rechercher un formateur…' : 'Ajouter un formateur…'"
                    @focus="showMultiFormateurPicker = true"
                    @input="showMultiFormateurPicker = true"
                    @blur="hideMultiFormateurPicker"
                    @keydown.esc="showMultiFormateurPicker = false"
                  />

                  <!-- Caret icon -->
                  <svg class="ml-auto h-4 w-4 flex-shrink-0 text-slate-400 transition-transform" :class="showMultiFormateurPicker ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" pointer-events="none">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </div>

                <!-- Dropdown -->
                <Transition
                  enter-active-class="transition duration-100 ease-out"
                  enter-from-class="opacity-0 scale-95 -translate-y-1"
                  enter-to-class="opacity-100 scale-100 translate-y-0"
                  leave-active-class="transition duration-75 ease-in"
                  leave-from-class="opacity-100 scale-100 translate-y-0"
                  leave-to-class="opacity-0 scale-95 -translate-y-1"
                >
                  <div
                    v-if="showMultiFormateurPicker"
                    class="absolute z-30 mt-1.5 max-h-64 w-full origin-top overflow-auto rounded-xl border border-slate-200 bg-white py-1 shadow-xl ring-1 ring-slate-900/5"
                  >
                    <div class="sticky top-0 z-10 border-b border-slate-100 bg-white/95 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-400 backdrop-blur">
                      Liste des formateurs
                    </div>
                    <template v-if="filteredMultiFormateurOptions.length > 0">
                      <button
                        v-for="f in filteredMultiFormateurOptions"
                        :key="f.id"
                        type="button"
                        class="group flex w-full items-center gap-3 px-3 py-2 text-left text-sm transition"
                        :class="selectedCompareFormateurIds.includes(f.id) ? 'bg-slate-50' : 'hover:bg-slate-50'"
                        @mousedown.prevent="toggleFormateurSelection(f.id)"
                      >
                        <!-- Color swatch / checkmark -->
                        <span
                          class="relative inline-flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full border-2 transition-all"
                          :style="selectedCompareFormateurIds.includes(f.id)
                            ? { backgroundColor: getFormateurColor(f.id)?.solid || '#64748b', borderColor: getFormateurColor(f.id)?.solid || '#64748b' }
                            : { backgroundColor: 'transparent', borderColor: '#cbd5e1' }"
                        >
                          <svg v-if="selectedCompareFormateurIds.includes(f.id)" class="h-2.5 w-2.5 text-white" viewBox="0 0 12 12" fill="currentColor">
                            <path d="M10.28 2.28a1 1 0 00-1.41 0L4.5 6.65 3.13 5.28a1 1 0 00-1.41 1.41l2.08 2.08a1 1 0 001.41 0l5.07-5.07a1 1 0 000-1.42z"/>
                          </svg>
                          <span
                            v-else
                            class="h-2 w-2 rounded-full opacity-0 transition group-hover:opacity-100"
                            :style="{ backgroundColor: FORMATEUR_COLOR_PALETTE[filteredMultiFormateurOptions.indexOf(f) % FORMATEUR_COLOR_PALETTE.length]?.solid || '#94a3b8' }"
                          ></span>
                        </span>

                        <!-- Avatar initials -->
                        <span
                          class="inline-flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full text-[10px] font-bold text-white shadow-sm"
                          :style="{ backgroundColor: selectedCompareFormateurIds.includes(f.id) ? (getFormateurColor(f.id)?.solid || '#64748b') : '#94a3b8' }"
                        >{{ f.label.split(' ').map((w: string) => w[0]).slice(0, 2).join('').toUpperCase() }}</span>

                        <span class="flex-1 truncate font-medium text-slate-700" :class="selectedCompareFormateurIds.includes(f.id) ? 'text-slate-900' : ''">{{ f.label }}</span>

                        <span v-if="selectedCompareFormateurIds.includes(f.id)" class="text-[10px] font-semibold text-slate-400">Selectionne</span>
                      </button>
                    </template>
                    <p v-else class="px-4 py-3 text-xs text-slate-500">Aucun formateur trouvé.</p>
                  </div>
                </Transition>
              </div>

              <p v-if="selectedCompareFormateurIds.length === 0" class="mt-2 text-xs text-slate-400 italic">Aucun formateur sélectionné — tapez pour en chercher.</p>
            </div>

            <!-- Formateur picker -->
            <div v-else-if="viewMode === 'formateur'">
              <span class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Formateur</span>
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
                    v-for="f in filteredFormateurOptions"
                    :key="f.id"
                    type="button"
                    class="w-full rounded-md px-2 py-1.5 text-left text-sm text-slate-700 hover:bg-slate-100"
                    :class="selectedFormateurId === f.id ? 'bg-slate-100 font-semibold text-slate-900' : ''"
                    @mousedown.prevent="selectFormateurOption(f.id, f.label)"
                  >
                    {{ f.label }}
                  </button>
                  <p v-if="!filteredFormateurOptions.length" class="px-2 py-2 text-xs text-slate-500">Aucun formateur trouve.</p>
                </div>
              </div>
            </div>

            <!-- Hint -->
            <div v-else class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs text-slate-600 sm:col-span-1 lg:col-span-1">
              Utilisez les filtres pour voir le planning general, par formateur, ou comparer deux formateurs.
            </div>

            <!-- Drag hint for filtered modes -->
            <div
              v-if="viewMode === 'formateur'"
              class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs text-slate-600"
            >
              Glissez sur les cases vides pour creer une seance. Glissez une seance existante pour la deplacer.
            </div>
          </div>
        </section>

        <!-- Week navigation -->
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
          <div class="flex items-center gap-2">
            <select v-model="generationTargetType" class="rounded-lg border border-slate-300 bg-white px-2 py-2 text-xs font-semibold text-slate-700">
              <option value="formateur">Formateur</option>
              <option value="groupe">Groupe</option>
            </select>
            <select
              v-if="generationTargetType === 'formateur'"
              v-model.number="generationFormateurId"
              class="max-w-[170px] rounded-lg border border-slate-300 bg-white px-2 py-2 text-xs text-slate-700"
            >
              <option v-for="f in formateurOptions" :key="`gen-f-${f.id}`" :value="f.id">{{ f.label }}</option>
            </select>
            <select
              v-else
              v-model.number="generationGroupeId"
              class="max-w-[170px] rounded-lg border border-slate-300 bg-white px-2 py-2 text-xs text-slate-700"
            >
              <option v-for="g in groupeOptions" :key="`gen-g-${g.id}`" :value="g.id">{{ g.label }}</option>
            </select>
            <button
              @click="generateSmartWeeklySchedule"
              class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="generatingSmart || (generationTargetType === 'formateur' ? !generationFormateurId : !generationGroupeId)"
            >
              {{ generatingSmart ? 'Generation intelligente...' : 'Generer semaine intelligente' }}
            </button>
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
        </div>

        <div v-if="error" class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
          {{ error }}
        </div>

        <div v-if="generationMessage" class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
          {{ generationMessage }}
        </div>

        <div v-if="loading" class="rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm">
          <div class="inline-block h-8 w-8 animate-spin rounded-full border-b-2 border-sky-600"></div>
          <p class="mt-2 text-slate-600">Chargement...</p>
        </div>

        <!-- Global planner layout for general/duo views -->
        <template v-else-if="viewMode === 'general' || viewMode === 'duo'">

          <!-- Color legend for multi-formateur view -->
          <div v-if="viewMode === 'duo' && selectedCompareFormateurIds.length > 0" class="mb-3 flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 shadow-sm">
            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Legende:</span>
            <span
              v-for="fId in selectedCompareFormateurIds"
              :key="`legend-${fId}`"
              class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold"
              :style="{ backgroundColor: getFormateurColor(fId)?.light || 'rgba(148,163,184,0.12)', borderColor: getFormateurColor(fId)?.border || '#94a3b8' }"
            >
              <span class="inline-block h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: getFormateurColor(fId)?.solid || '#64748b' }"></span>
              {{ getFormateurLabel(fId) }}
            </span>
          </div>

          <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
              <table class="min-w-full table-fixed border-separate border-spacing-0">
                <thead>
                  <tr class="bg-slate-100/90">
                    <th class="w-36 border-b border-r border-slate-200 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Jour</th>
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
                  <template v-for="(day, dayIdx) in weekDays" :key="day.name">
                    <tr
                      v-for="(lane, laneIdx) in globalDayRows[dayIdx]"
                      :key="`${day.name}-lane-${laneIdx}`"
                      class="bg-white align-top even:bg-slate-50/20"
                    >
                      <td v-if="laneIdx === 0" :rowspan="globalDayRows[dayIdx]?.length || 1" class="border-r border-slate-200 px-4 py-3 align-top">
                        <p class="text-sm font-semibold text-slate-800">{{ day.name }}</p>
                        <p class="mt-0.5 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-500">{{ formatDate(day.date) }}</p>
                        <p v-if="daySessionCount(dayIdx) > 0" class="mt-1.5 text-[10px] font-semibold text-slate-400">{{ daySessionCount(dayIdx) }} seance(s)</p>
                      </td>

                      <td
                        v-for="cell in lane"
                        :key="cell.key"
                        :colspan="cell.colspan"
                        class="min-w-[190px] border-r border-slate-200 p-2 align-top last:border-r-0"
                      >
                        <div v-if="cell.session" class="space-y-1.5">
                          <article
                            class="flex min-h-[90px] flex-col overflow-hidden rounded-lg border p-2 shadow-[0_1px_0_rgba(15,23,42,0.03)]"
                            :style="getSessionCardStyle(cell.session, conflictSessionIds.has(cell.session.id))"
                          >
                            <div class="mb-0.5 flex items-center justify-between gap-2">
                              <p class="truncate text-[11px] font-semibold text-slate-800">{{ cell.session.module_groupe_formateur.module.nom_module }}</p>
                              <span v-if="conflictSessionIds.has(cell.session.id)" class="rounded-full bg-amber-100 px-1.5 py-0.5 text-[9px] font-semibold text-amber-700">Conflit</span>
                            </div>
                            <p class="whitespace-nowrap text-[10px] font-medium text-slate-700">{{ cell.session.heure_debut.substring(0, 5) }} - {{ cell.session.heure_fin.substring(0, 5) }}</p>
                            <p class="truncate text-[10px] text-slate-600">{{ cell.session.module_groupe_formateur.groupe.nom_groupe }} • {{ getFormateurDisplayName(cell.session) }}</p>
                          </article>
                        </div>

                        <button
                          v-else
                          type="button"
                          class="flex h-10 w-full items-center justify-center rounded-lg border border-dashed border-slate-300 text-slate-400 transition hover:border-slate-400 hover:text-slate-600"
                          @click="cell.slotIdx !== null ? openCreateModalForSlot(dayIdx, cell.slotIdx) : undefined"
                          title="Creer une seance"
                        >
                          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                          </svg>
                        </button>
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </div>
        </template>

        <!-- FILTERED VIEW: single session per slot with colspan and drag support -->
        <div v-else-if="viewMode === 'formateur'" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" @mouseleave="endDrag(); cancelMove()">
          <div class="overflow-x-auto">
            <table class="min-w-full select-none table-fixed border-separate border-spacing-0">
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
                    v-for="cell in filteredDayCells[dayIdx]"
                    :key="cell.key"
                    :colspan="cell.colspan"
                    class="min-w-[170px] border-r border-slate-200 px-2 py-2 align-top last:border-r-0"
                    @mouseenter="cell.slotIdx !== null ? handleCellEnter(dayIdx, cell.slotIdx) : undefined"
                    @mouseup="cell.slotIdx !== null ? handleCellMouseUp(dayIdx, cell.slotIdx) : undefined"
                  >
                    <div
                      v-if="cell.session"
                      class="flex min-h-[108px] flex-col overflow-hidden rounded-lg border border-slate-200 bg-white p-2.5 text-left shadow-sm"
                      :class="{ 'cursor-move opacity-70': isMovingSession(cell.session) }"
                      @mousedown.prevent.stop="startMove(cell.session, dayIdx, cell.slotIdx ?? 0)"
                    >
                      <p class="truncate text-xs font-bold text-slate-800">{{ cell.session.module_groupe_formateur.module.nom_module }}</p>
                      <p class="mt-1 inline-flex w-fit whitespace-nowrap rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">
                        {{ cell.session.heure_debut.substring(0, 5) }} - {{ cell.session.heure_fin.substring(0, 5) }}
                      </p>
                      <p class="mt-1 inline-flex w-fit whitespace-nowrap rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">
                        {{ getSessionDurationLabel(cell.session) }}
                      </p>
                      <p class="mt-1.5 truncate text-[11px] font-medium text-slate-700">
                        {{ cell.session.module_groupe_formateur.groupe.nom_groupe }}
                      </p>
                      <p v-if="cell.session.salle" class="text-[11px] text-slate-500">{{ cell.session.salle.nom_salle }}</p>
                      <div class="mt-auto flex items-center justify-end gap-2 pt-2">
                        <button
                          type="button"
                          class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-300 text-slate-600 hover:bg-slate-50"
                          @click.stop="openEditModal(cell.session)"
                          title="Modifier"
                        >
                          <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 3.487a2.1 2.1 0 113.03 2.908L8.7 18.078 4 19.5l1.34-4.56L16.862 3.487z" />
                          </svg>
                        </button>
                        <button
                          type="button"
                          class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-red-200 text-red-600 hover:bg-red-50"
                          @click.stop="deleteSession(cell.session)"
                          title="Supprimer"
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

    <!-- Sidebar overlay (mobile) -->
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

    <!-- Create / Edit Modal -->
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

            <!-- Date (only shown in global mode create, since in filtered modes date comes from drag) -->
            <div v-if="modalMode === 'create' && viewMode !== 'formateur'">
              <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Date</span>
              <input v-model="modalDate" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
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
              <button class="rounded-full border border-slate-300 px-3 py-1 text-[11px] font-semibold text-slate-600 hover:bg-slate-100" @click="applyPresetDuration(150)">2h30</button>
              <button class="rounded-full border border-slate-300 px-3 py-1 text-[11px] font-semibold text-slate-600 hover:bg-slate-100" @click="applyPresetDuration(300)">5h00</button>
            </div>

            <!-- Assignment (Groupe + Module + Formateur) -->
            <div class="block">
              <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Affectation (Groupe | Module | Formateur)</span>
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
                    {{ assignmentLabel(a) }}
                  </button>
                  <p v-if="!filteredModalAssignments.length" class="px-2 py-2 text-xs text-slate-500">Aucune affectation trouvee.</p>
                </div>
              </div>
            </div>

            <!-- Salle picker -->
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
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '../../../../composables/useApi'
import { useAuthStore } from '../../../../stores/auth'

definePageMeta({
  middleware: ['auth'],
})

type ViewMode = 'general' | 'duo' | 'formateur'

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
  id_filiere?: number | null
}

interface ModuleItem {
  id: number
  nom_module: string
  id_filiere?: number | null
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
const GLOBAL_SLOT_VISIBLE_LIMIT = 3

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
const generatingSmart = ref(false)
const generationMessage = ref<string | null>(null)

const rawView = route.query.view as string | undefined
const initialView: ViewMode = rawView === 'formateur' ? 'formateur' : rawView === 'duo' ? 'duo' : 'general'
const viewMode = ref<ViewMode>(initialView)

const selectedFormateurId = ref<number | null>(null)
const formateurSearch = ref('')
const showFormateurPicker = ref(false)

const selectedCompareFormateurIds = ref<number[]>([])
const multiFormateurSearch = ref('')
const showMultiFormateurPicker = ref(false)
const multiFormateurPickerRef = ref<HTMLElement | null>(null)

const generationTargetType = ref<'formateur' | 'groupe'>('formateur')
const generationFormateurId = ref<number | null>(null)
const generationGroupeId = ref<number | null>(null)

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
const activeGlobalSlot = ref<{ dayIdx: number; slotIdx: number } | null>(null)

const DAY_START_MINUTES = toMinutes('08:30')
const DAY_END_MINUTES = toMinutes('18:30')

// ─── Helpers ─────────────────────────────────────────────────────────────────

function toMinutes(time: string): number {
  const hhmm = time.substring(0, 5)
  const [h = 0, m = 0] = hhmm.split(':').map(Number)
  return h * 60 + m
}

function minutesToHHMM(totalMinutes: number): string {
  const h = Math.floor(totalMinutes / 60)
  const m = totalMinutes % 60
  return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`
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

// ─── Computed ─────────────────────────────────────────────────────────────────

const fullName = computed(() => {
  if (!authStore.user) return 'Utilisateur'
  return `${authStore.user.nom} ${authStore.user.prenom}`.trim()
})

const userInitials = computed(() => {
  if (!authStore.user) return 'DI'
  const nom = authStore.user.nom?.charAt(0) || ''
  const prenom = authStore.user.prenom?.charAt(0) || ''
  return `${nom}${prenom}`.toUpperCase() || 'DI'
})

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

const activeCompareLabel = computed(() => {
  if (viewMode.value !== 'duo' || selectedCompareFormateurIds.value.length === 0) return 'Tous les formateurs'
  return selectedCompareFormateurIds.value
    .map((id) => formateurOptions.value.find((f) => f.id === id)?.label || `Formateur #${id}`)
    .join(' + ')
})

const formateurOptions = computed(() => {
  const map = new Map<number, { id: number; label: string }>()
  for (const a of allAssignments.value) {
    const label = a.formateur?.user
      ? `${a.formateur.user.nom} ${a.formateur.user.prenom}`
      : `Formateur #${a.id_formateur}`
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
  const query = formateurSearch.value.trim().toLowerCase()
  if (!query) return formateurOptions.value
  return formateurOptions.value.filter((f) => f.label.toLowerCase().includes(query))
})

const filteredMultiFormateurOptions = computed(() => {
  const query = multiFormateurSearch.value.trim().toLowerCase()
  const base = !query
    ? formateurOptions.value
    : formateurOptions.value.filter((f) => f.label.toLowerCase().includes(query))

  return [...base].sort((a, b) => {
    const aSelected = selectedCompareFormateurIds.value.includes(a.id)
    const bSelected = selectedCompareFormateurIds.value.includes(b.id)
    if (aSelected !== bSelected) return aSelected ? 1 : -1
    return a.label.localeCompare(b.label)
  })
})

const compareFormateurIds = computed(() => new Set(selectedCompareFormateurIds.value))

const formateurColorMap = computed(() => {
  const map = new Map<number, { bg: string; border: string; solid: string; light: string }>()
  selectedCompareFormateurIds.value.forEach((id, idx) => {
    map.set(id, FORMATEUR_COLOR_PALETTE[idx % FORMATEUR_COLOR_PALETTE.length]!)
  })
  return map
})

const filteredSchedule = computed(() => {
  if (viewMode.value === 'formateur' && selectedFormateurId.value) {
    return allSchedule.value.filter((s) => s.module_groupe_formateur.id_formateur === selectedFormateurId.value)
  }

  if (viewMode.value === 'duo' && compareFormateurIds.value.size > 0) {
    return allSchedule.value.filter((s) => {
      return compareFormateurIds.value.has(s.module_groupe_formateur.id_formateur)
    })
  }

  return allSchedule.value
})

// Assignments filtered for modal (in global mode: all; in filtered modes: relevant subset)
const modalAssignments = computed(() => {
  if (viewMode.value === 'formateur' && selectedFormateurId.value) {
    return allAssignments.value.filter((a) => a.id_formateur === selectedFormateurId.value)
  }

  if (viewMode.value === 'duo' && compareFormateurIds.value.size > 0) {
    return allAssignments.value.filter((a) => {
      return compareFormateurIds.value.has(a.id_formateur)
    })
  }

  return allAssignments.value
})

const filteredModalAssignments = computed(() => {
  const query = modalAssignmentSearch.value.trim().toLowerCase()
  if (!query) return modalAssignments.value
  return modalAssignments.value.filter((a) => assignmentLabel(a).toLowerCase().includes(query))
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

// ─── Cell builders ────────────────────────────────────────────────────────────

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

function getStartSlotIndex(startMinutes: number): number {
  for (let idx = 0; idx < TIME_SLOTS.length; idx += 1) {
    const slotStart = toMinutes(SLOT_BOUNDARIES[idx] || '08:30')
    const slotEnd = toMinutes(SLOT_BOUNDARIES[idx + 1] || '23:59')
    if (startMinutes >= slotStart && startMinutes < slotEnd) {
      return idx
    }
  }

  if (startMinutes < toMinutes(SLOT_BOUNDARIES[0] || '08:30')) {
    return 0
  }

  return TIME_SLOTS.length - 1
}

// For directeur planner views: each session is rendered in every slot it overlaps.
const globalDayCells = computed(() =>
  weekDays.value.map((day) => {
    const dateStr = formatDateISO(day.date)
    const cells: Session[][] = TIME_SLOTS.map(() => [])

    const daySessions = filteredSchedule.value
      .filter((s) => s.date_seance === dateStr)
      .sort((a, b) => toMinutes(a.heure_debut) - toMinutes(b.heure_debut))

    for (const session of daySessions) {
      for (let slotIdx = 0; slotIdx < TIME_SLOTS.length; slotIdx += 1) {
        if (!overlapsSlot(session, slotIdx)) continue
        const slot = cells[slotIdx]
        if (slot) {
          slot.push(session)
        }
      }
    }

    return cells
  })
)

function buildGlobalLaneCells(dayIdx: number, laneSessions: Session[]): DayCell[] {
  const cells: DayCell[] = []
  let slotIdx = 0

  while (slotIdx < TIME_SLOTS.length) {
    const session = laneSessions.find((item) => getStartSlotIndex(toMinutes(item.heure_debut)) === slotIdx)

    if (!session) {
      cells.push({ key: `global-${dayIdx}-${slotIdx}-empty`, colspan: 1, session: null, slotIdx })
      slotIdx += 1
      continue
    }

    const span = getSessionSpan(session)
    cells.push({
      key: `global-${dayIdx}-${slotIdx}-session-${session.id}`,
      colspan: span,
      session,
      slotIdx,
    })
    slotIdx += span
  }

  return cells
}

const globalDayRows = computed(() =>
  weekDays.value.map((day, dayIdx) => {
    const dateStr = formatDateISO(day.date)
    const daySessions = filteredSchedule.value
      .filter((s) => s.date_seance === dateStr)
      .sort((a, b) => {
        const byStart = toMinutes(a.heure_debut) - toMinutes(b.heure_debut)
        if (byStart !== 0) return byStart
        return toMinutes(a.heure_fin) - toMinutes(b.heure_fin)
      })

    const lanes: Session[][] = []
    const laneEndMinutes: number[] = []

    for (const session of daySessions) {
      const sessionStart = toMinutes(session.heure_debut)
      let placed = false

      for (let laneIdx = 0; laneIdx < lanes.length; laneIdx += 1) {
        if (sessionStart >= (laneEndMinutes[laneIdx] || 0)) {
          lanes[laneIdx]?.push(session)
          laneEndMinutes[laneIdx] = toMinutes(session.heure_fin)
          placed = true
          break
        }
      }

      if (!placed) {
        lanes.push([session])
        laneEndMinutes.push(toMinutes(session.heure_fin))
      }
    }

    if (lanes.length === 0) {
      return [[{ key: `global-${dayIdx}-empty-row`, colspan: 1, session: null, slotIdx: 0 }, ...TIME_SLOTS.slice(1).map((_, slotIdx) => ({ key: `global-${dayIdx}-${slotIdx + 1}-empty-row`, colspan: 1, session: null, slotIdx: slotIdx + 1 }))]]
    }

    return lanes.map((laneSessions) => buildGlobalLaneCells(dayIdx, laneSessions))
  })
)

// Conflict detection: returns Map<sessionId, conflictTypes[]> for entire loaded week
const conflictSessionIds = computed(() => {
  const ids = new Map<number, string[]>()
  const sessions = filteredSchedule.value
  for (let i = 0; i < sessions.length; i++) {
    for (let j = i + 1; j < sessions.length; j++) {
      const a = sessions[i]!
      const b = sessions[j]!
      if (a.date_seance !== b.date_seance) continue
      const aStart = toMinutes(a.heure_debut)
      const aEnd = toMinutes(a.heure_fin)
      const bStart = toMinutes(b.heure_debut)
      const bEnd = toMinutes(b.heure_fin)
      if (!(aStart < bEnd && aEnd > bStart)) continue

      const types: string[] = []
      if (a.module_groupe_formateur.id_groupe === b.module_groupe_formateur.id_groupe) types.push('groupe')
      if (a.module_groupe_formateur.id_formateur === b.module_groupe_formateur.id_formateur) types.push('formateur')
      if (a.id_salle === b.id_salle) types.push('salle')

      if (types.length > 0) {
        if (!ids.has(a.id)) ids.set(a.id, [])
        if (!ids.has(b.id)) ids.set(b.id, [])
        for (const t of types) {
          if (!ids.get(a.id)!.includes(t)) ids.get(a.id)!.push(t)
          if (!ids.get(b.id)!.includes(t)) ids.get(b.id)!.push(t)
        }
      }
    }
  }
  return ids
})

const weekSessionCount = computed(() => filteredSchedule.value.length)

const busiestSlotLabel = computed(() => {
  let bestCount = 0
  let bestDayIdx = -1
  let bestSlotIdx = -1

  for (let d = 0; d < globalDayCells.value.length; d += 1) {
    const daySlots = globalDayCells.value[d] || []
    for (let s = 0; s < daySlots.length; s += 1) {
      const count = daySlots[s]?.length || 0
      if (count > bestCount) {
        bestCount = count
        bestDayIdx = d
        bestSlotIdx = s
      }
    }
  }

  if (bestCount === 0 || bestDayIdx < 0 || bestSlotIdx < 0) {
    return 'Aucun cours'
  }

  const day = weekDays.value[bestDayIdx]
  const slot = TIME_SLOTS[bestSlotIdx]
  if (!day || !slot) return 'Aucun cours'
  return `${day.name} ${slot.label} (${bestCount})`
})

// For filtered modes: single session per slot with colspan (same as chef logic)
function buildFilteredCells(dayIdx: number): DayCell[] {
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

const filteredDayCells = computed(() => weekDays.value.map((_, idx) => buildFilteredCells(idx)))

// ─── Global view hover & conflict helpers ───────────────────────────────────

function setActiveGlobalSlot(dayIdx: number, slotIdx: number) {
  activeGlobalSlot.value = { dayIdx, slotIdx }
}

function clearActiveGlobalSlot() {
  activeGlobalSlot.value = null
}

function isActiveGlobalSlot(dayIdx: number, slotIdx: number): boolean {
  return activeGlobalSlot.value?.dayIdx === dayIdx && activeGlobalSlot.value?.slotIdx === slotIdx
}

function slotHasConflict(slotSessions: Session[]): boolean {
  return slotSessions.some((session) => conflictSessionIds.value.has(session.id))
}

function stackCardStyle(session: Session, stackIndex: number, hasConflict: boolean): Record<string, string> {
  const palette = [
    { bg: 'rgba(14, 165, 233, 0.20)', border: 'rgba(14, 165, 233, 0.45)' },
    { bg: 'rgba(99, 102, 241, 0.20)', border: 'rgba(99, 102, 241, 0.45)' },
    { bg: 'rgba(16, 185, 129, 0.20)', border: 'rgba(16, 185, 129, 0.45)' },
    { bg: 'rgba(244, 114, 182, 0.18)', border: 'rgba(244, 114, 182, 0.42)' },
  ]

  const color = palette[session.id % palette.length] || palette[0]
  const opacityDrop = stackIndex > 0 ? '0.92' : '1'

  return {
    backgroundColor: color?.bg || 'rgba(14, 165, 233, 0.20)',
    borderColor: hasConflict ? 'rgba(217, 119, 6, 0.62)' : (color?.border || 'rgba(14, 165, 233, 0.45)'),
    backdropFilter: 'blur(1px)',
    opacity: opacityDrop,
  }
}

function getSessionCardStyle(session: Session, hasConflict: boolean): Record<string, string> {
  if (viewMode.value === 'duo') {
    const color = formateurColorMap.value.get(session.module_groupe_formateur.id_formateur)
    if (color) {
      return {
        backgroundColor: color.bg,
        borderColor: hasConflict ? 'rgba(217, 119, 6, 0.62)' : color.border,
        backdropFilter: 'blur(1px)',
      }
    }
  }
  return stackCardStyle(session, 0, hasConflict)
}

function daySessionCount(dayIdx: number): number {
  const day = weekDays.value[dayIdx]
  if (!day) return 0
  const dateStr = formatDateISO(day.date)
  return filteredSchedule.value.filter((s) => s.date_seance === dateStr).length
}

// ─── Display helpers ──────────────────────────────────────────────────────────

function assignmentLabel(assignment: Assignment): string {
  const formateurName = assignment.formateur?.user
    ? `${assignment.formateur.user.nom} ${assignment.formateur.user.prenom}`
    : `Formateur #${assignment.id_formateur}`
  return `${assignment.groupe?.nom_groupe || '-'} | ${assignment.module?.nom_module || '-'} | ${formateurName}`
}

function getFormateurDisplayName(session: Session): string {
  const direct = session.module_groupe_formateur.formateur?.user
  if (direct) return `${direct.nom} ${direct.prenom}`.trim()

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
  return m === 0 ? `${h}h` : `${h}h${String(m).padStart(2, '0')}`
}

// ─── Filter pickers ───────────────────────────────────────────────────────────

function selectFormateurOption(id: number, label: string) {
  selectedFormateurId.value = id
  formateurSearch.value = label
  showFormateurPicker.value = false
}

function hideFormateurPicker() {
  setTimeout(() => { showFormateurPicker.value = false }, 120)
}

function toggleFormateurSelection(id: number) {
  const idx = selectedCompareFormateurIds.value.indexOf(id)
  if (idx === -1) {
    selectedCompareFormateurIds.value = [...selectedCompareFormateurIds.value, id]
  } else {
    selectedCompareFormateurIds.value = selectedCompareFormateurIds.value.filter((fId) => fId !== id)
  }
  multiFormateurSearch.value = ''
}

function removeFormateurSelection(id: number) {
  selectedCompareFormateurIds.value = selectedCompareFormateurIds.value.filter((fId) => fId !== id)
}

function hideMultiFormateurPicker() {
  setTimeout(() => { showMultiFormateurPicker.value = false }, 120)
}

function handleMultiFormateurOutsideClick(event: MouseEvent) {
  if (!showMultiFormateurPicker.value) return
  const target = event.target as Node | null
  if (!target) return
  if (multiFormateurPickerRef.value && !multiFormateurPickerRef.value.contains(target)) {
    showMultiFormateurPicker.value = false
  }
}

function getFormateurColor(id: number) {
  return formateurColorMap.value.get(id) || null
}

function getFormateurLabel(id: number): string {
  return formateurOptions.value.find((f) => f.id === id)?.label || `Formateur #${id}`
}

function selectModalAssignment(assignmentId: number) {
  modalAssignmentId.value = assignmentId
  const selected = allAssignments.value.find((a) => a.id === assignmentId)
  modalAssignmentSearch.value = selected ? assignmentLabel(selected) : ''
  showModalAssignmentPicker.value = false
}

function selectModalSalle(salleId: number) {
  modalSalleId.value = salleId
  const selected = salles.value.find((s) => s.id === salleId)
  modalSalleSearch.value = selected?.nom_salle || ''
  showModalSallePicker.value = false
}

function hideModalAssignmentPicker() {
  setTimeout(() => { showModalAssignmentPicker.value = false }, 120)
}

function hideModalSallePicker() {
  setTimeout(() => { showModalSallePicker.value = false }, 120)
}

// ─── View mode ────────────────────────────────────────────────────────────────

function setViewMode(mode: ViewMode) {
  viewMode.value = mode
  router.replace({ query: { ...route.query, view: mode === 'general' ? undefined : mode } })
}

// ─── Drag-to-create (filtered modes only) ────────────────────────────────────

function startDrag(dayIdx: number, slotIdx: number) {
  if (movingSession.value) return
  if (viewMode.value === 'formateur' && !selectedFormateurId.value) {
    error.value = 'Choisissez un formateur avant de planifier.'
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

  const draftConflict = getConflictMessage(dateISO, startTime, endTime, {
    formateurId: viewMode.value === 'formateur' ? selectedFormateurId.value : null,
  })

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

function isSlotSelected(dayIdx: number, slotIdx: number): boolean {
  if (dragDayIdx.value !== dayIdx || dragStartSlot.value === null || dragCurrentSlot.value === null) {
    return false
  }
  const start = Math.min(dragStartSlot.value, dragCurrentSlot.value)
  const end = Math.max(dragStartSlot.value, dragCurrentSlot.value)
  return slotIdx >= start && slotIdx <= end
}

function isSlotHighlighted(dayIdx: number, slotIdx: number): boolean {
  if (movingSession.value) return isMoveTarget(dayIdx, slotIdx)
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

// ─── Drag-to-move ─────────────────────────────────────────────────────────────

function startMove(session: Session, dayIdx: number, slotIdx: number) {
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

function isMoveTarget(dayIdx: number, slotIdx: number): boolean {
  return moveHoverDayIdx.value === dayIdx && moveHoverSlotIdx.value === slotIdx
}

function canMoveToTarget(dayIdx: number, slotIdx: number): boolean {
  if (!movingSession.value) return false
  const targetDay = weekDays.value[dayIdx]?.date
  const targetStart = SLOT_BOUNDARIES[slotIdx]
  if (!targetDay || !targetStart) return false

  const durationMinutes = toMinutes(movingSession.value.heure_fin) - toMinutes(movingSession.value.heure_debut)
  const newStartMin = toMinutes(targetStart)
  const newEndMin = newStartMin + durationMinutes
  if (newEndMin > DAY_END_MINUTES) return false

  const newDate = formatDateISO(targetDay)
  const newStart = minutesToHHMM(newStartMin)
  const newEnd = minutesToHHMM(newEndMin)
  const conflict = getConflictMessage(
    newDate, newStart, newEnd,
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

function isMovingSession(session: Session): boolean {
  return movingSession.value?.id === session.id
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

// ─── Conflict detection (client-side preview) ─────────────────────────────────

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

// ─── Modal ────────────────────────────────────────────────────────────────────

function openCreateModal() {
  modalMode.value = 'create'
  editingSessionId.value = null
  modalDate.value = formatDateISO(new Date())
  modalStartTime.value = '08:30'
  modalEndTime.value = '11:00'
  modalAssignmentId.value = null
  modalSalleId.value = null
  modalAssignmentSearch.value = ''
  modalSalleSearch.value = ''
  showModalAssignmentPicker.value = false
  showModalSallePicker.value = false
  modalError.value = null
  isModalOpen.value = true
}

function openCreateModalForSlot(dayIdx: number, slotIdx: number) {
  const dayDate = weekDays.value[dayIdx]?.date
  if (!dayDate) return
  modalMode.value = 'create'
  editingSessionId.value = null
  modalDate.value = formatDateISO(dayDate)
  modalStartTime.value = SLOT_BOUNDARIES[slotIdx] || '08:30'
  modalEndTime.value = SLOT_BOUNDARIES[slotIdx + 1] || '11:00'
  modalAssignmentId.value = null
  modalSalleId.value = null
  modalAssignmentSearch.value = ''
  modalSalleSearch.value = ''
  showModalAssignmentPicker.value = false
  showModalSallePicker.value = false
  modalError.value = null
  isModalOpen.value = true
}

function openEditModal(session: Session) {
  modalMode.value = 'edit'
  editingSessionId.value = session.id
  modalDate.value = session.date_seance
  modalStartTime.value = session.heure_debut.substring(0, 5)
  modalEndTime.value = session.heure_fin.substring(0, 5)
  modalAssignmentId.value = session.id_module_groupe_formateur
  modalSalleId.value = session.id_salle
  const selectedAssignment = allAssignments.value.find((a) => a.id === session.id_module_groupe_formateur)
  modalAssignmentSearch.value = selectedAssignment ? assignmentLabel(selectedAssignment) : ''
  const selectedSalle = salles.value.find((s) => s.id === session.id_salle)
  modalSalleSearch.value = selectedSalle?.nom_salle || ''
  showModalAssignmentPicker.value = false
  showModalSallePicker.value = false
  modalError.value = null
  isModalOpen.value = true
}

function closeModal() {
  modalError.value = null
  modalMode.value = 'create'
  editingSessionId.value = null
  showModalAssignmentPicker.value = false
  showModalSallePicker.value = false
  isModalOpen.value = false
}

function applyPresetDuration(durationMinutes: number) {
  const nextEnd = toMinutes(modalStartTime.value) + durationMinutes
  const bounded = Math.min(nextEnd, DAY_END_MINUTES)
  modalEndTime.value = minutesToHHMM(bounded)
}

async function saveSession() {
  if (!modalAssignmentId.value || !modalSalleId.value) {
    modalError.value = 'Choisissez une affectation et une salle.'
    return
  }
  if (toMinutes(modalEndTime.value) <= toMinutes(modalStartTime.value)) {
    modalError.value = 'Heure fin doit etre apres heure debut.'
    return
  }
  if (toMinutes(modalStartTime.value) < DAY_START_MINUTES || toMinutes(modalEndTime.value) > DAY_END_MINUTES) {
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

async function deleteSession(session: Session) {
  try {
    await api.delete(`/emploi-du-temps/${session.id}`)
    await loadSchedule()
  } catch (err: any) {
    error.value = err?.data?.message || err?.message || 'Erreur lors de la suppression de la seance'
  }
}

// ─── Data loading ─────────────────────────────────────────────────────────────

async function loadBaseData() {
  const [assignmentsRes, sallesRes] = await Promise.all([
    api.get<Assignment[]>('/module-formateur'),
    api.get<SalleItem[]>('/salles'),
  ])
  allAssignments.value = Array.isArray(assignmentsRes) ? assignmentsRes : ((assignmentsRes as any)?.data || [])
  salles.value = Array.isArray(sallesRes) ? sallesRes : ((sallesRes as any)?.data || [])

  if (!selectedFormateurId.value && formateurOptions.value[0]) {
    selectedFormateurId.value = formateurOptions.value[0].id
  }

  if (!generationFormateurId.value && formateurOptions.value[0]) {
    generationFormateurId.value = formateurOptions.value[0].id
  }

  if (!generationGroupeId.value && groupeOptions.value[0]) {
    generationGroupeId.value = groupeOptions.value[0].id
  }

  if (selectedCompareFormateurIds.value.length === 0 && formateurOptions.value.length > 0) {
    const first = formateurOptions.value[0]!.id
    const second = formateurOptions.value[1]?.id
    selectedCompareFormateurIds.value = second ? [first, second] : [first]
  }
}

async function generateSmartWeeklySchedule() {
  if (generatingSmart.value) return

  const startDate = formatDateISO(currentWeekStart.value)
  const targetType = generationTargetType.value
  const targetId = targetType === 'formateur' ? generationFormateurId.value : generationGroupeId.value

  if (!targetId) {
    error.value = 'Choisissez une cible valide pour la generation intelligente.'
    return
  }

  generatingSmart.value = true
  generationMessage.value = null
  error.value = null

  try {
    const response = await api.post('/emploi-du-temps/generate-weekly-smart', {
      start_date: startDate,
      target_type: targetType,
      target_id: targetId,
    })

    const generatedCount = Number((response as any)?.generated_count || (response as any)?.data?.generated_count || 0)
    const skippedPastDays = Number((response as any)?.skipped_past_days || (response as any)?.data?.skipped_past_days || 0)
    const message = (response as any)?.message || (response as any)?.data?.message || 'Generation intelligente terminee.'
    generationMessage.value = skippedPastDays > 0
      ? `${message} (${generatedCount} seance(s) creee(s), ${skippedPastDays} jour(s) passes ignores).`
      : `${message} (${generatedCount} seance(s) creee(s)).`
    await loadSchedule()
  } catch (err: any) {
    error.value = err?.data?.message || err?.message || 'Erreur lors de la generation intelligente'
  } finally {
    generatingSmart.value = false
  }
}

async function loadSchedule() {
  loading.value = true
  error.value = null

  try {
    const params: Record<string, string | number> = {
      start_date: formatDateISO(currentWeekStart.value),
    }
    if (viewMode.value === 'formateur' && selectedFormateurId.value) {
      params.id_formateur = selectedFormateurId.value
    }
    const response = await api.get<{ success: boolean; data: Session[] }>('/emploi-du-temps/weekly', params)
    allSchedule.value = Array.isArray(response.data) ? response.data : []
  } catch (err: any) {
    error.value = err?.data?.message || err?.message || 'Erreur lors du chargement de l emploi du temps'
  } finally {
    loading.value = false
  }
}

// ─── Week navigation ──────────────────────────────────────────────────────────

function prevWeek() {
  clearActiveGlobalSlot()
  const d = new Date(currentWeekStart.value)
  d.setDate(d.getDate() - 7)
  currentWeekStart.value = d
}

function nextWeek() {
  clearActiveGlobalSlot()
  const d = new Date(currentWeekStart.value)
  d.setDate(d.getDate() + 7)
  currentWeekStart.value = d
}

function goToCurrentWeek() {
  clearActiveGlobalSlot()
  currentWeekStart.value = getMonday(new Date())
}

const handleLogout = async () => {
  isSidebarOpen.value = false
  await authStore.logout()
  router.push('/login')
}

// ─── Watchers ─────────────────────────────────────────────────────────────────

watch(viewMode, () => {
  clearActiveGlobalSlot()
  if (viewMode.value === 'formateur' && !selectedFormateurId.value && formateurOptions.value[0]) {
    selectedFormateurId.value = formateurOptions.value[0].id
  }

  if (viewMode.value === 'duo' && selectedCompareFormateurIds.value.length === 0 && formateurOptions.value.length > 0) {
    const first = formateurOptions.value[0]!.id
    const second = formateurOptions.value[1]?.id
    selectedCompareFormateurIds.value = second ? [first, second] : [first]
  }

  loadSchedule()
})

watch(selectedFormateurId, (id) => {
  if (!id) return
  const selected = formateurOptions.value.find((f) => f.id === id)
  if (selected) formateurSearch.value = selected.label
})

watch(generationTargetType, (type) => {
  if (type === 'formateur' && !generationFormateurId.value && formateurOptions.value[0]) {
    generationFormateurId.value = formateurOptions.value[0].id
  }

  if (type === 'groupe' && !generationGroupeId.value && groupeOptions.value[0]) {
    generationGroupeId.value = groupeOptions.value[0].id
  }
})

watch(modalAssignmentId, (id) => {
  if (!id) {
    modalAssignmentSearch.value = ''
    return
  }
  const selected = allAssignments.value.find((a) => a.id === id)
  if (selected) modalAssignmentSearch.value = assignmentLabel(selected)
})

watch(modalSalleId, (id) => {
  if (!id) {
    modalSalleSearch.value = ''
    return
  }
  const selected = salles.value.find((s) => s.id === id)
  if (selected) modalSalleSearch.value = selected.nom_salle
})

watch([selectedFormateurId, selectedCompareFormateurIds, currentWeekStart], () => {
  loadSchedule()
})

onMounted(async () => {
  document.addEventListener('mousedown', handleMultiFormateurOutsideClick)
  await loadBaseData()
  await loadSchedule()
})

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', handleMultiFormateurOutsideClick)
})
</script>


