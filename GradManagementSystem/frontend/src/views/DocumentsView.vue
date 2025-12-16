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
            <li @click="router.push('/assignments')">Assignments</li>
            <li v-if="term?.unlocks?.term2" @click="router.push('/major-professor')">Major Professor</li>
            <li v-if="term?.unlocks?.term3" @click="router.push('/thesis-project')">Thesis / Project</li>
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
              <div v-if="!term?.unlocks?.term2" class="msg bad" style="margin: 10px 0 0">
                Locked: available starting Term 2.
              </div>
              <input type="file" @change="onMpFormFileChange" accept=".pdf,.jpg,.png" />
              <button
                class="btn-primary"
                @click="uploadMpForm"
                :disabled="!term?.unlocks?.term2 || !mpFormFile || uploadingMpForm"
              >
                {{ uploadingMpForm ? 'Uploading...' : 'Upload' }}
              </button>
            </div>

            <div class="upload-panel">
              <h3>Thesis / Project</h3>
              <p class="text-muted">Upload your thesis/project file (PDF). Allowed only in Term 3 and Term 4.</p>
              <div v-if="!thesisUploadUnlocked" class="msg bad" style="margin: 10px 0 0">
                Locked: available only in Term 3 and Term 4.
              </div>
              <input type="file" @change="onThesisFileChange" :disabled="!thesisUploadUnlocked" accept=".pdf" />
              <button class="btn-primary" @click="uploadThesis" :disabled="!thesisUploadUnlocked || !thesisFile || uploadingThesis">
                {{ uploadingThesis ? 'Uploading...' : 'Upload' }}
              </button>
            </div>

            <div class="upload-panel">
              <h3>Research Method (Proof)</h3>
              <p class="text-muted">
                Term 3 hold release requires you to register/take the Research Method course. Faculty will verify your course registration.
                Upload proof here if requested (PDF/JPG/PNG).
              </p>
              <div v-if="!researchMethodUploadUnlocked" class="msg bad" style="margin: 10px 0 0">
                Locked: available starting Term 3.
              </div>
              <input
                type="file"
                @change="onResearchMethodFileChange"
                :disabled="!researchMethodUploadUnlocked"
                accept=".pdf,.jpg,.jpeg,.png"
              />
              <button
                class="btn-primary"
                @click="uploadResearchMethodProof"
                :disabled="!researchMethodUploadUnlocked || !researchMethodFile || uploadingResearchMethod"
              >
                {{ uploadingResearchMethod ? 'Uploading...' : 'Upload' }}
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
                <div class="doc-meta-row">
                  <span class="doc-source-pill">{{ docTypeLabel(d.doc_type) }}</span>
                  <span class="doc-format-pill">{{ fileFormatLabel(d.file_path) }}</span>
                  <span class="doc-status-pill" :class="statusPillClass(d.status)">{{ statusLabel(d.status) }}</span>
                  <span v-if="d.upload_date" class="meta">· {{ d.upload_date }}</span>
                </div>
                <div v-if="d.admin_comment" class="comment">Admin comment: {{ d.admin_comment }}</div>
              </div>
              <div class="actions">
                <button class="btn-secondary" @click="openDoc(d)">View</button>
                <button class="btn-secondary" @click="openComments(d)">Comments</button>
                <button class="btn-secondary" @click="openReplace(d)" :disabled="!canModify(d)">Replace</button>
                <button class="btn-danger" @click="openDelete(d)" :disabled="!canModify(d)">Delete</button>
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

        <textarea v-model="newComment" placeholder="Add a note..." rows="3"></textarea>
        <div v-if="commentMsg" class="msg" :class="{ ok: commentOk, bad: !commentOk }">{{ commentMsg }}</div>
        <div class="modal-actions">
          <button class="btn-cancel" @click="closeComments">Close</button>
          <button class="btn-primary" @click="postComment" :disabled="commentPosting || !newComment.trim() || !activeDoc">
            {{ commentPosting ? 'Posting...' : 'Post' }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="showReplace" class="modal-overlay">
      <div class="modal-box wide-modal">
        <h3>Replace Document</h3>
        <div class="comment-meta" v-if="replaceDoc">
          <strong>{{ replaceDoc.doc_type }}</strong> · doc_id={{ replaceDoc.doc_id }}
        </div>
        <input type="file" @change="onReplaceFileChange" :accept="replaceAccept" />
        <div v-if="replaceMsg" class="msg" :class="{ ok: replaceOk, bad: !replaceOk }">{{ replaceMsg }}</div>
        <div class="modal-actions">
          <button class="btn-cancel" @click="closeReplace">Cancel</button>
          <button class="btn-primary" @click="doReplace" :disabled="replacing || !replaceFile || !replaceDoc">
            {{ replacing ? 'Uploading...' : 'Replace' }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="showDelete" class="modal-overlay">
      <div class="modal-box wide-modal">
        <h3>Delete Document</h3>
        <div class="comment-meta" v-if="deleteDoc">
          <strong>{{ deleteDoc.doc_type }}</strong> · {{ deleteDoc.file_path }}
        </div>
        <div class="msg bad">This will remove the file from the system. Approved documents cannot be deleted.</div>
        <div v-if="deleteMsg" class="msg" :class="{ ok: deleteOk, bad: !deleteOk }">{{ deleteMsg }}</div>
        <div class="modal-actions">
          <button class="btn-cancel" @click="closeDelete" :disabled="deleting">Cancel</button>
          <button class="btn-danger" @click="doDelete" :disabled="deleting || !deleteDoc">
            {{ deleting ? 'Deleting...' : 'Delete' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api/client'
import { docTypeLabel, fileFormatLabel, statusLabel, statusPillClass } from '../utils/docDisplay'

const router = useRouter()
const user = ref({})

const loading = ref(true)
const documents = ref([])

const admissionFile = ref(null)
const mpFormFile = ref(null)
const thesisFile = ref(null)
const researchMethodFile = ref(null)
const uploadingAdmission = ref(false)
const uploadingMpForm = ref(false)
const uploadingThesis = ref(false)
const uploadingResearchMethod = ref(false)

const message = ref('')
const messageOk = ref(true)

const showComments = ref(false)
const activeDoc = ref(null)
const comments = ref([])
const commentsLoading = ref(false)
const newComment = ref('')
const commentPosting = ref(false)
const commentMsg = ref('')
const commentOk = ref(true)
const term = ref({ term_number: 1, unlocks: { term2: false, term3: false } })

const thesisUploadUnlocked = computed(() => {
  const n = Number(term.value?.term_number || 1)
  return n >= 3 && n <= 4
})

const researchMethodUploadUnlocked = computed(() => {
  const n = Number(term.value?.term_number || 1)
  return n >= 3
})

const onAdmissionFileChange = (e) => {
  admissionFile.value = e?.target?.files?.[0] ?? null
}

const onMpFormFileChange = (e) => {
  mpFormFile.value = e?.target?.files?.[0] ?? null
}

const onThesisFileChange = (e) => {
  if (!thesisUploadUnlocked.value) {
    thesisFile.value = null
    return
  }
  thesisFile.value = e?.target?.files?.[0] ?? null
}

const onResearchMethodFileChange = (e) => {
  if (!researchMethodUploadUnlocked.value) {
    researchMethodFile.value = null
    return
  }
  researchMethodFile.value = e?.target?.files?.[0] ?? null
}

const fetchDocs = async () => {
  loading.value = true
  try {
    const res = await api.get('get_status.php')
    if (res.data.status === 'success') {
      documents.value = res.data.documents || []
      term.value = res.data.term || term.value
    }
  } finally {
    loading.value = false
  }
}

const uploadThesis = async () => {
  if (!thesisFile.value) return
  uploadingThesis.value = true
  message.value = ''
  try {
    const form = new FormData()
    form.append('file', thesisFile.value)
    const res = await api.post('upload_thesis_project.php', form)
    if (res.data.status === 'success') {
      messageOk.value = true
      message.value = 'Thesis/Project file uploaded. Waiting for review.'
      thesisFile.value = null
      await fetchDocs()
    } else {
      messageOk.value = false
      message.value = res.data.message || 'Upload failed'
    }
  } catch (e) {
    messageOk.value = false
    message.value = e?.response?.data?.message || 'Upload failed'
  } finally {
    uploadingThesis.value = false
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

const uploadResearchMethodProof = async () => {
  if (!researchMethodFile.value) return
  uploadingResearchMethod.value = true
  message.value = ''
  try {
    const form = new FormData()
    form.append('file', researchMethodFile.value)
    const res = await api.post('upload_research_method_proof.php', form)
    if (res.data.status === 'success') {
      messageOk.value = true
      message.value = res.data.message || 'Research Method proof uploaded.'
      researchMethodFile.value = null
      await fetchDocs()
    } else {
      messageOk.value = false
      message.value = res.data.message || 'Upload failed'
    }
  } catch (e) {
    messageOk.value = false
    message.value = e?.response?.data?.message || 'Upload failed'
  } finally {
    uploadingResearchMethod.value = false
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
    messageOk.value = false
    message.value = e?.response?.data?.message || 'Failed to open document.'
  }
}

const openComments = async (doc) => {
  activeDoc.value = doc
  showComments.value = true
  newComment.value = ''
  commentMsg.value = ''
  comments.value = []
  commentsLoading.value = true
  try {
    await fetchComments(doc.doc_id)
  } finally {
    commentsLoading.value = false
  }
}

const closeComments = () => {
  showComments.value = false
  activeDoc.value = null
  comments.value = []
  newComment.value = ''
  commentMsg.value = ''
}

const postComment = async () => {
  if (!activeDoc.value) return
  commentPosting.value = true
  commentMsg.value = ''
  commentOk.value = true
  try {
    const res = await api.post('document_comments_add.php', { doc_id: activeDoc.value.doc_id, comment: newComment.value })
    if (res.data?.status === 'success') {
      commentOk.value = true
      commentMsg.value = 'Posted.'
      newComment.value = ''
      await fetchComments(activeDoc.value.doc_id)
    } else {
      commentOk.value = false
      commentMsg.value = res.data?.message || 'Failed to post'
    }
  } catch (e) {
    commentOk.value = false
    commentMsg.value = e?.response?.data?.message || 'Failed to post'
  } finally {
    commentPosting.value = false
  }
}

const fetchComments = async (docId) => {
  const res = await api.get(`document_comments_list.php?doc_id=${docId}`)
  if (res.data?.status === 'success') comments.value = res.data.data || []
}

const canModify = (d) => String(d?.status || '').toLowerCase() !== 'approved'

// Replace/Delete modals
const showReplace = ref(false)
const replaceDoc = ref(null)
const replaceFile = ref(null)
const replacing = ref(false)
const replaceMsg = ref('')
const replaceOk = ref(true)

const showDelete = ref(false)
const deleteDoc = ref(null)
const deleting = ref(false)
const deleteMsg = ref('')
const deleteOk = ref(true)

const replaceAccept = computed(() => {
  const t = String(replaceDoc.value?.doc_type || '')
  if (t === 'thesis_project') return '.pdf'
  return '.pdf,.jpg,.jpeg,.png'
})

const openReplace = (d) => {
  replaceDoc.value = d
  replaceFile.value = null
  replaceMsg.value = ''
  replaceOk.value = true
  showReplace.value = true
}

const closeReplace = () => {
  showReplace.value = false
  replaceDoc.value = null
  replaceFile.value = null
  replaceMsg.value = ''
}

const onReplaceFileChange = (e) => {
  replaceFile.value = e?.target?.files?.[0] ?? null
}

const doReplace = async () => {
  if (!replaceDoc.value || !replaceFile.value) return
  replacing.value = true
  replaceMsg.value = ''
  replaceOk.value = true
  try {
    const form = new FormData()
    form.append('doc_id', String(replaceDoc.value.doc_id))
    form.append('file', replaceFile.value)
    const res = await api.post('student_replace_document.php', form)
    if (res.data?.status === 'success') {
      replaceOk.value = true
      replaceMsg.value = res.data?.message || 'Replaced.'
      messageOk.value = true
      message.value = replaceMsg.value
      await fetchDocs()
      closeReplace()
    } else {
      replaceOk.value = false
      replaceMsg.value = res.data?.message || 'Replace failed'
    }
  } catch (e) {
    replaceOk.value = false
    replaceMsg.value = e?.response?.data?.message || 'Replace failed'
  } finally {
    replacing.value = false
  }
}

const openDelete = (d) => {
  deleteDoc.value = d
  deleteMsg.value = ''
  deleteOk.value = true
  showDelete.value = true
}

const closeDelete = () => {
  showDelete.value = false
  deleteDoc.value = null
  deleteMsg.value = ''
}

const doDelete = async () => {
  if (!deleteDoc.value) return
  deleting.value = true
  deleteMsg.value = ''
  deleteOk.value = true
  try {
    const res = await api.post('student_delete_document.php', { doc_id: deleteDoc.value.doc_id })
    if (res.data?.status === 'success') {
      deleteOk.value = true
      deleteMsg.value = res.data?.message || 'Deleted.'
      messageOk.value = true
      message.value = deleteMsg.value
      await fetchDocs()
      closeDelete()
    } else {
      deleteOk.value = false
      deleteMsg.value = res.data?.message || 'Delete failed'
    }
  } catch (e) {
    deleteOk.value = false
    deleteMsg.value = e?.response?.data?.message || 'Delete failed'
  } finally {
    deleting.value = false
  }
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
.btn-danger {
  background: #dc3545;
  color: white;
  border: none;
  padding: 8px 12px;
  border-radius: 6px;
  cursor: pointer;
}
.btn-danger:disabled {
  background: #f1aeb5;
  cursor: not-allowed;
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
  margin-top: 0;
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
.wide-modal {
  max-width: 760px;
}
.modal-box textarea {
  width: 100%;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 10px 12px;
  resize: vertical;
  font-family: inherit;
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
  gap: 10px;
}
.btn-cancel {
  background: #fff;
  border: 1px solid #ccc;
  padding: 8px 15px;
  cursor: pointer;
}
</style>
