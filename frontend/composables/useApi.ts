import { useRuntimeConfig } from 'nuxt/app'
import { useAuthStore } from '../stores/auth'
import { useRouter } from 'vue-router'

export const useApi = () => {
  // These are auto-imported in Nuxt 3, but we ensure they're available
  const config = useRuntimeConfig()
  const authStore = useAuthStore()
  const router = useRouter()

  interface ApiOptions {
    method?: 'GET' | 'POST' | 'PUT' | 'DELETE' | 'PATCH'
    body?: any
    params?: Record<string, any>
    headers?: Record<string, string>
  }

  const apiCall = async <T = any>(
    endpoint: string,
    options: ApiOptions = {}
  ): Promise<T> => {
    const { method = 'GET', body, params, headers = {} } = options

    // Get token from auth store
    const token = authStore.token

    // Build headers
    const requestHeaders: Record<string, string> = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...headers,
    }

    if (token) {
      requestHeaders['Authorization'] = `Bearer ${token}`
    }

    try {
      const url = `${config.public.apiBase}${endpoint}`
      
      const response = await $fetch<T>(url, {
        method,
        headers: requestHeaders,
        body: body ? JSON.stringify(body) : undefined,
        params,
      })

      return response
    } catch (error: any) {
      console.error('API Error:', error)
      
      // Handle authentication errors
      if (error.status === 401 || error.statusCode === 401) {
        authStore.logout()
        router.push('/login')
      }
      
      throw error
    }
  }

  return {
    get: <T = any>(endpoint: string, params?: Record<string, any>) =>
      apiCall<T>(endpoint, { method: 'GET', params }),
    
    post: <T = any>(endpoint: string, body: any) =>
      apiCall<T>(endpoint, { method: 'POST', body }),
    
    put: <T = any>(endpoint: string, body: any) =>
      apiCall<T>(endpoint, { method: 'PUT', body }),
    
    delete: <T = any>(endpoint: string) =>
      apiCall<T>(endpoint, { method: 'DELETE' }),
    
    patch: <T = any>(endpoint: string, body: any) =>
      apiCall<T>(endpoint, { method: 'PATCH', body }),
  }
}
