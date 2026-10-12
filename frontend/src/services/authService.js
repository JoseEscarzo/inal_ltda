import api from './api'

export const authService = {
  async login(credentials) {
    const response = await api.post('/login', credentials)
    return response.data
  },

  async verifyLoginDevice(payload) {
    const response = await api.post('/login/verify-device', payload)
    return response.data
  },

  async register(userData) {
    const response = await api.post('/register', userData)
    return response.data
  },

  async logout() {
    const response = await api.post('/logout')
    return response.data
  },

  async verifyOtp(payload) {
    const response = await api.post('/verify-otp', payload)
    return response.data
  },
}
