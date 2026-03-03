/**
 * Composable dùng API student: tự gắn token từ auth store.
 * Chỉ dùng trong component (setup) hoặc trong composable khác sau khi app đã mount.
 *
 * Ví dụ:
 *   const { getExams, getExamById, getAttempts, createAttempt, updateAttempt } = useStudentApi()
 *   const { ok, data, pagination } = await getExams({ page: 1, limit: 10 })
 *   if (ok) latestExams = data  // data là mảng exam
 */

import { useAuthStore } from '@/stores/auth'
import * as studentApi from '@/api/student.js'

export function useStudentApi() {
  const authStore = useAuthStore()
  const authHeaders = () => authStore.getAuthHeader()

  return {
    getExams: (params) => studentApi.getExams(params, authHeaders()),
    getExamById: (id) => studentApi.getExamById(id, authHeaders()),
    getAttempts: (params) => studentApi.getAttempts(params, authHeaders()),
    getAttemptById: (id) => studentApi.getAttemptById(id, authHeaders()),
    createAttempt: (body) => studentApi.createAttempt(body, authHeaders()),
    updateAttempt: (id, body) => studentApi.updateAttempt(id, body, authHeaders()),
  }
}
