import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '../views/LoginView.vue'
import api from '../api/client'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'login',
      component: LoginView,
      meta: { requiresAuth: false },
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('../views/RegisterView.vue'),
      meta: { requiresAuth: false },
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: () => import('../views/DashboardView.vue'),
      meta: { requiresAuth: true, role: 'student' },
    },
    {
      path: '/faculty',
      name: 'faculty',
      component: () => import('../views/FacultyDashboardView.vue'),
      meta: { requiresAuth: true, role: 'faculty' },
    },
    {
      path: '/admin',
      name: 'admin',
      component: () => import('../views/AdminDashboardView.vue'),
      meta: { requiresAuth: true, role: 'admin' },
    },
    {
      path: '/major-professor',
      name: 'major-professor',
      component: () => import('../views/MajorProfessorView.vue'),
      meta: { requiresAuth: true, role: 'student' },
    },
    {
      path: '/my-courses',
      name: 'my-courses',
      component: () => import('../views/MyCoursesView.vue'),
      meta: { requiresAuth: true, role: 'student' },
    },
    {
      path: '/documents',
      name: 'documents',
      component: () => import('../views/DocumentsView.vue'),
      meta: { requiresAuth: true, role: 'student' },
    },
    {
      path: '/assignments',
      name: 'assignments',
      component: () => import('../views/AssignmentsView.vue'),
      meta: { requiresAuth: true, role: 'student' },
    },
    {
      path: '/thesis-project',
      name: 'thesis-project',
      component: () => import('../views/ThesisProjectView.vue'),
      meta: { requiresAuth: true, role: 'student' },
    },
  ],
})

router.beforeEach(async (to) => {
  if (to.meta?.requiresAuth === false) return true
  if (!to.meta?.requiresAuth) return true

  try {
    const res = await api.get('me.php')
    if (res.data?.status !== 'success') throw new Error('not authed')

    const user = res.data.user
    localStorage.setItem('user', JSON.stringify(user))

    if (to.meta.role && user.role !== to.meta.role) {
      if (user.role === 'faculty') return { path: '/faculty' }
      if (user.role === 'admin') return { path: '/admin' }
      return { path: '/dashboard' }
    }

    return true
  } catch {
    localStorage.removeItem('user')
    return { path: '/' }
  }
})

export default router
