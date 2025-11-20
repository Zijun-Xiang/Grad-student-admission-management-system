<script setup>
// 学生查看 ChooseInstructor 提交流程的里程碑状态
import { onMounted, ref } from 'vue'
import axios from 'axios'

const milestones = ref([])
const loading = ref(false)
const errorMsg = ref('')

const loadMilestones = async () => {
  loading.value = true
  errorMsg.value = ''
  try {
    const res = await axios.get('/api/student/milestones/')
    milestones.value = res.data || []
  } catch (error) {
    errorMsg.value = 'Failed to load milestone data.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadMilestones()
})
</script>

<template>
  <div class="page">
    <h1>Milestone Tracker</h1>

    <div v-if="errorMsg" class="alert alert-error">{{ errorMsg }}</div>
    <div v-if="loading" class="alert">Loading...</div>

    <div class="card">
      <ul>
        <li v-for="item in milestones" :key="item.title">
          {{ item.title }} - <span>{{ item.done ? '✔' : '✘' }}</span>
        </li>
      </ul>
      <p v-if="!loading && milestones.length === 0">No milestone data available.</p>
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
</style>