<template>
  <div>
    <el-container>
      <el-main>

        <div class="main-content"
          style="display: flex;align-items: center;  flex-direction: column;">
          <info-card style="width: 50%;margin-top: 80px;"></info-card>
          <el-card style="width: 50%; margin-top: 10px;display: flex;flex-direction: column;;">
            <p style="font-size: 24px;"><i class="iconfont icon-r-setting"
                style="margin-right: 5px;font-size: 28px;"></i>Current system: Student Information Management System</p>
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
import InfoCard from "@/components/infoCard";
export default {
  name: "home",
  components: { InfoCard, Logout },
}
</script>

<style scoped></style>
