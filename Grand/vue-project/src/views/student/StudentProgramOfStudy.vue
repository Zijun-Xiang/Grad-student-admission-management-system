<script setup>
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'

// --------------------------
// 状态变量
// --------------------------
const facultyList = ref([])
const selectedFacultyId = ref('')
const chooseRequestId = ref(null)
const studentId = ref(null)

const fileName = ref('No file chosen')
const selectedFile = ref(null)
const studentComment = ref('')

const message = ref('')
const messageType = ref('') // success / error

const fileUploaded = ref(false)
const submitting = ref(false)
const uploading = ref(false)

// 提交按钮是否可用
const canSubmit = computed(
  () => !!selectedFacultyId.value && !!chooseRequestId.value && fileUploaded.value
)

// --------------------------
// 拉取导师列表（使用 admin 端接口）
// --------------------------
const loadFaculty = async () => {
  try {
    const res = await axios.get('/api/student/choose-instructor/faculty/')
    facultyList.value = res.data || []
  } catch (error) {
    message.value = 'Failed to load faculty list.'
    messageType.value = 'error'
  }
}

const createChooseRequest = async () => {
  if (!selectedFacultyId.value) return false

  try {
    const res = await axios.post('/api/student/choose-instructor/create/', {
      faculty_id: selectedFacultyId.value
    })
    chooseRequestId.value = res.data.id
    studentId.value = res.data.studentId
    return true
  } catch (error) {
    message.value = 'Failed to start request. Please try again.'
    messageType.value = 'error'
    return false
  }
}

// 选择导师后创建/重置申请记录
const handleFacultySelect = async () => {
  message.value = ''
  fileUploaded.value = false
  selectedFile.value = null
  fileName.value = 'No file chosen'
  chooseRequestId.value = null

  if (!selectedFacultyId.value) return

  await createChooseRequest()
}

// --------------------------
const onFileChange = (event) => {
  const file = event.target.files?.[0]
  if (file) {
    selectedFile.value = file
    fileName.value = file.name
    fileUploaded.value = false
  } else {
    selectedFile.value = null
    fileName.value = 'No file chosen'
    fileUploaded.value = false
  }
}

// --------------------------
const handleUpload = async () => {
  message.value = ''
  if (!selectedFacultyId.value) {
    message.value = 'Please select an advisor before uploading.'
    messageType.value = 'error'
    return
  }
  if (!selectedFile.value) {
    message.value = 'Please choose a file to upload.'
    messageType.value = 'error'
    return
  }

  // 确保已有申请记录（若因网络原因未创建则补建）
  if (!chooseRequestId.value) {
    const created = await createChooseRequest()
    if (!created) return
  }

  const formData = new FormData()
  formData.append('file', selectedFile.value)
  if (studentId.value) {
    formData.append('student_id', studentId.value)
  }

  //关键修复点：必须传 student_id
  formData.append('student_id', localStorage.getItem('user_id'))

  try {
    uploading.value = true
    await axios.post(
      `/api/student/choose-instructor/${chooseRequestId.value}/upload-file/`,
      formData,
      { headers: { 'Content-Type': 'multipart/form-data' } }
    )
    fileUploaded.value = true
    message.value = 'File uploaded successfully.'
    messageType.value = 'success'
  } catch (error) {
    fileUploaded.value = false
    message.value = 'Upload failed. Please try again.'
    messageType.value = 'error'
  } finally {
    uploading.value = false
  }
}


// --------------------------
const handleSubmit = async () => {
  message.value = ''
  if (!selectedFacultyId.value) {
    message.value = 'Please select an advisor first.'
    messageType.value = 'error'
    return
  }
  if (!fileUploaded.value) {
    message.value = 'Please upload your file before submitting.'
    messageType.value = 'error'
    return
  }

  if (!chooseRequestId.value) {
    const created = await createChooseRequest()
    if (!created) return
  }

  try {
    submitting.value = true
    await axios.post(
      `/api/student/choose-instructor/${chooseRequestId.value}/submit/`,
      {
        studentComment: studentComment.value,
        student_id: studentId.value
      }
    )
    message.value = 'Submitted for review successfully.'
    messageType.value = 'success'
  } catch (error) {
    message.value = 'Submission failed. Please try again.'
    messageType.value = 'error'
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  loadFaculty()
})
</script>

<template>
  <div class="page">
    <h1>Program of Study</h1>

    <div class="card">
      <h3>Submit Program of Study</h3>

      <p v-if="message" :class="['alert', messageType === 'error' ? 'alert-error' : 'alert-success']">
        {{ message }}
      </p>

      <form @submit.prevent="handleSubmit">

        <div class="form-group">
          <label>Choose Advisor:</label>
          <select v-model="selectedFacultyId" @change="handleFacultySelect">
            <option value="">Select a faculty</option>
            <option v-for="f in facultyList" :key="f.user_id" :value="f.user_id">
              {{ f.name }} ({{ f.department }})
            </option>
          </select>
        </div>

        <div class="form-group">
          <label>Upload Program of Study Document:</label>

          <label for="pos-file" class="btn btn-primary">Choose File</label>
          <span>{{ fileName }}</span>

          <input
            type="file"
            id="pos-file"
            @change="onFileChange"
            style="position:absolute;opacity:0;width:1px;height:1px;"
          />

          <button type="button" class="btn btn-secondary" @click="handleUpload" :disabled="uploading">
            Upload
          </button>
        </div>

        <div class="form-group">
          <label>Comments:</label>
          <textarea rows="4" v-model="studentComment"></textarea>
        </div>

        <button class="btn btn-submit" :disabled="!canSubmit">Submit for Review</button>

      </form>
    </div>
  </div>
</template>

<style scoped>
.alert { margin-bottom: 12px; padding: 10px; border-radius: 4px; }
.alert-error { background: #fde8e8; color: #c0392b; }
.alert-success { background: #e8f8f5; color: #1e8449; }
</style>
