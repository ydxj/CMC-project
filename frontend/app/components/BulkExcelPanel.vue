<template>
  <div>
    <button
      type="button"
      class="inline-flex items-center rounded-lg border border-sky-600 bg-white px-3 py-2 text-sm font-semibold text-sky-700 hover:bg-sky-50"
      @click="openModal"
    >
      Import / Export Excel
    </button>

    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isOpen"
        class="fixed inset-0 z-50 bg-slate-900/50 p-4 backdrop-blur-[1px]"
        @click.self="closeModal"
      >
        <div class="mx-auto mt-10 w-full max-w-3xl rounded-xl border border-slate-200 bg-white p-4 shadow-2xl">
          <div class="mb-3 flex items-center justify-between">
            <div>
              <p class="text-sm font-semibold text-slate-900">Import / Export Excel</p>
              <p class="text-xs text-slate-500">Telecharger le modele puis importer votre fichier.</p>
            </div>
            <button
              type="button"
              class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-100"
              @click="closeModal"
            >
              x
            </button>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <button
              type="button"
              class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60"
              :disabled="downloading"
              @click="handleDownload"
            >
              {{ downloading ? 'Telechargement...' : 'Download Template' }}
            </button>

            <label class="inline-flex cursor-pointer items-center rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
              <input class="hidden" type="file" accept=".xlsx,.xls,.csv" @change="onFileChange" />
              <span>{{ selectedFile ? selectedFile.name : 'Choisir fichier Excel' }}</span>
            </label>

            <button
              type="button"
              class="inline-flex items-center rounded-lg bg-sky-600 px-3 py-2 text-sm font-semibold text-white hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="!selectedFile || importing"
              @click="handleImport"
            >
              {{ importing ? 'Import en cours...' : 'Import' }}
            </button>
          </div>

          <p v-if="message" class="mt-3 text-sm" :class="messageClass">{{ message }}</p>

          <div v-if="summary" class="mt-3 grid gap-2 sm:grid-cols-4">
            <div class="rounded-lg bg-slate-50 p-2 text-xs text-slate-700">Total: <strong>{{ summary.total_rows }}</strong></div>
            <div class="rounded-lg bg-emerald-50 p-2 text-xs text-emerald-700">Inseres: <strong>{{ summary.inserted_rows }}</strong></div>
            <div class="rounded-lg bg-amber-50 p-2 text-xs text-amber-700">Ignores: <strong>{{ summary.skipped_rows }}</strong></div>
            <div class="rounded-lg bg-rose-50 p-2 text-xs text-rose-700">Erreurs: <strong>{{ summary.error_rows }}</strong></div>
          </div>

          <div v-if="errors.length" class="mt-3 max-h-72 overflow-auto rounded-lg border border-rose-200">
            <table class="min-w-full divide-y divide-rose-200 bg-white">
              <thead class="bg-rose-50">
                <tr>
                  <th class="px-3 py-2 text-left text-xs font-semibold text-rose-700">Row</th>
                  <th class="px-3 py-2 text-left text-xs font-semibold text-rose-700">Field</th>
                  <th class="px-3 py-2 text-left text-xs font-semibold text-rose-700">Message</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-rose-100">
                <tr v-for="(item, idx) in errors" :key="`${item.row}-${item.field}-${idx}`">
                  <td class="px-3 py-2 text-xs text-slate-700">{{ item.row }}</td>
                  <td class="px-3 py-2 text-xs text-slate-700">{{ item.field }}</td>
                  <td class="px-3 py-2 text-xs text-slate-700">{{ item.message }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useExcelBulk } from '../../composables/useExcelBulk'

interface ImportSummary {
  total_rows: number
  inserted_rows: number
  skipped_rows: number
  error_rows: number
}

interface ImportErrorItem {
  row: number
  field: string
  message: string
}

const props = defineProps<{
  entity: 'users' | 'salles' | 'filiers' | 'groupes' | 'modules' | 'poles' | 'secteurs'
}>()

const emit = defineEmits<{
  imported: []
}>()

const { downloadTemplate, importExcel } = useExcelBulk()

const isOpen = ref(false)
const downloading = ref(false)
const importing = ref(false)
const selectedFile = ref<File | null>(null)
const message = ref('')
const messageType = ref<'success' | 'error'>('success')
const summary = ref<ImportSummary | null>(null)
const errors = ref<ImportErrorItem[]>([])

const messageClass = computed(() => {
  return messageType.value === 'success' ? 'text-emerald-700' : 'text-rose-700'
})

const onFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  selectedFile.value = target.files?.[0] || null
}

const openModal = () => {
  isOpen.value = true
}

const closeModal = () => {
  isOpen.value = false
}

const handleDownload = async () => {
  downloading.value = true
  message.value = ''
  try {
    await downloadTemplate(props.entity)
    messageType.value = 'success'
    message.value = 'Template telecharge avec succes.'
  } catch (err: any) {
    messageType.value = 'error'
    message.value = err?.message || 'Erreur de telechargement du template.'
  } finally {
    downloading.value = false
  }
}

const handleImport = async () => {
  if (!selectedFile.value) {
    messageType.value = 'error'
    message.value = 'Veuillez choisir un fichier Excel.'
    return
  }

  importing.value = true
  message.value = ''
  errors.value = []
  summary.value = null

  try {
    const response = await importExcel(props.entity, selectedFile.value)
    summary.value = response.summary
    errors.value = response.errors || []
    messageType.value = 'success'
    message.value = response.message || 'Import termine.'
    emit('imported')
  } catch (err: any) {
    messageType.value = 'error'
    message.value = err?.message || 'Erreur pendant import.'
  } finally {
    importing.value = false
  }
}
</script>
