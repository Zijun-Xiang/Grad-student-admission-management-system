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
          <h2>📄 Pending Admission Reviews</h2>
          <div v-if="loading" class="loading-text">Loading...</div>
          <div v-else-if="requests.length === 0" class="empty-state">✅ No pending admission reviews.</div>
          <div v-else class="review-list">
            <div v-for="req in requests" :key="req.doc_id" class="review-item">
              <div class="info">
                <span class="student-name">Student ID: {{ req.student_id }}</span>
                <span class="file-link">📄 {{ req.file_path }}</span>
              </div>
              <div class="actions">
                <a :href="'http://localhost:8080/uploads/' + req.file_path" target="_blank" class="btn-view">View</a>
                <button @click="openRejectModal(req)" class="btn-reject">Reject</button>
                <button @click="openApproveModal(req)" class="btn-approve">Approve</button>
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <h2>🎓 Major Professor Requests</h2>
          <p class="subtitle">Students requesting you as their advisor.</p>
          
          <div v-if="mpRequests.length === 0" class="empty-state">✅ No new advising requests.</div>
          <div v-else class="review-list">
            <div v-for="stu in mpRequests" :key="stu.student_id" class="review-item highlight-item">
              <div class="info">
                <span class="student-name">{{ stu.first_name }} {{ stu.last_name }}</span>
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

    <div v-if="showRejectModal" class="modal-overlay">
      <div class="modal-box">
        <h3>❌ Reject Document</h3>
        <textarea v-model="rejectReason" placeholder="Reason..." rows="4"></textarea>
        <div class="modal-actions">
          <button @click="closeModals" class="btn-cancel">Cancel</button>
          <button @click="confirmReject" class="btn-confirm-reject">Confirm</button>
        </div>
      </div>
    </div>

    <div v-if="showApproveModal" class="modal-overlay">
      <div class="modal-box wide-modal">
        <h3>✅ Approve & Assign Deficiencies</h3>
        <div class="course-grid">
            <label v-for="course in courseList" :key="course.course_code" class="course-item">
              <input type="checkbox" :value="course.course_code" v-model="selectedCourses">
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
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const user = ref({});
const requests = ref([]); // 入学审核列表
const mpRequests = ref([]); // 导师申请列表 (新)
const loading = ref(true);

// 弹窗与课程数据
const showRejectModal = ref(false);
const showApproveModal = ref(false);
const targetReq = ref(null);
const rejectReason = ref('');
const courseList = ref([]);
const selectedCourses = ref([]);

onMounted(() => {
  const storedUser = localStorage.getItem('user');
  if (storedUser) {
    user.value = JSON.parse(storedUser);
    if (user.value.role !== 'faculty') return router.push('/dashboard');
    
    // 加载两个列表
    fetchPendingReviews();
    fetchMPRequests(); 
    fetchCourses();
  } else {
    router.push('/');
  }
});

// 1. 获取入学审核
const fetchPendingReviews = async () => {
  try {
    const res = await axios.get('http://localhost:8080/api/faculty_get_pending.php');
    if (res.data.status === 'success') requests.value = res.data.data;
  } finally { loading.value = false; }
};

// 2. (新) 获取导师申请
const fetchMPRequests = async () => {
  try {
    const res = await axios.get(`http://localhost:8080/api/faculty_get_mp_requests.php?prof_id=${user.value.id}`);
    if (res.data.status === 'success') mpRequests.value = res.data.data;
  } catch(e) { console.error(e); }
};

// 3. (新) 处理导师申请 (接受/拒绝)
const respondMP = async (stu, action) => {
  if(!confirm(`Are you sure you want to ${action.toUpperCase()} ${stu.first_name}?`)) return;
  
  try {
    const res = await axios.post('http://localhost:8080/api/faculty_respond_mp.php', {
      student_id: stu.student_id,
      action: action
    });
    if (res.data.status === 'success') {
      alert("Success: " + res.data.message);
      fetchMPRequests(); // 刷新列表
    }
  } catch(e) { alert("Network Error"); }
};

// --- 下面是之前的入学审核逻辑 (保持不变) ---
const fetchCourses = async () => {
  try {
    const res = await axios.get('http://localhost:8080/api/get_courses.php');
    if (res.data.status === 'success') courseList.value = res.data.data;
  } catch (e) {}
};

const openRejectModal = (req) => { targetReq.value = req; showRejectModal.value = true; };
const openApproveModal = (req) => { targetReq.value = req; selectedCourses.value = []; showApproveModal.value = true; };
const closeModals = () => { showRejectModal.value = false; showApproveModal.value = false; };

const confirmReject = async () => {
  // ... (逻辑同之前，此处省略以节省篇幅，请保留你之前的 confirmReject 代码)
   if (!targetReq.value) return;
  try {
    const res = await axios.post('http://localhost:8080/api/faculty_review.php', {
      doc_id: targetReq.value.doc_id, student_id: targetReq.value.student_id, action: 'reject', comment: rejectReason.value
    });
    if (res.data.status === 'success') { closeModals(); fetchPendingReviews(); }
  } catch (e) {}
};

const confirmApprove = async () => {
  // ... (逻辑同之前，此处省略以节省篇幅，请保留你之前的 confirmApprove 代码)
   if (!targetReq.value) return;
  try {
    await axios.post('http://localhost:8080/api/assign_deficiency.php', { student_id: targetReq.value.student_id, courses: selectedCourses.value });
    const res = await axios.post('http://localhost:8080/api/faculty_review.php', {
      doc_id: targetReq.value.doc_id, student_id: targetReq.value.student_id, action: 'approve', comment: 'Approved'
    });
    if (res.data.status === 'success') { closeModals(); fetchPendingReviews(); }
  } catch (e) {}
};

const logout = () => { localStorage.removeItem('user'); router.push('/'); };
</script>

<style scoped>
/* 复用样式 */
.dashboard-layout { display: flex; flex-direction: column; width: 100vw; height: 100vh; background-color: #f0f2f5; }
.admin-nav { background-color: #2c3e50; color: white; padding: 0 2rem; height: 64px; display: flex; justify-content: space-between; align-items: center; }
.main-container { flex: 1; padding: 40px; overflow-y: auto; display: flex; justify-content: center; }
.content { width: 100%; max-width: 1000px; }
.card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; }
.review-item { display: flex; justify-content: space-between; align-items: center; padding: 20px; border: 1px solid #eee; border-radius: 8px; margin-bottom: 15px; background: white; }
.highlight-item { border-left: 5px solid #003366; background-color: #fdfdfe; }
.info { display: flex; flex-direction: column; gap: 5px; }
.student-name { font-weight: bold; font-size: 1.1rem; }
.actions { display: flex; gap: 10px; }
.btn-view, .btn-reject, .btn-approve { border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-weight: 600; text-decoration: none; color: white;}
.btn-view { background: #6c757d; }
.btn-reject { background-color: #dc3545; }
.btn-approve { background-color: #28a745; }
/* Modal Styles */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 1000; }
.modal-box { background: white; width: 90%; max-width: 500px; padding: 25px; border-radius: 12px; display: flex; flex-direction: column; gap: 15px; }
.wide-modal { max-width: 600px; }
.course-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; max-height: 300px; overflow-y: auto; }
.modal-actions { display: flex; justify-content: flex-end; gap: 10px; }
.btn-cancel { background: #fff; border: 1px solid #ccc; padding: 8px 15px; cursor: pointer; }
.btn-confirm-reject, .btn-confirm-approve { color: white; border: none; padding: 8px 15px; cursor: pointer; }
.btn-confirm-reject { background: #dc3545; } .btn-confirm-approve { background: #28a745; }
textarea { width: 100%; padding: 10px; border: 1px solid #ccc; }
</style>