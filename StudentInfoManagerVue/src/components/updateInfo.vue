<template>
  <el-container>
    <el-main style="width: 60%; display: flex;flex-direction: column;align-items: center;align-content: center; ">
      <el-card style="padding: 20px;width:60%;display: flex;flex-direction: column;align-items: center;align-content: center; ">
        <span style="width: 100%;;font-family:'HarmonyOS_Sans'  ;text-align: center;font-size: 30px;margin-bottom: 30px;font-weight: bolder;color: black;">Update Profile</span>
        <el-form :model="ruleForm" status-icon :rules="rules" ref="ruleForm" label-width="100px" class="demo-ruleForm" label-position="left">
          <el-form-item  label="Name:" prop="name">
            <el-input v-model="ruleForm.name" :value="ruleForm.name"></el-input>
          </el-form-item>
          <el-form-item label="New Password:" prop="pass">
            <el-input type="password" v-model="ruleForm.pass" autocomplete="off"></el-input>
          </el-form-item>
          <el-form-item label="Confirm Password:" prop="checkPass">
            <el-input type="password" v-model="ruleForm.checkPass" autocomplete="off"></el-input>
          </el-form-item>
          <el-form-item>
            <el-button type="primary" @click="submitForm('ruleForm')">Submit</el-button>
            <el-button @click="resetForm('ruleForm')" type="warning">Reset</el-button>
          </el-form-item>
        </el-form>
      </el-card>
    </el-main>
  </el-container>
</template>
<style scope>

.el-card__body{
  width: 100%;
  display: flex;
  flex-direction: column;
  
  justify-items: center;
}
.el-form{
  width: 100%;
}
.el-form-item__label{
  font-family:'HarmonyOS_Sans'!important  ;
  font-size: 15px!important;
  color: black !important;
  width: 100%;
}
</style>
<script>
export default {
  data() {
    var validatePass = (rule, value, callback) => {
      if (value === '') {
        callback(new Error('Please enter a password'));
      } else {
        if (this.ruleForm.checkPass !== '') {
          this.$refs.ruleForm.validateField('checkPass');
        }
        callback();
      }
    };
    var validatePass2 = (rule, value, callback) => {
      if (value === '') {
        callback(new Error('Please re-enter the password'));
      } else if (value !== this.ruleForm.pass) {
        callback(new Error('The passwords do not match!'));
      } else {
        callback();
      }
    };
    return {
      ruleForm: {
        pass: '',
        checkPass: '',
        name: sessionStorage.getItem('name')
      },
      rules: {
        pass: [
          { validator: validatePass, trigger: 'blur' }
        ],
        checkPass: [
          { validator: validatePass2, trigger: 'blur' }
        ],
        name: [
          { require: true, message: 'Name cannot be empty', trigger: 'blur'}
        ]
      }
    };
  },
  methods: {
    submitForm(formName) {
      this.$refs[formName].validate((valid) => {
        if (valid) {
          const that = this
          sessionStorage.setItem('name', that.ruleForm.name)
          const type = sessionStorage.getItem('type')
          let form = null
          let ss = null
          if (type === 'student') {
            ss = 'Student'
            form = {
              sid: sessionStorage.getItem('sid'),
              sname: that.ruleForm.name,
              password: that.ruleForm.pass,
            }
          }
          else {
            ss = 'Teacher'
            form = {
              tid: sessionStorage.getItem('tid'),
              tname: that.ruleForm.name,
              password: that.ruleForm.pass,
            }
          }

          axios.post('http://localhost:9451/' + type + '/update' + ss, form).then(function (resp) {
            if (resp.data === true) {
              that.$message({
                showClose: true,
                message: 'Updated successfully',
                type: 'success'
              });
            }
            else {
              that.$message.error('Update failed, please contact the administrator');
            }
            that.$router.push("/" + type + 'Home')
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
