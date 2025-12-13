<template>
  <div>
      <el-popconfirm
          confirm-button-text='Confirm'
          cancel-button-text='Cancel'
          icon="el-icon-info"
          icon-color="red"
          title="Sign out now?"
          @confirm="out"
      >
        <el-button slot="reference" type="danger" style="margin-top: 10px" >
          Log Out
        </el-button>
      </el-popconfirm>
  </div>
</template>

<script>
export default {
  name: "logout",
  methods: {
    out() {
      sessionStorage.removeItem("token")
      this.$router.push('/')
    }
  }
}
</script>

<style scoped>

</style>
