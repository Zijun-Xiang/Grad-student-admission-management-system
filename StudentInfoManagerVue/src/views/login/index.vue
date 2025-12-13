<template>
  <div>
    <el-container>
      <el-header id="title">
        <span style="font-weight: bolder">
          Student Information Management System
        </span>
      </el-header>
      <el-main style="padding: 100px 65vh 0 65vh;width: 100%;" class="login-container">
        <el-card>
          <div slot="header" class="clearfix" style="width: 100%;">
            <span style="text-align: center; font-size: 26px;">
              <p style="color: black;font-weight: bolder;font-size: larger;"><i class="iconfont icon-r-user2"
                  style="margin-right: 5px;font-size: 34px; "></i>User Login</p>
            </span>
          </div>
          <div style="width: 95%;">
            <el-form :model="ruleForm" :rules="rules" ref="ruleForm" label-width="100px" class="login-info-wrapper"
              label-position="left">
              <el-form-item label="User ID" prop="id" style="width: 85%;">
                <el-input v-model.number="ruleForm.id" prefix-icon="iconfont icon-r-user2"></el-input>
              </el-form-item>
              <el-form-item label="Password" prop="password" style="width: 85%;">
                <el-input v-model="ruleForm.password" placeholder="Enter password" show-password
                  prefix-icon="iconfont icon-r-lock"></el-input>
              </el-form-item>
              <el-form-item label="Login Role" prop="type" style="width: 85%;">
                <el-radio-group v-model="ruleForm.type">
                  <el-radio label="student" value="student">Student</el-radio>
                  <el-radio label="teacher" value="teacher">Teacher</el-radio>
                  <el-radio label="admin" value="admin">Administrator</el-radio>
                </el-radio-group>
              </el-form-item>
              <el-form-item class="btns-wrapper" style="width: 100%; text-align: center;">
                <el-button type="primary" @click="submitForm('ruleForm')">Log In</el-button>
                <el-button @click="resetForm('ruleForm')" type="warning">Reset</el-button>
              </el-form-item>
            </el-form>
          </div>
        </el-card>
      </el-main>
    </el-container>
  </div>
</template>
<script>
// import '../../css/btn.min.css'
export default {
  data() {
    return {
      ruleForm: {
        id: null,
        password: null,
        type: null,
      },
      rules: {
        id: [
          { required: true, message: 'Please enter your user ID', trigger: 'blur' },
          { type: 'number', message: 'User ID must contain only numbers', trigger: 'blur' },
        ],
        password: [
          { required: true, message: 'Please enter your password', trigger: 'blur' }
        ],
        type: [
          { required: true, message: 'Please select a user role', trigger: 'change' }
        ],
      }
    };
  },
  methods: {
    submitForm(formName) {
      const that = this
      this.$refs[formName].validate((valid) => {
        if (valid) {
          let check = false
          let name = null

          axios.get('http://localhost:9451/info/getCurrentTerm').then(function (resp) {
            sessionStorage.setItem("currentTerm", resp.data)
          })

          axios.get('http://localhost:9451/info/getForbidCourseSelection').then(function (resp) {
            sessionStorage.setItem("ForbidCourseSelection", resp.data)
          })

          if (that.ruleForm.type === 'admin' || that.ruleForm.type === 'teacher') {
            let form = { tid: that.ruleForm.id, password: that.ruleForm.password }
            console.log(form)
            axios.post("http://localhost:9451/teacher/login", form).then(function (resp) {
              console.log("教师登录验证信息：" + resp.data)
              check = resp.data
              if (check === true) {
                axios.get("http://localhost:9451/teacher/findById/" + that.ruleForm.id).then(function (resp) {
                  console.log("正在获取用户信息" + resp.data)
                  name = resp.data.tname

                  sessionStorage.setItem("token", 'true')
                  sessionStorage.setItem("type", that.ruleForm.type)
                  sessionStorage.setItem("name", name)
                  sessionStorage.setItem("tid", resp.data.tid)

                  console.log('name: ' + name + ' ' + that.ruleForm.type + ' ' + resp.data.tid)

                  if (that.ruleForm.type === 'admin' && name === 'admin') {
                    that.$message({
                      showClose: true,
                      message: 'Login successful, welcome ' + name + '!',
                      type: 'success'
                    });
                    that.$router.push('/admin')
                  }
                  else if (that.ruleForm.type === 'teacher' && name !== 'admin') {
                    that.$message({
                      showClose: true,
                      message: 'Login successful, welcome ' + name + '!',
                      type: 'success'
                    });
                    that.$router.push('/teacher')
                  }
                  else {
                    that.$message({
                      showClose: true,
                      message: 'Administrator login failed, please check the selected role',
                      type: 'error'
                    });
                  }
                })
              }
              else {
                that.$message({
                  showClose: true,
                  message: 'Login failed, please check your ID and password',
                  type: 'error'
                });
              }
            }).catch((e) => {
              console.log(e);
              if (
                e.response == undefined ||
                e.response.data == undefined
              ) {
                this.$message({
                  showClose: true,
                  message: e,
                  type: "error",
                  duration: 20000,
                });
              } else {
                this.$message({
                  showClose: true,
                  message: e.response.data,
                  type: "error",
                  duration: 20000,
                });
              }
            })
          }
          else if (that.ruleForm.type === 'student') {
            console.log(that.ruleForm)
            let form = { sid: that.ruleForm.id, password: that.ruleForm.password }
            axios.post("http://localhost:9451/student/login", form).then(function (resp) {
              console.log("学生登录验证信息：" + resp.data)
              check = resp.data
              if (check === true) {
                axios.get("http://localhost:9451/student/findById/" + that.ruleForm.id).then(function (resp) {
                  console.log("正在获取用户信息" + resp.data)
                  name = resp.data.sname
                  sessionStorage.setItem("token", 'true')
                  sessionStorage.setItem("type", that.ruleForm.type)
                  sessionStorage.setItem("name", name)
                  sessionStorage.setItem("sid", resp.data.sid)
                  that.$message({
                    showClose: true,
                    message: 'Login successful, welcome ' + name + '!',
                    type: 'success'
                  });

                  console.log('正在跳转：' + '/' + that.ruleForm.type)
                  // that.$router.push('/student')
                  // // 3. 路由跳转
                  that.$router.push({
                    path: '/' + that.ruleForm.type,
                    query: {}
                  })

                })
              }
              else {
                that.$message({
                  showClose: true,
                  message: 'The ID or password is incorrect; please contact the administrator for assistance.',
                  type: 'error'
                });
              }
            }).catch((e) => {
              console.log(e);
              if (
                e.response == undefined ||
                e.response.data == undefined
              ) {
                this.$message({
                  showClose: true,
                  message: e,
                  type: "error",
                  duration: 20000,
                });
              } else {
                this.$message({
                  showClose: true,
                  message: e.response.data,
                  type: "error",
                  duration: 20000,
                });
              }
            })
          }
          else {
            console.log("! error type")
          }
        } else {
          console.log('error submit!!');
          return false;
        }
      });
    },
    resetForm(formName) {
      this.$refs[formName].resetFields();
    },
  }
}
</script>

<style>
.login-module {
  position: relative;
  /*height: 325px;*/
  /*border: none;*/
  height: 50% !important;
  width: 30%;
}

.login-container {
  height: 95vh;
  display: flex;

  align-items: center;
}

.el-header {
  background-color: black;
  color: #ffffff;
  line-height: 60px;
}

.login-info-wrapper {
  display: flex;
  flex-direction: column;

  align-items: center;
  width: 100%;
}

.el-card__header {
  height: 20%;
  display: flex;
  flex-direction: row;

  align-items: center;
}

.el-card__body {

  height: 80%;
  flex-direction: column;

  align-items: center;
}

.btns-wrapper .el-form-item__content {
  margin: 0px !important;
  padding-right: 5px
}

.btns-wrapper button {
  margin: 0 20px;
  /* 每个按钮左右各7.5px的外边距，总间距为15px */
}

#title {
  text-align: center;
  font-size: 25px;
}
</style>
