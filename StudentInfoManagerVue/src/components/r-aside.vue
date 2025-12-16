<template>
  <div>
    <el-aside width="210px"
      style="background-color: rgb(238, 241, 246)!important; height: 100%; min-height: 750px;">
      <el-menu router :default-active="this.$route.path" style="height: 100%;" background-color="rgb(31,31,31)"
        text-color="rgb(144,147,153)">
        <div v-for="(item, index) in $router.options.routes" v-if="item.name === type" :index="index + ''" :key="index">
          <el-submenu v-for="(item2, index2) in item.children" :index="item2.path" :key="item2.path">
            <template slot="title"><i :class="'iconfont icon-r-' + item2.icon" style="font-size: 26px;"></i><span
                style="font-size: 22px;"> {{ item2.name }}</span></template>
            <el-menu-item v-if="item3.show == undefined ? true : item3.show" v-for="(item3, index3) in item2.children"
              :index="item3.path" :key="item3.path"><i :class="'iconfont icon-r-' + item3.icon"
                style="font-size: 24px;"></i><span style="font-size: 20px"> {{ item3.name }}</span></el-menu-item>
          </el-submenu>
        </div>
      </el-menu>
    </el-aside>
  </div>
</template>

<script>
export default {
  name: "r-aside",
  data() {
    return {
      type: null
    }
  },
  created() {
    this.type = sessionStorage.getItem("type")
    console.log('aside: ' + this.type)
    console.log(this.$router.options)
  }
}
</script>

<style scoped></style>