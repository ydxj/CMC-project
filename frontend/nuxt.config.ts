// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },

  srcDir: 'app',
  
  alias: {
    '@': '/app'
  },
  
  modules: [
    '@nuxtjs/tailwindcss',
    '@pinia/nuxt',
    '@vee-validate/nuxt'
  ],

  runtimeConfig: {
    public: {
      // Overridden by NUXT_PUBLIC_API_BASE from .env when provided.
      apiBase: 'http://localhost:8000/api'
    }
  },

  app: {
    head: {
      title: 'CMC Project',
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' }
      ]
    }
  }
})
