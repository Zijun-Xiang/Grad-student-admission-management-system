<template>
  <div class="page">
    <h1>User Management</h1>
    <div class="card">
      <h3>Manage Users</h3>
      <p>Add, edit, or delete user information here.</p>
      <button class="btn btn-primary" @click="openAddForm">Add New User</button>
    </div>

    <!-- 弹窗 -->
    <div v-if="showForm" class="modal">
      <div class="modal-content">
        <h2>{{ isEditing ? 'Edit User' : 'Add New User' }}</h2>

        <label>ID:</label>
        <input v-model="form.user_id" type="text" placeholder="Enter ID" :disabled="isEditing" />

        <label>Name:</label>
        <input v-model="form.name" type="text" placeholder="Enter Name" />

        <label>Department:</label>
        <input v-model="form.department" type="text" placeholder="Enter Department" />

        <label>Identity:</label>
        <select v-model="form.identity">
          <option value="Faculty">Faculty</option>
          <option value="Student">Student</option>
        </select>

        <div class="buttons">
          <button class="save" @click="saveUser">{{ isEditing ? 'Update' : 'Save' }}</button>
          <button class="cancel" @click="closeForm">Cancel</button>
        </div>
      </div>
    </div>

    <!-- 用户表格 -->
    <div class="user-list" v-if="persons.length">
      <h3>Existing Users</h3>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Department</th>
            <th>Identity</th>
            <th>Password</th>
            <th>Actions</th>
            
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in persons" :key="p.id">
            <td>{{ p.user_id }}</td>
            <td>{{ p.name }}</td>
            <td>{{ p.department }}</td>
            <td>{{ p.identity }}</td>
            <td>{{ p.password }}</td>
            <td>
              <button class="edit" @click="editUser(p)">Edit</button>
              <button class="delete" @click="deleteUser(p.id)">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else class="empty">
      <p>No users found. Click "Add New User" to create one.</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const showForm = ref(false)
const persons = ref([])
const isEditing = ref(false)
const editId = ref(null)

const form = ref({
  user_id: '',
  name: '',
  department: '',
  identity: 'Student' // 默认 Student
})

const API_URL = 'http://127.0.0.1:8000/api/admin/persons/'

// 获取所有用户
const fetchUsers = async () => {
  try {
    const res = await axios.get(API_URL)
    persons.value = res.data
  } catch (err) {
    console.error('Error fetching users:', err)
  }
}

// 打开添加窗口
const openAddForm = () => {
  isEditing.value = false
  form.value = { user_id: '', name: '', department: '', identity: 'Student' }
  showForm.value = true
}

// 打开编辑窗口
const editUser = (p) => {
  isEditing.value = true
  editId.value = p.id
  form.value = { ...p }
  showForm.value = true
}

// 保存或更新
const saveUser = async () => {
  try {
    if (isEditing.value) {
      await axios.put(`${API_URL}${editId.value}/`, form.value)
      console.log('User added successfully!')

    } else {
      await axios.post(API_URL, form.value)
     console.log('User added successfully!')

    }
    closeForm()
    fetchUsers()
  } catch (err) {
    console.error('Error saving user:', err)
    alert('Failed to save user.')
  }
}

// 删除用户
const deleteUser = async (id) => {
  if (!confirm('Are you sure you want to delete this user?')) return
  try {
    await axios.delete(`${API_URL}${id}/`)
    alert('User deleted successfully!')
    fetchUsers()
  } catch (err) {
    console.error('Error deleting user:', err)
    alert('Failed to delete user.')
  }
}

// 关闭弹窗
const closeForm = () => {
  showForm.value = false
  form.value = { user_id: '', name: '', department: '', identity: 'Student' }
}

onMounted(fetchUsers)
</script>

<style scoped>
/* 保留原有整体风格 */
.page {
  padding: 20px;
}
.card {
  background: #fff;
  padding: 20px;
  border-radius: 10px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}
.btn {
  padding: 8px 16px;
  border: none;
  background-color: #00b894;
  color: white;
  border-radius: 6px;
  cursor: pointer;
}
.btn:hover {
  background-color: #019874;
}
.modal {
  position: fixed;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background-color: rgba(0,0,0,0.4);
  display: flex;
  align-items: center;
  justify-content: center;
}
.modal-content {
  background: #fff;
  padding: 20px;
  border-radius: 10px;
  width: 320px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.modal-content label {
  font-weight: 600;
}
.modal-content input, .modal-content select {
  padding: 6px;
  border: 1px solid #ccc;
  border-radius: 5px;
}
.buttons {
  display: flex;
  justify-content: space-between;
  margin-top: 10px;
}
.save {
  background-color: #00b894;
  color: white;
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
}
.cancel {
  background-color: #636e72;
  color: white;
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
}
.user-list {
  margin-top: 30px;
}
table {
  width: 100%;
  border-collapse: collapse;
}
th, td {
  border: 1px solid #ddd;
  padding: 8px;
  text-align: center;
}
th {
  background-color: #00b894;
  color: white;
}
.edit {
  background-color: #0984e3;
  color: white;
  border: none;
  padding: 6px 10px;
  border-radius: 4px;
  margin-right: 5px;
}
.delete {
  background-color: #d63031;
  color: white;
  border: none;
  padding: 6px 10px;
  border-radius: 4px;
}
.edit:hover {
  background-color: #0870c0;
}
.delete:hover {
  background-color: #b21f1f;
}
.empty {
  margin-top: 20px;
  text-align: center;
  color: #636e72;
}
</style>
