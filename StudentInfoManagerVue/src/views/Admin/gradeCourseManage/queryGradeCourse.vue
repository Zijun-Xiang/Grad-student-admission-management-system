<template>
  <div>
    <el-container>
      <el-main style="display: flex;flex-direction: column;align-items: center; ">
        <div style="color: black;font-size: 30px;font-weight: bolder;margin-bottom: 30px;">Student Grade Search</div>
        <el-card style="width: 90%;">
          <el-form :inline="true" :model="ruleForm" :rules="rules" ref="ruleForm" label-width="120px"
            class="demo-ruleForm" style="height: 100%;;width: 100%;display: flex;flex-direction: column;">
            <div style="width: 100%;display: flex;flex-direction: row;">
            <el-form-item label="Student ID" prop="sid" style="display: flex;flex-direction: row;">
              <el-input v-model.number="ruleForm.sid"></el-input>
            </el-form-item>
            <el-form-item label="Student Name" prop="sname" style="display: flex;flex-direction: row;">
              <el-input v-model="ruleForm.sname"></el-input>
            </el-form-item>
            <el-form-item label="Fuzzy Search" prop="sFuzzy" style="display: flex;flex-direction: row;">
              <el-switch v-model="ruleForm.sFuzzy"></el-switch>
            </el-form-item>
          </div>
          <div style="width: 100%;display: flex;flex-direction: row;">
            <el-form-item label="Teacher ID" prop="tid"  style="display: flex;flex-direction: row;">
              <el-input v-model.number="ruleForm.tid"></el-input>
            </el-form-item>
            <el-form-item label="Instructor Name" prop="tname"  style="display: flex;flex-direction: row;">
              <el-input v-model="ruleForm.tname"></el-input>
            </el-form-item>
            <el-form-item label="Fuzzy Search" prop="tFuzzy"  style="display: flex;flex-direction: row;">
              <el-switch v-model="ruleForm.tFuzzy"></el-switch>
            </el-form-item>
            </div>
            <div style="width: 100%;display: flex;flex-direction: row;">
            <el-form-item label="Course ID" prop="cid"  style="display: flex;flex-direction: row;">
              <el-input v-model.number="ruleForm.cid"></el-input>
            </el-form-item>
            <el-form-item label="Course Name" prop="cname"  style="display: flex;flex-direction: row;">
              <el-input v-model="ruleForm.cname"></el-input>
            </el-form-item>
            <el-form-item label="Fuzzy Search" prop="cFuzzy"  style="display: flex;flex-direction: row;">
              <el-switch v-model="ruleForm.cFuzzy"></el-switch>
            </el-form-item>
            </div>
            <div style="width: 100%;display: flex;flex-direction: row;">
            <el-form-item label="Minimum Grade" prop="lowBound"  style="display: flex;flex-direction: row;">
              <el-input v-model.number="ruleForm.lowBound"></el-input>
            </el-form-item>
            <el-form-item label="Maximum Grade" prop="highBound"  style="display: flex;flex-direction: row;">
              <el-input v-model.number="ruleForm.highBound"></el-input>
            </el-form-item>
          </div>
            <el-form-item label="Select Term"  style="display: flex;flex-direction: row;">
              <el-select v-model="ruleForm.term" placeholder="Please choose a term">
                <el-option v-for="(item, index) in termList" :key="index" :label="item" :value="item"></el-option>
              </el-select>
            </el-form-item>
           
            <el-form-item style="width: 100%; display: flex;align-items: center;">
              <el-button type="primary" @click="resetForm('ruleForm')">Reset</el-button>
            </el-form-item>
          </el-form>
        </el-card>
        <el-card style="margin-top: 10px;width: 90%">
          <grade-course-list :rule-form="ruleForm" style="height: 100%;"></grade-course-list>
        </el-card>
      </el-main>
    </el-container>
  </div>
</template>
<script>
import GradeCourseList from "@/views/Admin/gradeCourseManage/gradeCourseList";
export default {
  components: { GradeCourseList },
  data() {
    return {
      termList: null,
      ruleForm: {
        sid: null,
        sname: null,
        sFuzzy: true,
        tid: null,
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
      console.log(1333)
      console.log( resp.data)
    })
  },
  methods: {
    resetForm(formName) {
      this.$refs[formName].resetFields();
    }
  }
}
</script>
