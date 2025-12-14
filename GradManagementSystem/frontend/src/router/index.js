import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '../views/LoginView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'login',
      component: LoginView
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      // 懒加载：只有访问时才加载文件
      component: () => import('../views/DashboardView.vue')
    },
    {
      path: '/faculty',
      name: 'faculty',
      // 教师/管理员专用后台
      component: () => import('../views/FacultyDashboardView.vue')
    },
    {
      path: '/major-professor',
      name: 'major-professor',
      // 新增：选择导师页面
      component: () => import('../views/MajorProfessorView.vue')
    },
    
    {
      path: '/my-courses',
      name: 'my-courses',
      component: () => import('../views/MyCoursesView.vue')
    }
  ]
})

export default router