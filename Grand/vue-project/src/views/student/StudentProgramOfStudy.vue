<script setup>
// 我们需要 'ref' 来保存被选中的文件名
import { ref } from 'vue'

// 1. 创建一个变量来存储文件名，默认值是 "No file chosen"
const fileName = ref('No file chosen');

// 2. 创建一个函数，当文件被选中时，这个函数会被调用
function handleFileUpload(event) {
  const file = event.target.files[0];
  if (file) {
    // 3. 如果有文件，就把文件名更新到变量上
    fileName.value = file.name;
  } else {
    // 4. 如果用户取消了选择，就重置
    fileName.value = 'No file chosen';
  }
}
</script>

<template>
  <div class="page">
    <h1>Program of Study</h1>
    <div class="card">
      <h3>Submit Program of Study</h3>
      <form>
        <div class="form-group">
          <label>Upload Program of Study Document:</label>
          
          <label for="pos-file" class="btn btn-primary" style="cursor: pointer;">Choose File</label>
          
          <span style="margin-left: 10px;">{{ fileName }}</span>

          <input 
            type="file" 
            id="pos-file" 
            @change="handleFileUpload" 
            style="opacity: 0; position: absolute; z-index: -1; width: 1px; height: 1px;"
          >
        </div>
        <div class="form-group">
          <label for="pos-comments">Comments:</label>
          <textarea id="pos-comments" rows="4" placeholder="Comments for your advisor..."></textarea>
        </div>
        <button type="submit" class="btn btn-submit">Submit for Review</button>
      </form>
    </div>
  </div>
</template>

<style scoped>
/* 我们给自制的按钮添加一个 'cursor: pointer' 样式，
  让它在鼠标悬停时显示“小手”图标，更像一个按钮。
  不过，我已将它内联写在了上面的 <label> 中。
*/
</style>