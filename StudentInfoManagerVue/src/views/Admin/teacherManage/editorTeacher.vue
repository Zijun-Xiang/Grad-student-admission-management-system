<template>
  <div style="display: flex;flex-direction: column;align-items: center; ">
    <div style="color: black;font-size: 30px;font-weight: bolder;margin-bottom: 30px;">Edit Teacher Information</div>
    <el-form style="width: 40%" :model="ruleForm" :rules="rules" ref="ruleForm" label-width="100px"
      class="demo-ruleForm">
      <el-form-item label="Teacher Name" prop="tname">
        <el-input v-model="ruleForm.tname" :value="ruleForm.tname"></el-input>
      </el-form-item>
      <el-form-item label="Initial Password" prop="password">
        <el-input v-model="ruleForm.password" :value="ruleForm.password"></el-input>
      </el-form-item>
      <el-form-item class="btns-wrapper" style="width: 100%; display: flex ;">
        <el-button type="primary" @click="submitForm('ruleForm')">Submit</el-button>
      </el-form-item>


    </el-form>
  </div>
</template>
<script>
export default {
  data() {
    return {
      ruleForm: {
        tid: null,
        tname: null,
        password: null
      },
      rules: {
        tname: [
          { required: true, message: 'Please enter a name', trigger: 'blur' },
          { min: 2, max: 5, message: 'Length must be 2 to 5 characters', trigger: 'blur' }
        ],
        password: [
          { required: true, message: 'Please enter a password', trigger: 'change' }
        ],
      }
    };
  },
  created() {
    const that = this
    if (this.$route.query.tid === undefined) {
      this.ruleForm.tid = 6
    }
    else {
      this.ruleForm.tid = this.$route.query.tid
    }
    axios.get('http://localhost:9451/teacher/findById/' + this.ruleForm.tid).then(function (resp) {
      that.ruleForm = resp.data
    })
  },
  methods: {
    submitForm(formName) {
      this.$refs[formName].validate((valid) => {
        if (valid) {
          // 通过前端校验
          const that = this
          if (that.ruleForm.tname === 'admin') {
            that.$message({
              showClose: true,
              message: 'The admin account cannot be edited',
              type: 'error'
            });
            this.$router.push('/queryTeacher')
            return
          }
          console.log(this.ruleForm)
          axios.post("http://localhost:9451/teacher/updateTeacher", this.ruleForm).then(function (resp) {
            if (resp.data === true) {
              that.$message({
                showClose: true,
                message: 'Updated successfully',
                type: 'success'
              });
            }
            else {
              that.$message.error('Update failed, please check the database');
            }
            that.$router.push("/queryTeacher")
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
