<script setup>
// 1. 导入 ref
import { ref } from 'vue'

// 2. 创建一个变量来存储文件名
const fileName = ref('No file chosen');

// 3. 创建一个函数，当文件被选中时，这个函数会被调用
function handleFileUpload(event) {
  const file = event.target.files[0];
  if (file) {
    // 4. 如果有文件，就把文件名更新到变量上
    fileName.value = file.name;
  } else {
    // 5. 如果用户取消了选择，就重置
    fileName.value = 'No file chosen';
  }
}
</script>

<template>
  <div class="page">
    <h1>Profile Settings</h1>
    <div class="card">
      <h3>Edit Profile</h3>
      <form>
        <div class="form-group">
          <label for="prof-name">Name:</label>
          <input type="text" id="prof-name" value="Faculty Name">
        </div>
        <div class="form-group">
          <label for="prof-email">Email:</label>
          <input type="email" id="prof-email" value="faculty@example.com">
        </div>
        <div class="form-group">
          <label>E-Signature:</label>
          
          <label for="prof-signature" class="btn btn-primary" style="cursor: pointer;">Choose File</label>
          
          <div style="margin-left: 10px;">{{ fileName }}</div>

          <input 
            type="file" 
            id="prof-signature" 
            @change="handleFileUpload" 
            style="opacity: 0; position: absolute; z-index: -1; width: 1px; height: 1px;"
          >
          <p style="margin-top: 10px;"><small>Upload your e-signature for quick approvals.</small></p>
        </div>
        <button type="submit" class="btn btn-submit">Save Changes</button>
      </form>
    </div>
  </div>
</template>

