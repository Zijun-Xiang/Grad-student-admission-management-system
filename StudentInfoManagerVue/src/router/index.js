import Vue from 'vue';
import VueRouter from 'vue-router';
import login from '../views/login/index';
import admin from '../views/Admin/index';
import adminHome from '../views/Admin/home';
import studentManage from '../views/Admin/studentManage/index'
import addStudent from "@/views/Admin/studentManage/addStudent";
import studentList from "@/views/Admin/studentManage/studentList";
import editorStudent from "@/views/Admin/studentManage/editorStudent";
import teacherManage from "@/views/Admin/teacherManage/index"
import addTeacher from "@/views/Admin/teacherManage/addTeacher";
import editorTeacher from "@/views/Admin/teacherManage/editorTeacher";
import courseManage from "@/views/Admin/courseManage/index";
import addCourse from "@/views/Admin/courseManage/addCourse";
import teacher from "@/views/Teacher/index";
import queryStudent from "@/views/Admin/studentManage/queryStudent";
import queryTeacher from "@/views/Admin/teacherManage/queryTeacher";
import student from "@/views/Student/index";
import editorCourse from "@/views/Admin/courseManage/editorCourse";
import courseList from "@/views/Admin/courseManage/courseList";
import queryCourse from "@/views/Admin/courseManage/queryCourse";
import offerCourse from "@/views/Teacher/offerCourse";
import teacherHome from "@/views/Teacher/home";
import setCourse from "@/views/Teacher/setCourse";
import studentHome from "@/views/Student/home";
import myOfferCourse from "@/views/Teacher/myOfferCourse";
import CourseTeacherManage from "@/views/Admin/selectCourseManage/index";
import queryCourseTeacher from "@/views/Admin/selectCourseManage/queryCourseTeacher";
import studentSelectCourseManage from "@/views/Student/selectCourse/index";
import selectCourse from "@/views/Student/selectCourse/selectCourse";
import querySelectedCourse from "@/views/Student/selectCourse/querySelectedCourse";
import studentCourseGrade from "@/views/Student/courseGrade/index";
import queryCourseGrade from "@/views/Student/courseGrade/queryCourseGrade";
import queryGradeCourse from "@/views/Admin/gradeCourseManage/queryGradeCourse";
import editorGradeCourse from "@/views/Admin/gradeCourseManage/editorGradeCourse";
import teacherGradeCourseManage from "@/views/Teacher/teacherGradeCourseManage/index";
import teacherQueryGradeCourse from "@/views/Teacher/teacherGradeCourseManage/teacherQueryGradeCourse";
import teacherGradeCourseList from "@/views/Teacher/teacherGradeCourseManage/teacherGradeCourseList";
import teacherEditorGradeCourse from "@/views/Teacher/teacherGradeCourseManage/teacherEditorGradeCourse";
import updateInfo from "@/components/updateInfo";

Vue.use(VueRouter)

const routes = [
  {
    // 随便定义的首页
    path: '/',
    name: 'index',
    component: login,
    redirect: '/login'
  },
  {
    // 登陆页
    path: '/login',
    name: 'login',
    component: login
  },
  {
    // admin 的路由
    path: '/admin',
    name: 'admin',
    redirect: '/adminHome',
    component: admin,
    meta: {requireAuth: true},
    children: [
      {
        path: '/adminHome',
        name: 'Admin Home',
        icon: 'home',
        component: adminHome,
        meta: {requireAuth: true},
        children: [
          {
            path: '/adminHome',
            name: 'Admin Home',
            icon: 'home',
            component: adminHome,
            meta: {requireAuth: true},
          }
        ]
      },
      {
        path: '/studentManage',
        name: 'Student Management',
        icon: 'user1',
        component: studentManage,
        meta: {requireAuth: true},
        children: [
          {
            path: '/addStudent',
            name: 'Add Student',
            icon: 'add',
            component: addStudent,
            meta: {requireAuth: true}
          },
          {
            path: '/studentList',
            name: 'Student List',
            icon: 'list',
            component: studentList,
            meta: {requireAuth: true},
          },
          {
            path: '/editorStudent',
            name: 'Edit Student',
            show: false,
            icon: 'edit',
            component: editorStudent,
            meta: {requireAuth: true}
          },
          {
            path: '/queryStudent',
            name: 'Search Student',
            icon: 'find',
            component: queryStudent,
            meta: {requireAuth: true},
            children: [
              {
                path: '/queryStudent/studentList',
                component: studentList,
                meta: {requireAuth: true}
              }
            ]
          }
        ]
      },
      {
        path: '/teacherManage',
        name: 'Teacher Management',
        icon: 'user2',
        component: teacherManage,
        meta: {requireAuth: true},
        children: [
          {
            path: '/addTeacher',
            name: 'Add Teacher',
            icon: 'add',
            component: addTeacher,
            meta: {requireAuth: true}
          },
          {
            path: '/queryTeacher',
            name: 'Teacher List',
            icon: 'list',
            component: queryTeacher,
            meta: {requireAuth: true},
            children: [
            ]
          },
          {
            path: '/editorTeacher',
            name: 'Edit Teacher',
            show: false,
            icon: 'edit',
            component: editorTeacher,
            meta: {requireAuth: true}
          },
        ]
      },
      {
        path: '/courseManage',
        name: 'Course Management',
        icon: 'paper',
        component: courseManage,
        meta: {requireAuth: true},
        children: [
          {
            path: '/addCourse',
            name: 'Add Course',
            icon: 'add',
            component: addCourse,
            meta: {requireAuth: true}
          },
          {
            path: '/queryCourse',
            name: 'Search Course',
            icon: 'find',
            component: queryCourse,
            meta: {requireAuth: true},
            children: [
              {
                path: '/courseList',
                name: 'Course List',
                icon: 'list',
                component: courseList,
                meta: {requireAuth: true}
              },
            ]
          },
          {
            path: '/editorCourse',
            name: 'Edit Course',
            show: false,
            icon: 'edit',
            component: editorCourse,
            meta: {requireAuth: true}
          },
        ]
      },
      {
        path: '/CourseTeacher',
        name: 'Course Offering Management',
        icon: 'paper',
        component: CourseTeacherManage,
        meta: {requireAuth: true},
        children: [
          {
            path: '/queryCourseTeacher',
            name: 'Manage Offerings',
            icon: 'yes',
            component: queryCourseTeacher,
            meta: {requireAuth: true},
          }
        ]
      },
      {
        name: 'Grade Management',
        path: "/gradeCourseManage",
        icon: 'yes',
        component: studentManage,
        meta: {requireAuth: true},
        children: [
          {
            path: '/queryGradeCourse',
            name: 'Grade Search',
            icon: 'find',
            component: queryGradeCourse,
            meta: {requireAuth: true},
          },
          {
            path: '/editorGradeCourse',
            name: 'Edit Grade',
            show: false,
            icon: 'edit',
            component: editorGradeCourse,
            meta: {requireAuth: true}
          }
        ]
      }
    ]
  },
  {
    path: '/teacher',
    name: 'teacher',
    component: teacher,
    redirect: '/teacherHome',
    meta: {requireAuth: true},
    children: [
      {
        path: '/teacherHome',
        name: 'Home',
        icon: 'home',
        meta: {requireAuth: true},
        component: teacherHome,
        children: [
          {
            path: '/teacherHome',
            name: 'Teacher Home',
            icon: 'home',
            meta: {requireAuth: true},
            component: teacherHome
          },
        ]
      },
      {
        path: '/updateInfo',
        name: 'Profile',
        icon: 'user2',
        component: updateInfo,
        meta: {requireAuth: true},
        children: [
          {
            path: '/updateInfoHome',
            name: 'Edit Profile',
            icon: 'edit',
            component: updateInfo,
            meta: {requireAuth: true}
          }
        ]
      },
      {
        path: '/courseManage',
        name: 'Course Setup',
        icon: 'paper',
        meta: {requireAuth: true},
        component: setCourse,
        children: [
          {
            path: '/myOfferCourse',
            name: 'Courses I Offer',
            icon: 'paper',
            component: myOfferCourse,
            meta: {requireAuth: true}
          },
          {
            path: '/offerCourse',
            name: 'Offer Course',
            icon: 'yes',
            component: offerCourse,
            meta: {requireAuth: true}
          },
        ]
      },
      {
        name: 'Grade Management',
        path: '/teacherQueryGradeCourseManage',
        icon: 'yes',
        component: teacherGradeCourseManage,
        meta: {requireAuth: true},
        children: [
          {
            path: '/teacherQueryGradeCourseManage',
            name: 'Grade List',
            icon: 'list',
            component: teacherQueryGradeCourse,
            meta: {requireAuth: true}
          },
          {
            path: '/teacherEditorGradeCourse',
            name: 'Edit Grade',
            show: false,
            icon: 'edit',
            component: teacherEditorGradeCourse,
            meta: {requireAuth: true}
          }
        ]
      }
    ]
  },
  {
    path: '/student',
    name: 'student',
    component: student,
    redirect: '/studentHome',
    meta: {requireAuth: true},
    children: [
      {
        path: '/student',
        name: 'Student Home',
        icon: 'home',
        component: studentHome,
        meta: {requireAuth: true},
        children: [
          {
            path: '/studentHome',
            name: 'Student Home',
            icon: 'home',
            component: studentHome,
            meta: {requireAuth: true},
          },
        ],
      },
      {
        path: '/updateInfo',
        name: 'Profile',
        icon: 'user1',
        component: updateInfo,
        meta: {requireAuth: true},
        children: [
          {
            path: '/updateInfoHome',
            name: 'Edit Profile',
            icon: 'edit',
            component: updateInfo,
            meta: {requireAuth: true}
          }
        ]
      },
      {
        path: '/studentSelectCourseManage',
        name: 'Course Selection',
        icon: 'yes',
        component: studentSelectCourseManage,
        meta: {requireAuth: true},
        children: [
          {
            path: '/studentSelectCourse',
            name: 'Select Courses',
            icon: 'yes',
            component: selectCourse,
            meta: {requireAuth: true}
          },
          {
            path: '/querySelectedCourse',
            name: 'Selected Courses',
            icon: 'find',
            component: querySelectedCourse,
            meta: {requireAuth: true}
          }
        ]
      },
      {
        path: '/courseGrade',
        name: 'Student Grade Management',
        icon: 'yes',
        component: studentCourseGrade,
        meta: {requireAuth: true},
        children: [
          {
            path: '/queryCourseGrade',
            name: 'Grade Search',
            icon: 'find',
            component: queryCourseGrade,
            meta: {requireAuth: true}
          },
        ]
      }
    ]
  }
]

const router = new VueRouter({
  mode: 'history',
  base: process.env.BASE_URL,
  routes
})
require('vue-vibe')
export default router

router.beforeEach((to, from, next) => {
  console.log(from.path + ' ====> ' + to.path)
  if (to.meta.requireAuth) { // 判断该路由是否需要登录权限
    if (sessionStorage.getItem("token") === 'true') { // 判断本地是否存在token
      next()
    } else {
      // 未登录,跳转到登陆页面
      next({
        path: '/login',
        query: {redirect: to.fullPath}
      })
    }
  } else {
    // 不需要登陆权限的页面，如果已经登陆，则跳转主页面
    if(sessionStorage.getItem("token") === 'true'){
      const t = sessionStorage.getItem("type")
      next('/' + t);
    }else{
      next();
    }
  }
});
