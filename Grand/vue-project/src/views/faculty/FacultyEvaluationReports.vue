<template>
  <div class="page">
    <h1>Evaluation Reports</h1>
    <div class="card">
      <h3>Fill Annual Evaluation Report</h3>
      <form>
        <div class="form-group">
          <label for="student-select">Select Student:</label>
          <select id="student-select" name="student">
            <option value="zhangsan">Zhang San</option>
            <option value="lisi">Li Si</option>
            <option value="wangwu">Wang Wu</option>
          </select>
        </div>
        <div class="form-group">
          <label for="evaluation-text">Evaluation Content:</label>
          <textarea id="evaluation-text" name="evaluation" rows="8" placeholder="Enter annual evaluation for this student..."></textarea>
        </div>
        <div class="form-group">
          <label for="student-rating">Overall Rating:</label>
          <select id="student-rating" name="rating">
            <option value="excellent">Excellent</option>
            <option value="good">Good</option>
            <option value="satisfactory">Satisfactory</option>
            <option value="fail">Fail</option>
          </select>
        </div>
        <button type="submit" class="btn btn-submit">Submit Report</button>
      </form>
    </div>
  </div>
</template>