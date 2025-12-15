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
            <li v-if="term?.unlocks?.term2" @click="router.push('/major-professor')">Major Professor</li>
            <li class="active">Thesis / Project</li>
          </ul>
        </nav>
      </aside>

      <main class="content">
        <div class="content-inner">
          <div class="card">
            <h2>Thesis / Project Timeline</h2>

            <div v-if="Number(term?.term_number || 1) < 3" class="status-box pending">
              <h3>Locked</h3>
              <p>This feature is available starting <strong>Term 3</strong>.</p>
              <p class="text-muted">You are currently in Term {{ Number(term?.term_number || 1) }}.</p>
            </div>

            <template v-else>
              <div class="subtitle">
                Rule: submission date must be at least <strong>1 month</strong> before defense date.
              </div>

              <div v-if="msg" class="msg" :class="{ ok: msgOk, bad: !msgOk }">{{ msg }}</div>
              <div v-if="!isEditable" class="msg bad">
                Locked: you can edit this timeline only in <strong>Term 3</strong> and <strong>Term 4</strong>.
              </div>

              <div class="card-lite">
                <h3>Admin-published Defense Window</h3>
                <div v-if="windowsLoading" class="loading-text">Loading windows...</div>
                <div v-else-if="windows.length === 0" class="empty-state">
                  No defense window published yet. Please contact admin.
                </div>
                <div v-else class="form-grid">
                  <div class="form-group">
                    <label>Defense Year</label>
                    <select v-model="selectedYear" class="select">
                      <option disabled value="">-- Select year --</option>
                      <option v-for="y in availableYears" :key="y" :value="String(y)">{{ y }}</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Window Start</label>
                    <input :value="selectedWindow?.start_date || '-'" disabled />
                  </div>
                  <div class="form-group">
                    <label>Window End</label>
                    <input :value="selectedWindow?.end_date || '-'" disabled />
                  </div>
                </div>
              </div>

              <div class="card-lite mt-20">
                <h3>Your Selected Dates</h3>
                <div class="form-grid">
                  <div class="form-group">
                    <label>Type</label>
                    <select v-model="form.type" class="select">
                      <option value="thesis">Thesis</option>
                      <option value="project">Project</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Title (optional)</label>
                    <input v-model="form.title" placeholder="Optional" />
                  </div>

                  <div class="form-group">
                    <label>Defense Date</label>
                    <input
                      v-model="form.defense_date"
                      type="date"
                      :min="selectedWindow?.start_date || undefined"
                      :max="selectedWindow?.end_date || undefined"
                      :disabled="!selectedWindow || !isEditable"
                    />
                  </div>
                  <div class="form-group">
                    <label>Submission Date</label>
                    <input
                      v-model="form.submission_date"
                      type="date"
                      :max="maxSubmissionDate || undefined"
                      :disabled="!form.defense_date || !isEditable"
                    />
                    <div class="hint text-muted" v-if="maxSubmissionDate">
                      Must be on/before <strong>{{ maxSubmissionDate }}</strong>.
                    </div>
                  </div>
                </div>

                <div class="form-actions">
                  <button class="btn-primary" @click="save" :disabled="saving || !canSave">
                    {{ saving ? 'Saving...' : 'Save' }}
                  </button>
                  <button class="btn-view" @click="refresh" :disabled="saving">Refresh</button>
                </div>

                <div v-if="current" class="mt-20">
                  <div class="text-muted">Current record:</div>
                  <div class="table mt-10">
                    <div class="row header">
                      <div>Type</div>
                      <div>Title</div>
                      <div>Submission</div>
                      <div>Defense</div>
                      <div>Updated</div>
                    </div>
                    <div class="row">
                      <div>{{ current.type }}</div>
                      <div>{{ current.title || '-' }}</div>
                      <div>{{ current.submission_date }}</div>
                      <div>{{ current.defense_date }}</div>
                      <div>{{ current.created_at }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </template>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api/client'

const router = useRouter()
const user = ref({})
const term = ref(null)

const windowsLoading = ref(false)
const windows = ref([])
const selectedYear = ref('')
const msg = ref('')
const msgOk = ref(true)
const saving = ref(false)

const current = ref(null)
const form = ref({
  type: 'thesis',
  title: '',
  submission_date: '',
  defense_date: '',
})

const availableYears = computed(() => {
  const ys = (windows.value || []).map((w) => Number(w.year)).filter((y) => !Number.isNaN(y))
  return Array.from(new Set(ys)).sort((a, b) => b - a)
})

const selectedWindow = computed(() => {
  const y = Number(selectedYear.value)
  if (!y) return null
  return (windows.value || []).find((w) => Number(w.year) === y) || null
})

const maxSubmissionDate = computed(() => {
  const d = form.value.defense_date
  if (!d) return ''
  const dt = new Date(d + 'T00:00:00')
  if (isNaN(dt.getTime())) return ''
  const m = dt.getMonth()
  dt.setMonth(m - 1)
  const iso = dt.toISOString().slice(0, 10)
  return iso
})

const isEditable = computed(() => {
  const n = Number(term.value?.term_number || 1)
  return n >= 3 && n <= 4
})

const canSave = computed(() => {
  if (!isEditable.value) return false
  if (!selectedWindow.value) return false
  if (!form.value.defense_date || !form.value.submission_date) return false
  return true
})

watch(
  () => form.value.defense_date,
  (v) => {
    if (!v) return
    const y = v.slice(0, 4)
    if (y && y !== selectedYear.value && availableYears.value.includes(Number(y))) {
      selectedYear.value = y
    }
  },
)

const loadStatus = async () => {
  try {
    const res = await api.get('get_status.php')
    if (res.data?.status === 'success') term.value = res.data.term || null
  } catch {}
}

const loadWindows = async () => {
  windowsLoading.value = true
  try {
    const res = await api.get('defense_windows_list.php')
    if (res.data?.status === 'success') {
      windows.value = res.data.data || []
      const cy = String(res.data.current_year || new Date().getFullYear())
      if (!selectedYear.value && (windows.value || []).some((w) => String(w.year) === cy)) {
        selectedYear.value = cy
      }
    }
  } finally {
    windowsLoading.value = false
  }
}

const loadCurrent = async () => {
  try {
    const res = await api.get('student_get_thesis_project.php')
    if (res.data?.status === 'success') {
      current.value = res.data.data || null
      if (current.value) {
        form.value.type = current.value.type || 'thesis'
        form.value.title = current.value.title || ''
        form.value.submission_date = current.value.submission_date || ''
        form.value.defense_date = current.value.defense_date || ''
        if (current.value.defense_date) {
          selectedYear.value = current.value.defense_date.slice(0, 4)
        }
      }
    }
  } catch {}
}

const refresh = async () => {
  msg.value = ''
  await Promise.all([loadStatus(), loadWindows(), loadCurrent()])
}

const save = async () => {
  msg.value = ''
  msgOk.value = true
  saving.value = true
  try {
    const res = await api.post('submit_thesis_project.php', {
      type: form.value.type,
      title: form.value.title,
      submission_date: form.value.submission_date,
      defense_date: form.value.defense_date,
    })
    if (res.data?.status === 'success') {
      msg.value = res.data?.message || 'Saved.'
      msgOk.value = true
      await loadCurrent()
    } else {
      msg.value = res.data?.message || 'Save failed.'
      msgOk.value = false
    }
  } catch (e) {
    msg.value = e?.response?.data?.message || 'Save failed.'
    msgOk.value = false
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

onMounted(async () => {
  const stored = localStorage.getItem('user')
  if (!stored) return router.push('/')
  user.value = JSON.parse(stored)
  await refresh()
})
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
.content-inner {
  width: 100%;
  max-width: 980px;
  margin: 0 auto;
}
.card {
  background: white;
  padding: 30px;
  border-radius: 8px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}
.card-lite {
  border: 1px solid #eee;
  border-radius: 10px;
  padding: 16px;
  background: #fcfcfd;
}
.mt-10 {
  margin-top: 10px;
}
.mt-20 {
  margin-top: 20px;
}
h2 {
  color: #003366;
  border-bottom: 2px solid #eee;
  padding-bottom: 15px;
}
.subtitle {
  color: #334155;
  margin: 8px 0 16px;
}
.msg {
  padding: 12px;
  border-radius: 6px;
  margin-bottom: 16px;
  text-align: center;
  font-weight: 600;
}
.msg.ok {
  background: #e8f5e9;
  color: #2e7d32;
  border: 1px solid #c8e6c9;
}
.msg.bad {
  background: #fff5f5;
  color: #c92a2a;
  border: 1px solid #ffc9c9;
}
.empty-state {
  color: #6c757d;
}
.loading-text {
  color: #6c757d;
}
.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
  margin-top: 12px;
}
.form-group label {
  display: block;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 6px;
}
.form-group input,
.select {
  width: 100%;
  padding: 10px;
  border-radius: 6px;
  border: 1px solid #ccc;
  font-size: 1rem;
}
.hint {
  font-size: 12px;
  margin-top: 6px;
}
.text-muted {
  color: #6c757d;
}
.form-actions {
  margin-top: 16px;
  display: flex;
  gap: 10px;
}
.btn-primary {
  background: #003366;
  border: none;
  color: white;
  padding: 10px 18px;
  border-radius: 6px;
  cursor: pointer;
}
.btn-view {
  background: #6c757d;
  border: none;
  color: white;
  padding: 10px 18px;
  border-radius: 6px;
  cursor: pointer;
}
.btn-primary:disabled,
.btn-view:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.table {
  width: 100%;
  border: 1px solid #eee;
  border-radius: 10px;
  overflow: hidden;
}
.row {
  display: grid;
  grid-template-columns: 120px 1fr 140px 140px 170px;
  gap: 10px;
  padding: 10px 14px;
  border-top: 1px solid #eee;
  align-items: center;
}
.row.header {
  background: #f8fafc;
  font-weight: 800;
  border-top: none;
}
.status-box {
  padding: 16px;
  border-radius: 8px;
  text-align: center;
  border: 1px solid #ffe066;
  background: #fff9db;
  color: #d9480f;
}
</style>
