<template>
  <div class="dashboard-layout">
    <header class="navbar">
      <div class="brand">Grad System</div>
      <div class="user-info">
        <span v-if="user.username">Welcome, {{ user.username }}</span>
        <button @click="logout" class="btn-logout">Logout</button>
      </div>
    </header>

    <div class="main-container">
      <aside class="sidebar">
        <nav>
          <ul>
            <li @click="router.push('/dashboard')">Dashboard</li>
            <li @click="router.push('/my-courses')">My Courses</li>
            <li @click="router.push('/documents')">Documents</li>
            <li @click="router.push('/assignments')">Assignments</li>
            <li class="active">Profile</li>
            <li v-if="term?.unlocks?.term2" @click="router.push('/major-professor')">Major Professor</li>
            <li v-if="term?.unlocks?.term3" @click="router.push('/thesis-project')">Thesis / Project</li>
          </ul>
        </nav>
      </aside>

      <main class="content">
        <div class="card">
          <h2>My Profile</h2>
          <div v-if="loading" class="loading-text">Loading...</div>

          <div v-else class="form-grid">
            <div v-if="msg" class="msg" :class="{ ok: ok, bad: !ok }">{{ msg }}</div>

            <div class="form-group">
              <label>Username</label>
              <input :value="profile.username" disabled />
            </div>
            <div class="form-group">
              <label>Role</label>
              <input :value="profile.role" disabled />
            </div>

            <div class="form-group" v-if="profile.role === 'student'">
              <label>First Name</label>
              <input v-model="form.first_name" type="text" />
            </div>
            <div class="form-group" v-if="profile.role === 'student'">
              <label>Last Name</label>
              <input v-model="form.last_name" type="text" />
            </div>

            <div class="form-group">
              <label>Email</label>
              <input v-model="form.email" type="email" placeholder="Optional" />
            </div>

            <div class="form-group">
              <label>Major / Program</label>
              <select v-model="form.major_code">
                <option v-for="m in majors" :key="m.major_code" :value="m.major_code">{{ m.major_name }}</option>
              </select>
            </div>

            <div class="form-group">
              <label>New Password</label>
              <input v-model="form.password" type="password" placeholder="Leave blank to keep current password" />
            </div>
            <div class="form-group">
              <label>Confirm Password</label>
              <input v-model="form.confirm" type="password" placeholder="Re-enter new password" />
            </div>

            <div class="form-actions">
              <button class="btn-primary" @click="save" :disabled="saving">{{ saving ? 'Saving...' : 'Save Changes' }}</button>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api/client'

const router = useRouter()
const user = ref({})
const term = ref(null)

const loading = ref(true)
const saving = ref(false)
const msg = ref('')
const ok = ref(true)

const majors = ref([{ major_code: 'CS', major_name: 'Computer Science' }])
const profile = ref({ username: '', role: 'student' })
const form = ref({
  first_name: '',
  last_name: '',
  email: '',
  major_code: 'CS',
  password: '',
  confirm: '',
})

const fetchMajors = async () => {
  try {
    const res = await api.get('majors_list.php')
    if (res.data?.status === 'success' && Array.isArray(res.data.data) && res.data.data.length > 0) majors.value = res.data.data
  } catch {
    // keep fallback
  }
}

const fetchProfile = async () => {
  const res = await api.get('profile_get.php')
  if (res.data?.status !== 'success') throw new Error(res.data?.message || 'Failed')
  const p = res.data.data || {}
  profile.value = p
  form.value.first_name = p.first_name || ''
  form.value.last_name = p.last_name || ''
  form.value.email = p.email || ''
  form.value.major_code = p.major_code || 'CS'
}

const fetchTerm = async () => {
  try {
    const res = await api.get('get_status.php')
    if (res.data?.status === 'success') term.value = res.data.term || null
  } catch {
    term.value = null
  }
}

onMounted(async () => {
  const storedUser = localStorage.getItem('user')
  if (!storedUser) return router.push('/')
  user.value = JSON.parse(storedUser)
  if (user.value.role !== 'student') return router.push(user.value.role === 'faculty' ? '/faculty' : '/admin')

  loading.value = true
  msg.value = ''
  try {
    await Promise.all([fetchMajors(), fetchProfile(), fetchTerm()])
  } catch (e) {
    ok.value = false
    msg.value = e?.response?.data?.message || e?.message || 'Failed to load profile.'
  } finally {
    loading.value = false
  }
})

const save = async () => {
  msg.value = ''
  ok.value = true

  if (form.value.password && form.value.password !== form.value.confirm) {
    ok.value = false
    msg.value = 'Passwords do not match.'
    return
  }

  saving.value = true
  try {
    const payload = {
      email: form.value.email,
      major_code: form.value.major_code,
      first_name: profile.value.role === 'student' ? form.value.first_name : undefined,
      last_name: profile.value.role === 'student' ? form.value.last_name : undefined,
      password: form.value.password || '',
    }
    const res = await api.post('profile_update.php', payload)
    if (res.data?.status === 'success') {
      ok.value = true
      msg.value = res.data?.message || 'Saved.'
      form.value.password = ''
      form.value.confirm = ''
      await fetchProfile()
    } else {
      ok.value = false
      msg.value = res.data?.message || 'Save failed.'
    }
  } catch (e) {
    ok.value = false
    msg.value = e?.response?.data?.message || 'Save failed.'
  } finally {
    saving.value = false
  }
}

const logout = async () => {
  try {
    await api.post('logout.php')
  } catch {}
  localStorage.removeItem('user')
  router.push('/')
}
</script>

<style scoped>
.dashboard-layout {
  display: flex;
  flex-direction: column;
  width: 100vw;
  height: 100vh;
  background-color: #f4f7fa;
}
.navbar {
  background-color: #003366;
  color: white;
  padding: 0 2rem;
  height: 64px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.btn-logout {
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.3);
  color: white;
  padding: 6px 16px;
  border-radius: 4px;
  cursor: pointer;
}
.main-container {
  flex: 1;
  display: flex;
  overflow: hidden;
}
.sidebar {
  width: 260px;
  background: white;
  border-right: 1px solid #dee2e6;
  padding-top: 1rem;
}
.sidebar li {
  padding: 15px 25px;
  cursor: pointer;
  color: #495057;
}
.sidebar li.active {
  background-color: #e3f2fd;
  color: #003366;
  border-left: 4px solid #003366;
}
.content {
  flex: 1;
  padding: 2rem;
  overflow-y: auto;
}
.card {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  max-width: 900px;
}
.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
  margin-top: 12px;
}
.form-group label {
  display: block;
  margin-bottom: 6px;
  color: #333;
  font-weight: 600;
}
.form-group input,
.form-group select {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
  box-sizing: border-box;
}
.form-actions {
  grid-column: 1 / -1;
  display: flex;
  justify-content: flex-end;
}
.btn-primary {
  background: #003366;
  color: white;
  border: none;
  padding: 10px 16px;
  border-radius: 6px;
  cursor: pointer;
}
.loading-text {
  color: #64748b;
}
.msg {
  grid-column: 1 / -1;
  padding: 12px 14px;
  border-radius: 8px;
  font-weight: 600;
}
.msg.ok {
  background: #e8f7ee;
  border: 1px solid #b6e2c3;
  color: #1b7a3a;
}
.msg.bad {
  background: #fdebec;
  border: 1px solid #f5c2c7;
  color: #b4232c;
}
@media (max-width: 900px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
}
</style>

