<template>
  <div>
    <el-container>
      <el-main>

        <div class="main-content"
          style="display: flex;align-items: center;  flex-direction: column;">
          <info-card style="width: 50%;margin-top: 80px;"></info-card>
          <el-card style="width: 50%; margin-top: 10px;display: flex;flex-direction: column;;">
            <p class="system">Current system: Student Information Management System</p>
            <p class="welcome">Welcome to the system</p>
          </el-card>
          <logout style="margin-top: 20px;"></logout>
        </div>
      </el-main>
    </el-container>
  </div>
</template>

<script>
import Logout from "@/components/logout";
export default {
  name: "home",
  components: { Logout },
}
</script>

<style scoped>
.system,
.welcome {
  font-size: 24px;
}
</style>
