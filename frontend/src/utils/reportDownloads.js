export const imprimirPdfBlob = (blob, title = 'Reporte') => {
  const url = window.URL.createObjectURL(new Blob([blob], { type: 'application/pdf' }))
  const win = window.open(url, '_blank')

  if (!win) {
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `${title}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    return
  }

  win.addEventListener('load', () => {
    win.document.title = title
    setTimeout(() => win.print(), 350)
  }, { once: true })

  setTimeout(() => window.URL.revokeObjectURL(url), 60000)
}

export const descargarPdfBlob = (blob, filename) => {
  const safeName = filename.endsWith('.pdf') ? filename : `${filename}.pdf`
  const url = window.URL.createObjectURL(new Blob([blob], { type: 'application/pdf' }))
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', safeName)
  document.body.appendChild(link)
  link.click()
  link.remove()
  window.URL.revokeObjectURL(url)
}

export const descargarBlob = (blob, filename) => {
  const url = window.URL.createObjectURL(new Blob([blob]))
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', filename)
  document.body.appendChild(link)
  link.click()
  link.remove()
  window.URL.revokeObjectURL(url)
}
