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
            <li class="active">🏠 Dashboard</li>
           

<li @click="router.push('/my-courses')">📚 My Courses</li>
            <li>📄 Documents</li>
            
<li @click="router.push('/major-professor')">🎓 Major Professor</li>
            
          </ul>
        </nav>
      </aside>

      <main class="content">
        <div class="card">
          <h2>Student Status</h2>
          
          <div v-if="isLoadingData" class="loading-text">Loading status...</div>

          <div v-else class="status-grid">
             <div class="left-panel">
               
               <div class="status-item">
                  <h3>Current Holds</h3>
                  <div v-if="holds.length > 0" class="status-bad">
                    ⚠️ You have {{ holds.length }} active hold(s).
                  </div>
                  <div v-else class="status-good">
                    ✅ No Holds. You are good to register.
                  </div>
                  <ul class="hold-list" v-if="holds.length > 0">
                    <li v-for="hold in holds" :key="hold.id">{{ hold.hold_type }} (Active)</li>
                  </ul>
               </div>

               <div class="status-item deficiency-section">
                  <h3>Deficiency Courses</h3>
                  <div v-if="deficiencies.length === 0" class="text-muted">
                    No deficiency courses assigned.
                  </div>
                  <ul v-else class="deficiency-list">
                    <li v-for="course in deficiencies" :key="course.course_code">
                      <span class="course-code">{{ course.course_code }}</span>
                      <span class="course-name">{{ course.course_name }}</span>
                      <span class="course-badge">{{ course.status }}</span>
                    </li>
                  </ul>
               </div>

             </div>

             <div class="status-item upload-section">
                <div v-if="admissionDocStatus === 'pending'" class="status-pending">
                  <h3>⏳ Under Review</h3>
                  <p>You have uploaded your Admission Letter.</p>
                  <p>Please wait for approval.</p>
                </div>

                <div v-else-if="admissionDocStatus === 'approved'" class="status-good">
                  <h3>✅ Verified</h3>
                  <p>Your admission document has been accepted.</p>
                  <p>Check the deficiency list on the left.</p>
                </div>

                <div v-else>
                  <h3>Action Required</h3>
                  <p v-if="admissionDocStatus === 'rejected'" style="color:red">
                    ❌ Your previous upload was rejected. Please re-upload.
                  </p>
                  <p v-else>Please upload your Admission Letter.</p>
                  
                  <div class="upload-box">
                    <input type="file" ref="fileInput" @change="handleFileSelect" accept=".pdf,.jpg,.png" />
                    <button class="btn-primary" @click="uploadFile" :disabled="isUploading || !selectedFile">
                      {{ isUploading ? 'Uploading...' : 'Upload' }}
                    </button>
                  </div>
                  <p v-if="uploadMessage" :class="{'msg-success': uploadSuccess, 'msg-error': !uploadSuccess}">
                    {{ uploadMessage }}
                  </p>
                </div>
             </div>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const user = ref({});
const holds = ref([]);
const documents = ref([]);
const deficiencies = ref([]); // 新增变量
const isLoadingData = ref(true);

const fileInput = ref(null);
const selectedFile = ref(null);
const isUploading = ref(false);
const uploadMessage = ref('');
const uploadSuccess = ref(false);

const admissionDocStatus = computed(() => {
  const doc = documents.value.find(d => d.doc_type === 'admission_letter');
  if (!doc) return 'none';
  return doc.status;
});

onMounted(() => {
  const storedUser = localStorage.getItem('user');
  if (storedUser) {
    user.value = JSON.parse(storedUser);
    fetchStatus();
  } else {
    router.push('/');
  }
});

const fetchStatus = async () => {
  try {
    const response = await axios.get(`http://localhost:8080/api/get_status.php?student_id=${user.value.id}`);
    if (response.data.status === 'success') {
      holds.value = response.data.holds;
      documents.value = response.data.documents;
      deficiencies.value = response.data.deficiencies || []; // 获取后端传来的补修课
    }
  } catch (error) {
    console.error(error);
  } finally {
    isLoadingData.value = false;
  }
};

const logout = () => { localStorage.removeItem('user'); router.push('/'); };
const handleFileSelect = (e) => { selectedFile.value = e.target.files[0]; uploadMessage.value = ''; };

const uploadFile = async () => {
  if (!selectedFile.value || !user.value.id) return;
  isUploading.value = true;
  const formData = new FormData();
  formData.append('file', selectedFile.value);
  formData.append('student_id', user.value.id);

  try {
    const res = await axios.post('http://localhost:8080/api/upload_letter.php', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
    });
    if (res.data.status === 'success') {
      uploadSuccess.value = true;
      uploadMessage.value = 'Upload successful!';
      selectedFile.value = null;
      if (fileInput.value) fileInput.value.value = '';
      fetchStatus();
    } else {
        uploadSuccess.value = false;
        uploadMessage.value = res.data.message;
    }
  } catch (e) {
    uploadSuccess.value = false;
    uploadMessage.value = 'Network error.';
  } finally { isUploading.value = false; }
};
</script>

<style scoped>
/* 保持原有布局，增加新的样式 */
.dashboard-layout { display: flex; flex-direction: column; width: 100vw; height: 100vh; background-color: #f4f7fa; }
.navbar { background-color: #003366; color: white; padding: 0 2rem; height: 64px; display: flex; justify-content: space-between; align-items: center; }
.btn-logout { background: rgba(255, 255, 255, 0.15); border: 1px solid rgba(255, 255, 255, 0.3); color: white; padding: 6px 16px; border-radius: 4px; cursor: pointer; }
.main-container { flex: 1; display: flex; overflow: hidden; }
.sidebar { width: 260px; background: white; border-right: 1px solid #dee2e6; padding-top: 1rem; }
.sidebar li { padding: 15px 25px; cursor: pointer; color: #495057; }
.sidebar li.active { background-color: #e3f2fd; color: #003366; border-left: 4px solid #003366; }
.content { flex: 1; padding: 2rem; overflow-y: auto; }
.card { background: white; padding: 2rem; border-radius: 8px; max-width: 900px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
h2 { color: #003366; border-bottom: 2px solid #f1f3f5; padding-bottom: 15px; margin-bottom: 20px; }

.status-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
.left-panel { display: flex; flex-direction: column; gap: 20px; }
.status-item h3 { margin-top: 0; font-size: 1.1rem; color: #333; margin-bottom: 10px; }
.status-bad { background: #fff5f5; color: #c92a2a; padding: 15px; border-radius: 6px; font-weight: bold; }
.status-good { background: #e8f5e9; color: #2e7d32; padding: 15px; border-radius: 6px; font-weight: bold; }
.status-pending { background: #fff9db; color: #d9480f; padding: 15px; border-radius: 6px; text-align: center; font-weight: bold; border: 1px solid #ffe066;}

/* 补修课列表样式 */
.deficiency-list { list-style: none; padding: 0; margin: 0; border: 1px solid #eee; border-radius: 6px; }
.deficiency-list li { display: flex; align-items: center; justify-content: space-between; padding: 10px 15px; border-bottom: 1px solid #eee; }
.deficiency-list li:last-child { border-bottom: none; }
.course-code { font-weight: bold; color: #003366; }
.course-name { color: #555; font-size: 0.9rem; flex: 1; margin-left: 10px; }
.course-badge { background: #e9ecef; color: #495057; padding: 2px 8px; border-radius: 10px; font-size: 0.8rem; }
.text-muted { color: #888; font-style: italic; }

/* 上传相关 */
.upload-section { background: #fcfcfc; padding: 20px; border: 1px solid #eee; border-radius: 8px; }
.upload-box { display: flex; flex-direction: column; gap: 15px; margin-top: 15px; }
.btn-primary { background-color: #003366; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
.btn-primary:disabled { background-color: #ccc; }
.msg-success { color: green; margin-top: 10px; } .msg-error { color: red; margin-top: 10px; }
</style>