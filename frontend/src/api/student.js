/**
 * API dành cho student: danh sách đề thi, chi tiết đề, bài làm (attempts).
 * Mỗi hàm nhận thêm authHeaders = { Authorization: 'Bearer ...' } từ auth store.
 */

import { request } from './request.js'

const BASE = '/api'

/**
 * GET /api/exams — danh sách đề thi (có phân trang).
 * Student thấy tất cả đề (backend lọc theo role).
 * @param {object} params - { page, limit, search, class, type, status }
 * @param {object} authHeaders - { Authorization: 'Bearer <token>' }
 */
export async function getExams(params = {}, authHeaders = {}) {
  const q = new URLSearchParams()
  if (params.page != null) q.set('page', String(params.page))
  if (params.limit != null) q.set('limit', String(params.limit))
  if (params.search) q.set('search', params.search)
  if (params.class) q.set('class', params.class)
  if (params.type) q.set('type', params.type)
  if (params.status) q.set('status', params.status)
  const query = q.toString()
  return request(`${BASE}/exams${query ? `?${query}` : ''}`, {
    method: 'GET',
    headers: authHeaders,
  })
}

/**
 * GET /api/exams/:id — chi tiết một đề thi.
 */
export async function getExamById(id, authHeaders = {}) {
  return request(`${BASE}/exams/${id}`, {
    method: 'GET',
    headers: authHeaders,
  })
}

/**
 * GET /api/exam-attempts — danh sách bài làm của student (backend tự lọc theo userId).
 * @param {object} params - { page, limit, examId, status }
 */
export async function getAttempts(params = {}, authHeaders = {}) {
  const q = new URLSearchParams()
  if (params.page != null) q.set('page', String(params.page))
  if (params.limit != null) q.set('limit', String(params.limit))
  if (params.examId != null) q.set('examId', String(params.examId))
  if (params.status) q.set('status', params.status)
  const query = q.toString()
  return request(`${BASE}/exam-attempts${query ? `?${query}` : ''}`, {
    method: 'GET',
    headers: authHeaders,
  })
}

/**
 * GET /api/exam-attempts/:id — chi tiết một bài làm.
 */
export async function getAttemptById(id, authHeaders = {}) {
  return request(`${BASE}/exam-attempts/${id}`, {
    method: 'GET',
    headers: authHeaders,
  })
}

/**
 * POST /api/exam-attempts — bắt đầu làm bài (tạo attempt).
 * Backend tự gán userId cho student.
 * @param {object} body - { examId, status?, answers?, score? }
 */
export async function createAttempt(body, authHeaders = {}) {
  return request(`${BASE}/exam-attempts`, {
    method: 'POST',
    headers: authHeaders,
    body: JSON.stringify(body),
  })
}

/**
 * PUT /api/exam-attempts/:id — cập nhật bài làm (nộp bài / cập nhật điểm).
 * @param {string} id - attempt id
 * @param {object} body - { status?, answers?, score? }
 */
export async function updateAttempt(id, body, authHeaders = {}) {
  return request(`${BASE}/exam-attempts/${id}`, {
    method: 'PUT',
    headers: authHeaders,
    body: JSON.stringify(body),
  })
}
