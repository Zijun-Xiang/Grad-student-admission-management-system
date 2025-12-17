<template>
  <div class="dashboard-layout">
    <header class="navbar">
      <div class="brand">Grad System</div>
      <div class="user-info">
        <span>Welcome, {{ user.username }}</span>
        <button @click="logout" class="btn-logout">Logout</button>
      </div>
    </header>

    <div class="main-container">
      <aside class="sidebar">
        <nav>
          <ul>
            <li @click="router.push('/dashboard')">Dashboard</li>
            <li class="active">My Courses</li>
            <li @click="router.push('/documents')">Documents</li>
            <li @click="router.push('/assignments')">Assignments</li>
            <li @click="router.push('/profile')">Profile</li>
            <li v-if="term?.unlocks?.term2" @click="router.push('/major-professor')">Major Professor</li>
            <li v-if="term?.unlocks?.term3" @click="router.push('/thesis-project')">Thesis / Project</li>
          </ul>
        </nav>
      </aside>

      <main class="content">
        <div class="content-inner">
          <div class="card">
          <h2>Course Registration</h2>

          <div v-if="hasHolds" class="alert-box">You have active Holds. Registration is disabled.</div>

          <div v-else-if="!admissionApproved" class="info-box">
            <strong>Admission Letter required.</strong> Please upload your Admission Letter in
            <span class="link" @click="router.push('/documents')">Documents</span> and wait for approval before course
            selection.
          </div>

          <div v-else-if="needsCoreChecklist" class="core-card">
            <h3>Undergraduate Core Courses Checklist</h3>
            <p class="text-muted">
              Select the courses you have already completed in your undergraduate study. Any course not selected will
              become a <strong>Deficiency</strong> that you must take before graduate courses.
            </p>

            <div v-if="coreLoading" class="loading-text">Loading core courses...</div>
            <div v-else-if="coreError" class="alert-box">{{ coreError }}</div>
            <div v-else class="core-list">
              <label v-for="c in coreCourses" :key="c.course_code" class="core-item">
                <input type="checkbox" v-model="completedCore" :value="c.course_code" />
                <span class="code">{{ c.course_code }}</span>
                <span class="name">{{ c.course_name }}</span>
                <span class="credits">{{ c.credits }}</span>
              </label>
            </div>

            <div class="form-actions">
              <button class="btn-register" @click="submitCoreChecklist" :disabled="submittingCore">
                {{ submittingCore ? 'Saving...' : 'Save & Continue' }}
              </button>
            </div>
          </div>

          <div v-else>
            <div v-if="actionMessage" class="msg" :class="{ ok: actionOk, bad: !actionOk }">
              {{ actionMessage }}
            </div>

            <div class="advisor-card">
              <div class="advisor-head">
                <h3 style="margin: 0">Advisor Course Requests</h3>
                <button class="btn-secondary" @click="fetchAdvisorActions" :disabled="advisorActionsLoading">Refresh</button>
              </div>
              <div v-if="advisorActionsMsg" class="msg" :class="{ ok: advisorActionsOk, bad: !advisorActionsOk }">
                {{ advisorActionsMsg }}
              </div>
              <div v-if="advisorActionsLoading" class="loading-text">Loading advisor requests...</div>
              <div v-else-if="advisorPending.length === 0" class="text-muted">No pending advisor requests.</div>
              <div v-else class="events-list" style="margin-top: 10px">
                <div v-for="a in advisorPending" :key="a.id" class="event-item">
                  <input
                    type="checkbox"
                    :disabled="advisorBusyId === String(a.id)"
                    @change="(e) => applyAdvisorAction(a, e)"
                    :title="'Apply'"
                  />
                  <div class="event-body">
                    <div class="event-title">
                      {{ a.action_type === 'add' ? 'Add' : 'Drop' }}:
                      {{ a.course_code }}{{ a.course_name ? ` · ${a.course_name}` : '' }}
                    </div>
                    <div class="event-meta text-muted">
                      From: {{ a.faculty_username || 'Faculty' }} · {{ a.created_at }}
                    </div>
                    <div v-if="a.comment" class="text-muted" style="margin-top: 6px">Advisor comment: {{ a.comment }}</div>
                    <div class="form-row" style="margin-top: 10px">
                      <input
                        v-model="advisorRejectNotes[a.id]"
                        class="select"
                        type="text"
                        placeholder="Optional reply when rejecting..."
                        :disabled="advisorBusyId === String(a.id)"
                      />
                      <button class="btn-drop" @click="rejectAdvisorAction(a)" :disabled="advisorBusyId === String(a.id)">
                        {{ advisorBusyId === String(a.id) ? 'Working...' : 'Reject' }}
                      </button>
                    </div>
                    <div class="text-muted" style="margin-top: 6px">Check the box to accept and apply the change.</div>
                  </div>
                </div>
              </div>

              <div v-if="advisorHistory.length > 0" style="margin-top: 14px">
                <div class="text-muted" style="font-weight: 700">Recent Applied</div>
                <div class="events-list" style="margin-top: 8px">
                  <div v-for="h in advisorHistory" :key="`h-${h.id}`" class="event-item" style="cursor: default">
                    <div class="event-body" style="margin-left: 0">
                      <div class="event-title">
                        {{ h.action_type === 'add' ? 'Added' : 'Dropped' }}:
                        {{ h.course_code }}{{ h.course_name ? ` · ${h.course_name}` : '' }}
                      </div>
                      <div class="event-meta text-muted">
                        From: {{ h.faculty_username || 'Faculty' }} · {{ h.created_at }}
                        <span v-if="h.applied_at"> · Applied: {{ h.applied_at }}</span>
                      </div>
                      <div v-if="h.comment" class="text-muted" style="margin-top: 6px">Comment: {{ h.comment }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="credit-card">
              <div class="credit-row">
                <div class="credit-title">Credit Requirement (per term)</div>
                <div class="credit-values">
                  Min <strong>{{ minCredits }}</strong> · Max <strong>{{ maxCredits }}</strong> · Selected
                  <strong>{{ totalCredits }}</strong>
                </div>
              </div>
              <div class="progress">
                <div class="bar" :style="{ width: progressPercent + '%' }" :class="progressClass"></div>
              </div>
              <div class="credit-hint" :class="progressClass">
                <span v-if="totalCredits < minCredits">You need at least {{ minCredits }} credits.</span>
                <span v-else-if="totalCredits <= maxCredits">On track.</span>
                <span v-else>Over the maximum of {{ maxCredits }} credits.</span>
              </div>
            </div>

            <div class="info-box" v-if="deficiencyList.length > 0">
              You have <strong>{{ deficiencyList.length }} deficiency courses</strong>. According to policy, you must
              register for these first.
            </div>

            <div class="course-list">
              <div class="list-header">
                <span>Course Code</span>
                <span>Course Name</span>
                <span>Credits</span>
                <span>Status</span>
                <span>Action</span>
              </div>

              <div
                v-for="course in visibleCourses"
                :key="course.course_code"
                class="list-row"
                :class="{ 'highlight-row': isDeficiency(course.course_code) }"
              >
                <span class="code">{{ course.course_code }}</span>
                <span class="name">
                  {{ course.course_name }}
                  <span v-if="isDeficiency(course.course_code)" class="badge-deficiency">Deficiency</span>
                </span>

                <span class="credits">{{ course.credits }}</span>
                <span class="status">
                  <span v-if="isRegistered(course.course_code)" class="badge-registered">Registered</span>
                  <span v-else class="text-muted">-</span>
                </span>

                <span class="action">
                  <button
                    v-if="!isRegistered(course.course_code)"
                    @click="register(course)"
                    class="btn-register"
                    :disabled="wouldExceedMax(course) || isBusy(course.course_code)"
                    :title="wouldExceedMax(course) ? `Exceeds max ${maxCredits} credits` : ''"
                  >
                    {{ isBusy(course.course_code) ? 'Working...' : 'Register' }}
                  </button>
                  <button v-else @click="unregister(course)" class="btn-drop" :disabled="isBusy(course.course_code)">
                    {{ isBusy(course.course_code) ? 'Working...' : 'Drop' }}
                  </button>
                </span>
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

const router = useRouter()
const user = ref({})
const term = ref(null)
const allCourses = ref([])
const registeredCourses = ref([])
const deficiencyList = ref([])
const hasHolds = ref(false)
const minCredits = 12
const maxCredits = 20
const admissionApproved = ref(false)
const needsCoreChecklist = ref(false)
const coreLoading = ref(false)
const coreCourses = ref([])
const completedCore = ref([])
const submittingCore = ref(false)
const coreError = ref('')
const actionMessage = ref('')
const actionOk = ref(true)
const busyByCourse = ref({})
const advisorActionsLoading = ref(false)
const advisorActionsOk = ref(true)
const advisorActionsMsg = ref('')
const advisorPending = ref([])
const advisorHistory = ref([])
const advisorBusyId = ref('')
const advisorRejectNotes = ref({})

const setBusy = (courseCode, isBusy) => {
  busyByCourse.value = { ...busyByCourse.value, [courseCode]: isBusy }
}

const isBusy = (courseCode) => Boolean(busyByCourse.value?.[courseCode])

onMounted(() => {
  const storedUser = localStorage.getItem('user')
  if (storedUser) {
    user.value = JSON.parse(storedUser)
    checkStatusAndLoad()
  } else {
    router.push('/')
  }
})

const checkStatusAndLoad = async () => {
  try {
    const res = await api.get('get_status.php')
    if (res.data.status === 'success') {
      term.value = res.data.term || null
      hasHolds.value = res.data.holds.length > 0
      deficiencyList.value = res.data.deficiencies || []
      const docs = res.data.documents || []
      const admission = docs.find((d) => d.doc_type === 'admission_letter')
      admissionApproved.value = admission?.status === 'approved'

      const submittedAt = res.data.core_checklist?.submitted_at
      needsCoreChecklist.value = admissionApproved.value && !submittedAt

      if (!hasHolds.value) {
        if (needsCoreChecklist.value) {
          await fetchCoreCourses()
        } else if (admissionApproved.value) {
          fetchAllCourses()
          fetchRegisteredCourses()
        }
      }
      if (admissionApproved.value) {
        fetchAdvisorActions()
      }
    }
  } catch (e) {
    console.error(e)
  }
}

const logout = async () => {
  try {
    await api.post('logout.php')
  } catch {}
  localStorage.removeItem('user')
  router.push('/')
}

const fetchCoreCourses = async () => {
  coreLoading.value = true
  coreError.value = ''
  try {
    const res = await api.get('get_undergrad_core_courses.php')
    if (res.data.status === 'success') {
      coreCourses.value = res.data.data || []
      if (coreCourses.value.length === 0) {
        coreError.value = 'No undergrad core courses found. Run backend/sql/07_core_courses_seed.sql first.'
      }
    } else {
      coreError.value = res.data.message || 'Failed to load core courses.'
    }
  } catch (e) {
    coreError.value = e?.response?.data?.message || 'Failed to load core courses. Run backend/sql/07_core_courses_seed.sql.'
  } finally {
    coreLoading.value = false
  }
}

const submitCoreChecklist = async () => {
  submittingCore.value = true
  actionMessage.value = ''
  actionOk.value = true
  try {
    const res = await api.post('student_submit_core_checklist.php', {
      completed_courses: completedCore.value,
    })
    if (res.data.status === 'success') {
      actionOk.value = true
      actionMessage.value = `Saved. Assigned deficiencies: ${res.data.assigned_deficiencies}`
      needsCoreChecklist.value = false
      await checkStatusAndLoad()
    } else {
      actionOk.value = false
      actionMessage.value = res.data.message || 'Failed to save checklist'
    }
  } catch (e) {
    actionOk.value = false
    actionMessage.value = e?.response?.data?.message || 'Failed to save checklist'
  } finally {
    submittingCore.value = false
  }
}

const fetchAdvisorActions = async () => {
  advisorActionsLoading.value = true
  advisorActionsOk.value = true
  advisorActionsMsg.value = ''
  try {
    const res = await api.get('student_list_course_actions.php')
    if (res.data?.status === 'success') {
      advisorPending.value = res.data.pending || []
      advisorHistory.value = res.data.history || []
    } else {
      advisorActionsOk.value = false
      advisorActionsMsg.value = res.data?.message || 'Failed to load advisor requests.'
    }
  } catch (e) {
    advisorActionsOk.value = false
    advisorActionsMsg.value = e?.response?.data?.message || 'Failed to load advisor requests.'
  } finally {
    advisorActionsLoading.value = false
  }
}

const applyAdvisorAction = async (a, e) => {
  const checked = Boolean(e?.target?.checked)
  if (!checked) return
  advisorBusyId.value = String(a?.id || '')
  advisorActionsMsg.value = ''
  advisorActionsOk.value = true
  try {
    const res = await api.post('student_apply_course_action.php', { action_id: a.id })
    if (res.data?.status === 'success') {
      actionOk.value = true
      actionMessage.value = res.data?.message || 'Saved.'
      await fetchRegisteredCourses()
      await fetchAdvisorActions()
    } else {
      advisorActionsOk.value = false
      advisorActionsMsg.value = res.data?.message || 'Failed.'
      if (e?.target) e.target.checked = false
    }
  } catch (err) {
    advisorActionsOk.value = false
    advisorActionsMsg.value = err?.response?.data?.message || 'Failed.'
    if (e?.target) e.target.checked = false
  } finally {
    advisorBusyId.value = ''
  }
}

const rejectAdvisorAction = async (a) => {
  if (!a?.id) return
  advisorBusyId.value = String(a.id)
  advisorActionsMsg.value = ''
  advisorActionsOk.value = true
  try {
    const res = await api.post('student_reject_course_action.php', {
      action_id: a.id,
      comment: advisorRejectNotes.value?.[a.id] || '',
    })
    if (res.data?.status === 'success') {
      actionOk.value = true
      actionMessage.value = res.data?.message || 'Rejected.'
      advisorRejectNotes.value = { ...advisorRejectNotes.value, [a.id]: '' }
      await fetchAdvisorActions()
    } else {
      advisorActionsOk.value = false
      advisorActionsMsg.value = res.data?.message || 'Failed.'
    }
  } catch (err) {
    advisorActionsOk.value = false
    advisorActionsMsg.value = err?.response?.data?.message || 'Failed.'
  } finally {
    advisorBusyId.value = ''
  }
}

const fetchAllCourses = async () => {
  const res = await api.get('get_courses.php')
  if (res.data.status === 'success') allCourses.value = res.data.data
}

const fetchRegisteredCourses = async () => {
  const res = await api.get('get_student_courses.php')
  if (res.data.status === 'success') registeredCourses.value = res.data.data
}

const isDeficiency = (code) => deficiencyList.value.some((d) => d.course_code === code)
const isRegistered = (code) => registeredCourses.value.some((r) => r.course_code === code)

const visibleCourses = computed(() => {
  // After core checklist, hide UG required courses unless they are deficiencies.
  return allCourses.value.filter((c) => {
    const level = String(c.level || '').toUpperCase()
    const isReq = Number(c.is_required || 0) === 1
    if (level === 'UG' && isReq) {
      return isDeficiency(c.course_code)
    }
    return true
  })
})

const totalCredits = computed(() =>
  registeredCourses.value.reduce((sum, c) => sum + Number(c.credits || 0), 0),
)

const progressPercent = computed(() => Math.min(100, Math.round((totalCredits.value / maxCredits) * 100)))

const progressClass = computed(() => {
  if (totalCredits.value < minCredits) return 'low'
  if (totalCredits.value <= maxCredits) return 'ok'
  return 'high'
})

const wouldExceedMax = (course) => {
  const add = Number(course?.credits || 0)
  return totalCredits.value + add > maxCredits
}

const register = async (course) => {
  actionMessage.value = ''
  if (wouldExceedMax(course)) {
    actionOk.value = false
    actionMessage.value = `Cannot exceed ${maxCredits} credits per term.`
    return
  }
  if (isBusy(course.course_code)) return
  setBusy(course.course_code, true)
  try {
    const res = await api.post('register_course.php', { course_code: course.course_code })
    if (res.data.status === 'success') {
      actionOk.value = true
      actionMessage.value = `Registered: ${course.course_code}`
      await fetchRegisteredCourses()
      await checkStatusAndLoad()
    } else {
      actionOk.value = false
      actionMessage.value = res.data.message || 'Registration failed.'
    }
  } catch (e) {
    actionOk.value = false
    actionMessage.value = e?.response?.data?.message || 'Network Error'
  } finally {
    setBusy(course.course_code, false)
  }
}

const unregister = async (course) => {
  actionMessage.value = ''
  if (isBusy(course.course_code)) return
  setBusy(course.course_code, true)
  try {
    const res = await api.post('unregister_course.php', { course_code: course.course_code })
    if (res.data.status === 'success') {
      actionOk.value = true
      actionMessage.value = `Dropped: ${course.course_code}`
      await fetchRegisteredCourses()
      await checkStatusAndLoad()
    } else {
      actionOk.value = false
      actionMessage.value = res.data.message || 'Drop failed.'
    }
  } catch (e) {
    actionOk.value = false
    actionMessage.value = e?.response?.data?.message || 'Network Error'
  } finally {
    setBusy(course.course_code, false)
  }
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
.content-inner {
  width: 100%;
  max-width: 900px;
  margin: 0 auto;
}
.card {
  background: white;
  padding: 30px;
  border-radius: 8px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}
h2 {
  color: #003366;
  border-bottom: 2px solid #eee;
  padding-bottom: 15px;
}
.credit-card {
  border: 1px solid #eee;
  border-radius: 10px;
  padding: 14px;
  background: #fcfcfd;
  margin-bottom: 18px;
}
.credit-row {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: baseline;
  flex-wrap: wrap;
}
.credit-title {
  font-weight: 800;
  color: #0f172a;
}
.credit-values {
  color: #334155;
}
.progress {
  height: 10px;
  background: #e5e7eb;
  border-radius: 999px;
  overflow: hidden;
  margin-top: 10px;
}
.bar {
  height: 100%;
  border-radius: 999px;
}
.bar.low {
  background: #f59e0b;
}
.bar.ok {
  background: #16a34a;
}
.bar.high {
  background: #ef4444;
}
.credit-hint {
  margin-top: 10px;
  font-size: 13px;
  font-weight: 600;
}
.credit-hint.low {
  color: #b45309;
}
.credit-hint.ok {
  color: #166534;
}
.credit-hint.high {
  color: #991b1b;
}
.alert-box {
  background: #fff5f5;
  color: #c92a2a;
  padding: 15px;
  border-radius: 6px;
  font-weight: bold;
  border: 1px solid #ffc9c9;
  text-align: center;
}
.info-box {
  background: #e3f2fd;
  color: #003366;
  padding: 15px;
  border-radius: 6px;
  margin-bottom: 20px;
  border: 1px solid #bbdefb;
}
.link {
  text-decoration: underline;
  cursor: pointer;
  font-weight: 700;
}
.core-card {
  border: 1px solid #eee;
  border-radius: 10px;
  padding: 16px;
  background: #fff;
}
.core-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: 12px;
  border: 1px solid #eee;
  border-radius: 10px;
  padding: 12px;
  background: #fcfcfc;
  max-height: 420px;
  overflow-y: auto;
}
.core-item {
  display: grid;
  grid-template-columns: 26px 0.8fr 2fr 0.6fr;
  gap: 10px;
  align-items: center;
  padding: 8px 10px;
  border: 1px solid #eee;
  border-radius: 10px;
  background: #fff;
}
.form-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 12px;
}
.course-list {
  border: 1px solid #eee;
  border-radius: 8px;
  overflow: hidden;
}
.list-header {
  display: grid;
  grid-template-columns: 0.8fr 2fr 0.6fr 1fr 1fr;
  background: #f8f9fa;
  padding: 12px 15px;
  font-weight: bold;
  color: #555;
  border-bottom: 1px solid #eee;
}
.list-row {
  display: grid;
  grid-template-columns: 0.8fr 2fr 0.6fr 1fr 1fr;
  padding: 15px;
  border-bottom: 1px solid #eee;
  align-items: center;
}
.list-row:last-child {
  border-bottom: none;
}
.highlight-row {
  background-color: #fff9db;
  border-left: 4px solid #fcc419;
}
.badge-deficiency {
  background: #e03131;
  color: white;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 0.75rem;
  margin-left: 8px;
  text-transform: uppercase;
}
.badge-registered {
  background: #28a745;
  color: white;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 0.85rem;
}
.btn-register {
  background-color: #003366;
  color: white;
  border: none;
  padding: 6px 15px;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 600;
}
.btn-register:hover {
  background-color: #004080;
}
.btn-register:disabled {
  background: #cbd5e1;
  cursor: not-allowed;
}
.btn-drop {
  background-color: #6c757d;
  color: white;
  border: none;
  padding: 6px 15px;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 600;
}
.btn-drop:hover {
  background-color: #5a6268;
}
.msg {
  padding: 12px 14px;
  border-radius: 8px;
  margin: 0 0 16px;
  font-weight: 600;
}
.msg.ok {
  background: #e8f7ee;
  border: 1px solid #b6e2c3;
  color: #1b7a3a;
}
.msg.bad {
  background: #fdebec;
  border: 1px solid #f5c2c7;
  color: #b4232c;
}
.btn-secondary {
  background: #6c757d;
  color: white;
  border: none;
  padding: 6px 15px;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 600;
}
.btn-secondary:hover {
  background: #5a6268;
}
.btn-secondary:disabled {
  background: #cbd5e1;
  cursor: not-allowed;
}
.advisor-card {
  border: 1px solid #eee;
  border-radius: 10px;
  padding: 14px;
  background: #fff;
  margin-bottom: 18px;
}
.advisor-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}
.events-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.event-item {
  display: flex;
  gap: 12px;
  align-items: flex-start;
  padding: 12px 12px;
  border: 1px solid #eef2f7;
  border-radius: 10px;
  background: #f8fafc;
  cursor: pointer;
}
.event-item input[type='checkbox'] {
  margin-top: 4px;
  width: 18px;
  height: 18px;
  cursor: pointer;
}
.event-body {
  flex: 1;
}
.event-title {
  font-weight: 800;
  color: #0f172a;
}
.event-meta {
  margin-top: 4px;
  font-size: 13px;
}
.text-registered {
  color: #28a745;
  font-weight: 800;
}
.code {
  font-weight: bold;
  color: #333;
}
.credits {
  text-align: center;
  font-weight: 500;
  color: #666;
}
</style>
