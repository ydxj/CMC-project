import { defineStore } from 'pinia'
import { useRuntimeConfig } from 'nuxt/app'

interface User {
  id: number
  nom: string
  prenom: string
  email: string
  role: string
}

interface AuthState {
  user: User | null
  token: string | null
  isAuthenticated: boolean
  initialized: boolean
}

export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    user: null,
    token: null,
    isAuthenticated: false,
    initialized: false,
  }),

  getters: {
    getUser: (state) => state.user,
    getToken: (state) => state.token,
    isLoggedIn: (state) => state.isAuthenticated,
  },

  actions: {
    async login(credentials: { email: string; password: string }) {
      try {
        const config = useRuntimeConfig()
        const response = await $fetch<{ success: boolean; user: User; token: string }>(
          `${config.public.apiBase}/auth/login`,
          {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
            },
            body: credentials,
          }
        )

        this.token = response.token
        this.user = response.user
        this.isAuthenticated = true
        this.initialized = true

        // Store token in localStorage
        if (import.meta.client) {
          localStorage.setItem('token', response.token)
          localStorage.setItem('user', JSON.stringify(response.user))
        }

        return { success: true, user: response.user }
      } catch (error: any) {
        console.error('Login error:', error)
        return {
          success: false,
          message: error.data?.message || 'Login failed. Please check your credentials.',
        }
      }
    },

    async logout() {
      try {
        const config = useRuntimeConfig()
        if (this.token) {
          await $fetch(`${config.public.apiBase}/auth/logout`, {
            method: 'POST',
            headers: {
              'Authorization': `Bearer ${this.token}`,
              'Accept': 'application/json',
            },
          })
        }
      } catch (error) {
        console.error('Logout error:', error)
      } finally {
        this.clearAuth()
      }
    },

    clearAuth() {
      this.user = null
      this.token = null
      this.isAuthenticated = false
      this.initialized = true

      if (import.meta.client) {
        localStorage.removeItem('token')
        localStorage.removeItem('user')
      }
    },

    async checkAuth() {
      // Restore local state first to avoid false redirects during page refresh.
      this.initializeAuth()

      if (import.meta.client) {
        const token = localStorage.getItem('token')
        const userStr = localStorage.getItem('user')

        if (token) {
          try {
            const config = useRuntimeConfig()
            // Verify token is still valid by fetching current user from API.
            const response = await $fetch<{ success: boolean; user: User }>(`${config.public.apiBase}/auth/me`, {
              headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
              },
            })

            this.token = token
            this.user = response.user
            this.isAuthenticated = true
          } catch (error) {
            this.clearAuth()
            return
          }
        } else {
          this.clearAuth()
        }

        // Keep local user cache coherent when token is valid.
        if (this.user && userStr !== JSON.stringify(this.user)) {
          localStorage.setItem('user', JSON.stringify(this.user))
        }
      }

      this.initialized = true
    },

    initializeAuth() {
      if (import.meta.client) {
        const token = localStorage.getItem('token')
        const userStr = localStorage.getItem('user')

        if (token && userStr) {
          this.token = token
          this.user = JSON.parse(userStr)
          this.isAuthenticated = true
        } else {
          this.token = null
          this.user = null
          this.isAuthenticated = false
        }

        this.initialized = true
      }
    },
  },
})
