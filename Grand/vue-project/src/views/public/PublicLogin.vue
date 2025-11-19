<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const username = ref('');
const password = ref('');
const loginError = ref('');
const router = useRouter();

async function handleLogin() {
  loginError.value = '';

  try {
    // 1. 发送登录请求到后端
    const response = await axios.post(
      'http://127.0.0.1:8000/api/admin/login/',
      {
        user_id: username.value,   // 必须与后端字段一致
        password: password.value
      }
    );

    // 2. 如果成功，后端会返回 identity
    const identity = response.data.identity;

    // 根据身份跳转
    if (identity === 'Student') {
      router.push('/student/dashboard');
    } else if (identity === 'Faculty') {
      router.push('/faculty/dashboard');
    } else if (identity === 'Admin') {
      router.push('/admin/dashboard');
    } else if (identity === 'Admin') {
      router.push('/admin/dashboard');
    }
     else {
      loginError.value = `Unknown identity: ${identity}`;
    }
  } catch (error) {
    // 3. 后端错误处理
    if (error.response && error.response.data) {
      const err = error.response.data.error;

      if (typeof err === 'string') {
        loginError.value = err; // 例如 "User does not exist"
      }
      // serializer 错误格式：{ "error": { "non_field_errors": [...] } }
      else if (err?.non_field_errors) {
        loginError.value = err.non_field_errors[0];
      }
      else {
        loginError.value = 'Login failed. Please try again.';
      }
    } else {
      loginError.value = 'Network error. Please check server.';
    }

    // 清空输入框
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

      <!-- 错误提示 -->
      <p v-if="loginError" style="color: #e74c3c; text-align: center; margin-bottom: 15px;">
        {{ loginError }}
      </p>

      <form @submit.prevent="handleLogin">
        <div class="form-group">
          <label for="username">Username:</label>
          <input
            type="text"
            id="username"
            placeholder="Student ID / Faculty ID"
            v-model="username"
          />
        </div>

        <div class="form-group">
          <label for="password">Password:</label>
          <input
            type="password"
            id="password"
            v-model="password"
          />
        </div>

        <button type="submit" class="btn btn-submit">Login</button>
      </form>
    </div>
  </div>
</template>
