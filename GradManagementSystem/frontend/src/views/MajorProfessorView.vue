<template>
  <div class="dashboard-layout">
    <header class="navbar">
      <div class="brand">Grad System</div>
      <div class="user-info">
        <span>Welcome, {{ user.username }}</span>
        <button @click="router.push('/dashboard')" class="btn-back">Back to Dashboard</button>
      </div>
    </header>

    <div class="main-container">
      <main class="content">
        <div class="card">
          <h2>Select Major Professor</h2>

          <div v-if="currentStatus === 'approved'" class="status-box approved">
            <h3>You have a Major Professor</h3>
            <p>Your advisor is: <strong>{{ professorName }}</strong></p>
          </div>

          <div v-else-if="currentStatus === 'pending'" class="status-box pending">
            <h3>Request Pending</h3>
            <p>You have requested <strong>{{ professorName }}</strong> as your advisor.</p>
            <p>Please wait for their approval.</p>
          </div>

          <div v-else class="form-block">
            <div class="alert-box">You have a "Major Professor" Hold. Please select an advisor to proceed.</div>

            <div class="form-section">
              <label>Choose a Faculty Member:</label>
              <select v-model="selectedProf" class="prof-select">
                <option disabled value="">-- Select Professor --</option>
                <option v-for="prof in facultyList" :key="prof.user_id" :value="prof.user_id">
                  {{ prof.username }} ({{ prof.email }})
                </option>
              </select>

              <button @click="submitRequest" class="btn-primary" :disabled="!selectedProf">Submit Request</button>
            </div>
          </div>
        </div>

        <div v-if="currentStatus === 'approved'" class="card mt-20">
          <h2>Major Professor Form (Term 2)</h2>
          <p class="text-muted">
            All document uploads are handled in <strong>Documents</strong>. This page only manages advisor selection.
          </p>

          <div class="status-box pending" v-if="mpFormStatus === 'pending'">
            <p>Your form is under review.</p>
          </div>
          <div class="status-box approved" v-else-if="mpFormStatus === 'approved'">
            <p>Your form has been approved.</p>
          </div>
          <div class="status-box" v-else>
            <p v-if="mpFormStatus === 'rejected'" class="text-error">Previous upload was rejected. Please re-upload.</p>
            <p v-else>No form uploaded yet.</p>
            <button class="btn-primary" @click="router.push('/documents')">Go to Documents</button>
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

const facultyList = ref([])
const selectedProf = ref('')

const currentStatus = ref('none')
const professorName = ref('')
const documents = ref([])

const mpFormStatus = ref('none')

onMounted(() => {
  const storedUser = localStorage.getItem('user')
  if (storedUser) {
    user.value = JSON.parse(storedUser)
    fetchFaculty()
    refreshMyStatus()
  } else {
    router.push('/')
  }
})

const fetchFaculty = async () => {
  try {
    const res = await api.get('get_faculty_list.php')
    if (res.data.status === 'success') facultyList.value = res.data.data
  } catch (e) {
    console.error(e)
  }
}

const refreshMyStatus = async () => {
  try {
    const res = await api.get('get_status.php')
    if (res.data.status !== 'success') return

    const info = res.data.mp_info
    if (info) {
      currentStatus.value = info.mp_status
      professorName.value = info.prof_name || 'Unknown'
    }

    documents.value = res.data.documents || []
    const formDoc = documents.value.find((d) => d.doc_type === 'major_professor_form')
    mpFormStatus.value = formDoc?.status || 'none'
  } catch (e) {
    console.error('Failed to check status', e)
  }
}

const submitRequest = async () => {
  if (!confirm('Are you sure you want to request this professor?')) return
  try {
    const res = await api.post('request_major_professor.php', { professor_id: selectedProf.value })
    if (res.data.status === 'success') {
      alert('Request Sent!')
      currentStatus.value = 'pending'
      const prof = facultyList.value.find((p) => p.user_id === selectedProf.value)
      professorName.value = prof ? prof.username : 'Unknown'
    } else {
      alert(res.data.message || 'Request failed')
    }
  } catch (e) {
    alert('Network Error')
  }
}

// Upload moved to DocumentsView.vue
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
.main-container {
  flex: 1;
  display: flex;
  justify-content: center;
  padding: 40px;
}
.content {
  width: 100%;
  max-width: 800px;
}
.card {
  background: white;
  padding: 30px;
  border-radius: 8px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}
.mt-20 {
  margin-top: 20px;
}
.btn-back {
  background: rgba(255, 255, 255, 0.2);
  border: 1px solid white;
  color: white;
  padding: 5px 15px;
  cursor: pointer;
  border-radius: 4px;
}
h2 {
  color: #003366;
  border-bottom: 2px solid #eee;
  padding-bottom: 15px;
}

.alert-box {
  background: #fff5f5;
  color: #c92a2a;
  padding: 15px;
  border-radius: 6px;
  font-weight: bold;
  margin-bottom: 20px;
  border: 1px solid #ffc9c9;
}
.status-box {
  padding: 16px;
  border-radius: 8px;
  text-align: center;
}
.status-box.pending {
  background: #fff9db;
  color: #d9480f;
  border: 1px solid #ffe066;
}
.status-box.approved {
  background: #e8f5e9;
  color: #2e7d32;
  border: 1px solid #c8e6c9;
}

.form-section {
  display: flex;
  flex-direction: column;
  gap: 15px;
  max-width: 400px;
  margin: 0 auto;
}
.prof-select {
  padding: 10px;
  border-radius: 6px;
  border: 1px solid #ccc;
  font-size: 1rem;
}
.btn-primary {
  background: #003366;
  color: white;
  padding: 12px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 1rem;
}
.btn-primary:disabled {
  background: #ccc;
  cursor: not-allowed;
}
.upload-box {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 10px;
}
.text-error {
  color: #c92a2a;
}
.text-success {
  color: #2e7d32;
}
</style>
