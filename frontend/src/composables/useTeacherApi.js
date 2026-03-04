/**
 * Composable dùng API teacher: tự gắn token từ auth store.
 * Dùng trong component (setup) hoặc composable sau khi app đã mount.
 */

import { useAuthStore } from '@/stores/auth'
import * as teacherApi from '@/api/teacher.js'

export function useTeacherApi() {
  const authStore = useAuthStore()
  const authHeaders = () => authStore.getAuthHeader()

  return {
    // Exams
    getExams:    (params) => teacherApi.getExams(params, authHeaders()),
    getExamById: (id)     => teacherApi.getExamById(id, authHeaders()),
    createExam:  (body)   => teacherApi.createExam(body, authHeaders()),
    updateExam:  (id, body) => teacherApi.updateExam(id, body, authHeaders()),
    deleteExam:  (id)     => teacherApi.deleteExam(id, authHeaders()),

    // Question Bank (ngân hàng câu hỏi) 
    getBankQuestions:    (params)     => teacherApi.getBankQuestions(params, authHeaders()),
    getBankQuestionById: (id)         => teacherApi.getBankQuestionById(id, authHeaders()),
    createBankQuestion:  (body)       => teacherApi.createBankQuestion(body, authHeaders()),
    updateBankQuestion:  (id, body)   => teacherApi.updateBankQuestion(id, body, authHeaders()),
    deleteBankQuestion:  (id)         => teacherApi.deleteBankQuestion(id, authHeaders()),

    // Questions (câu hỏi thuộc đề thi) 
    getQuestionsByExam: (examId)      => teacherApi.getQuestionsByExam(examId, authHeaders()),
    createQuestion:     (body)        => teacherApi.createQuestion(body, authHeaders()),
    updateQuestion:     (id, body)    => teacherApi.updateQuestion(id, body, authHeaders()),
    deleteQuestion:     (id)          => teacherApi.deleteQuestion(id, authHeaders()),

    // ── Uploads ────────────────────────────────────────────
    /**
     * Upload file .docx/.xlsx → trả về mảng câu hỏi đọc được.
     * @param {File} file
     */
    uploadExamFile:      (file)     => teacherApi.uploadExamFile(file, authHeaders()),

    /**
     * Upload ảnh câu hỏi → trả về { url: '/uploads/questions/...' }.
     * @param {File} file
     */
    uploadQuestionImage: (file)     => teacherApi.uploadQuestionImage(file, authHeaders()),
  
  }
}