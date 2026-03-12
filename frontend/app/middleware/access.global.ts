import { defineNuxtRouteMiddleware, navigateTo } from 'nuxt/app'
import { useAuthStore } from '../../stores/auth'

type RoleKey = 'DIRECTEUR' | 'CHEF_POLE' | 'FORMATEUR'

const roleRoutePrefixes: Record<RoleKey, string[]> = {
  DIRECTEUR: ['/dashboard', '/users', '/poles', '/salles', '/formateurs', '/filieres', '/emploi-du-temps', '/settings'],
  CHEF_POLE: ['/dashboard', '/chef', '/salles', '/settings'],
  FORMATEUR: ['/dashboard', '/settings', '/formateur'],
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

  if (isLoginRoute) {
    return navigateTo('/dashboard')
  }

  const role = authStore.user?.role as RoleKey | undefined
  if (!role || !(role in roleRoutePrefixes)) {
    authStore.clearAuth()
    return navigateTo('/login')
  }

  const allowedPrefixes = roleRoutePrefixes[role]
  if (!isAllowedPath(to.path, allowedPrefixes)) {
    return navigateTo('/dashboard')
  }
})
