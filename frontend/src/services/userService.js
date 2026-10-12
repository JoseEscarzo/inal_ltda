import api from './api'

export const userService = {
  async list() {
    const { data } = await api.get('/users')
    return data.usuarios ?? []
  },

  async get(id) {
    const { data } = await api.get(`/users/${id}`)
    return data
  },

  async create(payload) {
    const { data } = await api.post('/users', payload)
    return data
  },

  async update(id, payload) {
    const { data } = await api.put(`/users/${id}`, payload)
    return data
  },

  async remove(id) {
    const { data } = await api.delete(`/users/${id}`)
    return data
  },
  async getRoles() {
    const { data } = await api.get('/roles');
    return data;
  },
  async listInactivos() {
    const { data } = await api.get('/users/inactivos')
    return data.usuarios ?? []
  },

  async activar(id) {
    const { data } = await api.patch(`/users/${id}/activar`)
    return data
  },

  async eliminarDefinitivo(id) {
    const { data } = await api.delete(`/users/${id}/definitivo`)
    return data
  },

  async exportPdf(params = {}) {
    return api.get('/reportes/usuarios/pdf', { responseType: 'blob', params })
  },

  async exportExcel(params = {}) {
    return api.get('/reportes/usuarios/excel', { responseType: 'blob', params })
  },
}
