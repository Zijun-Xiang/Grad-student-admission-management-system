<script setup>
import { onMounted, ref } from 'vue'
import axios from 'axios'

const records = ref([])
const loading = ref(false)
const errorMsg = ref('')

const loadRecords = async () => {
  loading.value = true
  errorMsg.value = ''
  try {
    const res = await axios.get('/api/admin/choose-instructor/')
    records.value = res.data || []
  } catch (error) {
    errorMsg.value = 'Failed to load workflow data.'
  } finally {
    loading.value = false
  }
}

const handleAction = async (id, action) => {
  try {
    await axios.post(`/api/admin/choose-instructor/${id}/${action}/`)
    await loadRecords()
  } catch (error) {
    errorMsg.value = 'Operation failed. Please try again.'
  }
}

const handleDelete = async (id) => {
  try {
    await axios.delete(`/api/admin/choose-instructor/${id}/`)
    await loadRecords()
  } catch (error) {
    errorMsg.value = 'Delete failed. Please try again.'
  }
}

onMounted(() => {
  loadRecords()
})
</script>

<template>
  <div class="page">
    <h1>Workflow Management</h1>
    <div class="card">
      <h3>Approval Processes</h3>
      <p>Manage approval processes for "Program of Study", "Committee Formation", etc.</p>
      <p><strong>Current Flow:</strong> Program of Study -> Advisor Approval -> Grad School Approval</p>
      <button class="btn btn-primary">Edit Flow</button>
    </div>

    <div v-if="errorMsg" class="alert alert-error">{{ errorMsg }}</div>
    <div v-if="loading" class="alert">Loading...</div>

    <div v-for="item in records" :key="item.id" class="card">
      <h3>{{ item.studentName }} - {{ item.facultyName }}</h3>
      <p><strong>State:</strong> {{ item.state }}</p>
      <p><strong>Submitted At:</strong> {{ item.submittedAt || 'N/A' }}</p>
      <p><strong>Reviewed At:</strong> {{ item.reviewedAt || 'N/A' }}</p>
      <p><strong>File:</strong> <a v-if="item.file" :href="item.file" target="_blank" rel="noopener noreferrer">View</a></p>
      <p><strong>Faculty Comment:</strong> {{ item.facultyComment || 'N/A' }}</p>
      <p><strong>Student Comment:</strong> {{ item.studentComment || 'N/A' }}</p>
      <div class="action-row" style="display: flex; gap: 8px; flex-wrap: wrap;">
        <button class="btn btn-primary" @click="handleAction(item.id, 'approve')">Approve</button>
        <button class="btn btn-secondary" @click="handleAction(item.id, 'reject')">Reject</button>
        <button class="btn btn-danger" @click="handleDelete(item.id)">Delete</button>
      </div>
    </div>
  </div>
</template>