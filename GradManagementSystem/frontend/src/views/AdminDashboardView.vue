<template>
  <div class="dashboard-layout">
    <header class="navbar">
      <div class="brand">Grad System (Admin Portal)</div>
      <div class="user-info">
        <span v-if="user.username">Admin: {{ user.username }}</span>
        <button @click="refreshAll" class="btn-refresh">Refresh</button>
        <button @click="logout" class="btn-logout">Logout</button>
      </div>
    </header>

    <div class="main-container">
      <aside class="sidebar">
        <nav>
          <ul>
            <li :class="{ active: activeSection === 'holds' }" @click="activeSection = 'holds'">Active Holds</li>
            <li :class="{ active: activeSection === 'docs' }" @click="activeSection = 'docs'">Pending Documents</li>
            <li :class="{ active: activeSection === 'defense' }" @click="activeSection = 'defense'">Defense Dates</li>
            <li :class="{ active: activeSection === 'thesis' }" @click="activeSection = 'thesis'">Thesis / Project</li>
            <li :class="{ active: activeSection === 'users' }" @click="activeSection = 'users'">User Management</li>
          </ul>
        </nav>
      </aside>

      <main class="content">
        <div v-if="error" class="card mb-30 error-card">
          {{ error }}
        </div>
        <div v-if="flashMsg" class="msg" :class="{ ok: flashOk, bad: !flashOk }">{{ flashMsg }}</div>

        <div v-if="activeSection === 'holds'" class="card mb-30">
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
              <div>{{ h.student_username || `#${h.student_id}` }}</div>
              <div><span class="doc-source-pill">{{ docTypeLabel(h.hold_type) }}</span></div>
              <div>{{ h.term_code || '-' }}</div>
              <div class="actions">
                <button class="btn-approve" @click="liftHold(h)">Lift Hold</button>
              </div>
            </div>
          </div>
        </div>

        <div v-else-if="activeSection === 'docs'" class="card">
          <h2>Pending Documents</h2>
          <div v-if="loading" class="loading-text">Loading...</div>
          <div v-else-if="pendingDocs.length === 0" class="empty-state">No pending documents.</div>
          <div v-else class="review-list">
            <div v-for="d in pendingDocs" :key="d.doc_id" class="review-item">
              <div class="info">
                <span class="student-name">{{ d.student_username || `#${d.student_id}` }}</span>
                <div class="doc-meta-row">
                  <span class="doc-source-pill">{{ docTypeLabel(d.doc_type) }}</span>
                  <span class="doc-format-pill">{{ fileFormatLabel(d.file_path) }}</span>
                  <span class="doc-status-pill" :class="statusPillClass(d.status)">{{ statusLabel(d.status) }}</span>
                  <span v-if="d.upload_date" class="muted">· {{ d.upload_date }}</span>
                </div>
              </div>
              <div class="actions">
                <button class="btn-view" @click="openDoc(d)">View</button>
                <button @click="reviewDoc(d, 'reject')" class="btn-reject">Reject</button>
                <button @click="reviewDoc(d, 'approve')" class="btn-approve">Approve</button>
              </div>
            </div>
          </div>
        </div>

        <div v-else-if="activeSection === 'defense'" class="stack">
          <div class="card mb-30">
            <h2>Defense Dates (Admin)</h2>
            <p class="subtitle">
              Publish a defense date window per year. Students must pick a defense date within the window.
            </p>

            <div v-if="defenseMsg" class="msg" :class="{ ok: defenseOk, bad: !defenseOk }">{{ defenseMsg }}</div>

            <div class="form-row">
              <div class="form-group">
                <label>Year</label>
                <input v-model="defenseForm.year" type="number" min="2000" max="2100" />
              </div>
              <div class="form-group">
                <label>Start Date</label>
                <EnglishDatePicker v-model="defenseForm.start_date" />
              </div>
              <div class="form-group">
                <label>End Date</label>
                <EnglishDatePicker v-model="defenseForm.end_date" />
              </div>
              <div class="form-group" style="align-self: end">
                <button class="btn-approve" @click="saveDefenseWindow" :disabled="defenseBusy">
                  {{ defenseBusy ? 'Saving...' : 'Save' }}
                </button>
              </div>
            </div>

            <div v-if="defenseLoading" class="loading-text">Loading windows...</div>
            <div v-else-if="defenseWindows.length === 0" class="empty-state">No windows.</div>
            <div v-else class="table mt-20">
              <div class="row header">
                <div>Year</div>
                <div>Start</div>
                <div>End</div>
                <div></div>
              </div>
              <div v-for="w in defenseWindows" :key="w.year" class="row">
                <div><strong>{{ w.year }}</strong></div>
                <div>{{ w.start_date }}</div>
                <div>{{ w.end_date }}</div>
                <div class="actions">
                  <button class="btn-view" @click="prefillDefenseWindow(w)" :disabled="defenseBusy">Edit</button>
                  <button class="btn-reject" @click="deleteDefenseWindow(w)" :disabled="defenseBusy">Delete</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-else-if="activeSection === 'thesis'" class="card mt-30">
          <h2>Student Thesis / Project Timeline</h2>

          <div class="form-row" style="margin: 10px 0 6px">
            <input v-model="thesisSearch" class="select" type="text" placeholder="Search students..." />
            <button class="btn-view" @click="fetchThesisSchedules" :disabled="thesisLoading">Refresh</button>
          </div>

          <div v-if="thesisLoading" class="loading-text">Loading...</div>
          <div v-else-if="filteredThesisSchedules.length === 0" class="empty-state">No data.</div>

          <div v-else class="table mt-20">
            <div class="row header thesis-row">
              <div>Student</div>
              <div>Term</div>
              <div>Type</div>
              <div>Submission</div>
              <div>Defense</div>
              <div>Title</div>
            </div>
            <div v-for="r in filteredThesisSchedules" :key="r.student_id" class="row thesis-row">
              <div>
                <strong>{{ r.username || `#${r.student_id}` }}</strong>
              </div>
              <div>
                <span v-if="r.term_number">Term {{ r.term_number }}</span>
                <span v-else>-</span>
              </div>
              <div>{{ r.type || '-' }}</div>
              <div>{{ r.submission_date || '-' }}</div>
              <div>{{ r.defense_date || '-' }}</div>
              <div>{{ r.title || '-' }}</div>
            </div>
          </div>
        </div>

        <div v-else class="card mt-30">
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
                  <label>Major / Program</label>
                  <select v-model="newUser.major_code">
                    <option v-for="m in majors" :key="m.major_code" :value="m.major_code">
                      {{ m.major_name }}
                    </option>
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
	              <div>Major</div>
	              <div>Email</div>
	              <div>Entry Date</div>
	              <div>Entry Term</div>
	              <div></div>
	            </div>
	            <div v-for="u in users" :key="u.user_id" class="row users-row">
	              <div>{{ u.username }}</div>
	              <div>{{ u.role }}</div>
	              <div>{{ u.major_name || u.major_code || 'CS' }}</div>
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
        <div v-if="editMsg" class="inline-msg" :class="{ ok: editMsgType === 'ok', err: editMsgType === 'err' }">
          {{ editMsg }}
        </div>
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
            <label>Major / Program</label>
            <select v-model="editUser.major_code">
              <option v-for="m in majors" :key="m.major_code" :value="m.major_code">
                {{ m.major_name }}
              </option>
            </select>
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
import { ref, onMounted, watch, computed, reactive } from 'vue'
import { useRouter } from 'vue-router'
import api, { apiBaseURL } from '../api/client'
import { docTypeLabel, fileFormatLabel, statusLabel, statusPillClass } from '../utils/docDisplay'
import EnglishDatePicker from '../components/EnglishDatePicker.vue'

const router = useRouter()

const user = ref({})
const activeSection = ref('holds')
const loading = ref(true)
const holds = ref([])
const pendingDocs = ref([])
const error = ref('')
const flashMsg = ref('')
const flashOk = ref(true)

const usersLoading = ref(false)
const users = ref([])
const majors = ref([{ major_code: 'CS', major_name: 'Computer Science' }])
const newUser = ref({
  role: 'student',
  major_code: 'CS',
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
const editMsg = ref('')
const editMsgType = ref('') // ok | err

// Defense windows (admin)
const defenseLoading = ref(false)
const defenseBusy = ref(false)
const defenseWindows = ref([])
const defenseForm = reactive({ year: String(new Date().getFullYear()), start_date: '', end_date: '' })
const defenseMsg = ref('')
const defenseOk = ref(true)

// Thesis schedules (admin)
const thesisLoading = ref(false)
const thesisSchedules = ref([])
const thesisSearch = ref('')

const docUrl = (doc) => `${apiBaseURL}/download_document.php?doc_id=${doc.doc_id}`
const holdKey = (h) => `${h.student_id}-${h.hold_type}-${h.term_code || ''}`

const setFlash = (ok, msg) => {
  flashOk.value = Boolean(ok)
  flashMsg.value = String(msg || '')
}

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
        setFlash(false, `Failed to open document (${status || 'network'}): ${text || 'Forbidden/Not logged in'}`)
        return
      }
    } catch {}
    setFlash(false, `Failed to open document (${status || 'network'}).`)
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

const fetchDefenseWindows = async () => {
  defenseLoading.value = true
  try {
    const res = await api.get('defense_windows_list.php')
    if (res.data?.status === 'success') defenseWindows.value = res.data.data || []
  } catch (e) {
    // ignore
  } finally {
    defenseLoading.value = false
  }
}

const prefillDefenseWindow = (w) => {
  defenseForm.year = String(w.year)
  defenseForm.start_date = w.start_date
  defenseForm.end_date = w.end_date
  defenseMsg.value = ''
}

const saveDefenseWindow = async () => {
  defenseMsg.value = ''
  defenseOk.value = true
  defenseBusy.value = true
  try {
    const payload = {
      year: Number(defenseForm.year),
      start_date: defenseForm.start_date,
      end_date: defenseForm.end_date,
    }
    const res = await api.post('admin_defense_windows_upsert.php', payload)
    if (res.data?.status === 'success') {
      defenseMsg.value = res.data?.message || 'Saved.'
      defenseOk.value = true
      await fetchDefenseWindows()
    } else {
      defenseMsg.value = res.data?.message || 'Save failed.'
      defenseOk.value = false
    }
  } catch (e) {
    defenseMsg.value = e?.response?.data?.message || 'Save failed.'
    defenseOk.value = false
  } finally {
    defenseBusy.value = false
  }
}

const deleteDefenseWindow = async (w) => {
  defenseMsg.value = ''
  defenseOk.value = true
  defenseBusy.value = true
  try {
    const res = await api.post('admin_defense_windows_delete.php', { year: Number(w.year) })
    if (res.data?.status === 'success') {
      defenseMsg.value = res.data?.message || 'Deleted.'
      defenseOk.value = true
      await fetchDefenseWindows()
    } else {
      defenseMsg.value = res.data?.message || 'Delete failed.'
      defenseOk.value = false
    }
  } catch (e) {
    defenseMsg.value = e?.response?.data?.message || 'Delete failed.'
    defenseOk.value = false
  } finally {
    defenseBusy.value = false
  }
}

const fetchThesisSchedules = async () => {
  thesisLoading.value = true
  try {
    const res = await api.get('admin_get_thesis_projects.php')
    if (res.data?.status === 'success') thesisSchedules.value = res.data.data || []
  } catch (e) {
    // ignore
  } finally {
    thesisLoading.value = false
  }
}

const filteredThesisSchedules = computed(() => {
  const q = (thesisSearch.value || '').trim().toLowerCase()
  const rows = thesisSchedules.value || []
  if (!q) return rows
  return rows.filter((r) => {
    const fields = [r.student_id, r.username, r.email, r.first_name, r.last_name, r.type, r.title, r.submission_date, r.defense_date]
      .map((x) => String(x || '').toLowerCase())
      .join(' ')
    return fields.includes(q)
  })
})

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
    else setFlash(false, res.data?.message || 'Failed to load users')
  } catch (e) {
    setFlash(false, e?.response?.data?.message || 'Failed to load users')
  } finally {
    usersLoading.value = false
  }
}

const fetchMajors = async () => {
  try {
    const res = await api.get('majors_list.php')
    if (res.data?.status === 'success' && Array.isArray(res.data.data) && res.data.data.length > 0) {
      majors.value = res.data.data
      if (!majors.value.some((m) => m.major_code === newUser.value.major_code)) {
        newUser.value.major_code = majors.value[0].major_code
      }
    }
  } catch {
    // keep fallback
  }
}

const createUser = async () => {
  if (!newUser.value.username || !newUser.value.password) {
    setFlash(false, 'Username and password are required.')
    return
  }
  if (newUser.value.password.length < 6) {
    setFlash(false, 'Password must be at least 6 characters.')
    return
  }

  usersLoading.value = true
  try {
    const res = await api.post('admin_users_create.php', newUser.value)
    if (res.data?.status === 'success') {
      setFlash(true, res.data?.message || 'User created.')
      newUser.value.entry_date = todayISO()
      newUser.value.username = ''
      newUser.value.password = ''
      newUser.value.email = ''
      newUser.value.first_name = ''
      newUser.value.last_name = ''
      if (!majors.value.some((m) => m.major_code === newUser.value.major_code)) {
        newUser.value.major_code = majors.value?.[0]?.major_code || 'CS'
      }
      newUser.value.term_code = termCodeFromDate(newUser.value.entry_date)
      await refreshUsers()
      await fetchDashboard()
    } else {
      setFlash(false, res.data?.message || 'Create failed')
    }
  } catch (e) {
    setFlash(false, e?.response?.data?.message || 'Create failed')
  } finally {
    usersLoading.value = false
  }
}

const deleteUser = async (u) => {
  if (u.role === 'admin') {
    setFlash(false, 'Deleting admin is disabled.')
    return
  }

  usersLoading.value = true
  try {
    const res = await api.post('admin_users_delete.php', { user_id: u.user_id })
    if (res.data?.status === 'success') {
      setFlash(true, res.data?.message || 'User deleted.')
      await refreshUsers()
      await fetchDashboard()
    } else {
      setFlash(false, res.data?.message || 'Delete failed')
    }
  } catch (e) {
    setFlash(false, e?.response?.data?.message || 'Delete failed')
  } finally {
    usersLoading.value = false
  }
}

const openEdit = (u) => {
  editMsg.value = ''
  editMsgType.value = ''
  editUser.value = {
    user_id: u.user_id,
    role: u.role,
    username: u.username,
    email: u.email || '',
    entry_date: u.entry_date || '',
    entry_term_code: u.entry_term_code || (u.entry_date ? termCodeFromDate(u.entry_date) : ''),
    major_code: u.major_code || 'CS',
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
  editMsg.value = ''
  editMsgType.value = ''
}

const saveEdit = async () => {
  if (!editUser.value) return
  usersLoading.value = true
  editMsg.value = ''
  editMsgType.value = ''
  try {
    const payload = {
      user_id: editUser.value.user_id,
      username: editUser.value.username,
      email: editUser.value.email,
      entry_date: editUser.value.entry_date,
      entry_term_code: editUser.value.entry_term_code,
      major_code: editUser.value.major_code,
    }
    const res = await api.post('admin_users_update.php', payload)
    if (res.data?.status === 'success') {
      closeEdit()
      await refreshUsers()
      await fetchDashboard()
    } else {
      editMsg.value = res.data?.message || 'Update failed'
      editMsgType.value = 'err'
    }
  } catch (e) {
    editMsg.value = e?.response?.data?.message || 'Update failed'
    editMsgType.value = 'err'
  } finally {
    usersLoading.value = false
  }
}

const reviewDoc = async (doc, action) => {
  const comment = action === 'reject' ? 'Rejected' : 'Approved'
  try {
    const res = await api.post('admin_review_document.php', {
      doc_id: doc.doc_id,
      action,
      comment,
    })
    if (res.data?.status === 'success') {
      const extra = res.data?.registrar_code ? ` Registrar code: ${res.data.registrar_code}` : ''
      setFlash(true, `${res.data?.message || 'Saved.'}${extra}`)
      await fetchDashboard()
    } else {
      setFlash(false, res.data?.message || 'Action failed')
    }
  } catch (e) {
    setFlash(false, e?.response?.data?.message || 'Network error')
  }
}

const liftHold = async (h) => {
  try {
    const res = await api.post('admin_lift_hold.php', {
      student_id: h.student_id,
      hold_type: h.hold_type,
      term_code: h.term_code || null,
    })
    if (res.data?.status === 'success') {
      const extra = res.data?.registrar_code ? ` Registrar code: ${res.data.registrar_code}` : ''
      setFlash(true, `${res.data?.message || 'Hold lifted.'}${extra}`)
      await fetchDashboard()
    } else {
      setFlash(false, res.data?.message || 'Lift failed')
    }
  } catch (e) {
    setFlash(false, e?.response?.data?.message || 'Network error')
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
  await Promise.all([fetchDashboard(), refreshUsers(), fetchDefenseWindows(), fetchThesisSchedules(), fetchMajors()])
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
.inline-msg {
  margin: 12px 0 16px;
  padding: 10px 12px;
  border-radius: 6px;
  font-size: 14px;
  border: 1px solid transparent;
}
.inline-msg.ok {
  background: #e8f5e9;
  border-color: #c8e6c9;
  color: #2e7d32;
}
.inline-msg.err {
  background: #ffebee;
  border-color: #ffcdd2;
  color: #c62828;
}
.card {
  background: white;
  padding: 2rem;
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
.subtitle {
  color: #555;
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
.muted {
  color: #6c757d;
  font-size: 12px;
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
.row.thesis-row {
  grid-template-columns: 1.6fr 0.7fr 0.8fr 1fr 1fr 1.6fr;
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
  grid-template-columns: 2fr 0.8fr 1.4fr 2fr 1.2fr 1fr 1fr;
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
