import { useAuthStore } from '@/stores/auth'
import * as adminApi from '@/api/admin.js'

export function useAdminApi() {
  const authStore = useAuthStore()
  const authHeaders = () => authStore.getAuthHeader()

  return {
    // Users
    getUsers: () => adminApi.getUsers({}, authHeaders()),
    getUserById: (id) => adminApi.getUserById(id, authHeaders()),
    createUser: (body) => adminApi.createUser(body, authHeaders()),
    updateUser: (id, body) => adminApi.updateUser(id, body, authHeaders()),
    deleteUser: (id) => adminApi.deleteUser(id, authHeaders()),

    //Exam
    getExams: (params) => adminApi.getExams(params, authHeaders()),
    deleteExam: (id) => adminApi.deleteExam(id, authHeaders()),

    //Questions
    getQuestions: (params) => adminApi.getQuestions(params, authHeaders()),
    deleteQuestion: (id) => adminApi.deleteQuestion(id, authHeaders()),

    // Notifications
    getNotifications: (params) => adminApi.getNotifications(params, authHeaders()),
    markNotificationRead: (id) => adminApi.markNotificationRead(id, authHeaders()),
    markAllNotificationsRead: () => adminApi.markAllNotificationsRead(authHeaders()),
  }
}
