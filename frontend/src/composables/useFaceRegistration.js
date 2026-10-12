import * as faceapi from 'face-api.js'

const MODEL_ROOT =
  'https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@v0.22.2/weights'

let modelsLoaded = false
let loadPromise = null

export const tinyFaceDetectorOptions = () =>
  new faceapi.TinyFaceDetectorOptions({
    inputSize: 224,
    scoreThreshold: 0.45,
  })

/**
 * Foto JPEG en data URI desde el fotograma actual del video.
 */
export function snapshotVideoToDataUrl(video, canvas, quality = 0.84) {
  const w = video.videoWidth
  const h = video.videoHeight
  if (!w || !h) {
    return null
  }
  canvas.width = w
  canvas.height = h
  const ctx = canvas.getContext('2d')
  if (!ctx) {
    return null
  }
  ctx.drawImage(video, 0, 0, w, h)
  return canvas.toDataURL('image/jpeg', quality)
}

/** TinyFaceDetector + landmarks + descriptor: rápido con face-api.js */
export function useFaceRegistration() {
  function loadModels() {
    if (modelsLoaded) {
      return Promise.resolve()
    }
    if (loadPromise) {
      return loadPromise
    }
    loadPromise = Promise.all([
      faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_ROOT),
      faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_ROOT),
      faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_ROOT),
    ]).then(() => {
      modelsLoaded = true
    })
    return loadPromise
  }

  /**
   * @param {HTMLVideoElement} video
   * @param {HTMLCanvasElement} canvas
   * @returns {Promise<{ dataUrl: string, descriptor: number[] } | null>}
   */
  async function captureFaceFromVideo(video, canvas) {
    await loadModels()

    const w = video.videoWidth
    const h = video.videoHeight
    if (!w || !h) {
      return null
    }

    const detection = await faceapi
      .detectSingleFace(video, tinyFaceDetectorOptions())
      .withFaceLandmarks()
      .withFaceDescriptor()

    if (!detection) {
      return null
    }

    const dataUrl = snapshotVideoToDataUrl(video, canvas, 0.84)
    if (!dataUrl) {
      return null
    }

    const descriptor = Array.from(detection.descriptor)

    return { dataUrl, descriptor }
  }

  return { loadModels, captureFaceFromVideo }
}

export { faceapi }
