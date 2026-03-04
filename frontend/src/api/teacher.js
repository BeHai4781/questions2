/**
 * API dành cho teacher: quản lý đề thi, câu hỏi trong ngân hàng.
 * Mỗi hàm nhận authHeaders = { Authorization: 'Bearer ...' } từ auth store.
 */

import { request } from './request.js'

const BASE = '/api'

// ─────────────────────────────────────────────
// EXAMS
// ─────────────────────────────────────────────

/**
 * GET /api/exams — danh sách đề thi của teacher (backend tự lọc theo createdBy).
 * @param {object} params - { page, limit, search, type }
 * @param {object} authHeaders
 */
export async function getExams(params = {}, authHeaders = {}) {
  const q = new URLSearchParams()
  if (params.page != null) q.set('page', String(params.page))
  if (params.limit != null) q.set('limit', String(params.limit))
  if (params.search) q.set('search', params.search)
  if (params.type) q.set('type', params.type)
  const query = q.toString()
  return request(`${BASE}/exams${query ? `?${query}` : ''}`, {
    method: 'GET',
    headers: authHeaders,
  })
}

/**
 * GET /api/exams/:id — chi tiết đề thi kèm danh sách câu hỏi.
 */
export async function getExamById(id, authHeaders = {}) {
  return request(`${BASE}/exams/${id}`, {
    method: 'GET',
    headers: authHeaders,
  })
}

/**
 * POST /api/exams — tạo đề thi mới.
 * @param {object} body - { title, description, class_id, type_id, duration, ... }
 */
export async function createExam(body, authHeaders = {}) {
  return request(`${BASE}/exams`, {
    method: 'POST',
    headers: authHeaders,
    body: JSON.stringify(body),
  })
}

/**
 * PUT /api/exams/:id — cập nhật đề thi.
 */
export async function updateExam(id, body, authHeaders = {}) {
  return request(`${BASE}/exams/${id}`, {
    method: 'PUT',
    headers: authHeaders,
    body: JSON.stringify(body),
  })
}

/**
 * DELETE /api/exams/:id — xóa đề thi.
 */
export async function deleteExam(id, authHeaders = {}) {
  return request(`${BASE}/exams/${id}`, {
    method: 'DELETE',
    headers: authHeaders,
  })
}

/**
 * GET /api/question-bank — danh sách câu hỏi trong ngân hàng 
 * @param {object} params - { page, limit, search, classId, difficulty }
 */
export async function getBankQuestions(params = {}, authHeaders = {}) {
  const q = new URLSearchParams()
  if (params.page != null) q.set('page', String(params.page))
  if (params.limit != null) q.set('limit', String(params.limit))
  if (params.search) q.set('search', params.search)
  if (params.classId) q.set('classId', String(params.classId))
  if (params.difficulty) q.set('difficulty', String(params.difficulty))
  const query = q.toString()
  return request(`${BASE}/question-bank${query ? `?${query}` : ''}`, {
    method: 'GET',
    headers: authHeaders,
  })
}

/**
 * GET /api/question-bank/:id — chi tiết câu hỏi ngân hàng.
 */
export async function getBankQuestionById(id, authHeaders = {}) {
  return request(`${BASE}/question-bank/${id}`, {
    method: 'GET',
    headers: authHeaders,
  })
}

/**
 * POST /api/question-bank — tạo câu hỏi mới trong ngân hàng.
 * @param {object} body - { content, class_id, level_id, image, answers: [...] }
 */
export async function createBankQuestion(body, authHeaders = {}) {
  return request(`${BASE}/question-bank`, {
    method: 'POST',
    headers: authHeaders,
    body: JSON.stringify(body),
  })
}

/**
 * PUT /api/question-bank/:id — cập nhật câu hỏi ngân hàng.
 */
export async function updateBankQuestion(id, body, authHeaders = {}) {
  return request(`${BASE}/question-bank/${id}`, {
    method: 'PUT',
    headers: authHeaders,
    body: JSON.stringify(body),
  })
}

/**
 * DELETE /api/question-bank/:id — xóa câu hỏi ngân hàng.
 */
export async function deleteBankQuestion(id, authHeaders = {}) {
  return request(`${BASE}/question-bank/${id}`, {
    method: 'DELETE',
    headers: authHeaders,
  })
}


/**
 * GET /api/questions?examId=:examId — câu hỏi của một đề thi.
 */
export async function getQuestionsByExam(examId, authHeaders = {}) {
  return request(`${BASE}/questions?examId=${examId}&limit=100`, {
    method: 'GET',
    headers: authHeaders,
  })
}

/**
 * POST /api/questions — tạo câu hỏi cho đề thi.
 */
export async function createQuestion(body, authHeaders = {}) {
  return request(`${BASE}/questions`, {
    method: 'POST',
    headers: authHeaders,
    body: JSON.stringify(body),
  })
}

/**
 * PUT /api/questions/:id — cập nhật câu hỏi đề thi.
 */
export async function updateQuestion(id, body, authHeaders = {}) {
  return request(`${BASE}/questions/${id}`, {
    method: 'PUT',
    headers: authHeaders,
    body: JSON.stringify(body),
  })
}

/**
 * DELETE /api/questions/:id — xóa câu hỏi khỏi đề thi.
 */
export async function deleteQuestion(id, authHeaders = {}) {
  return request(`${BASE}/questions/${id}`, {
    method: 'DELETE',
    headers: authHeaders,
  })
}

// ─────────────────────────────────────────────
// UPLOADS
// ─────────────────────────────────────────────

/**
 * POST /api/upload-exam — parse file .docx/.xlsx thành mảng câu hỏi.
 * Dùng FormData (không JSON), KHÔNG set Content-Type thủ công.
 * @param {File} file - file .docx hoặc .xlsx
 * @param {object} authHeaders - { Authorization: 'Bearer ...' }
 * @returns {{ data: { data: Array } }} mảng câu hỏi đọc được
 */
export async function uploadExamFile(file, authHeaders = {}) {
  const fd = new FormData()
  fd.append('uploadFile', file)
  // Không dùng request() vì request() set Content-Type: application/json
  const res = await fetch(`${BASE}/upload-exam`, {
    method: 'POST',
    headers: authHeaders,   // chỉ Authorization, KHÔNG set Content-Type
    body: fd,
  })
  return res.json()
}

/**
 * POST /api/upload-image — upload ảnh câu hỏi, trả về URL.
 * @param {File} file - file ảnh (jpg, png, webp, ...)
 * @param {object} authHeaders
 * @returns {{ data: { url: string } }}
 */
export async function uploadQuestionImage(file, authHeaders = {}) {
  const fd = new FormData()
  fd.append('image', file)
  const res = await fetch(`${BASE}/upload-image`, {
    method: 'POST',
    headers: authHeaders,
    body: fd,
  })
  return res.json()
}