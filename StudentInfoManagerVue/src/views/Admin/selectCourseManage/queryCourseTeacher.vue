<template>
  <div>
    <el-container>
      <el-main style="display: flex;flex-direction: column;align-items: center; ">
        <div style="color: black;font-size: 30px;font-weight: bolder;margin-bottom: 30px;">Course Offering Management</div>
        <el-card style="width: 80%;">
          <el-form :inline="true" :model="ruleForm" :rules="rules" ref="ruleForm" label-width="150px" class="demo-ruleForm">
            <el-form-item label="Instructor ID" prop="tid">
              <el-input v-model.number="ruleForm.tid"></el-input>
            </el-form-item>
            <el-form-item label="Instructor Name" prop="tname">
              <el-input v-model.number="ruleForm.tname"></el-input>
            </el-form-item>
            <el-form-item label="Instructor Fuzzy Search">
              <el-switch v-model="ruleForm.tFuzzy"></el-switch>
            </el-form-item>
            <el-form-item label="Course ID" prop="cid">
              <el-input v-model.number="ruleForm.cid"></el-input>
            </el-form-item>
            <el-form-item label="Course Name" prop="cname">
              <el-input v-model.number="ruleForm.cname"></el-input>
            </el-form-item>
            <el-form-item label="Course Fuzzy Search">
              <el-switch v-model="ruleForm.cFuzzy"></el-switch>
            </el-form-item>
            <el-form-item>
              <el-button type="primary" @click="resetForm('ruleForm')">Reset</el-button>
            </el-form-item>
          </el-form>
        </el-card>
        <el-card style="margin-top: 10px">
          <course-tacher-list :ruleForm="ruleForm"></course-tacher-list>
        </el-card>
      </el-main>
    </el-container>
  </div>
</template>
<script>
import CourseTacherList from "@/views/Admin/selectCourseManage/CourseTacherList";
export default {
  components: {CourseTacherList},
  data() {
    return {
      ruleForm: {
        tid: null,
        cid: null,
        cname: null,
        tname: null,
        tFuzzy: true,
        cFuzzy: true
      },
      rules: {
        tid: [
          { type: 'number', message: 'Must be a number' }
        ],
        cid: [
          { type: 'number', message: 'Must be a number' }
        ],
      }
    };
  },
  methods: {
    resetForm(formName) {
      this.$refs[formName].resetFields();
    }
  }
}
</script>
