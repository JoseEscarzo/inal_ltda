/**
 * Construye el cuerpo JSON para registrar un usuario (servicio de registro).
 * La foto va en data URI (base64) en el campo que consume la API Laravel.
 */
export function buildUserRegistrationPayload({
  nombreCompleto,
  email,
  phone,
  password,
  password_confirmation,
  fotoDataUri,
  face_descriptor,
}) {
  return {
    name: nombreCompleto,
    email,
    phone,
    password,
    password_confirmation,
    face_photo_base64: fotoDataUri,
    face_descriptor,
  }
}

/** Versión solo lectura para depuración (sin contraseña). */
export function buildUserRegistrationPreviewJson({
  nombreCompleto,
  email,
  phone,
  fotoDataUri,
}) {
  return JSON.stringify(
    {
      nombre: nombreCompleto,
      email,
      celular: phone,
      foto: fotoDataUri,
    },
    null,
    2,
  )
}
