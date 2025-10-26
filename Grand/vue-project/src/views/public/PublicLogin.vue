<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router' // 导入路由

const username = ref('');
const password = ref('');
const loginError = ref('');
const router = useRouter(); // 获取路由实例

// 这是从 public.html 翻译并修改而来的
function handleLogin() {
  loginError.value = '';

  // 模拟学生登录
  if (username.value === 'student' && password.value === '123') {
    // window.location.href = 'student.html'; <-- 不再使用
    router.push('/student/dashboard'); // 使用路由跳转
  } 
  // 模拟导师登录
  else if (username.value === 'faculty' && password.value === '123') {
    // window.location.href = 'faculty_portal.html'; <-- 不再使用
    router.push('/faculty/dashboard');
  } 
  // 模拟管理员登录
  else if (username.value === 'admin' && password.value === '123') {
    // window.location.href = 'admin.html'; <-- 不再使用
    router.push('/admin/dashboard');
  } 
  // 登录失败
  else {
    loginError.value = 'Invalid username or password';
    username.value = '';
    password.value = '';
  }
}
</script>

<template>
  <div class="page">
    <h1>Login</h1>
    <div class="card">
      <h3>System Login</h3>
      <p v-if="loginError" style="color: #e74c3c; text-align: center; margin-bottom: 15px;">
        {{ loginError }}
      </p>
      <form @submit.prevent="handleLogin">
        <div class="form-group">
          <label for="username">Username:</label>
          <input type="text" id="username" placeholder="Student ID / Faculty ID" v-model="username">
        </div>
        <div class="form-group">
          <label for="password">Password:</label>
          <input type="password" id="password" v-model="password">
        </div>
        <button type="submit" class="btn btn-submit">Login</button>
      </form>
    </div>
  </div>
</template>