<template>
      <div style="display: flex;flex-direction: column;align-items: center; ">
      <div style="color: black;font-size: 30px;font-weight: bolder;margin-bottom: 30px;margin-top: 20px;">Course List</div>
      <div>
    <el-table :data="tableData" border stripe >
      <el-table-column fixed prop="cid" label="Course ID" width="150">
      </el-table-column>
      <el-table-column prop="cname" label="Course Name" width="150">
      </el-table-column>
      <el-table-column prop="ccredit" label="Credits" width="150">
      </el-table-column>
      <el-table-column label="Actions" width="260" fixed="right">
        <template slot-scope="scope">
          <el-popconfirm confirm-button-text='Delete' cancel-button-text='Cancel' icon="el-icon-info" icon-color="red"
            title="Deletion cannot be undone" @confirm="deleteTeacher(scope.row)">
            <el-button slot="reference" type="danger">Delete</el-button>
          </el-popconfirm>
          <el-button @click="editor(scope.row)" type="primary">Edit</el-button>
        </template>
      </el-table-column>
    </el-table>
    </div>
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
    deleteTeacher(row) {
      const that = this
      axios.get('http://localhost:9451/course/deleteById/' + row.cid).then(function (resp) {
        console.log(resp)
        if (resp.data === true) {
          that.$message({
            showClose: true,
            message: 'Deleted successfully',
            type: 'success'
          });
          window.location.reload()
        }
        else {
          that.$message({
            showClose: true,
            message: 'Deletion failed, please check the database connection',
            type: 'error'
          });
        }
      }).catch(function (error) {
        that.$message({
          showClose: true,
          message: 'Deletion failed due to foreign key constraints',
          type: 'error'
        });
      })
    },
    offer(row) {
      const tid = sessionStorage.getItem("tid")
      const cid = row.cid
      const term = sessionStorage.getItem("currentTerm")

      const that = this
      axios.get('http://localhost:9451/courseTeacher/insert/' + cid + '/' + tid + '/' + term).then(function (resp) {
        if (resp.data === true) {
          that.$message({
            showClose: true,
            message: 'Offering created successfully',
            type: 'success'
          });
          window.location.reload()
        }
        else {
          that.$message({
            showClose: true,
            message: 'Creation failed, please contact the administrator',
            type: 'error'
          });
        }
      })


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
        path: '/editorCourse',
        query: {
          cid: row.cid
        }
      })
    }
  },
  created() {
    console.log(this.type)
  },
  data() {
    return {
      tableData: null,
      pageSize: 10,
      total: null,
      tmpList: null,
      type: sessionStorage.getItem("type"),
    }
  },
  props: {
    ruleForm: Object,
    isActive: Boolean
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
        axios.post("http://localhost:9451/course/findBySearch", newRuleForm).then(function (resp) {
          console.log("查询结果:");
          console.log(resp)
          that.tmpList = resp.data
          that.total = resp.data.length
          let start = 0, end = that.pageSize
          let length = that.tmpList.length
          let ans = (end < length) ? end : length
          that.tableData = that.tmpList.slice(start, ans)
        })
      },
      deep: true,
      immediate: true
    }
  },
}
</script>
