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
          
          <div v-if="hasHolds" class="alert-box">
            ⛔ You have active Holds. Registration is disabled.
          </div>

          <div v-else>
            <div class="info-box" v-if="deficiencyList.length > 0">
              ℹ️ You have <strong>{{ deficiencyList.length }} deficiency courses</strong>. 
              According to policy, you must register for these first.
            </div>

            <div class="course-list">
              <div class="list-header">
                <span>Course Code</span>
                <span>Course Name</span>
                <span>Credits</span> <span>Status</span>
                <span>Action</span>
              </div>

              <div v-for="course in allCourses" :key="course.course_code" class="list-row" 
                   :class="{'highlight-row': isDeficiency(course.course_code)}">
                
                <span class="code">{{ course.course_code }}</span>
                <span class="name">
                  {{ course.course_name }}
                  <span v-if="isDeficiency(course.course_code)" class="badge-deficiency">Deficiency</span>
                </span>
                
                <span class="credits">{{ course.credits }}</span> <span class="status">
                  <span v-if="isRegistered(course.course_code)" class="badge-registered">Registered</span>
                  <span v-else class="text-muted">-</span>
                </span>

                <span class="action">
                  <button 
                    v-if="!isRegistered(course.course_code)" 
                    @click="register(course)" 
                    class="btn-register"
                  >
                    Register
                  </button>
                  <span v-else class="text-registered">✅</span>
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
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const user = ref({});
const allCourses = ref([]);
const registeredCourses = ref([]);
const deficiencyList = ref([]);
const hasHolds = ref(false);

onMounted(() => {
  const storedUser = localStorage.getItem('user');
  if (storedUser) {
    user.value = JSON.parse(storedUser);
    checkStatusAndLoad();
  } else {
    router.push('/');
  }
});

const checkStatusAndLoad = async () => {
  try {
    const res = await axios.get(`http://localhost:8080/api/get_status.php?student_id=${user.value.id}`);
    if (res.data.status === 'success') {
      hasHolds.value = res.data.holds.length > 0;
      deficiencyList.value = res.data.deficiencies || [];
      if (!hasHolds.value) {
        fetchAllCourses();
        fetchRegisteredCourses();
      }
    }
  } catch (e) { console.error(e); }
};

const fetchAllCourses = async () => {
  const res = await axios.get('http://localhost:8080/api/get_courses.php');
  if (res.data.status === 'success') allCourses.value = res.data.data;
};

const fetchRegisteredCourses = async () => {
  const res = await axios.get(`http://localhost:8080/api/get_student_courses.php?student_id=${user.value.id}`);
  if (res.data.status === 'success') registeredCourses.value = res.data.data;
};

const isDeficiency = (code) => deficiencyList.value.some(d => d.course_code === code);
const isRegistered = (code) => registeredCourses.value.some(r => r.course_code === code);

const register = async (course) => {
  if (!confirm(`Register for ${course.course_code}?`)) return;
  try {
    const res = await axios.post('http://localhost:8080/api/register_course.php', {
      student_id: user.value.id,
      course_code: course.course_code
    });
    if (res.data.status === 'success') {
      alert("Registered Successfully!");
      fetchRegisteredCourses();
    } else {
      alert("Error: " + res.data.message);
    }
  } catch (e) { alert("Network Error"); }
};
</script>

<style scoped>
/* 基础样式保持不变 */
.dashboard-layout { display: flex; flex-direction: column; width: 100vw; height: 100vh; background-color: #f4f7fa; }
.navbar { background-color: #003366; color: white; padding: 0 2rem; height: 64px; display: flex; justify-content: space-between; align-items: center; }
.main-container { flex: 1; display: flex; justify-content: center; padding: 40px; }
.content { width: 100%; max-width: 900px; }
.card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
.btn-back { background: rgba(255,255,255,0.2); border: 1px solid white; color: white; padding: 5px 15px; cursor: pointer; border-radius: 4px; }
h2 { color: #003366; border-bottom: 2px solid #eee; padding-bottom: 15px; }
.alert-box { background: #fff5f5; color: #c92a2a; padding: 15px; border-radius: 6px; font-weight: bold; border: 1px solid #ffc9c9; text-align: center;}
.info-box { background: #e3f2fd; color: #003366; padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #bbdefb; }
.course-list { border: 1px solid #eee; border-radius: 8px; overflow: hidden; }

/* 3. 重点修改 CSS Grid：调整列宽比例以容纳 Credits */
/* 原来是 4 列，现在改为 5 列： Code | Name | Credits | Status | Action */
.list-header { 
  display: grid; 
  grid-template-columns: 0.8fr 2fr 0.6fr 1fr 1fr; /* ✨ 修改了这里 */
  background: #f8f9fa; 
  padding: 12px 15px; 
  font-weight: bold; 
  color: #555; 
  border-bottom: 1px solid #eee; 
}
.list-row { 
  display: grid; 
  grid-template-columns: 0.8fr 2fr 0.6fr 1fr 1fr; /* ✨ 修改了这里，必须和 header 一致 */
  padding: 15px; 
  border-bottom: 1px solid #eee; 
  align-items: center; 
}

.list-row:last-child { border-bottom: none; }
.highlight-row { background-color: #fff9db; border-left: 4px solid #fcc419; }
.badge-deficiency { background: #e03131; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; margin-left: 8px; text-transform: uppercase; }
.badge-registered { background: #28a745; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.85rem; }
.btn-register { background-color: #003366; color: white; border: none; padding: 6px 15px; border-radius: 4px; cursor: pointer; font-weight: 600; }
.btn-register:hover { background-color: #004080; }
.code { font-weight: bold; color: #333; }
.credits { text-align: center; font-weight: 500; color: #666; } /* ✨ 新增样式 */
</style>