import { ref, computed } from 'vue'
import { defineStore } from 'pinia'

const TOKEN_KEY = 'auth_token'
const REFRESH_KEY = 'auth_refresh_token'
const USER_KEY = 'auth_user'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(null)
  const refreshToken = ref(null)
  const user = ref(null)

  const isAuthenticated = computed(() => Boolean(token.value && user.value))

  /** Lấy user từ localStorage (chạy khi khởi động app) */
  function initFromStorage() {
    const t = localStorage.getItem(TOKEN_KEY)
    const r = localStorage.getItem(REFRESH_KEY)
    const u = localStorage.getItem(USER_KEY)
    if (t) token.value = t
    if (r) refreshToken.value = r
    if (u) {
      try {
        user.value = JSON.parse(u)
      } catch {
        user.value = null
      }
    }
  }

  /** Lưu auth vào state + localStorage (sau khi login/refresh) */
  function setAuth(payload) {
    const { token: t, refreshToken: r, user: u } = payload
    token.value = t ?? null
    refreshToken.value = r ?? null
    user.value = u ?? null
    if (t) localStorage.setItem(TOKEN_KEY, t)
    else localStorage.removeItem(TOKEN_KEY)
    if (r) localStorage.setItem(REFRESH_KEY, r)
    else localStorage.removeItem(REFRESH_KEY)
    if (u) localStorage.setItem(USER_KEY, JSON.stringify(u))
    else localStorage.removeItem(USER_KEY)
  }

  /** Xóa đăng nhập */
  function logout() {
    token.value = null
    refreshToken.value = null
    user.value = null
    localStorage.removeItem(TOKEN_KEY)
    localStorage.removeItem(REFRESH_KEY)
    localStorage.removeItem(USER_KEY)
  }

  /** Trả về route dashboard theo role */
  function getDashboardRoute() {
    const role = user.value?.role
    if (role === 'admin') return '/admin/dashboard'
    if (role === 'teacher') return '/teacher/dashboard'
    if (role === 'student') return '/student/dashboard'
    return '/'
  }

  /** Gọi GET /api/auth/me để cập nhật thông tin user */
  async function fetchMe() {
    if (!token.value) return null
    const res = await fetch('/api/auth/me', {
      headers: {
        Authorization: `Bearer ${token.value}`,
      },
    })
    const data = await res.json().catch(() => null)
    if (!res.ok || !data?.success || !data?.data?.user) return null
    user.value = data.data.user
    localStorage.setItem(USER_KEY, JSON.stringify(user.value))
    return user.value
  }

  /** Gọi POST /api/auth/refresh để gia hạn token */
  async function refreshTokens() {
    if (!refreshToken.value) return false
    const res = await fetch('/api/auth/refresh', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ refreshToken: refreshToken.value }),
    })
    const data = await res.json().catch(() => null)
    if (!res.ok || !data?.success || !data?.data?.token) return false
    setAuth({
      token: data.data.token,
      refreshToken: data.data.refreshToken ?? refreshToken.value,
      user: user.value,
    })
    return true
  }

  /** Header Authorization để gắn vào request (hoặc null) */
  function getAuthHeader() {
    return token.value ? { Authorization: `Bearer ${token.value}` } : {}
  }

  return {
    token,
    refreshToken,
    user,
    isAuthenticated,
    initFromStorage,
    setAuth,
    logout,
    getDashboardRoute,
    fetchMe,
    refreshTokens,
    getAuthHeader,
  }
})
