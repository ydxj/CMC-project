<template>
  <div class="min-h-screen bg-slate-50 text-slate-800">
    <header class="border-b border-slate-200 bg-white/90 backdrop-blur">
      <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 ring-1 ring-slate-200">
            <img src="/logo.png" alt="CMC logo" class="h-7 w-7 object-contain" />
          </div>
          <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">CMC</p>
            <h1 class="text-sm font-semibold text-slate-900">Schedule Management</h1>
          </div>
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

    <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
      <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-600">Dashboard</p>
        <h2 class="mt-2 text-2xl font-bold text-slate-900">{{ greetingTitle }}</h2>
        <p class="mt-2 max-w-3xl text-sm text-slate-600">
          Manage poles, salles, formateurs, modules and planning from one place. Access and actions adapt to your role.
        </p>
      </section>

      <section class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Poles</p>
          <p class="mt-3 text-2xl font-bold text-slate-900">{{ stats.poles }}</p>
          <p class="mt-1 text-xs text-slate-500">Training departments</p>
        </article>

        <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Salles / Ateliers</p>
          <p class="mt-3 text-2xl font-bold text-slate-900">{{ stats.salles }}</p>
          <p class="mt-1 text-xs text-slate-500">Available learning spaces</p>
        </article>

        <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Formateurs</p>
          <p class="mt-3 text-2xl font-bold text-slate-900">{{ stats.formateurs }}</p>
          <p class="mt-1 text-xs text-slate-500">Assigned teachers</p>
        </article>

        <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Modules</p>
          <p class="mt-3 text-2xl font-bold text-slate-900">{{ stats.modules }}</p>
          <p class="mt-1 text-xs text-slate-500">Courses and units</p>
        </article>
      </section>

      <section class="mt-6 grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
          <h3 class="text-base font-semibold text-slate-900">Role-Based Quick Actions</h3>
          <p class="mt-1 text-sm text-slate-600">Actions available for {{ roleLabel.toLowerCase() }}.</p>

          <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <button
              v-for="action in quickActions"
              :key="action.label"
              class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-left hover:border-sky-300 hover:bg-sky-50"
              @click="goTo(action.path)"
            >
              <p class="text-sm font-semibold text-slate-900">{{ action.label }}</p>
              <p class="mt-1 text-xs text-slate-600">{{ action.description }}</p>
            </button>
          </div>
        </article>

        <!-- <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
          <h3 class="text-base font-semibold text-slate-900">CMC Workflow</h3>
          <ul class="mt-4 space-y-3 text-sm text-slate-700">
            <li class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
              Directeur Pedagogique creates poles, salles and chef de poles.
            </li>
            <li class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
              Chef de Pole manages formateurs and assigns modules.
            </li>
            <li class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
              Formateurs deliver modules to groupes in available salles.
            </li>
            <li class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
              Emploi du temps centralizes weekly planning and conflict control.
            </li>
          </ul>
        </article> -->
      </section>
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

interface QuickAction {
  label: string
  description: string
  path: string
}

const authStore = useAuthStore()
const router = useRouter()

const stats = reactive({
  poles: '--',
  salles: '--',
  formateurs: '--',
  modules: '--',
})

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

const greetingTitle = computed(() => {
  if (!authStore.user) return 'Welcome to CMC platform'
  return `Welcome, ${authStore.user.prenom}`
})

const userInitials = computed(() => {
  if (!authStore.user) return 'CM'
  const nom = authStore.user.nom?.charAt(0) || ''
  const prenom = authStore.user.prenom?.charAt(0) || ''
  return `${nom}${prenom}`.toUpperCase() || 'CM'
})

const quickActions = computed<QuickAction[]>(() => {
  const role = authStore.user?.role

  if (role === 'DIRECTEUR') {
    return [
      { label: 'Manage Poles', description: 'Create and update poles', path: '/poles' },
      { label: 'Manage Salles', description: 'Add and organize salles', path: '/salles' },
      { label: 'Manage Users', description: 'Create chef de poles and users', path: '/users' },
      { label: 'View Schedule', description: 'Monitor global planning', path: '/emploi-du-temps' },
    ]
  }

  if (role === 'CHEF_POLE') {
    return [
      { label: 'Manage Formateurs', description: 'Add and update formateurs', path: '/formateurs' },
      { label: 'Assign Modules', description: 'Map modules to formateurs', path: '/modules' },
      { label: 'Manage Groupes', description: 'Organize student groups', path: '/groupes' },
      { label: 'View Schedule', description: 'Follow pole planning', path: '/emploi-du-temps' },
    ]
  }

  return [
    { label: 'My Schedule', description: 'See your weekly sessions', path: '/emploi-du-temps' },
    { label: 'My Modules', description: 'Check assigned modules', path: '/modules' },
  ]
})

const goTo = (path: string) => {
  router.push(path)
}

const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}

onMounted(async () => {
  authStore.initializeAuth()

  if (!authStore.isAuthenticated) {
    router.push('/login')
    return
  }

  // Refresh user profile from backend to keep session coherent across refresh.
  await authStore.checkAuth()

  if (!authStore.isAuthenticated) {
    router.push('/login')
  }
})
</script>
