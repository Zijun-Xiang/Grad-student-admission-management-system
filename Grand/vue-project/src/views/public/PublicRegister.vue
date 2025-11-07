<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from "axios"

const router = useRouter()

const username = ref('')
const password = ref('')
const confirmPassword = ref('')
const role = ref('student')   // 默认注册学生
const errorMsg = ref('')
const successMsg = ref('')


function handleRegister() {
  errorMsg.value = ''
  successMsg.value = ''

  if (!username.value || !password.value || !confirmPassword.value) {
    errorMsg.value = 'All fields are required.'
    return
  }

  if (password.value !== confirmPassword.value) {
    errorMsg.value = 'Passwords do not match.'
    return
  }

  axios.post('http://127.0.0.1:8000/api/admin/register/', {
    user_id: username.value,
    password: password.value
  })
  .then(res => {
    successMsg.value = "Registration successful! Redirecting..."
    setTimeout(() => {
      router.push('/public/login')
    }, 1000)
  })
  .catch(err => {
    errorMsg.value = err.response?.data?.error || "Registration failed."
  })
  // 模拟注册成功
  successMsg.value = 'Registration successful! Redirecting to login...'

  setTimeout(() => {
    router.push('/public/login')
  }, 1000)
}
</script>

<template>
  <div class="page">
    <h1>Register</h1>

    <div class="card">
      <h3>Create Account</h3>

      <!-- 成功提示 -->
      <p v-if="successMsg" style="color: #27ae60; text-align: center; margin-bottom: 15px;">
        {{ successMsg }}
      </p>

      <!-- 错误提示 -->
      <p v-if="errorMsg" style="color: #e74c3c; text-align: center; margin-bottom: 15px;">
        {{ errorMsg }}
      </p>

      <form @submit.prevent="handleRegister">
        
        <div class="form-group">
          <label for="username">Username / ID:</label>
          <input type="text" id="username" placeholder="Enter your ID" v-model="username">
        </div>

        <div class="form-group">
          <label for="password">Password:</label>
          <input type="password" id="password" v-model="password">
        </div>

        <div class="form-group">
          <label for="confirm-password">Confirm Password:</label>
          <input type="password" id="confirm-password" v-model="confirmPassword">
        </div>

        <button type="submit" class="btn btn-submit">Register</button>
      </form>

      <p style="margin-top: 10px; text-align: center;">
        Already have an account?
        <a href="/login">Login here</a>
      </p>
    </div>
  </div>
</template>
