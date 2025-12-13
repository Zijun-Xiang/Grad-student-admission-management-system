<template>
  <div>
    <el-container>
      <el-main style="display: flex;flex-direction: column;align-items: center; ">
        <div style="color: black;font-size: 30px;font-weight: bolder;margin-bottom: 30px;">学生选课</div>
        <el-card
          style="width: 90%;display: flex;flex-direction: column;align-items: center;align-content: center;">
          <div style="width: 100%;text-align: center;color: black;font-size: 30px;font-weight: bolder;margin-bottom: 20px;">选课查询</div>
          <el-form :inline="true" :model="ruleForm" :rules="rules" ref="ruleForm" label-width="150px"
            class="demo-ruleForm"  style="width: 100%;display: flex;flex-direction: column;justify-content: flex-start;align-items: flex-start;">
            <el-form-item label="工号" prop="tid">
              <el-input v-model.number="ruleForm.tid"></el-input>
            </el-form-item>
            <div style="width: 100%; display: flex;flex-direction: row;" >
            <el-form-item label="教师名" prop="tname">
              <el-input v-model.number="ruleForm.tname"></el-input>
            </el-form-item>
            <el-form-item label="教师模糊查询">
              <el-switch v-model="ruleForm.tFuzzy"></el-switch>
            </el-form-item>
          </div>
            <el-form-item label="课程号" prop="cid">
              <el-input v-model.number="ruleForm.cid"></el-input>
            </el-form-item>
            <div style="width: 100%; display: flex;flex-direction: row;" >
            <el-form-item label="课程名" prop="cname">
              <el-input v-model.number="ruleForm.cname"></el-input>
            </el-form-item>
            <el-form-item label="课程模糊查询">
              <el-switch v-model="ruleForm.cFuzzy"></el-switch>
            </el-form-item>
            </div>
            <el-form-item style="width: 100%; align-items: center; display: flex;padding-left: 50px; ">
              <el-button type="primary" @click="resetForm('ruleForm')">重置</el-button>
            </el-form-item>
          </el-form>
        </el-card>
        <el-card style="margin-top: 10px;width: 90%;">
          <div style="width: 100%;text-align: center;color: black;font-size: 30px;font-weight: bolder;margin-bottom: 20px;">查询结果</div>
          <select-course-list :rule-form="ruleForm"></select-course-list>
        </el-card>
      </el-main>
    </el-container>
  </div>
</template>
<style scope>
.el-card__body {
  width: 100%;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: flex-start;
}
</style>
<script>
import SelectCourseList from "@/views/Student/selectCourse/selectCourseList";
export default {
  components: { SelectCourseList },
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
          { type: 'number', message: '必须是数字类型' }
        ],
        cid: [
          { type: 'number', message: '必须是数字类型' }
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