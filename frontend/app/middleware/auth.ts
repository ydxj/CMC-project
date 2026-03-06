import { defineNuxtRouteMiddleware, navigateTo } from 'nuxt/app'
import { useAuthStore } from '../../stores/auth'

export default defineNuxtRouteMiddleware((to, from) => {
  // Local storage is only available client-side for token-based auth.
  if (import.meta.server) {
    return
  }

  const authStore = useAuthStore()
  if (!authStore.initialized) {
    authStore.initializeAuth()
  }

  // If not authenticated and trying to access protected route
  if (!authStore.isAuthenticated && to.path !== '/login') {
    return navigateTo('/login')
  }

  // If authenticated and trying to access login page
  if (authStore.isAuthenticated && to.path === '/login') {
    return navigateTo('/dashboard')
  }
})
