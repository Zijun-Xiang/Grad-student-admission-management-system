<template>
  <div class="page">
    <h1>Program of Study</h1>
    <div class="card">
      <h3>Submit Program of Study</h3>
      <form>
        <div class="form-group">
          <label for="pos-file">Upload Program of Study Document:</label>
          <input type="file" id="pos-file">
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