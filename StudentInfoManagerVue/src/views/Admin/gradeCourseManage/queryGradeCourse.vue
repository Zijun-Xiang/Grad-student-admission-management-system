<template>
  <div>
    <el-container>
      <el-main style="display: flex;flex-direction: column;align-items: center; ">
        <div style="color: black;font-size: 30px;font-weight: bolder;margin-bottom: 30px;">学生成绩查询</div>
        <el-card style="width: 90%;">
          <el-form :inline="true" :model="ruleForm" :rules="rules" ref="ruleForm" label-width="120px"
            class="demo-ruleForm" style="height: 100%;;width: 100%;display: flex;flex-direction: column;">
            <div style="width: 100%;display: flex;flex-direction: row;">
            <el-form-item label="学号" prop="sid" style="display: flex;flex-direction: row;">
              <el-input v-model.number="ruleForm.sid"></el-input>
            </el-form-item>
            <el-form-item label="学生名" prop="sname" style="display: flex;flex-direction: row;">
              <el-input v-model="ruleForm.sname"></el-input>
            </el-form-item>
            <el-form-item label="模糊查询" prop="sFuzzy" style="display: flex;flex-direction: row;">
              <el-switch v-model="ruleForm.sFuzzy"></el-switch>
            </el-form-item>
          </div>
          <div style="width: 100%;display: flex;flex-direction: row;">
            <el-form-item label="工号" prop="tid"  style="display: flex;flex-direction: row;">
              <el-input v-model.number="ruleForm.tid"></el-input>
            </el-form-item>
            <el-form-item label="教师名" prop="tname"  style="display: flex;flex-direction: row;">
              <el-input v-model="ruleForm.tname"></el-input>
            </el-form-item>
            <el-form-item label="模糊查询" prop="tFuzzy"  style="display: flex;flex-direction: row;">
              <el-switch v-model="ruleForm.tFuzzy"></el-switch>
            </el-form-item>
            </div>
            <div style="width: 100%;display: flex;flex-direction: row;">
            <el-form-item label="课程号" prop="cid"  style="display: flex;flex-direction: row;">
              <el-input v-model.number="ruleForm.cid"></el-input>
            </el-form-item>
            <el-form-item label="课程名" prop="cname"  style="display: flex;flex-direction: row;">
              <el-input v-model="ruleForm.cname"></el-input>
            </el-form-item>
            <el-form-item label="模糊查询" prop="cFuzzy"  style="display: flex;flex-direction: row;">
              <el-switch v-model="ruleForm.cFuzzy"></el-switch>
            </el-form-item>
            </div>
            <div style="width: 100%;display: flex;flex-direction: row;">
            <el-form-item label="成绩下限" prop="lowBound"  style="display: flex;flex-direction: row;">
              <el-input v-model.number="ruleForm.lowBound"></el-input>
            </el-form-item>
            <el-form-item label="成绩上限" prop="highBound"  style="display: flex;flex-direction: row;">
              <el-input v-model.number="ruleForm.highBound"></el-input>
            </el-form-item>
          </div>
            <el-form-item label="选择学期"  style="display: flex;flex-direction: row;">
              <el-select v-model="ruleForm.term" placeholder="请选择学期">
                <el-option v-for="(item, index) in termList" :key="index" :label="item" :value="item"></el-option>
              </el-select>
            </el-form-item>
           
            <el-form-item style="width: 100%; display: flex;align-items: center;">
              <el-button type="primary" @click="resetForm('ruleForm')">重置</el-button>
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
          { type: 'number', message: '必须是数字类型' }
        ],
        tid: [
          { type: 'number', message: '必须是数字类型' }
        ],
        sid: [
          { type: 'number', message: '必须是数字类型' }
        ],
        cname: [
        ],
        lowBound: [
          { type: 'number', message: '必须是数字类型' }
        ],
        highBound: [
          { type: 'number', message: '必须是数字类型' }
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