<template>
    <div  style="display: flex;flex-direction: column;align-items: center; ">
      <div style="color: black;font-size: 30px;font-weight: bolder;margin-bottom: 30px;">添加课程</div>
    <el-form style="width: 40%" :model="ruleForm" :rules="rules" ref="ruleForm" label-width="100px" class="demo-ruleForm">
      <el-form-item label="课程名" prop="cname">
        <el-input v-model="ruleForm.cname"></el-input>
      </el-form-item>
      <el-form-item label="学分" prop="ccredit">
        <el-input v-model.number="ruleForm.ccredit"></el-input>
      </el-form-item>
      <el-form-item  class="btns-wrapper" style="width: 100%; display: flex ;">
                <el-button type="primary" @click="submitForm('ruleForm')">提交</el-button>
                <el-button @click="resetForm('ruleForm')" type="warning">重置</el-button>
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
          { required: true, message: '请输入名称', trigger: 'blur' },
        ],
        ccredit: [
          { required: true, message: '请输入学分', trigger: 'change' },
          { type: 'number', message: '请输入数字', trigger: 'blur' },
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
                message: '插入成功',
                type: 'success'
              });
            }
            else {
              that.$message.error('插入失败，请检查数据库t');
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