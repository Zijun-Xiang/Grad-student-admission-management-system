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
          <label for="prof-signature">E-Signature:</label>
          <input type="file" id="prof-signature">
          <p><small>Upload your e-signature for quick approvals.</small></p>
        </div>
        <button type="submit" class="btn btn-submit">Save Changes</button>
      </form>
    </div>
  </div>
</template>