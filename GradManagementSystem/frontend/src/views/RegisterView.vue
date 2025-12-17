<template>
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <h1>Create Account</h1>
        <p>Register as Student or Faculty</p>
      </div>

      <form @submit.prevent="handleRegister">
        <div class="form-group">
          <label>Role</label>
          <select v-model="role" required>
            <option value="student">Student</option>
            <option value="faculty">Faculty (GPD/Major Professor)</option>
          </select>
        </div>

        <div class="form-group">
          <label>Major / Program</label>
          <select v-model="majorCode" required>
            <option v-for="m in majors" :key="m.major_code" :value="m.major_code">
              {{ m.major_name }}
            </option>
          </select>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Entry Year</label>
            <select v-model="entryYear">
              <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
            </select>
          </div>
          <div class="form-group">
            <label>Entry Date</label>
            <DatePicker v-model="entryDate" locale="en-US" />
          </div>
        </div>

        <div class="form-row" v-if="role === 'student'">
          <div class="form-group">
            <label>First Name</label>
            <input v-model="firstName" type="text" placeholder="First name" />
          </div>
          <div class="form-group">
            <label>Last Name</label>
            <input v-model="lastName" type="text" placeholder="Last name" />
          </div>
        </div>

        <div class="form-group">
          <label>Entry Term Code (auto)</label>
          <input v-model="termCode" type="text" disabled />
          <div class="hint">Derived from Entry Date. Used to tag your first-semester Hold.</div>
        </div>

        <div class="form-group">
          <label>Username</label>
          <input v-model="username" type="text" placeholder="e.g. student1 / prof_jamil" required />
        </div>

        <div class="form-group">
          <label>Email</label>
          <input v-model="email" type="email" placeholder="Optional" />
        </div>

        <div class="form-group">
          <label>Password</label>
          <input v-model="password" type="password" placeholder="At least 6 characters" required />
        </div>

        <div class="form-group">
          <label>Confirm Password</label>
          <input v-model="confirmPassword" type="password" placeholder="Re-enter password" required />
        </div>

        <div v-if="errorMessage" class="error-msg">
          {{ errorMessage }}
        </div>
        <div v-if="successMessage" class="success-msg">
          {{ successMessage }}
        </div>

        <button type="submit" class="btn-primary" :disabled="isLoading">
          {{ isLoading ? 'Creating...' : 'Create account' }}
        </button>

        <button type="button" class="btn-secondary" @click="router.push('/')">Back to login</button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api/client'
import DatePicker from '../components/DatePicker.vue'

const router = useRouter()

const role = ref('student')
const firstName = ref('')
const lastName = ref('')
const termCode = ref('')
const entryDate = ref('')
const entryYear = ref(new Date().getFullYear())
const yearOptions = ref([])
const username = ref('')
const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const majors = ref([{ major_code: 'CS', major_name: 'Computer Science' }])
const majorCode = ref('CS')

const errorMessage = ref('')
const successMessage = ref('')
const isLoading = ref(false)

const termCodeFromDate = (dateStr) => {
  const d = new Date(dateStr + 'T00:00:00')
  if (isNaN(d.getTime())) return ''
  const year = d.getFullYear()
  const month = d.getMonth() + 1
  if (month <= 4) return `${year}SP`
  if (month <= 8) return `${year}SU`
  return `${year}FA`
}

const initYears = () => {
  const y = new Date().getFullYear()
  yearOptions.value = []
  for (let i = y - 5; i <= y + 5; i++) yearOptions.value.push(i)
}
initYears()

const todayISO = () => new Date().toISOString().slice(0, 10)
entryDate.value = todayISO()
termCode.value = termCodeFromDate(entryDate.value)

const syncYearToDate = () => {
  if (!entryDate.value) return
  const parts = entryDate.value.split('-')
  if (parts.length !== 3) return
  entryDate.value = `${entryYear.value}-${parts[1]}-${parts[2]}`
  termCode.value = termCodeFromDate(entryDate.value)
}

const syncDateToYearAndTerm = () => {
  if (!entryDate.value) return
  const d = new Date(entryDate.value + 'T00:00:00')
  if (isNaN(d.getTime())) return
  entryYear.value = d.getFullYear()
  termCode.value = termCodeFromDate(entryDate.value)
}

// Keep term code in sync
watch(entryYear, syncYearToDate)
watch(entryDate, syncDateToYearAndTerm)

const fetchMajors = async () => {
  try {
    const res = await api.get('majors_list.php')
    if (res.data?.status === 'success' && Array.isArray(res.data.data) && res.data.data.length > 0) {
      majors.value = res.data.data
      if (!majors.value.some((m) => m.major_code === majorCode.value)) {
        majorCode.value = majors.value[0].major_code
      }
    }
  } catch {
    // keep fallback
  }
}

onMounted(fetchMajors)

const handleRegister = async () => {
  errorMessage.value = ''
  successMessage.value = ''
  if (password.value !== confirmPassword.value) {
    errorMessage.value = 'Passwords do not match.'
    return
  }

  isLoading.value = true
  try {
    const payload = {
      role: role.value,
      username: username.value,
      email: email.value,
      password: password.value,
      first_name: firstName.value,
      last_name: lastName.value,
      term_code: termCode.value,
      entry_date: entryDate.value,
      major_code: majorCode.value,
    }

    const res = await api.post('register.php', payload)
    if (res.data?.status === 'success') {
      localStorage.setItem('user', JSON.stringify(res.data.user))
      successMessage.value = 'Registration successful. Redirecting...'
      setTimeout(() => {
        router.push(res.data.user.role === 'faculty' ? '/faculty' : res.data.user.role === 'admin' ? '/admin' : '/dashboard')
      }, 1500)
    } else {
      errorMessage.value = res.data?.message || 'Registration failed.'
    }
  } catch (e) {
    const serverMessage = e?.response?.data?.message
    errorMessage.value = serverMessage || 'Registration failed (network/CORS/DB).'
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
  max-width: 520px;
  border-top: 5px solid #003366;
}

.login-header {
  text-align: center;
  margin-bottom: 20px;
}

.login-header h1 {
  color: #003366;
  margin: 0;
  font-size: 26px;
}

.login-header p {
  color: #666;
  margin: 6px 0 0;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.form-group {
  margin-bottom: 14px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  color: #333;
  font-weight: 500;
}

.success-msg {
  background: #e8f7ee;
  border: 1px solid #b6e2c3;
  color: #1b7a3a;
  padding: 12px 14px;
  border-radius: 8px;
  margin: 12px 0;
  text-align: center;
}

input,
select {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
  box-sizing: border-box;
}

input:focus,
select:focus {
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
  margin-bottom: 12px;
  font-size: 14px;
  text-align: center;
  border: 1px solid #ffc9c9;
}

.hint {
  margin-top: 6px;
  font-size: 12px;
  color: #666;
}
</style>
