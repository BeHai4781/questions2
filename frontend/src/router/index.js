import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      component: () => import('../pages/LandingPage.vue'),
    },
    {
      path: '/login',
      component: () => import('../pages/LoginPage.vue'),
    },
    {
      path: '/register',
      component: () => import('../pages/RegisterPage.vue'),
    },
    {
      path: '/student/dashboard',
      component: () => import('../pages/students/StudentDashboardPage.vue'),
    },
    {
      path: '/student/history',
      component: () => import('../pages/students/HistoryPage.vue'),
    },
    {
      path: '/student/exam',
      component: () => import('../pages/students/ExamPage.vue'),
    },
    {
      path: '/teacher/dashboard',
      component: () => import('../pages/teachers/TeacherDashboardPage.vue'),
    },
    {
      path: '/teacher/exams',
      component: () => import('../pages/teachers/ExamsPage.vue'),
    },
    {
      path: '/teacher/questions',
      component: () => import('../pages/teachers/QuestionsPage.vue'),
    },
    {
      path: '/teacher/exam/:id/results',
      component: () => import('../pages/teachers/ExamResultsPage.vue'),
    },
    {
      path: '/admin/dashboard',
      component: () => import('../pages/admins/AdminDashboardPage.vue'),
    },
    {
      path: '/admin/users',
      component: () => import('../pages/admins/UserManagePage.vue'),
    },
    {
      path: '/admin/exams',
      component: () => import('../pages/admins/ExamManagePage.vue'),
    },
    {
      path: '/admin/questions',
      component: () => import('../pages/admins/QuestionManagePage.vue'),
    },
    {
      path: '/admin/notifications',
      component: () => import('../pages/admins/NotificationPage.vue'),
    }
  ],
})

export default router
