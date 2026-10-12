import { defineStore } from 'pinia'
import { authService } from '@/services/authService'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem('token') || null,
    user: JSON.parse(localStorage.getItem('user') || 'null') || null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
  },

  actions: {
    /**
     * @returns {Promise<{ requiresLoginOtp: true, email: string, otp_debug?: string, message?: string } | { requiresLoginOtp: false }>}
     */
    async login(credentials) {
      const data = await authService.login(credentials)
      if (data.requires_login_otp) {
        return {
          requiresLoginOtp: true,
          email: data.email,
          otp_debug: data.otp_debug,
          message: data.message,
        }
      }
      this.user = data.user
      this.token = data.token
      localStorage.setItem('token', data.token)
      localStorage.setItem('user', JSON.stringify(data.user))
      return { requiresLoginOtp: false }
    },

    async verifyLoginDevice(payload) {
      const data = await authService.verifyLoginDevice(payload)
      this.user = data.user
      this.token = data.token
      localStorage.setItem('token', this.token)
      localStorage.setItem('user', JSON.stringify(this.user))
    },

    async register(userData) {
      const data = await authService.register(userData)
      return data
    },

    async verifyOtp(otpData) {
      const data = await authService.verifyOtp(otpData)
      this.token = data.token
      this.user = data.user
      localStorage.setItem('token', this.token)
      localStorage.setItem('user', JSON.stringify(this.user))
    },

    logout() {
      this.user = null
      this.token = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
    },
  },
})
