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
    <h1>My Documents</h1>
    <div class="card">
      <h3>Document Hub</h3>
      <p>Upload and manage all your academic documents here.</p>
      
      <div class="form-group">
        <label>Upload New Document:</label>
        
        <label for="doc-upload" class="btn btn-primary" style="cursor: pointer;">Choose File</label>
        
        <span style="margin-left: 10px;">{{ fileName }}</span>

        <input 
          type="file" 
          id="doc-upload" 
          @change="handleFileUpload" 
          style="opacity: 0; position: absolute; z-index: -1; width: 1px; height: 1px;"
        >
      </div>
      
      <button class="btn btn-submit" style="margin-top: 10px;">Upload</button>
    </div>
  </div>
</template>

