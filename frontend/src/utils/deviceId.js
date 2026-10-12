const STORAGE_KEY = 'inal_ltda_device_id'

/**
 * Identificador estable por navegador para “dispositivo de confianza”.
 */
export function getOrCreateDeviceId() {
  if (typeof window === 'undefined' || !window.localStorage) {
    return ''
  }
  let id = localStorage.getItem(STORAGE_KEY)
  if (!id || id.length < 8) {
    id = crypto.randomUUID?.() ?? `${Date.now()}-${Math.random().toString(36).slice(2, 14)}`
    localStorage.setItem(STORAGE_KEY, id)
  }
  return id
}
