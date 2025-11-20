<script setup>
// 导师审批 ChooseInstructor 提交
import { onMounted, ref } from 'vue'
import axios from 'axios'

const requests = ref([])
const loading = ref(false)
const errorMsg = ref('')

// 为每条记录保存导师填写的评论
const commentMap = ref({})

const loadRequests = async () => {
  loading.value = true
  errorMsg.value = ''
  try {
    const res = await axios.get('/api/faculty/choose-instructor/requests/')
    requests.value = res.data || []
  } catch (error) {
    errorMsg.value = 'Failed to load approval requests.'
  } finally {
    loading.value = false
  }
}

const getComment = (id) => commentMap.value[id] || ''

const updateComment = (id, value) => {
  commentMap.value = { ...commentMap.value, [id]: value }
}

const handleAction = async (id, action) => {
  const comment = getComment(id)
  try {
    await axios.post(`/api/faculty/choose-instructor/requests/${id}/${action}/`, {
      facultyComment: comment,
    })
    await loadRequests()
  } catch (error) {
    errorMsg.value = 'Action failed. Please try again.'
  }
}

onMounted(() => {
  loadRequests()
})
</script>

<template>
  <div class="page">
    <h1>Approval Requests</h1>

    <div v-if="errorMsg" class="alert alert-error">{{ errorMsg }}</div>
    <div v-if="loading" class="alert">Loading...</div>

    <div v-if="!loading && requests.length === 0" class="card">
      <p>No submissions pending.</p>
    </div>

    <div v-for="req in requests" :key="req.id" class="card">
      <h3>{{ req.studentName }}: Program of Study</h3>
      <p><strong>Submitted:</strong> {{ req.submittedAt || 'N/A' }}</p>
      <p><strong>State:</strong> {{ req.state }}</p>
      <p><strong>Student Comment:</strong> {{ req.studentComment || 'N/A' }}</p>
      <p><strong>Faculty Comment:</strong> {{ req.facultyComment || 'N/A' }}</p>
      <p v-if="req.file">
        <a :href="req.file" class="btn btn-primary" target="_blank" rel="noopener noreferrer">View Document</a>
      </p>

      <div class="form-group">
        <label for="comment">Add Comment:</label>
        <textarea
          :id="`comment-${req.id}`"
          rows="3"
          placeholder="Comments to student..."
          :value="getComment(req.id)"
          @input="(e) => updateComment(req.id, e.target.value)"
        ></textarea>
      </div>

      <div class="action-row">
        <button class="btn btn-primary" @click="handleAction(req.id, 'approve')">Approve</button>
        <button class="btn btn-danger" @click="handleAction(req.id, 'reject')">Reject</button>
      </div>
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

.action-row {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
</style>