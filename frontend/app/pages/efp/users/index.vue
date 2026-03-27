<template>
  <div class="min-h-screen bg-slate-100 text-slate-800">
    <!-- Desktop Sidebar -->
    <aside class="fixed inset-y-0 left-0 z-30 hidden w-72 lg:block">
      <RoleSidebar :role="authStore.user?.role" />
    </aside>

    <div class="lg:pl-72">
      <!-- Header -->
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

      <!-- Page Content -->
      <main class="px-4 py-6 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 mb-6">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-600">Gestion</p>
          <h2 class="mt-2 text-2xl font-bold text-slate-900">Formateurs</h2>
          <p class="mt-2 text-sm text-slate-600">Gestion des formateurs de l'établissement</p>
        </section>

        <!-- Actions Bar -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <!-- Search -->
          <div class="relative flex-1 max-w-md">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Rechercher par nom, email ou matricule..."
              class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent bg-white text-slate-900"
            >
            <svg class="w-5 h-5 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>

          <div class="flex flex-wrap items-center gap-3">
            <button
              type="button"
              @click="openCreateModal"
              class="inline-flex items-center justify-center px-4 py-2 bg-sky-600 text-white font-medium rounded-lg hover:bg-sky-700 transition-colors"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Ajouter formateur
            </button>
            <BulkExcelPanel entity="users" @imported="loadUsers" />
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="bg-white rounded-xl border border-slate-200 shadow-sm p-8 text-center">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-sky-600"></div>
          <p class="mt-2 text-slate-600">Chargement...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-xl p-4">
          <p class="text-red-800">{{ error }}</p>
        </div>

        <!-- Users Table -->
        <div v-else class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
              <thead class="bg-slate-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                    Matricule
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                    Nom complet
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                    Email
                  </th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-slate-200">
                <tr v-if="filteredUsers.length === 0">
                  <td colspan="4" class="px-6 py-8 text-center text-slate-500">
                    Aucun formateur trouvé
                  </td>
                </tr>
                <tr
                  v-for="user in filteredUsers"
                  :key="user.id"
                  class="hover:bg-slate-50 transition-colors"
                >
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">
                    {{ user.matricule }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-slate-900">{{ user.prenom }} {{ user.nom }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                    {{ user.email }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <div class="flex items-center justify-end gap-2">
                      <button
                        @click="openEditModal(user)"
                        class="text-slate-400 hover:text-green-600"
                        title="Modifier"
                      >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                      </button>
                      <button
                        @click="openDeleteModal(user)"
                        class="text-slate-400 hover:text-red-600"
                        title="Supprimer"
                      >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="border-t border-slate-200 px-4 py-3 sm:px-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
              <p class="text-sm text-slate-600">
                Affichage {{ paginationStart }}-{{ paginationEnd }} sur {{ totalItems }}
              </p>
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  @click="goToPage(currentPage - 1)"
                  :disabled="currentPage <= 1"
                  class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                >
                  Precedent
                </button>

                <button
                  v-for="pageNumber in visiblePages"
                  :key="pageNumber"
                  type="button"
                  @click="goToPage(pageNumber)"
                  :class="[
                    'rounded-lg px-3 py-1.5 text-sm',
                    pageNumber === currentPage
                      ? 'bg-sky-600 text-white'
                      : 'border border-slate-300 text-slate-700 hover:bg-slate-50'
                  ]"
                >
                  {{ pageNumber }}
                </button>

                <button
                  type="button"
                  @click="goToPage(currentPage + 1)"
                  :disabled="currentPage >= totalPages"
                  class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                >
                  Suivant
                </button>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>

    <!-- Create User Modal -->
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="showCreateModal"
        class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-[1px] flex items-center justify-center p-4"
        @click.self="closeCreateModal"
      >
        <Transition
          enter-active-class="transition-all duration-300"
          enter-from-class="scale-95 opacity-0"
          enter-to-class="scale-100 opacity-100"
          leave-active-class="transition-all duration-200"
          leave-from-class="scale-100 opacity-100"
          leave-to-class="scale-95 opacity-0"
        >
          <div v-if="showCreateModal" class="bg-white rounded-xl shadow-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 border-b border-slate-200 bg-white px-6 py-4 flex items-center justify-between">
              <h3 class="text-lg font-semibold text-slate-900">Ajouter formateur</h3>
              <button
                @click="closeCreateModal"
                class="text-slate-500 hover:text-slate-700"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <form @submit.prevent="createUser" class="p-6 space-y-4">
              <div v-if="createError" class="bg-red-50 border border-red-200 rounded-lg p-3">
                <p class="text-sm text-red-800">{{ createError }}</p>
              </div>

              <div v-if="createSuccess" class="bg-green-50 border border-green-200 rounded-lg p-3">
                <p class="text-sm text-green-800">{{ createSuccess }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                  Matricule <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="createForm.matricule"
                  type="text"
                  required
                  class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent bg-white text-slate-900"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                  Nom <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="createForm.nom"
                  type="text"
                  required
                  class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent bg-white text-slate-900"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                  Prénom <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="createForm.prenom"
                  type="text"
                  required
                  class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent bg-white text-slate-900"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                  Email <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="createForm.email"
                  type="email"
                  required
                  class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent bg-white text-slate-900"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                  Mot de passe <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="createForm.password"
                  type="password"
                  required
                  minlength="6"
                  class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent bg-white text-slate-900"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                  Confirmer le mot de passe <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="createForm.password_confirmation"
                  type="password"
                  required
                  minlength="6"
                  class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent bg-white text-slate-900"
                />
                <p v-if="createPasswordMismatch" class="mt-1 text-sm text-red-600">
                  Les mots de passe ne correspondent pas
                </p>
              </div>

              <section
                v-if="isCreateFormateurSelected"
                class="rounded-xl border border-sky-200 bg-sky-50/60 p-4"
              >
                <h3 class="text-sm font-semibold text-sky-800">Informations Formateur</h3>
                <p class="mt-1 text-xs text-sky-700">Ces champs sont requis pour un utilisateur de type Formateur.</p>

                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                  <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                      Type formateur <span class="text-red-500">*</span>
                    </label>
                    <select
                      v-model="createForm.type_formateur"
                      :required="isCreateFormateurSelected"
                      class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent bg-white text-slate-900"
                    >
                      <option value="">Sélectionner</option>
                      <option value="FP">FP</option>
                      <option value="FV">FV</option>
                    </select>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                      MHS <span class="text-red-500">*</span>
                    </label>
                    <input
                      v-model.number="createForm.MHS"
                      type="number"
                      min="0"
                      step="1"
                      :required="isCreateFormateurSelected"
                      class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent bg-white text-slate-900"
                    />
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                      NBR semaine <span class="text-red-500">*</span>
                    </label>
                    <input
                      v-model.number="createForm.NBR_semaine"
                      type="number"
                      min="0"
                      step="1"
                      :required="isCreateFormateurSelected"
                      class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent bg-white text-slate-900"
                    />
                  </div>

                </div>
              </section>

              <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <button
                  type="button"
                  @click="closeCreateModal"
                  class="px-4 py-2 border border-slate-300 text-slate-700 font-medium rounded-lg hover:bg-slate-50 transition-colors"
                >
                  Annuler
                </button>
                <button
                  type="submit"
                  :disabled="createSubmitting || !!createPasswordMismatch"
                  class="px-4 py-2 bg-sky-600 text-white font-medium rounded-lg hover:bg-sky-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  <span v-if="createSubmitting" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Création...
                  </span>
                  <span v-else>Créer le formateur</span>
                </button>
              </div>
            </form>
          </div>
        </Transition>
      </div>
    </Transition>

    <!-- Edit User Modal -->
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="showEditModal"
        class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-[1px] flex items-center justify-center p-4"
        @click.self="closeEditModal"
      >
        <Transition
          enter-active-class="transition-all duration-300"
          enter-from-class="scale-95 opacity-0"
          enter-to-class="scale-100 opacity-100"
          leave-active-class="transition-all duration-200"
          leave-from-class="scale-100 opacity-100"
          leave-to-class="scale-95 opacity-0"
        >
          <div v-if="showEditModal" class="bg-white rounded-xl shadow-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 border-b border-slate-200 bg-white px-6 py-4 flex items-center justify-between">
              <h3 class="text-lg font-semibold text-slate-900">Modifier le formateur</h3>
              <button
                @click="closeEditModal"
                class="text-slate-500 hover:text-slate-700"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <form @submit.prevent="saveUserChanges" class="p-6 space-y-4">
              <div v-if="editError" class="bg-red-50 border border-red-200 rounded-lg p-3">
                <p class="text-sm text-red-800">{{ editError }}</p>
              </div>

              <!-- Matricule -->
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                  Matricule <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="editForm.matricule"
                  type="text"
                  required
                  class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent bg-white text-slate-900"
                />
              </div>

              <!-- Nom -->
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                  Nom <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="editForm.nom"
                  type="text"
                  required
                  class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent bg-white text-slate-900"
                />
              </div>

              <!-- Prénom -->
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                  Prénom <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="editForm.prenom"
                  type="text"
                  required
                  class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent bg-white text-slate-900"
                />
              </div>

              <!-- Email -->
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                  Email <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="editForm.email"
                  type="email"
                  required
                  class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent bg-white text-slate-900"
                />
              </div>

              <!-- Form Actions -->
              <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <button
                  type="button"
                  @click="closeEditModal"
                  class="px-4 py-2 border border-slate-300 text-slate-700 font-medium rounded-lg hover:bg-slate-50 transition-colors"
                >
                  Annuler
                </button>
                <button
                  type="submit"
                  :disabled="editSubmitting"
                  class="px-4 py-2 bg-sky-600 text-white font-medium rounded-lg hover:bg-sky-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  <span v-if="editSubmitting" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Enregistrement...
                  </span>
                  <span v-else>Enregistrer les modifications</span>
                </button>
              </div>
            </form>
          </div>
        </Transition>
      </div>
    </Transition>

    <!-- Delete Confirmation Modal -->
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="showDeleteModal"
        class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-[1px] flex items-center justify-center p-4"
        @click.self="closeDeleteModal"
      >
        <Transition
          enter-active-class="transition-all duration-300"
          enter-from-class="scale-95 opacity-0"
          enter-to-class="scale-100 opacity-100"
          leave-active-class="transition-all duration-200"
          leave-from-class="scale-100 opacity-100"
          leave-to-class="scale-95 opacity-0"
        >
          <div v-if="showDeleteModal" class="bg-white rounded-xl shadow-lg max-w-md w-full">
            <div class="p-6">
              <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 4v2M6 5h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2z" />
                </svg>
              </div>
              <h3 class="mt-4 text-lg font-semibold text-slate-900 text-center">Confirmer la suppression</h3>
              <p class="mt-2 text-sm text-slate-600 text-center">
                Êtes-vous sûr de vouloir supprimer le formateur <strong>{{ userToDelete?.prenom }} {{ userToDelete?.nom }}</strong> ? Cette action ne peut pas être annulée.
              </p>
              
              <div class="mt-6 flex items-center justify-center gap-3">
                <button
                  @click="closeDeleteModal"
                  class="px-4 py-2 border border-slate-300 text-slate-700 font-medium rounded-lg hover:bg-slate-50 transition-colors"
                >
                  Annuler
                </button>
                <button
                  @click="confirmDelete"
                  :disabled="deleteSubmitting"
                  class="px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  <span v-if="deleteSubmitting" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Suppression en cours...
                  </span>
                  <span v-else>Supprimer</span>
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>

    <!-- Mobile Drawer Overlay -->
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

    <!-- Mobile Drawer -->
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
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '../../../../composables/useApi'
import { useAuthStore } from '../../../../stores/auth'
import BulkExcelPanel from '../../../components/BulkExcelPanel.vue'

definePageMeta({
  middleware: ['auth'],
})

interface User {
  id: number
  nom: string
  prenom: string
  email: string
  matricule: string
  role: 'FORMATEUR'
}

interface UsersPaginatedResponse {
  data: User[]
  total: number
  current_page: number
  last_page: number
}

interface UserMutationResponse {
  success?: boolean
  data?: Partial<User> & { id?: number }
}

const PER_PAGE = 25

const router = useRouter()
const route = useRoute()
const api = useApi()
const authStore = useAuthStore()

const isSidebarOpen = ref(false)
const users = ref<User[]>([])
const loading = ref(true)
const error = ref<string | null>(null)
const searchQuery = ref('')
const currentPage = ref(1)
const totalPages = ref(1)
const totalItems = ref(0)
let searchDebounceTimer: ReturnType<typeof setTimeout> | null = null

// Create modal state
const showCreateModal = ref(false)
const createSubmitting = ref(false)
const createError = ref<string | null>(null)
const createSuccess = ref<string | null>(null)
const createForm = ref({
  matricule: '',
  nom: '',
  prenom: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'FORMATEUR',
  type_formateur: '',
  MHS: null as number | null,
  NBR_semaine: null as number | null,
  id_pole: null as number | null,
})

// Edit modal state
const showEditModal = ref(false)
const editForm = ref({
  id: 0,
  matricule: '',
  nom: '',
  prenom: '',
  email: '',
  role: 'FORMATEUR' as 'FORMATEUR'
})
const editError = ref<string | null>(null)
const editSubmitting = ref(false)

// Delete modal state
const showDeleteModal = ref(false)
const userToDelete = ref<User | null>(null)
const deleteSubmitting = ref(false)

const fullName = computed(() => {
  if (!authStore.user) return 'Utilisateur'
  return `${authStore.user.nom} ${authStore.user.prenom}`.trim()
})

const roleLabel = computed(() => {
  const role = authStore.user?.role
  if (role === 'DIRECTEUR_EFP' || role === 'DIRECTEUR') return 'Directeur Pedagogique'
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

const filteredUsers = computed(() => {
  return users.value
})

const paginationStart = computed(() => {
  if (totalItems.value === 0) return 0
  return (currentPage.value - 1) * PER_PAGE + 1
})

const paginationEnd = computed(() => {
  if (totalItems.value === 0) return 0
  return Math.min(currentPage.value * PER_PAGE, totalItems.value)
})

const visiblePages = computed(() => {
  const pages: number[] = []
  const start = Math.max(1, currentPage.value - 2)
  const end = Math.min(totalPages.value, currentPage.value + 2)

  for (let page = start; page <= end; page++) {
    pages.push(page)
  }

  return pages
})

const isCreateFormateurSelected = computed(() => createForm.value.role === 'FORMATEUR')

const createPasswordMismatch = computed(() => {
  return createForm.value.password && createForm.value.password_confirmation &&
         createForm.value.password !== createForm.value.password_confirmation
})

const loadUsers = async (page = currentPage.value) => {
  loading.value = true
  error.value = null
  
  try {
    const q = searchQuery.value.trim()
    const response = await api.get<UsersPaginatedResponse>('/users', {
      role: 'FORMATEUR',
      page,
      per_page: PER_PAGE,
      q: q || undefined,
    })

    users.value = response.data || []
    totalItems.value = Number(response.total) || 0
    totalPages.value = Math.max(Number(response.last_page) || 1, 1)
    currentPage.value = Math.min(Number(response.current_page) || page, totalPages.value)
  } catch (err: any) {
    error.value = err.message || 'Erreur lors du chargement des formateurs'
    console.error('Error loading users:', err)
  } finally {
    loading.value = false
  }
}

const goToPage = async (page: number) => {
  if (page < 1 || page > totalPages.value || page === currentPage.value) return
  currentPage.value = page
  await loadUsers(page)
}

const resetCreateForm = () => {
  createForm.value = {
    matricule: '',
    nom: '',
    prenom: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'FORMATEUR',
    type_formateur: '',
    MHS: null,
    NBR_semaine: null,
    id_pole: null,
  }
}

const openCreateModal = () => {
  createError.value = null
  createSuccess.value = null
  resetCreateForm()
  showCreateModal.value = true
}

const openCreateFormateurModal = () => {
  openCreateModal()
  createForm.value.role = 'FORMATEUR'
}

const closeCreateModal = () => {
  showCreateModal.value = false
  createError.value = null
  createSuccess.value = null
}

const createUser = async () => {
  createError.value = null
  createSuccess.value = null

  if (createPasswordMismatch.value) {
    createError.value = 'Les mots de passe ne correspondent pas'
    return
  }

  if (
    isCreateFormateurSelected.value &&
    (
      !createForm.value.type_formateur ||
      createForm.value.MHS === null ||
      createForm.value.NBR_semaine === null
    )
  ) {
    createError.value = 'Pour un formateur, veuillez renseigner type formateur, MHS et NBR semaine.'
    return
  }

  createSubmitting.value = true

  try {
    const { password_confirmation, ...userData } = createForm.value

    if (!isCreateFormateurSelected.value) {
      userData.type_formateur = ''
      userData.MHS = null
      userData.NBR_semaine = null
      userData.id_pole = null
    }

    const response = await api.post<UserMutationResponse>('/users', userData)
    const created = response?.data ?? {}
    const createdUser: User = {
      id: Number(created.id ?? Date.now()),
      matricule: created.matricule ?? userData.matricule,
      nom: created.nom ?? userData.nom,
      prenom: created.prenom ?? userData.prenom,
      email: created.email ?? userData.email,
      role: 'FORMATEUR',
    }

    totalItems.value += 1
    totalPages.value = Math.max(Math.ceil(totalItems.value / PER_PAGE), 1)

    // Keep UI responsive without refetching; show new row immediately on first page.
    if (currentPage.value === 1) {
      users.value = [createdUser, ...users.value].slice(0, PER_PAGE)
    }

    createSuccess.value = 'Formateur créé avec succès !'
    closeCreateModal()
  } catch (err: any) {
    console.error('Error creating user:', err)

    if (err.data && err.data.errors) {
      const errors = Object.values(err.data.errors).flat()
      createError.value = errors.join(', ')
    } else {
      createError.value = err.message || 'Erreur lors de la création du formateur'
    }
  } finally {
    createSubmitting.value = false
  }
}

const viewUser = (user: User) => {
  // TODO: Navigate to user detail page or show modal
  console.log('View user:', user)
}

const openEditModal = (user: User) => {
  editForm.value = {
    id: user.id,
    matricule: user.matricule,
    nom: user.nom,
    prenom: user.prenom,
    email: user.email,
    role: 'FORMATEUR'
  }
  editError.value = null
  showEditModal.value = true
}

const closeEditModal = () => {
  showEditModal.value = false
  editError.value = null
}

const saveUserChanges = async () => {
  editSubmitting.value = true
  editError.value = null

  try {
    const response = await api.put<UserMutationResponse>(`/users/${editForm.value.id}`, {
      matricule: editForm.value.matricule,
      nom: editForm.value.nom,
      prenom: editForm.value.prenom,
      email: editForm.value.email,
      role: 'FORMATEUR'
    })

    const updated = response?.data ?? {}
    const updatedId = Number(updated.id ?? editForm.value.id)

    users.value = users.value.map((item) => {
      if (item.id !== updatedId) return item

      return {
        ...item,
        ...updated,
        matricule: updated.matricule ?? editForm.value.matricule,
        nom: updated.nom ?? editForm.value.nom,
        prenom: updated.prenom ?? editForm.value.prenom,
        email: updated.email ?? editForm.value.email,
        role: 'FORMATEUR',
      }
    })

    closeEditModal()
  } catch (err: any) {
    editError.value = err.message || 'Erreur lors de la mise à jour'
    console.error('Error updating user:', err)
  } finally {
    editSubmitting.value = false
  }
}

const openDeleteModal = (user: User) => {
  userToDelete.value = user
  showDeleteModal.value = true
}

const closeDeleteModal = () => {
  showDeleteModal.value = false
  userToDelete.value = null
}

const confirmDelete = async () => {
  if (!userToDelete.value) return

  deleteSubmitting.value = true

  try {
    await api.delete(`/users/${userToDelete.value.id}`)

    if (users.value.length === 1 && currentPage.value > 1) {
      currentPage.value -= 1
    }

    await loadUsers(currentPage.value)
    closeDeleteModal()
  } catch (err: any) {
    console.error('Error deleting user:', err)
    // Don't close modal on error, let user see the error message
  } finally {
    deleteSubmitting.value = false
  }
}

const deleteUser = async (user: User) => {
  if (!confirm(`Êtes-vous sûr de vouloir supprimer le formateur ${user.prenom} ${user.nom} ?`)) {
    return
  }

  try {
    await api.delete(`/users/${user.id}`)
    // Remove from list
    users.value = users.value.filter(u => u.id !== user.id)
  } catch (err: any) {
    alert(err.message || 'Erreur lors de la suppression')
    console.error('Error deleting user:', err)
  }
}

const handleLogout = async () => {
  isSidebarOpen.value = false
  await authStore.logout()
  router.push('/login')
}

watch(searchQuery, () => {
  if (searchDebounceTimer) {
    clearTimeout(searchDebounceTimer)
  }

  searchDebounceTimer = setTimeout(() => {
    currentPage.value = 1
    loadUsers(1)
  }, 350)
})

onBeforeUnmount(() => {
  if (searchDebounceTimer) {
    clearTimeout(searchDebounceTimer)
  }
})

onMounted(() => {
  loadUsers(1)

  if (route.query.create === 'formateur') {
    openCreateFormateurModal()
  }
})
</script>


