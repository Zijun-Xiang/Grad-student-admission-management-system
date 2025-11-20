<script setup>
// 学生提交 Program of Study 的完整流程
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'

// 下拉导师列表与选择状态
const facultyList = ref([])
const selectedFacultyId = ref('')

// 文件与备注
const fileName = ref('No file chosen')
const selectedFile = ref(null)
const studentComment = ref('')

// 状态提示
const message = ref('')
const messageType = ref('') // success / error

// 是否已成功上传文件，用于防止未上传就提交
const fileUploaded = ref(false)
const submitting = ref(false)
const uploading = ref(false)

// 提交按钮是否可用
const canSubmit = computed(() => !!selectedFacultyId.value && fileUploaded.value)

// 拉取导师列表
const loadFaculty = async () => {
  try {
    const res = await axios.get('/api/student/faculty-list/')
    facultyList.value = res.data || []
  } catch (error) {
    message.value = 'Failed to load faculty list.'
    messageType.value = 'error'
  }
}

// 选择导师后重置状态
const handleFacultySelect = () => {
  message.value = ''
  fileUploaded.value = false
  selectedFile.value = null
  fileName.value = 'No file chosen'
}

// 选择文件，仅更新本地变量，不立即提交
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

// 上传文件到后端（必须先选择导师并创建记录）
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

  const formData = new FormData()
  formData.append('file', selectedFile.value)

  try {
    uploading.value = true
    await axios.post(
      `/api/student/choose-instructor/${selectedFacultyId.value}/upload-file/`,
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

// 最终提交审核
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

  try {
    submitting.value = true
    await axios.post(
      `/api/student/choose-instructor/${selectedFacultyId.value}/submit/`,
      {
        studentComment: studentComment.value,
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

      <!-- 信息提示 -->
      <p v-if="message" :class="['alert', messageType === 'error' ? 'alert-error' : 'alert-success']">{{ message }}</p>

      <form @submit.prevent="handleSubmit">
        <div class="form-group">
          <label for="faculty-select">Choose Advisor:</label>
          <select id="faculty-select" v-model="selectedFacultyId" @change="handleFacultySelect">
            <option value="">Select a faculty</option>
            <option v-for="faculty in facultyList" :key="faculty.id" :value="faculty.id">
              {{ faculty.name || faculty.facultyName || faculty.facultyId || faculty.id }}
            </option>
          </select>
        </div>

        <div class="form-group">
          <label>Upload Program of Study Document:</label>
          <div class="upload-row">
            <label for="pos-file" class="btn btn-primary" style="cursor: pointer;">Choose File</label>
            <span style="margin-left: 10px;">{{ fileName }}</span>
          </div>
          <input
            type="file"
            id="pos-file"
            @change="onFileChange"
            style="opacity: 0; position: absolute; z-index: -1; width: 1px; height: 1px;"
          />
          <button
            type="button"
            class="btn btn-secondary"
            style="margin-top: 10px;"
            @click="handleUpload"
            :disabled="uploading || !selectedFacultyId"
          >
            Upload
          </button>
        </div>

        <div class="form-group">
          <label for="pos-comments">Comments:</label>
          <textarea
            id="pos-comments"
            rows="4"
            placeholder="Comments for your advisor..."
            v-model="studentComment"
          ></textarea>
        </div>

        <button type="submit" class="btn btn-submit" :disabled="!canSubmit || submitting">
          Submit for Review
        </button>
      </form>
    </div>
  </div>
</template>

<style scoped>
.alert {
  margin-bottom: 12px;
  padding: 10px 12px;
  border-radius: 4px;
}

.alert-error {
  background: #fde8e8;
  color: #c0392b;
}

.alert-success {
  background: #e8f8f5;
  color: #1e8449;
}

.upload-row {
  display: flex;
  align-items: center;
  gap: 8px;
}
</style>

