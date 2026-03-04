/* API dành cho admin: quản lý users, classes, subjects, exam types.
 * Mỗi hàm nhận authHeaders = { Authorization*: 'Bearer ...' } từ auth store.
 */
import { request } from './request.js'

const BASE = '/api'

/********** USERS **********/

/**
 * GET /api/users — danh sách user (có phân trang).
 * @param {object} params - { page, limit, search, role }
 * @param {object} authHeaders - { Authorization: 'Bearer <token>' }
 */
export async function getUsers(params = {}, authHeaders = {}) {
    const q = new URLSearchParams()
    for (const [key, value] of Object.entries(params)) {
        q.set(key, value)
    }
    return request(`${BASE}/users?${q.toString()}`, {
        headers: authHeaders
    })
}

export async function createUser(userData, authHeaders = {}) {
    return request(`${BASE}/users`, {
        method: 'POST',
        headers: authHeaders,
        body: JSON.stringify(userData)
    })
}

export async function updateUser(id, userData, authHeaders = {}) {
    return request(`${BASE}/users/${id}`, {
        method: 'PUT',
        headers: authHeaders,
        body: JSON.stringify(userData)
    })
}

export async function deleteUser(id, authHeaders = {}) {
    return request(`${BASE}/users/${id}`, {
        method: 'DELETE',
        headers: authHeaders
    })
}

/********** EXAMS **********/

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

export async function deleteExam(id, authHeaders = {}) {
    return request(`${BASE}/exams/${id}`, {
        method: 'DELETE',
        headers: authHeaders,
    })
}

/* QUESSTIONS */

export async function getQuestions(params = {}, authHeaders = {}) {
    const q = new URLSearchParams()
    if (params.page != null) q.set('page', String(params.page))
    if (params.limit != null) q.set('limit', String(params.limit))
    if (params.search) q.set('search', params.search)
    if (params.class) q.set('class', params.class)
    if (params.subject) q.set('subject', params.subject)
    if (params.type) q.set('type', params.type)
    const query = q.toString()
    return request(`${BASE}/questions${query ? `?${query}` : ''}`, {
        method: 'GET',
        headers: authHeaders,
    })
}

export async function deleteQuestion(id, authHeaders = {}) {
    return request(`${BASE}/questions/${id}`, {
        method: 'DELETE',
        headers: authHeaders,
    })
}

/********** OTHER ADMIN API (e.g. notifications, reports) **********/
export async function getNotifications(params = {}, authHeaders = {}) {
    const q = new URLSearchParams()
    if (params.type != null) q.set('type', String(params.type))
    if (params.isRead != null) q.set('isRead', String(params.is_read))
    const query = q.toString()
    return request(`${BASE}/notifications${query ? `?${query}` : ''}`, {
        method: 'GET',
        headers: authHeaders,
    })
}