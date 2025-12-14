<template>
  <div class="dashboard-layout">
    <header class="navbar admin-nav">
      <div class="brand">Grad System (Faculty Portal)</div>
      <div class="user-info">
        <span v-if="user.username">Prof. {{ user.username }}</span>
        <button @click="logout" class="btn-logout">Logout</button>
      </div>
    </header>

    <div class="main-container">
      <main class="content">
        <div class="card mb-30">
          <h2>Pending Document Reviews</h2>
          <div v-if="loading" class="loading-text">Loading...</div>
          <div v-else-if="requests.length === 0" class="empty-state">No pending admission reviews.</div>
          <div v-else class="review-list">
            <div v-for="req in requests" :key="req.doc_id" class="review-item">
              <div class="info">
                <span class="student-name">Student ID: {{ req.student_id }}</span>
                <span class="file-link">{{ req.doc_type }} · {{ req.file_path }}</span>
              </div>
              <div class="actions">
                <a :href="docUrl(req)" target="_blank" class="btn-view">View</a>
                <button @click="openRejectModal(req)" class="btn-reject">Reject</button>
                <button @click="openApproveModal(req)" class="btn-approve">Approve</button>
              </div>
            </div>
          </div>
        </div>

        <div class="card mb-30">
          <h2>My Advisee Documents</h2>
          <p class="subtitle">Documents submitted by students who selected you as their Major Professor.</p>

          <div v-if="adviseeDocs.length === 0" class="empty-state">No advisee documents.</div>
          <div v-else class="review-list">
            <div v-for="doc in adviseeDocs" :key="doc.doc_id" class="review-item">
              <div class="info">
                <span class="student-name">{{ doc.student_username || `#${doc.student_id}` }} (#{{ doc.student_id }})</span>
                <span class="file-link">{{ doc.doc_type }} · {{ doc.file_path }} · {{ doc.status }}</span>
              </div>
              <div class="actions">
                <a :href="docUrl(doc)" target="_blank" class="btn-view">View</a>
                <button class="btn-view" @click="openComments(doc)">Comments</button>
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <h2>Major Professor Requests</h2>
          <p class="subtitle">Students requesting you as their advisor.</p>

          <div v-if="mpRequests.length === 0" class="empty-state">No new advising requests.</div>
          <div v-else class="review-list">
            <div v-for="stu in mpRequests" :key="stu.student_id" class="review-item highlight-item">
              <div class="info">
                <span class="student-name">{{ displayStudentName(stu) }} (#{{ stu.student_id }})</span>
                <span class="email-text">{{ stu.email }}</span>
              </div>
              <div class="actions">
                <button @click="respondMP(stu, 'reject')" class="btn-reject">Decline</button>
                <button @click="respondMP(stu, 'accept')" class="btn-approve">Accept Student</button>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>

    <div v-if="showCommentsModal" class="modal-overlay">
      <div class="modal-box wide-modal">
        <h3>Document Comments</h3>
        <div class="comment-meta" v-if="activeDoc">
          <div>
            <strong>{{ activeDoc.student_username || `#${activeDoc.student_id}` }}</strong>
            <span> · {{ activeDoc.doc_type }} · doc_id={{ activeDoc.doc_id }}</span>
          </div>
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

        <textarea v-model="newComment" placeholder="Write a comment..." rows="4"></textarea>
        <div class="modal-actions">
          <button @click="closeComments" class="btn-cancel">Close</button>
          <button @click="submitComment" class="btn-confirm-approve" :disabled="commentSubmitting || !newComment.trim()">
            {{ commentSubmitting ? 'Posting...' : 'Post Comment' }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="showRejectModal" class="modal-overlay">
      <div class="modal-box">
        <h3>Reject Document</h3>
        <textarea v-model="rejectReason" placeholder="Reason..." rows="4"></textarea>
        <div class="modal-actions">
          <button @click="closeModals" class="btn-cancel">Cancel</button>
          <button @click="confirmReject" class="btn-confirm-reject">Confirm</button>
        </div>
      </div>
    </div>

    <div v-if="showApproveModal" class="modal-overlay">
      <div class="modal-box wide-modal">
        <h3>Approve & Assign Deficiencies</h3>
        <div class="course-grid">
          <label v-for="course in courseList" :key="course.course_code" class="course-item">
            <input type="checkbox" :value="course.course_code" v-model="selectedCourses" />
            <span class="code">{{ course.course_code }}</span>
            <span class="name">{{ course.course_name }}</span>
          </label>
        </div>
        <div class="modal-actions">
          <button @click="closeModals" class="btn-cancel">Cancel</button>
          <button @click="confirmApprove" class="btn-confirm-approve">Confirm</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api, { apiBaseURL } from '../api/client'

const router = useRouter()
const user = ref({})
const requests = ref([])
const mpRequests = ref([])
const loading = ref(true)
const adviseeDocs = ref([])

const showRejectModal = ref(false)
const showApproveModal = ref(false)
const targetReq = ref(null)
const rejectReason = ref('')
const courseList = ref([])
const selectedCourses = ref([])

const showCommentsModal = ref(false)
const activeDoc = ref(null)
const comments = ref([])
const commentsLoading = ref(false)
const newComment = ref('')
const commentSubmitting = ref(false)

onMounted(() => {
  const storedUser = localStorage.getItem('user')
  if (storedUser) {
    user.value = JSON.parse(storedUser)
    if (user.value.role !== 'faculty') return router.push('/dashboard')
    fetchPendingReviews()
    fetchMPRequests()
    fetchCourses()
    fetchAdviseeDocs()
  } else {
    router.push('/')
  }
})

const docUrl = (req) => `${apiBaseURL}/download_document.php?doc_id=${req.doc_id}`

const fetchPendingReviews = async () => {
  try {
    const res = await api.get('faculty_get_pending.php')
    if (res.data.status === 'success') requests.value = res.data.data
  } finally {
    loading.value = false
  }
}

const fetchMPRequests = async () => {
  try {
    const res = await api.get('faculty_get_mp_requests.php')
    if (res.data.status === 'success') mpRequests.value = res.data.data
  } catch (e) {
    console.error(e)
  }
}

const fetchAdviseeDocs = async () => {
  try {
    const res = await api.get('faculty_get_advisee_documents.php')
    if (res.data.status === 'success') adviseeDocs.value = res.data.data || []
  } catch (e) {
    console.error(e)
  }
}

const displayStudentName = (stu) => {
  const name = `${stu.first_name || ''} ${stu.last_name || ''}`.trim()
  return name || stu.student_username || `Student`
}

const respondMP = async (stu, action) => {
  if (!confirm(`Are you sure you want to ${action.toUpperCase()} ${displayStudentName(stu)}?`)) return
  try {
    const res = await api.post('faculty_respond_mp.php', { student_id: stu.student_id, action })
    if (res.data.status === 'success') {
      alert('Success: ' + res.data.message)
      fetchMPRequests()
    } else {
      alert(res.data.message || 'Failed')
    }
  } catch (e) {
    alert('Network Error')
  }
}

const openComments = async (doc) => {
  activeDoc.value = doc
  showCommentsModal.value = true
  newComment.value = ''
  comments.value = []
  commentsLoading.value = true
  try {
    const res = await api.get(`document_comments_list.php?doc_id=${doc.doc_id}`)
    if (res.data.status === 'success') comments.value = res.data.data || []
    else alert(res.data.message || 'Failed to load comments')
  } catch (e) {
    alert(e?.response?.data?.message || 'Failed to load comments')
  } finally {
    commentsLoading.value = false
  }
}

const closeComments = () => {
  showCommentsModal.value = false
  activeDoc.value = null
  comments.value = []
  newComment.value = ''
}

const submitComment = async () => {
  if (!activeDoc.value) return
  commentSubmitting.value = true
  try {
    const res = await api.post('document_comments_add.php', {
      doc_id: activeDoc.value.doc_id,
      comment: newComment.value,
    })
    if (res.data.status === 'success') {
      newComment.value = ''
      await openComments(activeDoc.value)
    } else {
      alert(res.data.message || 'Failed to post comment')
    }
  } catch (e) {
    alert(e?.response?.data?.message || 'Failed to post comment')
  } finally {
    commentSubmitting.value = false
  }
}

const fetchCourses = async () => {
  try {
    const res = await api.get('get_courses.php')
    if (res.data.status === 'success') courseList.value = res.data.data
  } catch {}
}

const openRejectModal = (req) => {
  targetReq.value = req
  rejectReason.value = ''
  showRejectModal.value = true
}

const openApproveModal = (req) => {
  targetReq.value = req
  selectedCourses.value = []
  showApproveModal.value = true
}

const closeModals = () => {
  showRejectModal.value = false
  showApproveModal.value = false
}

const confirmReject = async () => {
  if (!targetReq.value) return
  try {
    const res = await api.post('faculty_review.php', {
      doc_id: targetReq.value.doc_id,
      action: 'reject',
      comment: rejectReason.value,
    })
    if (res.data.status === 'success') {
      if (res.data.registrar_code) {
        alert('Registrar Code: ' + res.data.registrar_code)
      }
      closeModals()
      fetchPendingReviews()
    } else {
      alert(res.data.message || 'Reject failed')
    }
  } catch {}
}

const confirmApprove = async () => {
  if (!targetReq.value) return
  try {
    await api.post('assign_deficiency.php', {
      student_id: targetReq.value.student_id,
      courses: selectedCourses.value,
    })
    const res = await api.post('faculty_review.php', {
      doc_id: targetReq.value.doc_id,
      action: 'approve',
      comment: 'Approved',
    })
    if (res.data.status === 'success') {
      if (res.data.registrar_code) {
        alert('Registrar Code: ' + res.data.registrar_code)
      }
      closeModals()
      fetchPendingReviews()
    } else {
      alert(res.data.message || 'Approve failed')
    }
  } catch (e) {
    alert('Network Error')
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
  background-color: #f0f2f5;
}
.admin-nav {
  background-color: #2c3e50;
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
  padding: 40px;
  overflow-y: auto;
  display: flex;
  justify-content: center;
}
.content {
  width: 100%;
  max-width: 1000px;
}
.card {
  background: white;
  padding: 30px;
  border-radius: 8px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  margin-bottom: 30px;
}
.review-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border: 1px solid #eee;
  border-radius: 8px;
  margin-bottom: 15px;
  background: white;
}
.highlight-item {
  border-left: 5px solid #003366;
  background-color: #fdfdfe;
}
.info {
  display: flex;
  flex-direction: column;
  gap: 5px;
}
.student-name {
  font-weight: bold;
  font-size: 1.1rem;
}
.actions {
  display: flex;
  gap: 10px;
}
.btn-view,
.btn-reject,
.btn-approve {
  border: none;
  padding: 8px 15px;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 600;
  text-decoration: none;
  color: white;
}
.btn-view {
  background: #6c757d;
}
.btn-reject {
  background-color: #dc3545;
}
.btn-approve {
  background-color: #28a745;
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
  max-width: 500px;
  padding: 25px;
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  gap: 15px;
}
.wide-modal {
  max-width: 600px;
}
.course-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
  max-height: 300px;
  overflow-y: auto;
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
.btn-confirm-reject,
.btn-confirm-approve {
  color: white;
  border: none;
  padding: 8px 15px;
  cursor: pointer;
}
.btn-confirm-reject {
  background: #dc3545;
}
.btn-confirm-approve {
  background: #28a745;
}
textarea {
  width: 100%;
  padding: 10px;
  border: 1px solid #ccc;
}

.comment-list {
  border: 1px solid #eee;
  border-radius: 8px;
  padding: 12px;
  max-height: 260px;
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
.comment-meta {
  color: #374151;
  font-size: 14px;
}
</style>
