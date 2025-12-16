<template>
  <div style="display: flex;flex-direction: column;align-items: center; ">
    <div style="color: black;font-size: 30px;font-weight: bolder;margin-bottom: 20px;">Edit Student Grade</div>
    <el-card style="width: 40%;">
      <el-form style="width: 100%" :model="ruleForm" :rules="rules" ref="ruleForm" label-width="100px"
        class="demo-ruleForm">
        <el-form-item label="Course Name:" prop="cname" style="width: 80%;">
          <el-input v-model="ruleForm.cname" :value="ruleForm.cname" :disabled="true"></el-input>
        </el-form-item>
        <el-form-item label="Instructor Name:" prop="tname" style="width: 80%;">
          <el-input v-model="ruleForm.tname" :value="ruleForm.tname" :disabled="true"></el-input>
        </el-form-item>
        <el-form-item label="Student Name:" prop="sname" style="width: 80%;">
          <el-input v-model="ruleForm.sname" :value="ruleForm.sname" :disabled="true"></el-input>
        </el-form-item>
        <el-form-item label="Score:" prop="grade" style="width: 80%;">
          <el-input v-model.number="ruleForm.grade" :value="ruleForm.grade"></el-input>
        </el-form-item>
        <el-form-item class="btns-wrapper" style="width: 100%; display: flex ;">
          <el-button type="primary" @click="submitForm('ruleForm')">Submit</el-button>
        </el-form-item>

      </el-form>
    </el-card>
  </div>
</template>
<script>
export default {
  data() {
    var checkGrade = (rule, value, callback) => {
      if (!value) {
        return callback(new Error('Grade cannot be empty'));
      }
      if (!Number.isInteger(value)) {
        callback(new Error('Please enter a numeric value'));
      } else {
        if (value > 100 || value < 0) {
          callback(new Error('Grade must be between 0 and 100'));
        } else {
          callback();
        }
      }
    };
    return {
      ruleForm: {
        cid: null,
        cname: null,
        grade: null,
        sid: null,
        sname: null,
        tid: null,
        tname: null,
      },
      rules: {
        grade: [
          { required: true, message: 'Please enter a grade', trigger: 'change' },
          { type: 'number', message: 'Please enter a number', trigger: 'change' },
          { validator: checkGrade, trigger: 'blur' }
        ],
      }
    };
  },
  created() {
    const that = this
    this.ruleForm.cid = this.$route.query.cid
    this.ruleForm.tid = this.$route.query.tid
    this.ruleForm.sid = this.$route.query.sid
    this.ruleForm.term = this.$route.query.term
    axios.get('http://localhost:9451/SCT/findById/' +
      this.ruleForm.sid + '/' +
      this.ruleForm.cid + '/' +
      this.ruleForm.tid + '/' +
      this.ruleForm.term).then(function (resp) {
        that.ruleForm = resp.data
      })
  },
  methods: {
    submitForm(formName) {
      this.$refs[formName].validate((valid) => {
        if (valid) {
          // 通过前端校验
          const that = this
          const sid = that.ruleForm.sid
          const cid = that.ruleForm.cid
          const tid = that.ruleForm.tid
          const term = that.ruleForm.term
          const grade = that.ruleForm.grade
          axios.get("http://localhost:9451/SCT/updateById/" + sid + '/' + cid + '/' + tid + '/' + term + '/' + grade).then(function (resp) {
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
            if (sessionStorage.getItem('type') === 'admin') {
              that.$router.push("/queryGradeCourse")
            } else {
              that.$router.push("/teacherQueryGradeCourseManage")
            }
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
