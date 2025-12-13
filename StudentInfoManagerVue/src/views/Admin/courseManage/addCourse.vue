<template>
  <div  style="display: flex;flex-direction: column;align-items: center; ">
      <div style="color: black;font-size: 30px;font-weight: bolder;margin-bottom: 30px;">Add Course</div>
    <el-form style="width: 40%" :model="ruleForm" :rules="rules" ref="ruleForm" label-width="100px" class="demo-ruleForm">
      <el-form-item label="Course Name" prop="cname">
        <el-input v-model="ruleForm.cname"></el-input>
      </el-form-item>
      <el-form-item label="Credits" prop="ccredit">
        <el-input v-model.number="ruleForm.ccredit"></el-input>
      </el-form-item>
      <el-form-item  class="btns-wrapper" style="width: 100%; display: flex ;">
                <el-button type="primary" @click="submitForm('ruleForm')">Submit</el-button>
                <el-button @click="resetForm('ruleForm')" type="warning">Reset</el-button>
              </el-form-item>


    </el-form>
  </div>
</template>
<script>
export default {
  data() {
    return {
      ruleForm: {
        cname: null,
        ccredit: null
      },
      rules: {
        cname: [
          { required: true, message: 'Please enter a name', trigger: 'blur' },
        ],
        ccredit: [
          { required: true, message: 'Please enter credits', trigger: 'change' },
          { type: 'number', message: 'Please enter a number', trigger: 'blur' },
        ],
      }
    };
  },
  methods: {
    submitForm(formName) {
      this.$refs[formName].validate((valid) => {
        if (valid) {
          // 通过前端校验
          const that = this
          // console.log(this.ruleForm)

          axios.post("http://localhost:9451/course/save", this.ruleForm).then(function (resp) {
            console.log(resp)
            if (resp.data === true) {
              that.$message({
                showClose: true,
                message: 'Created successfully',
                type: 'success'
              });
            }
            else {
              that.$message.error('Creation failed, please check the database');
            }
            that.$router.push("/queryCourse")
          })
        } else {
          return false;
        }
      });
    },
    resetForm(formName) {
      this.$refs[formName].resetFields();
    },
    test() {
      console.log(this.ruleForm)
    }
  }
}
</script>
<style scoped>
.btns-wrapper .el-form-item__content {
    margin: 0px !important;
    padding-right: 5px
}

.btns-wrapper button {
    margin: 0 20px;
    /* 每个按钮左右各7.5px的外边距，总间距为15px */
}
</style>
