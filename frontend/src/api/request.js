/**
 * Helper gọi API: fetch + parse JSON, trả về dạng thống nhất.
 * Không gắn sẵn auth — caller truyền header Authorization (vd từ authStore.getAuthHeader()).
 *
 * @param {string} url - URL đầy đủ (vd /api/exams)
 * @param {RequestInit & { headers?: Record<string, string> }} options - method, body, headers...
 * @returns {Promise<{ ok: boolean, data?: any, pagination?: object, error?: { code: string, message: string }, status: number }>}
 */
export async function request(url, options = {}) {
  const { headers: customHeaders = {}, ...rest } = options
  const headers = {
    'Content-Type': 'application/json',
    ...customHeaders,
  }
  let status = 0
  try {
    const res = await fetch(url, {
      ...rest,
      headers,
      credentials: 'include',
    })
    status = res.status
    const data = await res.json().catch(() => null)
    if (!data) {
      return { ok: false, error: { code: 'PARSE_ERROR', message: 'Invalid response' }, status }
    }
    if (!res.ok) {
      return {
        ok: false,
        data: data?.data,
        error: data?.error || { code: 'ERROR', message: data?.message || 'Request failed' },
        status,
      }
    }
    return {
      ok: true,
      data: data?.data,
      pagination: data?.pagination,
      message: data?.message,
      status,
    }
  } catch (err) {
    return {
      ok: false,
      error: { code: 'NETWORK_ERROR', message: err?.message || 'Network error' },
      status,
    }
  }
}
