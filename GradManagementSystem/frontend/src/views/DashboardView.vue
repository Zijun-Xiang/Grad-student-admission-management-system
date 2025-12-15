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
	            <li class="active">Dashboard</li>
	            <li @click="router.push('/my-courses')">My Courses</li>
	            <li @click="router.push('/documents')">Documents</li>
	            <li @click="router.push('/assignments')">Assignments</li>
	            <li v-if="termInfo.termNumber >= 2" @click="router.push('/major-professor')">Major Professor</li>
            <li v-if="termInfo.termNumber >= 3" @click="router.push('/thesis-project')">Thesis / Project</li>
	          </ul>
	        </nav>
	      </aside>

      <main class="content">
        <div class="card">
          <h2>Student Status</h2>

          <div v-if="isLoadingData" class="loading-text">Loading status...</div>

	          <div v-else class="status-grid">
	            <div class="left-panel">
	              <div class="status-item">
	                <h3>Academic Calendar</h3>
	                <div v-if="termInfo.entryDate" class="text-muted">
	                  Entry Date: <strong>{{ termInfo.entryDate }}</strong> · Entry Term:
	                  <strong>{{ termInfo.entryTerm }}</strong>
	                </div>
	                <div class="text-muted">
	                  Current Term: <strong>{{ termInfo.currentTerm }}</strong> · You are in
	                  <strong>Term {{ termInfo.termNumber }}</strong>
	                </div>
	                <MiniCalendar :entry-date="termInfo.entryDate" />
	              </div>

	              <div class="status-item">
	                <h3>Current Holds</h3>
	                <div v-if="holds.length > 0" class="status-bad">You have {{ holds.length }} active hold(s).</div>
	                <div v-else class="status-good">No Holds. You are good to register.</div>
	                <ul class="hold-list" v-if="holds.length > 0">
	                  <li v-for="hold in holds" :key="hold.id">{{ hold.hold_type }} (Active)</li>
	                </ul>
	              </div>

              <div class="status-item deficiency-section">
                <h3>Deficiency Courses</h3>
                <div v-if="deficiencies.length === 0" class="text-muted">No deficiency courses assigned.</div>
                <ul v-else class="deficiency-list">
                  <li v-for="course in deficiencies" :key="course.course_code">
                    <span class="course-code">{{ course.course_code }}</span>
                    <span class="course-name">{{ course.course_name }}</span>
                    <span class="course-badge">{{ course.status }}</span>
                  </li>
                </ul>
              </div>
	            </div>

	            <div class="status-item upload-section">
	              <h3>Documents Status</h3>
	              <div class="doc-status">
	                <div class="doc-row">
	                  <div class="doc-left">
	                    <div class="doc-title">Admission Letter</div>
	                    <div class="doc-sub">Required for Term 1 hold release</div>
	                  </div>
	                  <div class="doc-right">
	                    <span class="badge" :class="admissionStatus">{{ admissionStatus }}</span>
	                  </div>
	                </div>
	                <div v-if="admissionStatus === 'rejected' && admissionComment" class="doc-comment">
	                  Admin comment: {{ admissionComment }}
	                </div>

	                <div class="doc-row">
	                  <div class="doc-left">
	                    <div class="doc-title">Major Professor Form</div>
	                    <div class="doc-sub">Required for Term 2 hold release</div>
	                  </div>
	                  <div class="doc-right">
	                    <span class="badge" :class="mpFormStatus">{{ mpFormStatus }}</span>
	                  </div>
	                </div>
	                <div v-if="mpFormStatus === 'rejected' && mpFormComment" class="doc-comment">
	                  Admin comment: {{ mpFormComment }}
	                </div>
	              </div>

	              <button class="btn-primary" @click="router.push('/documents')">Go to Documents</button>
	              <p class="text-muted" style="margin-top: 10px">
	                Uploads are handled in Documents. Dashboard only shows status.
	              </p>
	            </div>
	          </div>
	        </div>
	      </main>
	    </div>
	  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api/client'
import MiniCalendar from '../components/MiniCalendar.vue'

const router = useRouter()
const user = ref({})
const holds = ref([])
const documents = ref([])
const deficiencies = ref([])
const profile = ref(null)
const term = ref(null)
const isLoadingData = ref(true)

const docByType = (type) => documents.value.find((d) => d.doc_type === type) || null
const admissionStatus = computed(() => docByType('admission_letter')?.status || 'none')
const admissionComment = computed(() => docByType('admission_letter')?.admin_comment || '')
const mpFormStatus = computed(() => docByType('major_professor_form')?.status || 'none')
const mpFormComment = computed(() => docByType('major_professor_form')?.admin_comment || '')

const termCodeFromDate = (dateStr) => {
  const d = new Date(dateStr + 'T00:00:00')
  if (isNaN(d.getTime())) return 'Unknown'
  const year = d.getFullYear()
  const month = d.getMonth() + 1
  if (month <= 4) return `${year}SP`
  if (month <= 8) return `${year}SU`
  return `${year}FA`
}

const termIndex = (termCode) => {
  const m = String(termCode).match(/^(\d{4})(SP|SU|FA)$/)
  if (!m) return 0
  const year = Number(m[1])
  const season = m[2]
  const offset = season === 'SP' ? 0 : season === 'SU' ? 1 : 2
  return year * 3 + offset
}

const termInfo = computed(() => {
  if (term.value) {
    return {
      entryDate: term.value.entry_date || '',
      entryTerm: term.value.entry_term_code || 'Unknown',
      currentTerm: term.value.current_term_code || 'Unknown',
      termNumber: Number(term.value.term_number || 1),
    }
  }
  const p = profile.value || {}
  const entryDate = p.entry_date || ''
  const entryTerm = p.entry_term_code || (entryDate ? termCodeFromDate(entryDate) : '')
  const currentTerm = termCodeFromDate(new Date().toISOString().slice(0, 10))
  const termNumber = entryTerm ? Math.max(1, termIndex(currentTerm) - termIndex(entryTerm) + 1) : 1
  return { entryDate, entryTerm: entryTerm || 'Unknown', currentTerm, termNumber }
})

onMounted(() => {
  const storedUser = localStorage.getItem('user')
  if (storedUser) {
    user.value = JSON.parse(storedUser)
    fetchStatus()
  } else {
    router.push('/')
  }
})

const fetchStatus = async () => {
  try {
    const response = await api.get('get_status.php')
    if (response.data.status === 'success') {
      holds.value = response.data.holds
      documents.value = response.data.documents
      deficiencies.value = response.data.deficiencies || []
      profile.value = response.data.profile || null
      term.value = response.data.term || null
    }
  } catch (error) {
    console.error(error)
  } finally {
    isLoadingData.value = false
  }
}

const logout = async () => {
  try {
    await api.post('logout.php')
  } catch {}
  localStorage.removeItem('user')
  router.push('/')
}

// Upload actions moved to DocumentsView.vue
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
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}
h2 {
  color: #003366;
  border-bottom: 2px solid #f1f3f5;
  padding-bottom: 15px;
  margin-bottom: 20px;
}

.status-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 30px;
}
.left-panel {
  display: flex;
  flex-direction: column;
  gap: 20px;
}
.status-item h3 {
  margin-top: 0;
  font-size: 1.1rem;
  color: #333;
  margin-bottom: 10px;
}
.status-bad {
  background: #fff5f5;
  color: #c92a2a;
  padding: 15px;
  border-radius: 6px;
  font-weight: bold;
}
.status-good {
  background: #e8f5e9;
  color: #2e7d32;
  padding: 15px;
  border-radius: 6px;
  font-weight: bold;
}
.status-pending {
  background: #fff9db;
  color: #d9480f;
  padding: 15px;
  border-radius: 6px;
  text-align: center;
  font-weight: bold;
  border: 1px solid #ffe066;
}

.deficiency-list {
  list-style: none;
  padding: 0;
  margin: 0;
  border: 1px solid #eee;
  border-radius: 6px;
}
.deficiency-list li {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 15px;
  border-bottom: 1px solid #eee;
}
.deficiency-list li:last-child {
  border-bottom: none;
}
.course-code {
  font-weight: bold;
  color: #003366;
}
.course-name {
  color: #555;
  font-size: 0.9rem;
  flex: 1;
  margin-left: 10px;
}
.course-badge {
  background: #e9ecef;
  color: #495057;
  padding: 2px 8px;
  border-radius: 10px;
  font-size: 0.8rem;
}
.text-muted {
  color: #888;
  font-style: italic;
}

.upload-section {
  background: #fcfcfc;
  padding: 20px;
  border: 1px solid #eee;
  border-radius: 8px;
}
.upload-box {
  display: flex;
  flex-direction: column;
  gap: 15px;
  margin-top: 15px;
}
.btn-primary {
  background-color: #003366;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 4px;
  cursor: pointer;
}
.btn-primary:disabled {
  background-color: #ccc;
}
.doc-status {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin: 12px 0 14px;
}
.doc-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  padding: 10px;
  border: 1px solid #eee;
  border-radius: 10px;
  background: #fff;
}
.doc-title {
  font-weight: 800;
  color: #0f172a;
}
.doc-sub {
  font-size: 12px;
  color: #64748b;
  margin-top: 4px;
}
.badge {
  padding: 4px 10px;
  border-radius: 999px;
  border: 1px solid #e5e7eb;
  font-weight: 700;
  font-size: 12px;
  text-transform: uppercase;
}
.badge.approved {
  background: #f0fdf4;
  border-color: #86efac;
  color: #166534;
}
.badge.pending {
  background: #fff9db;
  border-color: #ffe066;
  color: #d9480f;
}
.badge.rejected {
  background: #fff1f2;
  border-color: #fecaca;
  color: #991b1b;
}
.badge.none {
  background: #f8fafc;
  border-color: #e2e8f0;
  color: #475569;
}
.doc-comment {
  color: #374151;
  font-size: 13px;
  margin: -6px 0 6px;
}
</style>
