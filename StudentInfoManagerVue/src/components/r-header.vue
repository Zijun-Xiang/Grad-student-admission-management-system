<template>
  <el-row>
    <el-col :span="12" style="text-align: left;">
      <i class="iconfont icon-r-setting" style="font-size: 28px;margin-right: 5px;"></i>
      <span style="font-size: 24px;">欢迎访问学生信息管理系统</span>
    </el-col>
    <el-col :span="12" style="text-align: right;">

      <el-button type="danger" style=" font-size: 16px;" @click="out()">退出账号</el-button>

      <el-divider direction="vertical" style="color: #333333"></el-divider>
      <i class="iconfont icon-r-user2" style="margin-right: 5px;font-size: 28px;"></i>
      <span>{{ name }}</span>
    </el-col>
  </el-row>
</template>

<script>
import '../css/btn.min.css'
export default {
  name: "r-header",
  data() {
    return {
      name: null,
      currentTerm: null
    }
  },
  created() {
    this.name = sessionStorage.getItem("name")
    this.currentTerm = sessionStorage.getItem("currentTerm")
  },
  methods: {
    out() {
      sessionStorage.clear();
      this.$router.push('/')
    }
  }
}
</script>

<style scoped></style>