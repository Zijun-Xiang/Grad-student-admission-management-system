<template>
  <div>
    <el-container>
      <el-main style="display: flex;flex-direction: column;align-items: center; ">
        <div style="color: black;font-size: 30px;font-weight: bolder;margin-bottom: 30px;">Course Search</div>
        <el-card style="width: 90%;">
          <div style="color: black;font-size: 20px; font-weight: bold;margin-bottom: 20px;" >Filters</div>
          <el-form :inline="true" :model="ruleForm" :rules="rules" ref="ruleForm" label-width="120px"
            class="demo-ruleForm">
            <el-form-item label="Course ID" prop="cid">
              <el-input v-model.number="ruleForm.cid"></el-input>
            </el-form-item>
            <el-form-item label="Course Name" prop="cname">
              <el-input v-model="ruleForm.cname"></el-input>
            </el-form-item>
            <el-form-item label="Fuzzy Search" prop="fuzzy">
              <el-switch v-model="ruleForm.fuzzy"></el-switch>
            </el-form-item>
            <el-form-item label="Minimum Credits" prop="lowBound">
              <el-input v-model.number="ruleForm.lowBound"></el-input>
            </el-form-item>
            <el-form-item label="Maximum Credits" prop="highBound">
              <el-input v-model.number="ruleForm.highBound"></el-input>
            </el-form-item>
            <el-form-item>
              <el-button type="primary" @click="resetForm('ruleForm')">Reset</el-button>
            </el-form-item>
          </el-form>
        </el-card>
        <el-card style="margin-top: 10px;width: 90%;">
          <div style="color: black;font-size: 20px; font-weight: bold;margin-bottom: 20px;" >Search Results</div>
          <offer-course-list :rule-form="ruleForm"></offer-course-list>
        </el-card>
      </el-main>
    </el-container>
  </div>
</template>
<script>
import OfferCourseList from "@/views/Teacher/offerCourseList";
export default {
  components: { OfferCourseList },
  data() {
    return {
      ruleForm: {
        cid: null,
        cname: null,
        fuzzy: true,
        lowBound: null,
        highBound: null
      },
      rules: {
        cid: [
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
  methods: {
    resetForm(formName) {
      this.$refs[formName].resetFields();
    }
  }
}
</script>
