<template>
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <h1>Grad System</h1>
        <p>Student & Faculty Admission Portal</p>
      </div>

      <form @submit.prevent="handleLogin">
        <div class="form-group">
          <label>Username / ID</label>
          <input
            v-model="username"
            type="text"
            placeholder="Enter your ID (e.g. student1 or prof_jamil)"
            required
          />
        </div>

        <div class="form-group">
          <label>Password</label>
          <input v-model="password" type="password" placeholder="Enter your password" required />
        </div>

        <div v-if="errorMessage" class="error-msg">
          {{ errorMessage }}
        </div>

        <button type="submit" class="btn-primary" :disabled="isLoading">
          {{ isLoading ? 'Logging in...' : 'Login' }}
        </button>

        <button type="button" class="btn-secondary" @click="router.push('/register')">Create account</button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api/client'

const router = useRouter()

const username = ref('student1')
const password = ref('123456')
const errorMessage = ref('')
const isLoading = ref(false)

const handleLogin = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const response = await api.post('login.php', {
      username: username.value,
      password: password.value,
    })

    const data = response.data

    if (data.status === 'success') {
      localStorage.setItem('user', JSON.stringify(data.user))
      router.push(data.user.role === 'faculty' ? '/faculty' : data.user.role === 'admin' ? '/admin' : '/dashboard')
    } else {
      errorMessage.value = data.message || 'Login failed'
    }
  } catch (error) {
    console.error(error)
    const serverMessage = error?.response?.data?.message
    if (serverMessage) {
      errorMessage.value = serverMessage
    } else {
      errorMessage.value = 'Login failed (network/CORS/DB). Check backend is running and DB_PASS/CORS settings.'
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 100vw;
  height: 100vh;
  background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%);
}

.login-card {
  background: white;
  padding: 40px;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 400px;
  border-top: 5px solid #003366;
}

.login-header {
  text-align: center;
  margin-bottom: 30px;
}

.login-header h1 {
  color: #003366;
  margin: 0;
  font-size: 28px;
}

.login-header p {
  color: #666;
  margin: 5px 0 0;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  color: #333;
  font-weight: 500;
}

input {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
  box-sizing: border-box;
}

input:focus {
  outline: none;
  border-color: #0056b3;
  box-shadow: 0 0 0 3px rgba(0, 86, 179, 0.1);
}

.btn-primary {
  background-color: #003366;
  color: white;
  border: none;
  padding: 12px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 16px;
  width: 100%;
  transition: background 0.3s;
}

.btn-primary:hover {
  background-color: #004494;
}

.btn-primary:disabled {
  background-color: #ccc;
  cursor: not-allowed;
}

.btn-secondary {
  margin-top: 10px;
  background: transparent;
  border: 1px solid #003366;
  color: #003366;
  padding: 10px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  width: 100%;
}

.error-msg {
  color: #d32f2f;
  background-color: #ffebee;
  padding: 10px;
  border-radius: 4px;
  margin-bottom: 15px;
  font-size: 14px;
  text-align: center;
  border: 1px solid #ffc9c9;
}
</style>
