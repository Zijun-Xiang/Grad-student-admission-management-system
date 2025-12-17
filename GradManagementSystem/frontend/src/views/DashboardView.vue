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
              <li @click="router.push('/profile')">Profile</li>
	            <li v-if="termInfo.termNumber >= 2" @click="router.push('/major-professor')">Major Professor</li>
            <li v-if="termInfo.termNumber >= 3" @click="router.push('/thesis-project')">Thesis / Project</li>
	          </ul>
	        </nav>
	      </aside>

      <main class="content">
        <div class="card">
          <h2>Student Status</h2>

          <div v-if="isLoadingData" class="loading-text">Loading status...</div>

	          <div v-else>
              <div class="major-banner">
                <span class="major-label">Major / Program</span>
                <span class="major-pill">{{ majorDisplay }}</span>
              </div>

              <div class="status-grid">
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
                  <h3>Graduation Progress</h3>
                  <div class="progress-wrap">
                    <div class="progress-ring" :style="ringStyle">
                      <div class="progress-inner">
                        <div class="progress-pct">{{ graduationPercent }}%</div>
                        <div class="progress-sub">Term {{ Math.min(termInfo.termNumber, graduationTotalTerms) }} / {{ graduationTotalTerms }}</div>
                      </div>
                    </div>
                    <div class="text-muted">
                      Based on your current term in the program (Term 1 → Term {{ graduationTotalTerms }}).
                    </div>
                  </div>
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

              <div class="right-panel">
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
                      Reviewer comment: {{ admissionComment }}
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
                      Reviewer comment: {{ mpFormComment }}
                    </div>
                  </div>

                  <button class="btn-primary" @click="router.push('/documents')">Go to Documents</button>
                  <p class="text-muted" style="margin-top: 10px">
                    Uploads are handled in Documents. Dashboard only shows status.
                  </p>
                </div>

                <div class="status-item events-section">
                  <div class="events-head">
                    <h3 style="margin: 0">Events</h3>
                    <label class="dismiss-all">
                      <input type="checkbox" :disabled="eventsLoading || eventsBusyAll" @change="dismissAllEvents" />
                      <span>Dismiss all</span>
                    </label>
                  </div>
                  <div v-if="eventsMsg" class="msg" :class="{ ok: eventsOk, bad: !eventsOk }">{{ eventsMsg }}</div>
                  <div v-if="eventsLoading" class="loading-text">Loading events...</div>
                  <div v-else-if="events.length === 0" class="text-muted">No new events.</div>
                  <div v-else class="events-list">
                    <template v-for="ev in events" :key="`${ev.type || 'assignment'}-${ev.id}`">
                      <label v-if="(ev.type || 'assignment') === 'assignment'" class="event-item">
                        <input
                          type="checkbox"
                          :disabled="eventsBusyId === String(ev.id)"
                          @change="(e) => dismissEvent(ev, e)"
                        />
                        <div class="event-body">
                          <div class="event-title">{{ ev.title }}</div>
                          <div class="event-meta text-muted">
                            <span v-if="ev.course_name || ev.course_code">Course: {{ ev.course_name || ev.course_code }} · </span>
                            From: {{ ev.faculty_username || 'Faculty' }} · {{ ev.created_at }}
                            <span v-if="ev.due_at"> · Due: {{ ev.due_at }}</span>
                          </div>
                        </div>
                      </label>

                      <label v-else class="event-item">
                        <input
                          type="checkbox"
                          :disabled="eventsBusyId === `course-${ev.id}`"
                          @change="(e) => dismissEvent(ev, e)"
                        />
                        <div class="event-body">
                          <div class="event-title">{{ ev.title }}</div>
                          <div class="event-meta text-muted">
                            <span v-if="ev.course_name || ev.course_code">Course: {{ ev.course_name || ev.course_code }} · </span>
                            From: {{ ev.faculty_username || 'Faculty' }} · {{ ev.created_at }}
                          </div>
                          <div v-if="ev.comment" class="event-meta text-muted" style="margin-top: 6px">Comment: {{ ev.comment }}</div>
                          <div class="event-meta text-muted" style="margin-top: 6px">
                            Go to <span class="link" @click.stop="router.push('/my-courses')">My Courses</span> to review/apply.
                          </div>
                        </div>
                      </label>
                    </template>
                  </div>
                  <div style="display: flex; gap: 10px; margin-top: 12px; flex-wrap: wrap">
                    <button v-if="hasAssignmentEvents" class="btn-primary" @click="router.push('/assignments')">Go to Assignments</button>
                    <button v-if="hasCourseActionEvents" class="btn-primary" @click="router.push('/my-courses')">Go to My Courses</button>
                  </div>
                </div>
              </div>
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

const events = ref([])
const eventsLoading = ref(false)
const eventsMsg = ref('')
const eventsOk = ref(true)
const eventsBusyId = ref('')
const eventsBusyAll = ref(false)

const hasAssignmentEvents = computed(() => (events.value || []).some((e) => (e?.type || 'assignment') === 'assignment'))
const hasCourseActionEvents = computed(() => (events.value || []).some((e) => (e?.type || 'assignment') === 'course_action'))

const docByType = (type) => documents.value.find((d) => d.doc_type === type) || null
const admissionStatus = computed(() => docByType('admission_letter')?.status || 'none')
const admissionComment = computed(() => docByType('admission_letter')?.admin_comment || '')
const mpFormStatus = computed(() => docByType('major_professor_form')?.status || 'none')
const mpFormComment = computed(() => docByType('major_professor_form')?.admin_comment || '')

const majorDisplay = computed(() => {
  const name = String(profile.value?.major_name || '').trim()
  if (name) return name
  const code = String(profile.value?.major_code || '').trim()
  if (code === 'CS') return 'Computer Science'
  return code || 'Not set'
})

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

const graduationTotalTerms = 4
const graduationPercent = computed(() => {
  const n = Number(termInfo.value?.termNumber || 1)
  const capped = Math.max(1, Math.min(graduationTotalTerms, n))
  return Math.round((capped / graduationTotalTerms) * 100)
})

const ringStyle = computed(() => {
  const pct = graduationPercent.value
  return {
    background: `conic-gradient(#16a34a ${pct}%, #e5e7eb 0)`,
  }
})

onMounted(async () => {
  const storedUser = localStorage.getItem('user')
  if (storedUser) {
    user.value = JSON.parse(storedUser)
    await fetchStatus()
    await fetchEvents()
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

const fetchEvents = async () => {
  eventsLoading.value = true
  eventsMsg.value = ''
  eventsOk.value = true
  try {
    const res = await api.get('student_list_events.php')
    if (res.data?.status === 'success') {
      events.value = res.data.data || []
    } else {
      eventsOk.value = false
      eventsMsg.value = res.data?.message || 'Failed to load events.'
    }
  } catch (e) {
    eventsOk.value = false
    eventsMsg.value = e?.response?.data?.message || 'Failed to load events.'
  } finally {
    eventsLoading.value = false
  }
}

const dismissEvent = async (ev, e) => {
  const checked = Boolean(e?.target?.checked)
  if (!checked) return
  const type = String(ev?.type || 'assignment')
  eventsBusyId.value = type === 'assignment' ? String(ev?.id || '') : `course-${ev?.id || ''}`
  eventsMsg.value = ''
  eventsOk.value = true
  try {
    const res =
      type === 'assignment'
        ? await api.post('student_mark_assignment_read.php', { assignment_id: ev.id })
        : await api.post('student_mark_course_action_read.php', { action_id: ev.id })
    if (res.data?.status === 'success') {
      events.value = (events.value || []).filter((x) => x.id !== ev.id)
    } else {
      eventsOk.value = false
      eventsMsg.value = res.data?.message || 'Failed.'
      if (e?.target) e.target.checked = false
    }
  } catch (err) {
    eventsOk.value = false
    eventsMsg.value = err?.response?.data?.message || 'Failed.'
    if (e?.target) e.target.checked = false
  } finally {
    eventsBusyId.value = ''
  }
}

const dismissAllEvents = async (e) => {
  const checked = Boolean(e?.target?.checked)
  if (!checked) return
  eventsBusyAll.value = true
  eventsMsg.value = ''
  eventsOk.value = true
  try {
    const list = Array.isArray(events.value) ? [...events.value] : []
    for (const ev of list) {
      const type = String(ev?.type || 'assignment')
      try {
        const res =
          type === 'assignment'
            ? await api.post('student_mark_assignment_read.php', { assignment_id: ev.id })
            : await api.post('student_mark_course_action_read.php', { action_id: ev.id })
        if (res.data?.status === 'success') {
          events.value = (events.value || []).filter((x) => x.id !== ev.id)
        }
      } catch {
        // ignore per-item
      }
    }
  } catch (err) {
    eventsOk.value = false
    eventsMsg.value = err?.response?.data?.message || 'Failed.'
  } finally {
    eventsBusyAll.value = false
    if (e?.target) e.target.checked = false
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

.major-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 14px;
  margin: 0 0 18px;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  background: linear-gradient(90deg, #eff6ff, #f0fdf4);
}
.major-label {
  font-weight: 800;
  color: #0f172a;
}
.major-pill {
  display: inline-flex;
  align-items: center;
  padding: 6px 12px;
  border-radius: 999px;
  background: #003366;
  color: #fff;
  font-weight: 800;
  letter-spacing: 0.2px;
  box-shadow: 0 10px 20px rgba(0, 51, 102, 0.18);
}

.progress-wrap {
  display: grid;
  grid-template-columns: 140px 1fr;
  gap: 16px;
  align-items: center;
}

.progress-ring {
  width: 140px;
  height: 140px;
  border-radius: 999px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.progress-inner {
  width: 112px;
  height: 112px;
  border-radius: 999px;
  background: #fff;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border: 1px solid #eef2f7;
}

.progress-pct {
  font-size: 28px;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.1;
}

.progress-sub {
  margin-top: 6px;
  font-size: 12px;
  color: #64748b;
  font-weight: 600;
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
.right-panel {
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
.btn-secondary {
  background: #6c757d;
  color: #fff;
  border: none;
  padding: 8px 12px;
  border-radius: 6px;
  cursor: pointer;
}
.btn-secondary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.msg {
  padding: 10px 12px;
  border-radius: 8px;
  margin: 10px 0;
  font-weight: 700;
}
.msg.ok {
  background: #f0fdf4;
  border: 1px solid #86efac;
  color: #166534;
}
.msg.bad {
  background: #fff1f2;
  border: 1px solid #fecaca;
  color: #991b1b;
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
.events-section {
  background: #fff;
  border: 1px solid #eee;
  border-radius: 8px;
  padding: 20px;
}
.events-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
}
.dismiss-all {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  user-select: none;
  font-weight: 700;
  color: #334155;
}
.dismiss-all input {
  width: 18px;
  height: 18px;
  cursor: pointer;
}
.events-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 10px;
}
.event-item {
  display: grid;
  grid-template-columns: 18px 1fr;
  gap: 10px;
  align-items: start;
  padding: 10px 12px;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  background: #f8fafc;
}
.event-title {
  font-weight: 800;
  color: #0f172a;
}
.event-meta {
  margin-top: 2px;
  font-size: 12px;
  color: #64748b;
}
</style>
