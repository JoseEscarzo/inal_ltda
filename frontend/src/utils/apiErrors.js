/**
 * Mensaje legible desde respuestas Laravel (axios) u objetos lanzados por el store.
 */
export function laravelErrorMessage(err) {
  const data = err?.response?.data ?? err

  if (!data || typeof data !== 'object') {
    return typeof err === 'string' ? err : 'Ha ocurrido un error. Inténtalo de nuevo.'
  }

  if (data.errors && typeof data.errors === 'object') {
    const firstKey = Object.keys(data.errors)[0]
    if (firstKey && Array.isArray(data.errors[firstKey]) && data.errors[firstKey].length) {
      return data.errors[firstKey][0]
    }
  }

  if (typeof data.message === 'string' && data.message) {
    return data.message
  }

  return 'Ha ocurrido un error. Inténtalo de nuevo.'
}
