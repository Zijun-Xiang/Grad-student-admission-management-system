<template>
  <div >
    <el-container>
      <el-main style="display: flex;flex-direction: column;align-items: center; ">
        <div style="color: black;font-size: 30px;font-weight: bolder;margin-bottom: 30px;">Student Search</div>
        <el-card style="margin-bottom: 20px;">  
          <el-form :inline="true" :model="ruleForm" :rules="rules" ref="ruleForm" label-width="100px"
            class="demo-ruleForm">
            <el-form-item label="Student ID" prop="sid">
              <el-input v-model.number="ruleForm.sid"></el-input>
            </el-form-item>
            <el-form-item label="Student Name" prop="sname">
              <el-input v-model="ruleForm.sname"></el-input>
            </el-form-item>
            <el-form-item label="Fuzzy Search" prop="password">
              <el-switch v-model="ruleForm.password"></el-switch>
            </el-form-item>
            <el-form-item>
              <el-button type="primary" @click="submitForm('ruleForm')">Search</el-button>
              <el-button @click="resetForm('ruleForm')" type="warning">Reset</el-button>
              <el-button @click="flush('ruleForm')">Refresh</el-button>
            </el-form-item>
          </el-form>
        </el-card>
        <router-view></router-view>
      </el-main>
    </el-container>
  </div>
</template>
<script>
export default {
  data() {
    return {
      ruleForm: {
        sid: null,
        sname: null,
        password: true
      },
      rules: {
        sid: [
          { type: 'number', message: 'Must be a number' }
        ],
        sname: [

        ],
      }
    };
  },
  create() {
    this.sid = null
    this.sname = null
    this.password = true
  },
  methods: {
    flush(formName) {
      this.$router.push('/queryStudent');
      this.$refs[formName].resetFields();
    },
    submitForm(formName) {
      this.$refs[formName].validate((valid) => {
        if (valid) {
          if (this.ruleForm.password === true) {
            this.ruleForm.password = 'fuzzy'
          }
          else {
            this.ruleForm.password = null
          }
          let url = null
          if (this.ruleForm.sid === null && this.ruleForm.sname === null) {
            url = '/studentList'
          }
          else {
            url = '/queryStudent/studentList'
          }
          this.$router.push({
            path: url,
            query: {
              ruleForm: this.ruleForm
            }
          })
        } else {
          console.log('error submit!!');
          return false;
        }
      });
    },
    resetForm(formName) {
      this.$refs[formName].resetFields();
    }
  }
}
</script>
