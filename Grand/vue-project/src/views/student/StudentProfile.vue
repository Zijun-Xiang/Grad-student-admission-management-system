<template>
  <div class="page">
    <h1>Profile</h1>
    <div class="card">
      <h3>Edit Profile</h3>
      <form>
        <div class="form-group">
          <label for="student-name">Name:</label>
          <input type="text" id="student-name" value="Qiwei">
        </div>
        <div class="form-group">
          <label for="student-email">Email:</label>
          <input type="email" id="student-email" value="qiwei@example.com">
        </div>
        <div class="form-group">
          <label for="student-major">Major:</label>
         <input type="text" id="student-major" value="Computer Science">
        </div>
        <button type="submit" class="btn btn-submit">Save Changes</button>
      </form>
    </div>
  </div>
</template>