<template>
  <div class="dashboard-layout">
    <header class="navbar admin-nav">
      <div class="brand">Grad System (Admin Portal)</div>
      <div class="user-info">
        <span v-if="user.username">Admin: {{ user.username }}</span>
        <button @click="refreshAll" class="btn-refresh">Refresh</button>
        <button @click="logout" class="btn-logout">Logout</button>
      </div>
    </header>

    <div class="main-container">
      <main class="content">
        <div v-if="error" class="card mb-30 error-card">
          {{ error }}
        </div>

        <div class="card mb-30">
          <h2>Active Holds</h2>
          <div v-if="loading" class="loading-text">Loading...</div>
          <div v-else-if="holds.length === 0" class="empty-state">No active holds.</div>
          <div v-else class="table">
            <div class="row header">
              <div>Student</div>
              <div>Hold Type</div>
              <div>Term</div>
              <div></div>
            </div>
            <div v-for="h in holds" :key="h.hold_id || holdKey(h)" class="row">
              <div>{{ h.student_username || `#${h.student_id}` }} (#{{ h.student_id }})</div>
              <div>{{ h.hold_type }}</div>
              <div>{{ h.term_code || '-' }}</div>
              <div class="actions">
                <button class="btn-approve" @click="liftHold(h)">Lift Hold</button>
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <h2>Pending Documents</h2>
          <div v-if="loading" class="loading-text">Loading...</div>
          <div v-else-if="pendingDocs.length === 0" class="empty-state">No pending documents.</div>
          <div v-else class="review-list">
            <div v-for="d in pendingDocs" :key="d.doc_id" class="review-item">
              <div class="info">
                <span class="student-name">{{ d.student_username || `#${d.student_id}` }} (#{{ d.student_id }})</span>
                <span class="file-link">{{ d.doc_type }} · {{ d.file_path }}</span>
              </div>
              <div class="actions">
                <button class="btn-view" @click="openDoc(d)">View</button>
                <button @click="reviewDoc(d, 'reject')" class="btn-reject">Reject</button>
                <button @click="reviewDoc(d, 'approve')" class="btn-approve">Approve</button>
              </div>
            </div>
          </div>
        </div>

        <div class="card mt-30">
          <h2>User Management</h2>

	          <div class="user-form">
	            <div class="form-row">
	              <div class="form-group">
	                <label>Role</label>
	                <select v-model="newUser.role">
	                  <option value="student">Student</option>
	                  <option value="faculty">Faculty</option>
	                </select>
	              </div>
	              <div class="form-group">
	                <label>Entry Date</label>
	                <input v-model="newUser.entry_date" type="date" />
	              </div>
	              <div class="form-group">
	                <label>Username</label>
	                <input v-model="newUser.username" type="text" placeholder="e.g. student2 / prof_jamil" />
	              </div>
	            </div>

	            <div class="form-row">
	              <div class="form-group">
	                <label>Password</label>
	                <input v-model="newUser.password" type="password" placeholder="At least 6 characters" />
	              </div>
	              <div class="form-group">
	                <label>Email (optional)</label>
	                <input v-model="newUser.email" type="email" placeholder="Optional" />
	              </div>
              <div class="form-group" v-if="newUser.role === 'student'">
                <label>First Name (optional)</label>
                <input v-model="newUser.first_name" type="text" placeholder="Optional" />
              </div>
              <div class="form-group" v-if="newUser.role === 'student'">
                <label>Last Name (optional)</label>
                <input v-model="newUser.last_name" type="text" placeholder="Optional" />
              </div>
	              <div class="form-group" v-if="newUser.role === 'student'">
	                <label>Entry Term Code (auto)</label>
	                <input v-model="newUser.term_code" type="text" disabled />
	              </div>
	            </div>

            <div class="form-actions">
              <button class="btn-view" @click="refreshUsers" :disabled="usersLoading">Refresh</button>
              <button class="btn-approve" @click="createUser" :disabled="usersLoading">Create User</button>
            </div>
          </div>

          <div v-if="usersLoading" class="loading-text">Loading users...</div>
          <div v-else-if="users.length === 0" class="empty-state">No users.</div>
	          <div v-else class="table mt-20">
	            <div class="row header users-header">
	              <div>Username</div>
	              <div>Role</div>
	              <div>Email</div>
	              <div>Entry Date</div>
	              <div>Entry Term</div>
	              <div></div>
	            </div>
	            <div v-for="u in users" :key="u.user_id" class="row users-row">
	              <div>{{ u.username }} (#{{ u.user_id }})</div>
	              <div>{{ u.role }}</div>
	              <div>{{ u.email || '-' }}</div>
	              <div>{{ u.entry_date || '-' }}</div>
	              <div>{{ u.entry_term_code || '-' }}</div>
	              <div class="actions">
	                <button class="btn-view" @click="openEdit(u)" :disabled="u.role === 'admin'">Edit</button>
	                <button class="btn-reject" @click="deleteUser(u)" :disabled="u.role === 'admin'">Delete</button>
	              </div>
	            </div>
	          </div>
	        </div>
	      </main>
	    </div>
	  </div>

	  <div v-if="showEditModal" class="modal-overlay">
	    <div class="modal-box wide-modal">
	      <h3>Edit Registration Info</h3>
	      <div v-if="editUser" class="edit-grid">
	        <div class="form-group">
	          <label>User ID</label>
	          <input :value="editUser.user_id" disabled />
	        </div>
	        <div class="form-group">
	          <label>Role</label>
	          <input :value="editUser.role" disabled />
	        </div>
	        <div class="form-group">
	          <label>Username</label>
	          <input v-model="editUser.username" />
	        </div>
	        <div class="form-group">
	          <label>Email</label>
	          <input v-model="editUser.email" type="email" />
	        </div>
	        <div class="form-group">
	          <label>Entry Date</label>
	          <input v-model="editUser.entry_date" type="date" />
	        </div>
	        <div class="form-group">
	          <label>Entry Term Code (auto)</label>
	          <input v-model="editUser.entry_term_code" disabled />
	        </div>
	      </div>

	      <div class="modal-actions">
	        <button class="btn-cancel" @click="closeEdit">Cancel</button>
	        <button class="btn-confirm-approve" @click="saveEdit" :disabled="usersLoading">Save</button>
	      </div>
	    </div>
	  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import api, { apiBaseURL } from '../api/client'

const router = useRouter()

const user = ref({})
const loading = ref(true)
const holds = ref([])
const pendingDocs = ref([])
const error = ref('')

const usersLoading = ref(false)
const users = ref([])
const newUser = ref({
  role: 'student',
  entry_date: '',
  username: '',
  password: '',
  email: '',
  first_name: '',
  last_name: '',
  term_code: '',
})

const showEditModal = ref(false)
const editUser = ref(null)

const docUrl = (doc) => `${apiBaseURL}/download_document.php?doc_id=${doc.doc_id}`
const holdKey = (h) => `${h.student_id}-${h.hold_type}-${h.term_code || ''}`

const openDoc = async (doc) => {
  try {
    const res = await api.get(`download_document.php?doc_id=${doc.doc_id}`, { responseType: 'blob' })
    const blob = res.data
    const url = URL.createObjectURL(blob)
    window.open(url, '_blank', 'noopener,noreferrer')
    setTimeout(() => URL.revokeObjectURL(url), 60_000)
  } catch (e) {
    const status = e?.response?.status
    const blob = e?.response?.data
    try {
      if (blob instanceof Blob) {
        const text = await blob.text()
        alert(`Failed to open document (${status || 'network'}): ${text || 'Forbidden/Not logged in'}`)
        return
      }
    } catch {}
    alert(`Failed to open document (${status || 'network'}).`)
  }
}

const fetchDashboard = async () => {
  loading.value = true
  error.value = ''
  try {
    const res = await api.get('admin_get_dashboard.php')
    if (res.data?.status === 'success') {
      holds.value = res.data.holds || []
      pendingDocs.value = res.data.pending_documents || []
    } else {
      error.value = res.data?.message || 'Failed to load admin dashboard.'
    }
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Failed to load admin dashboard.'
  } finally {
    loading.value = false
  }
}

const termCodeFromDate = (dateStr) => {
  const d = new Date(dateStr + 'T00:00:00')
  if (isNaN(d.getTime())) return ''
  const year = d.getFullYear()
  const month = d.getMonth() + 1
  if (month <= 4) return `${year}SP`
  if (month <= 8) return `${year}SU`
  return `${year}FA`
}

const todayISO = () => new Date().toISOString().slice(0, 10)
newUser.value.entry_date = todayISO()
newUser.value.term_code = termCodeFromDate(newUser.value.entry_date)

watch(
  () => newUser.value.entry_date,
  (v) => {
    newUser.value.term_code = termCodeFromDate(v || '')
  },
)

const refreshUsers = async () => {
  usersLoading.value = true
  try {
    const res = await api.get('admin_users_list.php')
    if (res.data?.status === 'success') users.value = res.data.data || []
    else alert(res.data?.message || 'Failed to load users')
  } catch (e) {
    alert(e?.response?.data?.message || 'Failed to load users')
  } finally {
    usersLoading.value = false
  }
}

const createUser = async () => {
  if (!newUser.value.username || !newUser.value.password) {
    return alert('Username and password are required.')
  }
  if (newUser.value.password.length < 6) {
    return alert('Password must be at least 6 characters.')
  }
  const ok = confirm(`Create ${newUser.value.role} user "${newUser.value.username}"?`)
  if (!ok) return

  usersLoading.value = true
  try {
    const res = await api.post('admin_users_create.php', newUser.value)
    if (res.data?.status === 'success') {
      newUser.value.entry_date = todayISO()
      newUser.value.username = ''
      newUser.value.password = ''
      newUser.value.email = ''
      newUser.value.first_name = ''
      newUser.value.last_name = ''
      newUser.value.term_code = termCodeFromDate(newUser.value.entry_date)
      await refreshUsers()
      await fetchDashboard()
    } else {
      alert(res.data?.message || 'Create failed')
    }
  } catch (e) {
    alert(e?.response?.data?.message || 'Create failed')
  } finally {
    usersLoading.value = false
  }
}

const deleteUser = async (u) => {
  if (u.role === 'admin') return alert('Deleting admin is disabled.')
  const ok = confirm(`Delete user "${u.username}" (#${u.user_id})? This will also delete related records.`)
  if (!ok) return

  usersLoading.value = true
  try {
    const res = await api.post('admin_users_delete.php', { user_id: u.user_id })
    if (res.data?.status === 'success') {
      await refreshUsers()
      await fetchDashboard()
    } else {
      alert(res.data?.message || 'Delete failed')
    }
  } catch (e) {
    alert(e?.response?.data?.message || 'Delete failed')
  } finally {
    usersLoading.value = false
  }
}

const openEdit = (u) => {
  editUser.value = {
    user_id: u.user_id,
    role: u.role,
    username: u.username,
    email: u.email || '',
    entry_date: u.entry_date || '',
    entry_term_code: u.entry_term_code || (u.entry_date ? termCodeFromDate(u.entry_date) : ''),
  }
  showEditModal.value = true
}

watch(
  () => editUser.value?.entry_date,
  (v) => {
    if (!editUser.value) return
    editUser.value.entry_term_code = termCodeFromDate(v || '')
  },
)

const closeEdit = () => {
  showEditModal.value = false
  editUser.value = null
}

const saveEdit = async () => {
  if (!editUser.value) return
  usersLoading.value = true
  try {
    const payload = {
      user_id: editUser.value.user_id,
      username: editUser.value.username,
      email: editUser.value.email,
      entry_date: editUser.value.entry_date,
      entry_term_code: editUser.value.entry_term_code,
    }
    const res = await api.post('admin_users_update.php', payload)
    if (res.data?.status === 'success') {
      closeEdit()
      await refreshUsers()
      await fetchDashboard()
    } else {
      alert(res.data?.message || 'Update failed')
    }
  } catch (e) {
    alert(e?.response?.data?.message || 'Update failed')
  } finally {
    usersLoading.value = false
  }
}

const reviewDoc = async (doc, action) => {
  const ok = confirm(`${action.toUpperCase()} this document? (${doc.doc_type} · student ${doc.student_id})`)
  if (!ok) return

  const comment = action === 'reject' ? prompt('Reason (optional):') : 'Approved'
  try {
    const res = await api.post('admin_review_document.php', {
      doc_id: doc.doc_id,
      action,
      comment,
    })
    if (res.data?.status === 'success') {
      if (res.data.registrar_code) {
        alert(`Registrar code generated:\n${res.data.registrar_code}`)
      }
      await fetchDashboard()
    } else {
      alert(res.data?.message || 'Action failed')
    }
  } catch (e) {
    const msg = e?.response?.data?.message || 'Network error'
    alert(msg)
  }
}

const liftHold = async (h) => {
  const ok = confirm(`Lift hold "${h.hold_type}" for student ${h.student_id}?`)
  if (!ok) return

  try {
    const res = await api.post('admin_lift_hold.php', {
      student_id: h.student_id,
      hold_type: h.hold_type,
      term_code: h.term_code || null,
    })
    if (res.data?.status === 'success') {
      if (res.data.registrar_code) {
        alert(`Registrar code generated:\n${res.data.registrar_code}`)
      }
      await fetchDashboard()
    } else {
      alert(res.data?.message || 'Lift failed')
    }
  } catch (e) {
    const msg = e?.response?.data?.message || 'Network error'
    alert(msg)
  }
}

const logout = async () => {
  try {
    await api.post('logout.php')
  } catch {}
  localStorage.removeItem('user')
  router.push('/')
}

const refreshAll = async () => {
  await Promise.all([fetchDashboard(), refreshUsers()])
}

onMounted(() => {
  const storedUser = localStorage.getItem('user')
  if (!storedUser) return router.push('/')
  user.value = JSON.parse(storedUser)
  if (user.value.role !== 'admin') return router.push(user.value.role === 'faculty' ? '/faculty' : '/dashboard')
  refreshAll()
})
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
  background-color: #1f2937;
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
.btn-refresh {
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.3);
  color: white;
  padding: 6px 16px;
  border-radius: 4px;
  cursor: pointer;
  margin-right: 8px;
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
  max-width: 1100px;
}
.card {
  background: white;
  padding: 30px;
  border-radius: 8px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}
.mb-30 {
  margin-bottom: 30px;
}
.mt-20 {
  margin-top: 20px;
}
.mt-30 {
  margin-top: 30px;
}
.empty-state {
  color: #666;
  font-style: italic;
}
.loading-text {
  color: #666;
}
.error-card {
  border: 1px solid #fecaca;
  background: #fff1f2;
  color: #991b1b;
}
.review-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 18px;
  border: 1px solid #eee;
  border-radius: 8px;
  margin-bottom: 12px;
  background: white;
}
.info {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.student-name {
  font-weight: bold;
}
.file-link {
  color: #555;
  font-size: 0.95rem;
}
.actions {
  display: flex;
  gap: 10px;
  align-items: center;
}
.btn-view,
.btn-reject,
.btn-approve {
  border: none;
  padding: 8px 14px;
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
  background-color: #16a34a;
}
.table {
  border: 1px solid #eee;
  border-radius: 8px;
  overflow: hidden;
}
.row {
  display: grid;
  grid-template-columns: 2fr 1.2fr 0.8fr 0.8fr;
  gap: 12px;
  padding: 12px 14px;
  border-bottom: 1px solid #eee;
  align-items: center;
}
.row:last-child {
  border-bottom: none;
}
.row.header {
  background: #f8fafc;
  font-weight: 700;
}

.user-form {
  border: 1px solid #eee;
  border-radius: 8px;
  padding: 16px;
  background: #fcfcfd;
}
.form-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  margin-bottom: 12px;
}
.form-group label {
  display: block;
  font-size: 12px;
  color: #374151;
  margin-bottom: 6px;
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
  display: flex;
  gap: 10px;
  justify-content: flex-end;
}
.users-header,
.users-row {
  grid-template-columns: 2fr 0.8fr 2fr 1.2fr 1fr 1fr;
}

.edit-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}
.edit-grid .form-group input {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
  box-sizing: border-box;
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
  max-width: 720px;
  padding: 22px;
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.wide-modal {
  max-width: 760px;
}
.btn-cancel {
  background: #fff;
  border: 1px solid #ccc;
  padding: 8px 15px;
  cursor: pointer;
}
.btn-confirm-approve {
  background-color: #16a34a;
  color: white;
  border: none;
  padding: 8px 15px;
  cursor: pointer;
}
</style>
