<template>
  <aside class="h-full border-r border-slate-200 bg-white text-slate-800">
    <div class="flex h-16 items-center gap-3 border-b border-slate-200 px-4">
      <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 ring-1 ring-slate-200">
        <img src="/logo.png" alt="CMC logo" class="h-6 w-6 object-contain" />
      </div>
      <div>
        <p class="text-[10px] uppercase tracking-[0.18em] text-slate-500">CMC</p>
        <p class="text-sm font-semibold text-slate-900">Schedule Manager</p>
      </div>
    </div>

    <div class="px-4 py-3">
      <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">{{ roleLabel }}</p>
    </div>

    <nav class="h-[calc(100%-120px)] overflow-y-auto px-3 pb-4">
      <NuxtLink
        :to="menu.dashboard.path"
        @click="handleNavigate"
        class="mb-2 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition"
        :class="linkClass(menu.dashboard.path)"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="iconPath(menu.dashboard.icon)" />
        </svg>
        <span class="font-medium">{{ menu.dashboard.label }}</span>
      </NuxtLink>

      <section
        v-for="group in menu.groups"
        :key="group.label"
        class="mt-3 border-t border-slate-200 pt-3"
      >
        <p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ group.label }}</p>

        <div class="space-y-1">
          <NuxtLink
            v-for="item in group.items"
            :key="item.label"
            :to="item.path"
            @click="handleNavigate"
            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition"
            :class="linkClass(item.path)"
          >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="iconPath(item.icon)" />
            </svg>
            <span>{{ item.label }}</span>
          </NuxtLink>
        </div>
      </section>

      <section class="mt-3 border-t border-slate-200 pt-3">
        <NuxtLink
          :to="menu.settings.path"
          @click="handleNavigate"
          class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition"
          :class="linkClass(menu.settings.path)"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="iconPath(menu.settings.icon)" />
          </svg>
          <span class="font-medium">{{ menu.settings.label }}</span>
        </NuxtLink>
      </section>
    </nav>
  </aside>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'

type RoleKey = 'DIRECTEUR' | 'CHEF_POLE' | 'FORMATEUR'
type IconName =
  | 'home'
  | 'users'
  | 'user-plus'
  | 'building'
  | 'door'
  | 'book'
  | 'teacher'
  | 'calendar'
  | 'filter'
  | 'settings'
  | 'group'
  | 'module'
  | 'link'
  | 'clock'
  | 'table'
  | 'month'

interface MenuItem {
  label: string
  path: string
  icon: IconName
}

interface MenuGroup {
  label: string
  items: MenuItem[]
}

interface RoleMenu {
  dashboard: MenuItem
  groups: MenuGroup[]
  settings: MenuItem
}

const props = defineProps<{ role?: string }>()
const emit = defineEmits<{ (e: 'navigate'): void }>()
const route = useRoute()

const menusByRole: Record<RoleKey, RoleMenu> = {
  DIRECTEUR: {
    dashboard: { label: 'Dashboard', path: '/dashboard', icon: 'home' },
    groups: [
      {
        label: 'Utilisateurs',
        items: [
          { label: 'Liste des utilisateurs', path: '/users', icon: 'users' },
          { label: 'Ajouter utilisateur', path: '/users/add', icon: 'user-plus' },
        ],
      },
      {
        label: 'Poles',
        items: [
          { label: 'Liste des poles', path: '/poles', icon: 'building' },
          { label: 'Ajouter pole', path: '/poles/add', icon: 'building' },
        ],
      },
      {
        label: 'Salles & Ateliers',
        items: [
          { label: 'Liste des salles', path: '/salles', icon: 'door' },
          { label: 'Ajouter salle', path: '/salles/add', icon: 'door' },
        ],
      },
      {
        label: 'Filieres',
        items: [
          { label: 'Liste des filieres', path: '/filieres', icon: 'book' },
          { label: 'Ajouter filiere', path: '/filieres/add', icon: 'book' },
        ],
      },
      {
        label: 'Formateurs',
        items: [
          { label: 'Liste des formateurs', path: '/formateurs', icon: 'teacher' },
        ],
      },
      {
        label: 'Emploi du Temps',
        items: [
          { label: 'Vue globale', path: '/emploi-du-temps', icon: 'calendar' },
          { label: 'Par pole', path: '/emploi-du-temps?view=pole', icon: 'filter' },
          { label: 'Par formateur', path: '/emploi-du-temps?view=formateur', icon: 'filter' },
        ],
      },
    ],
    settings: { label: 'Parametres', path: '/settings', icon: 'settings' },
  },
  CHEF_POLE: {
    dashboard: { label: 'Dashboard', path: '/dashboard', icon: 'home' },
    groups: [
      {
        label: 'Formateurs',
        items: [
          { label: 'Liste des formateurs', path: '/formateurs', icon: 'teacher' },
          { label: 'Ajouter formateur', path: '/formateurs/create', icon: 'user-plus' },
        ],
      },
      {
        label: 'Filieres',
        items: [
          { label: 'Liste des filieres', path: '/filieres', icon: 'book' },
          { label: 'Ajouter filiere', path: '/filieres/add', icon: 'book' },
        ],
      },
      {
        label: 'Groupes',
        items: [
          { label: 'Liste des groupes', path: '/groupes', icon: 'group' },
          { label: 'Ajouter groupe', path: '/groupes/create', icon: 'group' },
        ],
      },
      {
        label: 'Modules',
        items: [
          { label: 'Liste des modules', path: '/modules', icon: 'module' },
          { label: 'Ajouter module', path: '/modules/create', icon: 'module' },
          { label: 'Assigner a formateur', path: '/modules/assign', icon: 'link' },
        ],
      },
      {
        label: 'Salles & Ateliers',
        items: [
          { label: 'Liste des salles', path: '/salles', icon: 'door' },
        ],
      },
      {
        label: 'Emploi du Temps',
        items: [
          { label: 'Vue du pole', path: '/emploi-du-temps', icon: 'calendar' },
          { label: 'Creer une seance', path: '/emploi-du-temps/create', icon: 'calendar' },
          { label: 'Par formateur', path: '/emploi-du-temps?view=formateur', icon: 'filter' },
          { label: 'Par groupe', path: '/emploi-du-temps?view=groupe', icon: 'filter' },
          { label: 'Par salle', path: '/emploi-du-temps?view=salle', icon: 'filter' },
        ],
      },
    ],
    settings: { label: 'Parametres', path: '/settings', icon: 'settings' },
  },
  FORMATEUR: {
    dashboard: { label: 'Dashboard', path: '/dashboard', icon: 'home' },
    groups: [
      {
        label: 'Mon Emploi du Temps',
        items: [
          { label: 'Vue hebdomadaire', path: '/emploi-du-temps?view=week', icon: 'table' },
          { label: 'Vue mensuelle', path: '/emploi-du-temps?view=month', icon: 'month' },
        ],
      },
      {
        label: 'Mes Modules',
        items: [
          { label: 'Liste des modules assignes', path: '/modules?mine=1', icon: 'module' },
        ],
      },
      {
        label: 'Mes Groupes',
        items: [
          { label: 'Liste des groupes', path: '/groupes?mine=1', icon: 'group' },
        ],
      },
    ],
    settings: { label: 'Parametres', path: '/settings', icon: 'settings' },
  },
}

const iconPaths: Record<IconName, string> = {
  home: 'M3 10.5L12 3l9 7.5M5.25 9.75V21h13.5V9.75',
  users: 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M16 3.5a4 4 0 010 8M20 21v-2a4 4 0 00-3-3.87M10 11.5a4 4 0 100-8 4 4 0 000 8z',
  'user-plus': 'M15 19v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M8.5 11a4 4 0 100-8 4 4 0 000 8zM19 8v6M16 11h6',
  building: 'M3 21h18M6 21V7l6-4 6 4v14M9 10h.01M9 13h.01M9 16h.01M15 10h.01M15 13h.01M15 16h.01',
  door: 'M4 21h16M7 21V5a2 2 0 012-2h6a2 2 0 012 2v16M10 12h.01',
  book: 'M4 5.5A2.5 2.5 0 016.5 3H20v16H6.5A2.5 2.5 0 014 16.5v-11zM4 16.5A2.5 2.5 0 016.5 14H20',
  teacher: 'M12 14l9-5-9-5-9 5 9 5zM7 12.5V17a5 5 0 0010 0v-4.5',
  calendar: 'M8 3v3M16 3v3M4 10h16M5 6h14a1 1 0 011 1v13H4V7a1 1 0 011-1z',
  filter: 'M3 5h18M6 12h12M10 19h4',
  settings: 'M12 8.5a3.5 3.5 0 100 7 3.5 3.5 0 000-7zM19 12a7 7 0 00-.08-1l2.08-1.62-2-3.46-2.5 1a7.1 7.1 0 00-1.72-1L14.5 3h-5l-.28 2.92a7.1 7.1 0 00-1.72 1l-2.5-1-2 3.46L5.08 11A7 7 0 005 12c0 .34.03.67.08 1L3 14.62l2 3.46 2.5-1c.53.4 1.11.73 1.72 1L9.5 21h5l.28-2.92c.61-.27 1.19-.6 1.72-1l2.5 1 2-3.46L18.92 13c.05-.33.08-.66.08-1z',
  group: 'M17 21v-1.5a3.5 3.5 0 00-3.5-3.5h-7A3.5 3.5 0 003 19.5V21M10 12a4 4 0 100-8 4 4 0 000 8M19 8v6M16 11h6',
  module: 'M4 4h16v16H4zM4 10h16M10 4v16',
  link: 'M10 14a4 4 0 010-5.66l2.34-2.34a4 4 0 115.66 5.66L16 13.6M14 10a4 4 0 010 5.66l-2.34 2.34a4 4 0 11-5.66-5.66L8 10.4',
  clock: 'M12 6v6l4 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
  table: 'M4 5h16v14H4zM4 10h16M9 5v14M15 5v14',
  month: 'M7 3v3M17 3v3M4 8h16M5 5h14a1 1 0 011 1v13H4V6a1 1 0 011-1M8 12h2M12 12h2M16 12h2M8 16h2M12 16h2',
}

const roleKey = computed<RoleKey>(() => {
  if (props.role === 'DIRECTEUR') return 'DIRECTEUR'
  if (props.role === 'CHEF_POLE') return 'CHEF_POLE'
  return 'FORMATEUR'
})

const roleLabel = computed(() => {
  if (roleKey.value === 'DIRECTEUR') return 'Directeur'
  if (roleKey.value === 'CHEF_POLE') return 'Chef de pole'
  return 'Formateur'
})

const menu = computed<RoleMenu>(() => menusByRole[roleKey.value])

const iconPath = (icon: IconName) => iconPaths[icon]

const isActive = (path: string) => {
  const pathWithoutQuery = path.split('?')[0] || path
  if (pathWithoutQuery === '/dashboard') {
    return route.path === '/dashboard'
  }
  return route.path.startsWith(pathWithoutQuery)
}

const linkClass = (path: string) => {
  return isActive(path)
    ? 'bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-200'
    : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900'
}

const handleNavigate = () => {
  emit('navigate')
}
</script>
