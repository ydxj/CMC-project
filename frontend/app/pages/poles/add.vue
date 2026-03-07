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
            <h1 class="text-sm font-semibold text-slate-900">Ajouter pôle</h1>
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
                to="/poles"
                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-100"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
              </NuxtLink>
              <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-600">Nouveau</p>
                <h2 class="text-2xl font-bold text-slate-900">Ajouter un pôle</h2>
              </div>
            </div>
            <p class="text-sm text-slate-600">Créer un nouveau pôle dans l'établissement</p>
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
              <!-- Nom Pole -->
              <div>
                <label for="nom_pole" class="block text-sm font-medium text-slate-700 mb-1">
                  Nom du pôle <span class="text-red-500">*</span>
                </label>
                <input
                  id="nom_pole"
                  v-model="form.nom_pole"
                  type="text"
                  required
                  maxlength="100"
                  class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent bg-white text-slate-900"
                  placeholder="Ex: Pôle Technologies de l'Information"
                />
              </div>

              <!-- Chef de Pole -->
              <div>
                <label for="id_chef" class="block text-sm font-medium text-slate-700 mb-1">
                  Chef de pôle <span class="text-slate-400">(Optionnel)</span>
                </label>
                <select
                  id="id_chef"
                  v-model.number="form.id_chef"
                  class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent bg-white text-slate-900"
                >
                  <option :value="null">Aucun chef assigné</option>
                  <option v-for="user in chefPoles" :key="user.id" :value="user.id">
                    {{ user.prenom }} {{ user.nom }} ({{ user.matricule }})
                  </option>
                </select>
                <p class="mt-1 text-xs text-slate-500">
                  Sélectionnez un utilisateur avec le rôle "Chef de Pôle"
                </p>
              </div>
            </div>

            <!-- Form Actions -->
            <div class="mt-8 flex items-center justify-end gap-4">
              <NuxtLink
                to="/poles"
                class="px-6 py-2 border border-slate-300 text-slate-700 font-medium rounded-lg hover:bg-slate-50 transition-colors"
              >
                Annuler
              </NuxtLink>
              <button
                type="submit"
                :disabled="submitting"
                class="px-6 py-2 bg-sky-600 text-white font-medium rounded-lg hover:bg-sky-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                <span v-if="submitting" class="flex items-center">
                  <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Création en cours...
                </span>
                <span v-else>Créer le pôle</span>
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

interface ChefPoleUser {
  id: number
  nom: string
  prenom: string
  matricule: string
  role: string
}

const router = useRouter()
const api = useApi()
const authStore = useAuthStore()

const isSidebarOpen = ref(false)
const submitting = ref(false)
const errorMessage = ref<string | null>(null)
const successMessage = ref<string | null>(null)

const chefPoles = ref<ChefPoleUser[]>([])

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
  nom_pole: '',
  id_chef: null as number | null,
})

const handleSubmit = async () => {
  // Clear previous messages
  errorMessage.value = null
  successMessage.value = null

  // Validate
  if (!form.value.nom_pole.trim()) {
    errorMessage.value = 'Le nom du pôle est requis'
    return
  }

  submitting.value = true

  try {
    await api.post('/poles', {
      nom_pole: form.value.nom_pole.trim(),
      id_chef: form.value.id_chef,
    })

    successMessage.value = 'Pôle créé avec succès !'
    
    // Reset form
    form.value = {
      nom_pole: '',
      id_chef: null,
    }

    // Redirect after 1.5 seconds
    setTimeout(() => {
      router.push('/poles')
    }, 1500)

  } catch (err: any) {
    console.error('Error creating pole:', err)
    
    // Handle validation errors
    if (err.data && err.data.errors) {
      const errors = Object.values(err.data.errors).flat()
      errorMessage.value = errors.join(', ')
    } else {
      errorMessage.value = err.message || 'Erreur lors de la création du pôle'
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

const loadChefPoles = async () => {
  try {
    const response = await api.get<ChefPoleUser[] | { data: ChefPoleUser[] }>('/users')
    const allUsers = Array.isArray(response) ? response : ((response as any).data || [])
    // Filter only users with CHEF_POLE role
    chefPoles.value = allUsers.filter((user: ChefPoleUser) => user.role === 'CHEF_POLE')
  } catch (err: any) {
    console.error('Error loading chef poles:', err)
  }
}

onMounted(() => {
  loadChefPoles()
})
</script>
