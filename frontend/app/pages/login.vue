<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-50 to-slate-100 px-4 py-8">
    <div class="w-full max-w-md">

      <div class="bg-white rounded-2xl shadow-xl p-8 sm:p-10">

        <!-- LOGO & TITLE -->
        <div class="text-center mb-8">
          <div class="flex justify-center mb-6">
            <div class="flex h-20 w-20 items-center justify-center rounded-xl bg-slate-100 ring-2 ring-slate-200">
              <img :src="logoImage" alt="CMC Logo" class="h-16 w-16 object-contain" />
            </div>
          </div>
          
          <h1 class="text-3xl font-bold text-slate-900 mb-2">
            Welcome Back
          </h1>
          <p class="text-sm text-slate-600">
            Sign in to access your dashboard
          </p>
        </div>

            <!-- ERROR -->
            <div
              v-if="errorMessage"
              class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800"
            >
              {{ errorMessage }}
            </div>

        <!-- ERROR -->
        <div
          v-if="errorMessage"
          class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
        >
          {{ errorMessage }}
        </div>

        <!-- FORM -->
        <form @submit.prevent="handleLogin" class="space-y-5">

          <!-- EMAIL -->
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">
              Email Address
            </label>
            <input
              v-model="credentials.email"
              type="email"
              required
              placeholder="name@cmc.ma"
              class="w-full h-12 px-4 text-base border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
              :class="{ 'border-red-500 focus:ring-red-500': errors.email }"
              :disabled="loading"
            />
            <p v-if="errors.email" class="mt-1 text-sm text-red-600">
              {{ errors.email }}
            </p>
          </div>

          <!-- PASSWORD -->
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">
              Password
            </label>
            <div class="relative">
              <input
                v-model="credentials.password"
                :type="showPassword ? 'text' : 'password'"
                required
                placeholder="Enter your password"
                class="w-full h-12 px-4 pr-12 text-base border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                :class="{ 'border-red-500 focus:ring-red-500': errors.password }"
                :disabled="loading"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-500 hover:text-slate-700"
              >
                <svg v-if="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                </svg>
              </button>
            </div>
            <p v-if="errors.password" class="mt-1 text-sm text-red-600">
              {{ errors.password }}
            </p>
          </div>

          <!-- OPTIONS -->
          <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2 cursor-pointer text-slate-600">
              <input
                v-model="rememberMe"
                type="checkbox"
                class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
              />
              Remember me
            </label>

            <a href="#" class="font-medium text-blue-600 hover:text-blue-700">
              Forgot password?
            </a>
          </div>

          <!-- BUTTON -->
          <button
            type="submit"
            :disabled="loading"
            class="w-full h-12 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
          >
            <svg
              v-if="loading"
              class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/>
              <path fill="currentColor" class="opacity-75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            {{ loading ? "Signing in..." : "Sign In" }}
          </button>

        </form>

        <!-- FOOTER -->
        <p class="mt-8 text-center text-xs text-slate-500">
          CMC Project © {{ new Date().getFullYear() }}
        </p>

      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '../../stores/auth'
import { useRouter } from 'vue-router'
import { ref, onMounted } from 'vue'

const authStore = useAuthStore()
const router = useRouter()

const logoImage = '/logo.png'

const credentials = ref({
  email: '',
  password: ''
})

const showPassword = ref(false)
const rememberMe = ref(false)
const loading = ref(false)
const errorMessage = ref('')

const errors = ref({
  email: '',
  password: ''
})

const validateForm = () => {
  errors.value = { email: '', password: '' }

  let isValid = true
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

  if (!credentials.value.email) {
    errors.value.email = 'Email is required'
    isValid = false
  } else if (!emailRegex.test(credentials.value.email)) {
    errors.value.email = 'Please enter a valid email'
    isValid = false
  }

  if (!credentials.value.password) {
    errors.value.password = 'Password is required'
    isValid = false
  } else if (credentials.value.password.length < 6) {
    errors.value.password = 'Password must be at least 6 characters'
    isValid = false
  }

  return isValid
}

const handleLogin = async () => {
  errorMessage.value = ''

  if (!validateForm()) return

  loading.value = true

  try {
    const result = await authStore.login(credentials.value)

    if (result.success) {
      router.push('/dashboard')
    } else {
      errorMessage.value = result.message || 'Login failed'
    }

  } catch (error) {
    console.error(error)
    errorMessage.value = 'Unexpected error occurred'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (authStore.isAuthenticated) {
    router.push('/dashboard')
  }
})
</script>

<style scoped>
.login-shell {
  background:
    radial-gradient(circle at 15% 15%, rgba(56,189,248,0.25), transparent 35%),
    radial-gradient(circle at 85% 10%, rgba(99,102,241,0.25), transparent 35%),
    linear-gradient(140deg,#f8fafc,#e2e8f0);
}

.login-card {
  backdrop-filter: blur(12px);
  background: rgba(255,255,255,0.85);
  box-shadow: 0 40px 80px rgba(0,0,0,0.12);
}

.logo-wrap {
  animation: floatLogo 3.5s ease-in-out infinite;
}

.input-field {
  width: 100%;
  border: 1px solid #cbd5e1;
  transition: all .25s ease;
}

.input-field:focus {
  outline: none;
  border-color: #38bdf8;
  box-shadow: 0 0 0 3px rgba(56,189,248,.15);
}

.input-error {
  border-color: #ef4444;
}

.error-message {
  font-size: 12px;
  color: #ef4444;
  margin-top: 6px;
}

.feature-box {
  border: 1px solid rgba(255,255,255,.2);
  background: rgba(255,255,255,.1);
  padding: 10px;
  border-radius: 10px;
}

.submit-btn {
  background: linear-gradient(135deg,#0f172a,#1e293b);
  box-shadow: 0 10px 25px rgba(15,23,42,.3);
  transition: all .25s;
}

.submit-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 15px 35px rgba(15,23,42,.35);
}

.divider{
  height:1px;
  width:100%;
  margin-bottom:24px;
  background:linear-gradient(to right,transparent,#e2e8f0,transparent);
}

.animate-reveal{
  animation: revealUp .7s cubic-bezier(.16,.84,.44,1);
}

@keyframes floatLogo{
  0%{transform:translateY(0)}
  50%{transform:translateY(-6px)}
  100%{transform:translateY(0)}
}

@keyframes revealUp{
  from{opacity:0;transform:translateY(14px)}
  to{opacity:1;transform:translateY(0)}
}
</style>
