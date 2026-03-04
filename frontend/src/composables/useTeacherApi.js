/**
 * Composable dùng API teacher: tự gắn token từ auth store.
 * Dùng trong component (setup) hoặc composable sau khi app đã mount.
 *
 * Ví dụ:
 *   const { getExams, deleteExam, getBankQuestions, uploadExamFile, uploadQuestionImage } = useTeacherApi()
 *   const { ok, data } = await getExams({ page: 1, limit: 100 })
 */

import { useAuthStore } from '@/stores/auth'
import * as teacherApi from '@/api/teacher.js'

export function useTeacherApi() {
  const authStore = useAuthStore()
  const h = () => authStore.getAuthHeader()   // { Authorization: 'Bearer ...' }

  return {
    // ── Exams ──────────────────────────────────────────────
    getExams:    (params)      => teacherApi.getExams(params, h()),
    getExamById: (id)          => teacherApi.getExamById(id, h()),
    createExam:  (body)        => teacherApi.createExam(body, h()),
    updateExam:  (id, body)    => teacherApi.updateExam(id, body, h()),
    deleteExam:  (id)          => teacherApi.deleteExam(id, h()),

    // ── Question Bank ──────────────────────────────────────
    getBankQuestions:    (params)   => teacherApi.getBankQuestions(params, h()),
    getBankQuestionById: (id)       => teacherApi.getBankQuestionById(id, h()),
    createBankQuestion:  (body)     => teacherApi.createBankQuestion(body, h()),
    updateBankQuestion:  (id, body) => teacherApi.updateBankQuestion(id, body, h()),
    deleteBankQuestion:  (id)       => teacherApi.deleteBankQuestion(id, h()),

    // ── Questions (thuộc đề thi) ───────────────────────────
    getQuestionsByExam: (examId)    => teacherApi.getQuestionsByExam(examId, h()),
    createQuestion:     (body)      => teacherApi.createQuestion(body, h()),
    updateQuestion:     (id, body)  => teacherApi.updateQuestion(id, body, h()),
    deleteQuestion:     (id)        => teacherApi.deleteQuestion(id, h()),

    // ── Uploads ────────────────────────────────────────────
    /**
     * Upload file .docx/.xlsx → trả về mảng câu hỏi đọc được.
     * @param {File} file
     */
    uploadExamFile:      (file)     => teacherApi.uploadExamFile(file, h()),

    /**
     * Upload ảnh câu hỏi → trả về { url: '/uploads/questions/...' }.
     * @param {File} file
     */
    uploadQuestionImage: (file)     => teacherApi.uploadQuestionImage(file, h()),

    // ── Exam Attempts ──────────────────────────────────────────
    getExamAttempts: (params)      => teacherApi.getExamAttempts(params, h()),
  }
}