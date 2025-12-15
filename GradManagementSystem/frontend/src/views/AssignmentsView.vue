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
            <li class="active">Assignments</li>
            <li v-if="termInfo.termNumber >= 2" @click="router.push('/major-professor')">Major Professor</li>
            <li v-if="termInfo.termNumber >= 3" @click="router.push('/thesis-project')">Thesis / Project</li>
          </ul>
        </nav>
      </aside>

      <main class="content">
        <div class="card">
          <h2>Assignments</h2>

          <div v-if="error" class="msg bad">{{ error }}</div>
          <div v-if="loading" class="loading-text">Loading...</div>
          <div v-else-if="assignments.length === 0" class="empty-state">No assignments.</div>

          <div v-else class="assignment-list">
            <div v-for="a in assignments" :key="a.id" class="assignment-item">
              <div class="head">
                <div class="title">
                  <strong>{{ a.title }}</strong>
                  <span class="badge" v-if="a.submission_id">Submitted</span>
                  <span class="badge warn" v-else>Not submitted</span>
                  <span class="badge ok" v-if="a.submission_id && a.grade !== null && a.grade !== undefined">Grade: {{ a.grade }}</span>
                </div>
                <div class="meta text-muted">
                  From: {{ a.faculty_username || 'Faculty' }}
                  <span v-if="a.due_at">· Due: {{ a.due_at }}</span>
                </div>
              </div>

              <div v-if="a.description" class="desc">{{ a.description }}</div>

              <div class="actions">
                <button v-if="a.attachment_path" class="btn-secondary" @click="downloadAssignment(a)" :disabled="busyId === a.id">
                  {{ busyId === a.id ? 'Working...' : 'Download' }}
                </button>
                <button v-if="a.submission_id" class="btn-secondary" @click="downloadSubmission(a)" :disabled="busyId === a.id">
                  {{ busyId === a.id ? 'Working...' : 'My Submission' }}
                </button>
                <button v-if="a.submission_id" class="btn-secondary" @click="openComments(a)" :disabled="busyId === a.id">
                  Comments ({{ Number(a.comments_count || 0) }})
                </button>
              </div>

              <div class="submit-box">
                <div class="text-muted">
                  {{ a.submission_id ? 'Resubmit (optional):' : 'Submit:' }}
                  <span v-if="a.submission_id && a.grade !== null && a.grade !== undefined" class="muted" style="margin-left: 8px">
                    (Your grade: {{ a.grade }})
                  </span>
                </div>
                <input type="file" @change="(e) => onPickFile(a.id, e)" />
                <button class="btn-primary" @click="submit(a)" :disabled="!pickedFiles[a.id] || busyId === a.id">
                  {{ busyId === a.id ? 'Uploading...' : a.submission_id ? 'Resubmit' : 'Submit' }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <div v-if="showComments" class="modal-overlay">
          <div class="modal-box wide-modal">
            <h3>Faculty Comments</h3>
            <div class="comment-meta" v-if="activeAssignment">
              <strong>{{ activeAssignment.title }}</strong>
              <span v-if="activeAssignment.submission_id"> · submission_id={{ activeAssignment.submission_id }}</span>
            </div>

            <div class="comment-list">
              <div v-if="commentsLoading" class="loading-text">Loading comments...</div>
              <div v-else-if="comments.length === 0" class="empty-state">No comments yet.</div>
              <div v-else>
                <div v-for="c in comments" :key="c.id" class="comment-item">
                  <div class="comment-head">
                    <span class="comment-author">{{ c.author_username || c.author_role }}</span>
                    <span class="comment-time">{{ c.created_at }}</span>
                  </div>
                  <div class="comment-body">{{ c.comment }}</div>
                </div>
              </div>
            </div>

            <div class="modal-actions">
              <button class="btn-cancel" @click="closeComments">Close</button>
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

const router = useRouter()
const user = ref({})
const loading = ref(true)
const assignments = ref([])
const error = ref('')
const busyId = ref(0)
const pickedFiles = ref({})

const showComments = ref(false)
const activeAssignment = ref(null)
const comments = ref([])
const commentsLoading = ref(false)

const term = ref(null)

const termInfo = computed(() => {
  const t = term.value || {}
  return { termNumber: Number(t.term_number || 1) }
})

onMounted(async () => {
  const stored = localStorage.getItem('user')
  if (!stored) return router.push('/')
  user.value = JSON.parse(stored)

  try {
    const s = await api.get('get_status.php')
    if (s.data?.status === 'success') term.value = s.data.term || null
  } catch {}

  await fetchAssignments()
})

const fetchAssignments = async () => {
  loading.value = true
  error.value = ''
  try {
    const res = await api.get('student_list_assignments.php')
    if (res.data?.status === 'success') assignments.value = res.data.data || []
    else error.value = res.data?.message || 'Failed to load assignments.'
  } catch (e) {
    error.value = e?.response?.data?.message || 'Failed to load assignments.'
  } finally {
    loading.value = false
  }
}

const onPickFile = (assignmentId, e) => {
  const f = e?.target?.files?.[0] ?? null
  pickedFiles.value = { ...pickedFiles.value, [assignmentId]: f }
}

const openBlob = (blob) => {
  const url = URL.createObjectURL(blob)
  window.open(url, '_blank', 'noopener,noreferrer')
  setTimeout(() => URL.revokeObjectURL(url), 60_000)
}

const downloadAssignment = async (a) => {
  busyId.value = a.id
  try {
    const res = await api.get(`download_assignment_file.php?assignment_id=${a.id}`, { responseType: 'blob' })
    openBlob(res.data)
  } finally {
    busyId.value = 0
  }
}

const downloadSubmission = async (a) => {
  if (!a.submission_id) return
  busyId.value = a.id
  try {
    const res = await api.get(`download_assignment_submission.php?submission_id=${a.submission_id}`, { responseType: 'blob' })
    openBlob(res.data)
  } finally {
    busyId.value = 0
  }
}

const submit = async (a) => {
  const file = pickedFiles.value?.[a.id]
  if (!file) return
  busyId.value = a.id
  try {
    const form = new FormData()
    form.append('assignment_id', String(a.id))
    form.append('file', file)
    const res = await api.post('student_submit_assignment.php', form)
    if (res.data?.status === 'success') {
      pickedFiles.value = { ...pickedFiles.value, [a.id]: null }
      await fetchAssignments()
    } else {
      alert(res.data?.message || 'Submit failed')
    }
  } catch (e) {
    alert(e?.response?.data?.message || 'Submit failed')
  } finally {
    busyId.value = 0
  }
}

const openComments = async (a) => {
  activeAssignment.value = a
  showComments.value = true
  comments.value = []
  commentsLoading.value = true
  try {
    const res = await api.get(`assignment_submission_comments_list.php?submission_id=${a.submission_id}`)
    if (res.data?.status === 'success') comments.value = res.data.data || []
  } finally {
    commentsLoading.value = false
  }
}

const closeComments = () => {
  showComments.value = false
  activeAssignment.value = null
  comments.value = []
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
  max-width: 980px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}
h2 {
  color: #003366;
  border-bottom: 2px solid #f1f3f5;
  padding-bottom: 15px;
  margin-bottom: 20px;
}
.assignment-item {
  border: 1px solid #eee;
  border-radius: 10px;
  padding: 16px;
  margin-bottom: 14px;
}
.head {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.title {
  display: flex;
  align-items: center;
  gap: 10px;
}
.badge {
  background: #28a745;
  color: white;
  padding: 2px 8px;
  border-radius: 999px;
  font-size: 0.8rem;
}
.badge.warn {
  background: #6c757d;
}
.badge.ok {
  background: #0ea5e9;
}
.muted {
  color: #6b7280;
  font-size: 0.9rem;
}
.text-muted {
  color: #6b7280;
}
.desc {
  margin: 10px 0;
  white-space: pre-wrap;
}
.actions {
  display: flex;
  gap: 10px;
  margin: 10px 0;
  flex-wrap: wrap;
}
.submit-box {
  display: grid;
  grid-template-columns: 160px 1fr 140px;
  gap: 10px;
  align-items: center;
}
.btn-primary {
  background: #003366;
  color: white;
  padding: 10px 12px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
}
.btn-primary:disabled {
  background: #cbd5e1;
  cursor: not-allowed;
}
.btn-secondary {
  background: #6c757d;
  color: white;
  padding: 8px 12px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}
.msg {
  padding: 12px 14px;
  border-radius: 8px;
  margin: 0 0 16px;
  font-weight: 600;
}
.msg.bad {
  background: #fdebec;
  border: 1px solid #f5c2c7;
  color: #b4232c;
}
.loading-text,
.empty-state {
  padding: 10px 0;
  color: #6b7280;
}
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.35);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}
.modal-box {
  background: white;
  width: 92%;
  max-width: 860px;
  border-radius: 10px;
  padding: 18px;
}
.wide-modal {
  max-width: 900px;
}
.comment-item {
  border-bottom: 1px solid #eee;
  padding: 10px 0;
}
.comment-head {
  display: flex;
  justify-content: space-between;
  color: #6b7280;
  font-size: 0.9rem;
}
.comment-body {
  margin-top: 6px;
  white-space: pre-wrap;
}
.modal-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 12px;
}
.btn-cancel {
  background: #6c757d;
  color: white;
  padding: 8px 12px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}
</style>
