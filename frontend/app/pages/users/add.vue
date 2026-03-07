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
            <h1 class="text-sm font-semibold text-slate-900">Ajouter utilisateur</h1>
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
        <div class="max-w-3xl mx-auto">
          <!-- Back Button & Header -->
          <div class="mb-6">
            <div class="flex items-center gap-3 mb-4">
              <NuxtLink
                to="/users"
                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-100"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
              </NuxtLink>
              <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-600">Nouveau</p>
                <h2 class="text-2xl font-bold text-slate-900">Ajouter un utilisateur</h2>
              </div>
            </div>
            <p class="text-sm text-slate-600">Créer un nouveau compte utilisateur</p>
          </div>

          <!-- Form -->
          <form @submit.prevent="handleSubmit" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <!-- Error Message -->
            <div v-if="errorMessage" class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4">
              <p class="text-sm text-red-800">{{ errorMessage }}</p>
            </div>

            <!-- Success Message -->
            <div v-if="successMessage" class="mb-6 bg-green-50 border border-green-200 rounded-xl p-4">
              <p class="text-sm text-green-800">{{ successMessage }}</p>
            </div>

            <div class="space-y-6">
              <!-- Matricule -->
              <div>
                <label for="matricule" class="block text-sm font-medium text-slate-700 mb-1">
                  Matricule <span class="text-red-500">*</span>
                </label>
                <input
                  id="matricule"
                  v-model="form.matricule"
                  type="text"
                  required
                  class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent bg-white text-slate-900"
                  placeholder="Ex: MAT001"
                />
              </div>

              <!-- Nom -->
              <div>
                <label for="nom" class="block text-sm font-medium text-slate-700 mb-1">
                  Nom <span class="text-red-500">*</span>
                </label>
                <input
                  id="nom"
                  v-model="form.nom"
                  type="text"
                  required
                  class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent bg-white text-slate-900"
                  placeholder="Nom de famille"
                />
              </div>

              <!-- Prenom -->
              <div>
                <label for="prenom" class="block text-sm font-medium text-slate-700 mb-1">
                  Prénom <span class="text-red-500">*</span>
                </label>
                <input
                  id="prenom"
                  v-model="form.prenom"
                  type="text"
                  required
                  class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent bg-white text-slate-900"
                  placeholder="Prénom"
                />
              </div>

              <!-- Email -->
              <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1">
                  Email <span class="text-red-500">*</span>
                </label>
                <input
                  id="email"
                  v-model="form.email"
                  type="email"
                  required
                  class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent bg-white text-slate-900"
                  placeholder="email@example.com"
                />
              </div>

              <!-- Password -->
              <div>
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1">
                  Mot de passe <span class="text-red-500">*</span>
                </label>
                <input
                  id="password"
                  v-model="form.password"
                  type="password"
                  required
                  minlength="6"
                  class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent bg-white text-slate-900"
                  placeholder="Minimum 6 caractères"
                />
              </div>

              <!-- Confirm Password -->
              <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">
                  Confirmer le mot de passe <span class="text-red-500">*</span>
                </label>
                <input
                  id="password_confirmation"
                  v-model="form.password_confirmation"
                  type="password"
                  required
                  minlength="6"
                  class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent bg-white text-slate-900"
                  placeholder="Confirmer le mot de passe"
                />
                <p v-if="passwordMismatch" class="mt-1 text-sm text-red-600">
                  Les mots de passe ne correspondent pas
                </p>
              </div>

              <!-- Role -->
              <div>
                <label for="role" class="block text-sm font-medium text-slate-700 mb-1">
                  Rôle <span class="text-red-500">*</span>
                </label>
                <select
                  id="role"
                  v-model="form.role"
                  required
                  class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent bg-white text-slate-900"
                >
                  <option value="">Sélectionner un rôle</option>
                  <option value="DIRECTEUR">Directeur</option>
                  <option value="CHEF_POLE">Chef de Pôle</option>
                  <option value="FORMATEUR">Formateur</option>
                </select>
              </div>

              <!-- Formateur Details -->
              <section
                v-if="isFormateurSelected"
                class="rounded-xl border border-sky-200 bg-sky-50/60 p-4"
              >
                <h3 class="text-sm font-semibold text-sky-800">Informations Formateur</h3>
                <p class="mt-1 text-xs text-sky-700">Ces champs sont requis pour un utilisateur de type Formateur.</p>

                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                  <div>
                    <label for="type_formateur" class="block text-sm font-medium text-slate-700 mb-1">
                      Type formateur <span class="text-red-500">*</span>
                    </label>
                    <select
                      id="type_formateur"
                      v-model="form.type_formateur"
                      :required="isFormateurSelected"
                      class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent bg-white text-slate-900"
                    >
                      <option value="">Sélectionner</option>
                      <option value="FP">FP</option>
                      <option value="FV">FV</option>
                    </select>
                  </div>

                  <div>
                    <label for="MHS" class="block text-sm font-medium text-slate-700 mb-1">
                      MHS <span class="text-red-500">*</span>
                    </label>
                    <input
                      id="MHS"
                      v-model.number="form.MHS"
                      type="number"
                      min="0"
                      step="1"
                      :required="isFormateurSelected"
                      class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent bg-white text-slate-900"
                      placeholder="Ex: 80"
                    />
                  </div>

                  <div>
                    <label for="NBR_semaine" class="block text-sm font-medium text-slate-700 mb-1">
                      NBR semaine <span class="text-red-500">*</span>
                    </label>
                    <input
                      id="NBR_semaine"
                      v-model.number="form.NBR_semaine"
                      type="number"
                      min="0"
                      step="1"
                      :required="isFormateurSelected"
                      class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent bg-white text-slate-900"
                      placeholder="Ex: 30"
                    />
                  </div>

                  <div class="sm:col-span-3">
                    <label for="id_pole" class="block text-sm font-medium text-slate-700 mb-1">
                      Pole <span class="text-red-500">*</span>
                    </label>
                    <select
                      id="id_pole"
                      v-model.number="form.id_pole"
                      :required="isFormateurSelected"
                      class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent bg-white text-slate-900"
                    >
                      <option :value="null">Sélectionner un pôle</option>
                      <option v-for="pole in poles" :key="pole.id" :value="pole.id">
                        {{ pole.nom_pole }}
                      </option>
                    </select>
                  </div>
                </div>
              </section>
            </div>

            <!-- Form Actions -->
            <div class="mt-8 flex items-center justify-end gap-4">
              <NuxtLink
                to="/users"
                class="px-6 py-2 border border-slate-300 text-slate-700 font-medium rounded-lg hover:bg-slate-50 transition-colors"
              >
                Annuler
              </NuxtLink>
              <button
                type="submit"
                :disabled="submitting || !!passwordMismatch"
                class="px-6 py-2 bg-sky-600 text-white font-medium rounded-lg hover:bg-sky-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                <span v-if="submitting" class="flex items-center">
                  <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Création en cours...
                </span>
                <span v-else>Créer l'utilisateur</span>
              </button>
            </div>
          </form>
        </div>
      </main>
    </div>

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
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '../../../composables/useApi'
import { useAuthStore } from '../../../stores/auth'

definePageMeta({
  middleware: ['auth']
})

const router = useRouter()
const api = useApi()
const authStore = useAuthStore()

const isSidebarOpen = ref(false)
const submitting = ref(false)
const errorMessage = ref<string | null>(null)
const successMessage = ref<string | null>(null)

interface PoleItem {
  id: number
  nom_pole: string
}

const poles = ref<PoleItem[]>([])

const fullName = computed(() => {
  if (!authStore.user) return 'Utilisateur'
  return `${authStore.user.nom} ${authStore.user.prenom}`.trim()
})

const roleLabel = computed(() => {
  const role = authStore.user?.role
  if (role === 'DIRECTEUR') return 'Directeur Pedagogique'
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

const form = ref({
  matricule: '',
  nom: '',
  prenom: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: '',
  type_formateur: '',
  MHS: null as number | null,
  NBR_semaine: null as number | null,
  id_pole: null as number | null,
})

const isFormateurSelected = computed(() => form.value.role === 'FORMATEUR')

const passwordMismatch = computed(() => {
  return form.value.password && form.value.password_confirmation && 
         form.value.password !== form.value.password_confirmation
})

const handleSubmit = async () => {
  // Clear previous messages
  errorMessage.value = null
  successMessage.value = null

  // Validate passwords match
  if (passwordMismatch.value) {
    errorMessage.value = 'Les mots de passe ne correspondent pas'
    return
  }

  if (
    isFormateurSelected.value &&
    (
      !form.value.type_formateur ||
      form.value.MHS === null ||
      form.value.NBR_semaine === null ||
      form.value.id_pole === null
    )
  ) {
    errorMessage.value = 'Pour un formateur, veuillez renseigner type formateur, MHS, NBR semaine et pôle.'
    return
  }

  submitting.value = true

  try {
    const { password_confirmation, ...userData } = form.value

    if (!isFormateurSelected.value) {
      userData.type_formateur = ''
      userData.MHS = null
      userData.NBR_semaine = null
      userData.id_pole = null
    }
    
    await api.post('/users', userData)

    successMessage.value = 'Utilisateur créé avec succès !'
    
    // Reset form
    form.value = {
      matricule: '',
      nom: '',
      prenom: '',
      email: '',
      password: '',
      password_confirmation: '',
      role: '',
      type_formateur: '',
      MHS: null,
      NBR_semaine: null,
      id_pole: null,
    }

    // Redirect after 1.5 seconds
    setTimeout(() => {
      router.push('/users')
    }, 1500)

  } catch (err: any) {
    console.error('Error creating user:', err)
    
    // Handle validation errors
    if (err.data && err.data.errors) {
      const errors = Object.values(err.data.errors).flat()
      errorMessage.value = errors.join(', ')
    } else {
      errorMessage.value = err.message || 'Erreur lors de la création de l\'utilisateur'
    }
  } finally {
    submitting.value = false
  }
}

const handleLogout = async () => {
  isSidebarOpen.value = false
  await authStore.logout()
  router.push('/login')
}

const loadPoles = async () => {
  try {
    const response = await api.get<PoleItem[] | { data: PoleItem[] }>('/poles')
    poles.value = Array.isArray(response) ? response : ((response as any).data || [])
  } catch (err) {
    console.error('Error loading poles:', err)
  }
}

onMounted(() => {
  loadPoles()
})
</script>
