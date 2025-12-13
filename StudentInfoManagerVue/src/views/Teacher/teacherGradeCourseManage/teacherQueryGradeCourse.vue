<template>
  <div>
    <el-container>
      <el-main style="display: flex;flex-direction: column;">
        <div style="color: black;font-size: 30px;font-weight: bolder;margin-bottom: 30px;">Student Grade Management</div>
        <el-card style="width: 90%;">
          <div style="color: black;font-size: 20px; font-weight: bold;margin-bottom: 20px;" >Grade Search</div>
          <el-form :inline="true" :model="ruleForm" :rules="rules" ref="ruleForm" label-width="120px"
            class="demo-ruleForm">
            <el-form-item label="Student ID" prop="sid">
              <el-input v-model.number="ruleForm.sid"></el-input>
            </el-form-item>
            <el-form-item label="Student Name" prop="sname">
              <el-input v-model="ruleForm.sname"></el-input>
            </el-form-item>
            <el-form-item label="Fuzzy Search" prop="sFuzzy">
              <el-switch v-model="ruleForm.sFuzzy"></el-switch>
            </el-form-item>
            <el-form-item label="Course ID" prop="cid">
              <el-input v-model.number="ruleForm.cid"></el-input>
            </el-form-item>
            <el-form-item label="Course Name" prop="cname">
              <el-input v-model="ruleForm.cname"></el-input>
            </el-form-item>
            <el-form-item label="Fuzzy Search" prop="cFuzzy">
              <el-switch v-model="ruleForm.cFuzzy"></el-switch>
            </el-form-item>
            <el-form-item label="Minimum Grade" prop="lowBound">
              <el-input v-model.number="ruleForm.lowBound"></el-input>
            </el-form-item>
            <el-form-item label="Maximum Grade" prop="highBound">
              <el-input v-model.number="ruleForm.highBound"></el-input>
            </el-form-item>
            <el-form-item label="Select Term">
              <el-select v-model="ruleForm.term" placeholder="Please choose a term">
                <el-option v-for="(item, index) in termList" :key="index" :label="item" :value="item"></el-option>
              </el-select>
            </el-form-item>
            <el-form-item>
              <el-button type="primary" @click="resetForm('ruleForm')">Reset</el-button>
            </el-form-item>
          </el-form>
        </el-card >
        <el-card style="margin-top: 10px;width: 90%;">
          <div style="color: black;font-size: 20px; font-weight: bold;margin-bottom: 20px;" >Search Results</div>
          <teacher-grade-course-list :rule-form="ruleForm"></teacher-grade-course-list>
        </el-card>
      </el-main>
    </el-container>
  </div>
</template>
<script>
import GradeCourseList from "@/views/Admin/gradeCourseManage/gradeCourseList";
import TeacherGradeCourseList from "@/views/Teacher/teacherGradeCourseManage/teacherGradeCourseList";
export default {
  components: { TeacherGradeCourseList, GradeCourseList },
  data() {
    return {
      termList: null,
      ruleForm: {
        sid: null,
        sname: null,
        sFuzzy: true,
        tid: sessionStorage.getItem('tid'),
        tname: null,
        tFuzzy: true,
        cid: null,
        cname: null,
        cFuzzy: true,
        lowBound: null,
        highBound: null,
        term: sessionStorage.getItem('currentTerm')
      },
      rules: {
        cid: [
          { type: 'number', message: 'Must be a number' }
        ],
        tid: [
          { type: 'number', message: 'Must be a number' }
        ],
        sid: [
          { type: 'number', message: 'Must be a number' }
        ],
        cname: [
        ],
        lowBound: [
          { type: 'number', message: 'Must be a number' }
        ],
        highBound: [
          { type: 'number', message: 'Must be a number' }
        ],
      }
    };
  },
  created() {
    const that = this
    axios.get('http://localhost:9451/SCT/findAllTerm').then(function (resp) {
      that.termList = resp.data
      console.log(1122)
      console.log(resp.data)
    })
  },
  methods: {
    resetForm(formName) {
      this.$refs[formName].resetFields();
    }
  }
}
</script>
