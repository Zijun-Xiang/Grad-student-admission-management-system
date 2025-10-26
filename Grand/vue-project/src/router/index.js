import { createRouter, createWebHistory } from 'vue-router'

// 导入布局
import AdminLayout from '@/layouts/AdminLayout.vue'
import FacultyLayout from '@/layouts/FacultyLayout.vue'
import StudentLayout from '@/layouts/StudentLayout.vue'
import PublicLayout from '@/layouts/PublicLayout.vue'

// 懒加载所有视图 (注意看路径的变化)

// --- Admin ---
// --- Admin (导入所有 7 个视图) ---
const AdminDashboard = () => import('@/views/admin/AdminDashboard.vue')
const AdminUsers = () => import('@/views/admin/AdminUsers.vue')
const AdminWorkFlows = () => import('@/views/admin/AdminWorkFlows.vue')
const AdminMigration = () => import('@/views/admin/AdminMigration.vue')
const AdminReports = () => import('@/views/admin/AdminReports.vue')
const AdminCompliance = () => import('@/views/admin/AdminCompliance.vue')
const AdminSettings = () => import('@/views/admin/AdminSettings.vue')
// --- Faculty ---
const FacultyDashboard = () => import('@/views/faculty/FacultyDashboard.vue')
const FacultyAdviseeProgress = () => import('@/views/faculty/FacultyAdviseeProgress.vue')
const FacultyApprovalRequests = () => import('@/views/faculty/FacultyApprovalRequests.vue')
const FacultyEvaluationReports = () => import('@/views/faculty/FacultyEvaluationReports.vue')
const FacultyNotifications = () => import('@/views/faculty/FacultyNotifications.vue')
const FacultyProfileSettings = () => import('@/views/faculty/FacultyProfileSettings.vue')

// --- Student ---
const StudentDashboard = () => import('@/views/student/StudentDashboard.vue')
const StudentMilestoneTracker = () => import('@/views/student/StudentMilestoneTracker.vue')
const StudentProgramOfStudy = () => import('@/views/student/StudentProgramOfStudy.vue')
const StudentCommittee = () => import('@/views/student/StudentCommittee.vue')
const StudentDocuments = () => import('@/views/student/StudentDocuments.vue')
const StudentNotifications = () => import('@/views/student/StudentNotifications.vue')
const StudentProfile = () => import('@/views/student/StudentProfile.vue')

// --- Public ---
const PublicHome = () => import('@/views/public/PublicHome.vue')
const PublicLogin = () => import('@/views/public/PublicLogin.vue')
const PublicHelp = () => import('@/views/public/PublicHelp.vue')
const PublicContact = () => import('@/views/public/PublicContact.vue')


const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      // --- 公共路由 (登录页等) ---
      path: '/',
      component: PublicLayout,
      redirect: '/home', 
      children: [
        { path: 'home', component: PublicHome },
        { path: 'login', component: PublicLogin },
        { path: 'help', component: PublicHelp },
        { path: 'contact', component: PublicContact }
      ]
    },
    {
      // --- 管理员路由 ---
      path: '/admin',
      component: AdminLayout,
      redirect: '/admin/dashboard',
      children: [
        { path: 'dashboard', component: AdminDashboard },
        { path: 'users', component: AdminUsers },
        { path: 'workflows', component: AdminWorkFlows },
        { path: 'migration', component: AdminMigration },
        { path: 'reports', component: AdminReports },
        { path: 'compliance', component: AdminCompliance },
        { path: 'settings', component: AdminSettings }
      ]
    },
    {
      // --- 导师路由 ---
      path: '/faculty',
      component: FacultyLayout,
      redirect: '/faculty/dashboard',
      children: [
        { path: 'dashboard', component: FacultyDashboard },
        { path: 'progress', component: FacultyAdviseeProgress },
        { path: 'approvals', component: FacultyApprovalRequests },
        { path: 'evaluations', component: FacultyEvaluationReports },
        { path: 'notifications', component: FacultyNotifications },
        { path: 'settings', component: FacultyProfileSettings }
      ]
    },
    {
      // --- 学生路由 ---
      path: '/student',
      component: StudentLayout,
      redirect: '/student/dashboard',
      children: [
        { path: 'dashboard', component: StudentDashboard },
        { path: 'milestones', component: StudentMilestoneTracker },
        { path: 'program', component: StudentProgramOfStudy },
        { path: 'committee', component: StudentCommittee },
        { path: 'documents', component: StudentDocuments },
        { path: 'notifications', component: StudentNotifications },
        { path: 'profile', component: StudentProfile }
      ]
    }
  ]
})

export default router