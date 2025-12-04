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
  ],
})

export default router
