<template>
  <div class="dashboard-layout">
    <header class="navbar">
      <div class="brand">Grad System</div>
      <div class="user-info">
        <span>Welcome, {{ user.username }}</span>
        <button @click="router.push('/dashboard')" class="btn-back">Back to Dashboard</button>
      </div>
    </header>

    <div class="main-container">
      <main class="content">
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
                    :disabled="wouldExceedMax(course)"
                    :title="wouldExceedMax(course) ? `Exceeds max ${maxCredits} credits` : ''"
                  >
                    Register
                  </button>
                  <span v-else class="text-registered">&#10003;</span>
                </span>
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
    }
  } catch (e) {
    console.error(e)
  }
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
  try {
    const res = await api.post('student_submit_core_checklist.php', {
      completed_courses: completedCore.value,
    })
    if (res.data.status === 'success') {
      alert(`Saved. Assigned deficiencies: ${res.data.assigned_deficiencies}`)
      needsCoreChecklist.value = false
      await checkStatusAndLoad()
    } else {
      alert(res.data.message || 'Failed to save checklist')
    }
  } catch (e) {
    alert(e?.response?.data?.message || 'Failed to save checklist')
  } finally {
    submittingCore.value = false
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
  if (wouldExceedMax(course)) return alert(`Cannot exceed ${maxCredits} credits per term.`)
  if (!confirm(`Register for ${course.course_code}?`)) return
  try {
    const res = await api.post('register_course.php', { course_code: course.course_code })
    if (res.data.status === 'success') {
      alert('Registered Successfully!')
      fetchRegisteredCourses()
    } else {
      alert('Error: ' + res.data.message)
    }
  } catch (e) {
    const msg = e?.response?.data?.message || 'Network Error'
    alert(msg)
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
.main-container {
  flex: 1;
  display: flex;
  justify-content: center;
  padding: 40px;
}
.content {
  width: 100%;
  max-width: 900px;
}
.card {
  background: white;
  padding: 30px;
  border-radius: 8px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}
.btn-back {
  background: rgba(255, 255, 255, 0.2);
  border: 1px solid white;
  color: white;
  padding: 5px 15px;
  cursor: pointer;
  border-radius: 4px;
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
