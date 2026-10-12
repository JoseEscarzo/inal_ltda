/**
 * Solicita la cámara probando restricciones del más específico al más permisivo
 * (evita OverconstrainedError en muchos equipos / escritorio).
 */
export async function getCameraStream() {
  if (typeof window === 'undefined') {
    throw new Error('NO_WINDOW')
  }
  if (!window.isSecureContext) {
    const host = window.location.hostname
    if (!isLocalhostHost(host)) {
      throw Object.assign(new Error('INSECURE'), { name: 'InsecureContext' })
    }
  }

  const api = navigator.mediaDevices?.getUserMedia?.bind(navigator.mediaDevices)
  if (!api) {
    throw Object.assign(new Error('NO_API'), { name: 'NoApi' })
  }

  const strategies = [
    () => ({ video: { facingMode: { ideal: 'user' } }, audio: false }),
    () => ({ video: { facingMode: 'user' }, audio: false }),
    () => ({ video: true, audio: false }),
    () => ({ video: { facingMode: 'environment' }, audio: false }),
  ]

  let lastError
  for (const build of strategies) {
    try {
      return await api(build())
    } catch (err) {
      lastError = err
      const retry =
        err?.name === 'OverconstrainedError' ||
        err?.name === 'ConstraintNotSatisfiedError'
      if (retry) {
        continue
      }
      throw err
    }
  }
  throw lastError
}

function isLocalhostHost(host) {
  return (
    host === 'localhost' ||
    host === '127.0.0.1' ||
    host === '[::1]' ||
    host.endsWith('.localhost')
  )
}

export function formatCameraError(err) {
  if (typeof window !== 'undefined' && !window.isSecureContext) {
    const host = window.location?.hostname || ''
    if (!isLocalhostHost(host)) {
      return 'La cámara requiere HTTPS (o abrir la app en http://localhost). No uses http://192.168.x.x u otra IP sin certificado: el navegador bloquea la cámara.'
    }
  }

  if (err?.name === 'InsecureContext' || err?.message === 'INSECURE') {
    return 'Entorno no seguro: usa https:// o http://localhost para permitir la cámara.'
  }

  if (err?.name === 'NoApi' || err?.message === 'NO_API' || err?.message === 'NO_WINDOW') {
    return 'Tu navegador no soporta getUserMedia o la página bloquea el acceso a la cámara.'
  }

  const name = err?.name || ''

  if (name === 'NotAllowedError' || name === 'PermissionDeniedError') {
    return 'Permiso denegado: en la barra de direcciones, pulsa el candado → Permisos → Cámara → Permitir. Si ya negaste antes, recarga la página tras cambiar el permiso.'
  }

  if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
    return 'No hay cámara disponible. Conecta una webcam o comprueba que el dispositivo tenga cámara activada en Windows (Configuración → Privacidad → Cámara).'
  }

  if (name === 'NotReadableError' || name === 'TrackStartError' || name === 'AbortError') {
    return 'La cámara está ocupada por otra app (Zoom, Teams, grabadora, otro navegador). Cierra esas ventanas y vuelve a intentar.'
  }

  if (name === 'OverconstrainedError' || name === 'ConstraintNotSatisfiedError') {
    return 'Tu cámara no acepta el modo solicitado. Prueba otro navegador (Chrome o Edge) o recarga la página.'
  }

  return 'No se pudo abrir la cámara. Revisa permisos del sistema (Windows: Configuración → Privacidad y seguridad → Cámara) y que el sitio pueda usarla.'
}
