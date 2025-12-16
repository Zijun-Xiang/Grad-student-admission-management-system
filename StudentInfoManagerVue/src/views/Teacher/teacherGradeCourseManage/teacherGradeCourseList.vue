<template>
  <div>

    <el-table :data="tableData" border stripe style="width: 100%">
      <el-table-column fixed prop="cid" label="Course ID" >
      </el-table-column>
      <el-table-column prop="cname" label="Course Name">
      </el-table-column>
      <el-table-column fixed prop="sid" label="Student ID">
      </el-table-column>
      <el-table-column prop="sname" label="Student Name">
      </el-table-column>
      <el-table-column prop="grade" label="Grade">
      </el-table-column>
      <el-table-column prop="term" label="Term">
      </el-table-column>
      <el-table-column label="Actions" width="140" fixed="right">
        <template slot-scope="scope">
          <el-button @click="editor(scope.row)" type="primary">Edit</el-button>
        </template>
      </el-table-column>
    </el-table>
    <p>
      Average grade: {{ avg }}
    </p>
    <el-pagination background layout="prev, pager, next" :total="total" :page-size="pageSize"
      @current-change="changePage">
    </el-pagination>
  </div>
</template>

<script>
export default {
  methods: {
    select(row) {
      console.log(row)
    },
    changePage(page) {
      page = page - 1
      const that = this
      let start = page * that.pageSize, end = that.pageSize * (page + 1)
      let length = that.tmpList.length
      let ans = (end < length) ? end : length
      that.tableData = that.tmpList.slice(start, ans)
    },
    editor(row) {
      this.$router.push({
        path: '/editorGradeCourse',
        query: {
          cid: row.cid,
          tid: row.tid,
          sid: row.sid,
          term: row.term
        }
      })
    }
  },
  data() {
    return {
      tableData: null,
      pageSize: 10,
      total: null,
      tmpList: null,
      avg: 0,
    }
  },
  props: {
    ruleForm: Object,
  },
  watch: {
    ruleForm: {
      handler(newRuleForm, oldRuleForm) {
        console.log("组件监听 form")
        console.log(newRuleForm)
        const that = this
        that.tmpList = null
        that.total = null
        that.tableData = null
        axios.post("http://localhost:9451/SCT/findBySearch", newRuleForm).then(function (resp) {
          console.log("查询结果:");
          console.log(resp)
          that.tmpList = resp.data
          that.total = resp.data.length
          let start = 0, end = that.pageSize
          let length = that.tmpList.length
          let ans = (end < length) ? end : length
          that.tableData = that.tmpList.slice(start, ans)

          for (let i = 0; i < that.tmpList.length; i++) {
            that.avg += that.tmpList[i].grade
          }
          that.avg /= that.total
          console.log('avg', that.avg)
        })
      },
      deep: true,
      immediate: true
    }
  },
}
</script>
