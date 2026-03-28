import { defineNuxtRouteMiddleware, navigateTo } from 'nuxt/app'
import { useAuthStore } from '../../stores/auth'

type RoleKey = 'DOSI' | 'DIRECTEUR_CMC' | 'DIRECTEUR_EFP' | 'CHEF_POLE' | 'FORMATEUR'

const roleRoutePrefixes: Record<RoleKey, string[]> = {
  DOSI: ['/dashboard', '/dosi', '/settings'],
  DIRECTEUR_CMC: ['/cmc'],
  DIRECTEUR_EFP: ['/efp'],
  CHEF_POLE: ['/dashboard', '/chef', '/salles', '/settings'],
  FORMATEUR: ['/dashboard', '/settings', '/formateur'],
}

const roleHomePath: Record<RoleKey, string> = {
  DOSI: '/dashboard',
  DIRECTEUR_CMC: '/cmc',
  DIRECTEUR_EFP: '/efp',
  CHEF_POLE: '/dashboard',
  FORMATEUR: '/dashboard',
}

const isAllowedPath = (path: string, prefixes: string[]) => {
  return prefixes.some((prefix) => path === prefix || path.startsWith(`${prefix}/`))
}

export default defineNuxtRouteMiddleware((to) => {
  // localStorage-backed auth state can only be read in browser runtime.
  if (import.meta.server) {
    return
  }

  const authStore = useAuthStore()
  if (!authStore.initialized) {
    authStore.initializeAuth()
  }

  const isLoginRoute = to.path === '/login'

  if (!authStore.isAuthenticated) {
    if (!isLoginRoute) {
      return navigateTo('/login')
    }
    return
  }

  const role = authStore.user?.role as RoleKey | undefined
  if (!role || !(role in roleRoutePrefixes)) {
    authStore.clearAuth()
    return navigateTo('/login')
  }

  if (isLoginRoute) {
    return navigateTo(roleHomePath[role])
  }

  const allowedPrefixes = roleRoutePrefixes[role]
  if (!isAllowedPath(to.path, allowedPrefixes)) {
    return navigateTo(roleHomePath[role])
  }
})
