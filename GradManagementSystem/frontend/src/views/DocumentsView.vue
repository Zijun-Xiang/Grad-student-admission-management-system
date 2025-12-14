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
            <li class="active">Documents</li>
            <li @click="router.push('/major-professor')">Major Professor</li>
          </ul>
        </nav>
      </aside>

      <main class="content">
        <div class="card mb-30">
          <h2>Upload Documents</h2>
          <div class="upload-grid">
            <div class="upload-panel">
              <h3>Admission Letter</h3>
              <p class="text-muted">Required for Term 1 hold release.</p>
              <input type="file" @change="onAdmissionFileChange" accept=".pdf,.jpg,.png" />
              <button
                class="btn-primary"
                @click="uploadAdmission"
                :disabled="!admissionFile || uploadingAdmission"
              >
                {{ uploadingAdmission ? 'Uploading...' : 'Upload' }}
              </button>
            </div>

            <div class="upload-panel">
              <h3>Major Professor Form</h3>
              <p class="text-muted">Required for Term 2 hold release.</p>
              <input type="file" @change="onMpFormFileChange" accept=".pdf,.jpg,.png" />
              <button class="btn-primary" @click="uploadMpForm" :disabled="!mpFormFile || uploadingMpForm">
                {{ uploadingMpForm ? 'Uploading...' : 'Upload' }}
              </button>
            </div>
          </div>

          <div v-if="message" class="msg" :class="{ ok: messageOk, bad: !messageOk }">{{ message }}</div>
        </div>

        <div class="card">
          <h2>My Documents</h2>
          <div v-if="loading" class="loading-text">Loading...</div>
          <div v-else-if="documents.length === 0" class="empty-state">No documents uploaded.</div>
          <div v-else class="doc-list">
            <div v-for="d in documents" :key="d.doc_id" class="doc-item">
              <div class="info">
                <div class="title">
                  <strong>{{ d.doc_type }}</strong>
                  <span class="badge" :class="d.status">{{ d.status }}</span>
                </div>
                <div class="meta">
                  <span>{{ d.file_path }}</span>
                  <span v-if="d.upload_date">· {{ d.upload_date }}</span>
                </div>
                <div v-if="d.admin_comment" class="comment">Admin comment: {{ d.admin_comment }}</div>
              </div>
              <div class="actions">
                <button class="btn-secondary" @click="openDoc(d)">View</button>
                <button class="btn-secondary" @click="openComments(d)">Comments</button>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>

    <div v-if="showComments" class="modal-overlay">
      <div class="modal-box wide-modal">
        <h3>Comments</h3>
        <div class="comment-meta" v-if="activeDoc">
          <strong>{{ activeDoc.doc_type }}</strong> · doc_id={{ activeDoc.doc_id }}
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
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api/client'

const router = useRouter()
const user = ref({})

const loading = ref(true)
const documents = ref([])

const admissionFile = ref(null)
const mpFormFile = ref(null)
const uploadingAdmission = ref(false)
const uploadingMpForm = ref(false)

const message = ref('')
const messageOk = ref(true)

const showComments = ref(false)
const activeDoc = ref(null)
const comments = ref([])
const commentsLoading = ref(false)

const onAdmissionFileChange = (e) => {
  admissionFile.value = e?.target?.files?.[0] ?? null
}

const onMpFormFileChange = (e) => {
  mpFormFile.value = e?.target?.files?.[0] ?? null
}

const fetchDocs = async () => {
  loading.value = true
  try {
    const res = await api.get('get_status.php')
    if (res.data.status === 'success') documents.value = res.data.documents || []
  } finally {
    loading.value = false
  }
}

const uploadAdmission = async () => {
  if (!admissionFile.value) return
  uploadingAdmission.value = true
  message.value = ''
  try {
    const form = new FormData()
    form.append('file', admissionFile.value)
    const res = await api.post('upload_letter.php', form)
    if (res.data.status === 'success') {
      messageOk.value = true
      message.value = 'Admission letter uploaded. Waiting for review.'
      admissionFile.value = null
      await fetchDocs()
    } else {
      messageOk.value = false
      message.value = res.data.message || 'Upload failed'
    }
  } catch (e) {
    messageOk.value = false
    message.value = e?.response?.data?.message || 'Upload failed'
  } finally {
    uploadingAdmission.value = false
  }
}

const uploadMpForm = async () => {
  if (!mpFormFile.value) return
  uploadingMpForm.value = true
  message.value = ''
  try {
    const form = new FormData()
    form.append('file', mpFormFile.value)
    const res = await api.post('upload_major_professor_form.php', form)
    if (res.data.status === 'success') {
      messageOk.value = true
      message.value = 'Major Professor form uploaded. Waiting for review.'
      mpFormFile.value = null
      await fetchDocs()
    } else {
      messageOk.value = false
      message.value = res.data.message || 'Upload failed'
    }
  } catch (e) {
    messageOk.value = false
    message.value = e?.response?.data?.message || 'Upload failed'
  } finally {
    uploadingMpForm.value = false
  }
}

const openDoc = async (doc) => {
  try {
    const res = await api.get(`download_document.php?doc_id=${doc.doc_id}`, { responseType: 'blob' })
    const blob = res.data
    const url = URL.createObjectURL(blob)
    window.open(url, '_blank', 'noopener,noreferrer')
    setTimeout(() => URL.revokeObjectURL(url), 60_000)
  } catch (e) {
    alert('Failed to open document.')
  }
}

const openComments = async (doc) => {
  activeDoc.value = doc
  showComments.value = true
  comments.value = []
  commentsLoading.value = true
  try {
    const res = await api.get(`document_comments_list.php?doc_id=${doc.doc_id}`)
    if (res.data.status === 'success') comments.value = res.data.data || []
  } finally {
    commentsLoading.value = false
  }
}

const closeComments = () => {
  showComments.value = false
  activeDoc.value = null
  comments.value = []
}

const logout = async () => {
  try {
    await api.post('logout.php')
  } catch {}
  localStorage.removeItem('user')
  router.push('/')
}

onMounted(() => {
  const stored = localStorage.getItem('user')
  if (!stored) return router.push('/')
  user.value = JSON.parse(stored)
  fetchDocs()
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
.card {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  max-width: 1000px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}
.mb-30 {
  margin-bottom: 30px;
}
.text-muted {
  color: #666;
  font-size: 0.9rem;
}
.upload-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}
.upload-panel {
  border: 1px solid #eee;
  border-radius: 8px;
  padding: 14px;
  background: #fcfcfc;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.btn-primary {
  background-color: #003366;
  color: white;
  border: none;
  padding: 10px 16px;
  border-radius: 6px;
  cursor: pointer;
}
.btn-primary:disabled {
  background: #ccc;
  cursor: not-allowed;
}
.btn-secondary {
  background: #6c757d;
  color: white;
  border: none;
  padding: 8px 12px;
  border-radius: 6px;
  cursor: pointer;
}
.msg {
  margin-top: 14px;
  padding: 10px 12px;
  border-radius: 8px;
  border: 1px solid #eee;
}
.msg.ok {
  background: #f0fdf4;
  border-color: #86efac;
  color: #166534;
}
.msg.bad {
  background: #fff1f2;
  border-color: #fecaca;
  color: #991b1b;
}
.doc-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.doc-item {
  border: 1px solid #eee;
  border-radius: 10px;
  padding: 14px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
}
.title {
  display: flex;
  align-items: center;
  gap: 10px;
}
.badge {
  padding: 2px 8px;
  border-radius: 999px;
  font-size: 12px;
  border: 1px solid #e5e7eb;
  color: #374151;
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
.meta {
  color: #6b7280;
  font-size: 13px;
  margin-top: 6px;
}
.comment {
  margin-top: 6px;
  color: #374151;
  font-size: 13px;
}
.actions {
  display: flex;
  gap: 10px;
  flex-shrink: 0;
}
.empty-state,
.loading-text {
  color: #666;
  font-style: italic;
}
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}
.modal-box {
  background: white;
  width: 90%;
  max-width: 680px;
  padding: 22px;
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.comment-list {
  border: 1px solid #eee;
  border-radius: 8px;
  padding: 12px;
  max-height: 320px;
  overflow-y: auto;
  background: #fafafa;
}
.comment-item {
  padding: 10px 0;
  border-bottom: 1px solid #eee;
}
.comment-item:last-child {
  border-bottom: none;
}
.comment-head {
  display: flex;
  justify-content: space-between;
  color: #6b7280;
  font-size: 12px;
  margin-bottom: 6px;
}
.comment-author {
  font-weight: 700;
  color: #374151;
}
.comment-body {
  color: #111827;
  white-space: pre-wrap;
}
.modal-actions {
  display: flex;
  justify-content: flex-end;
}
.btn-cancel {
  background: #fff;
  border: 1px solid #ccc;
  padding: 8px 15px;
  cursor: pointer;
}
</style>
