import { useRuntimeConfig } from 'nuxt/app'
import { useAuthStore } from '../stores/auth'

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

interface ImportResponse {
  success: boolean
  message: string
  summary: ImportSummary
  errors: ImportErrorItem[]
}

export const useExcelBulk = () => {
  const config = useRuntimeConfig()
  const authStore = useAuthStore()

  const getAuthHeaders = () => {
    const headers: Record<string, string> = {
      Accept: 'application/json',
    }

    if (authStore.token) {
      headers.Authorization = `Bearer ${authStore.token}`
    }

    return headers
  }

  const downloadTemplate = async (entity: string) => {
    const response = await fetch(`${config.public.apiBase}/${entity}/template`, {
      method: 'GET',
      headers: getAuthHeaders(),
    })

    if (!response.ok) {
      const text = await response.text()
      throw new Error(text || 'Template download failed')
    }

    const blob = await response.blob()
    const disposition = response.headers.get('content-disposition') || ''
    const fileNameMatch = disposition.match(/filename="?([^\";]+)"?/i)
    const fileName = fileNameMatch?.[1] || `${entity}-import-template.xlsx`

    const downloadUrl = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = downloadUrl
    link.download = fileName
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(downloadUrl)
  }

  const importExcel = async (entity: string, file: File): Promise<ImportResponse> => {
    const formData = new FormData()
    formData.append('file', file)

    const response = await fetch(`${config.public.apiBase}/${entity}/import`, {
      method: 'POST',
      headers: getAuthHeaders(),
      body: formData,
    })

    const payload = await response.json().catch(() => null)

    if (!response.ok) {
      throw new Error(payload?.message || 'Import failed')
    }

    return payload as ImportResponse
  }

  return {
    downloadTemplate,
    importExcel,
  }
}
