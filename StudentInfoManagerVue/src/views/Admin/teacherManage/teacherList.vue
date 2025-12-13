<template>
    <div style="display: flex;flex-direction: column;align-items: center; ">
      <div style="color: black;font-size: 30px;font-weight: bolder;margin-bottom: 30px;">Teacher List</div>
      <div>
    <el-table :data="tableData" >
      <el-table-column fixed prop="tid" label="Teacher ID" width="150">
      </el-table-column>
      <el-table-column prop="tname" label="Name" width="150">
      </el-table-column>
      <el-table-column prop="password" label="Password" width="150">
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
    deleteTeacher(row) {
      if (row.tname === 'admin') {
        this.$message({
          showClose: true,
          message: 'The admin account cannot be deleted',
          type: 'error'
        });
        return
      }
      const that = this
      axios.get('http://localhost:9451/teacher/deleteById/' + row.tid).then(function (resp) {
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
      }).catch(function (e) {
        that.$message({
          showClose: true,
          message: 'Deletion failed due to foreign key constraints',
          type: 'error'
        });
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
      if (row.tname === 'admin') {
        this.$message({
          showClose: true,
          message: 'The admin account cannot be edited',
          type: 'error'
        });
        return
      }
      this.$router.push({
        path: '/editorTeacher',
        query: {
          tid: row.tid
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
    }
  },
  props: {
    ruleForm: Object
  },
  watch: {
    ruleForm: {
      handler(newRuleForm, oldRuleForm) {
        console.log("组件监听 form")
        const that = this
        that.tmpList = null
        that.total = null
        that.tableData = null
        axios.post("http://localhost:9451/teacher/findBySearch", newRuleForm).then(function (resp) {
          console.log("查询结果:");
          console.log(newRuleForm)
          console.log(resp)
          that.tmpList = resp.data
          that.total = resp.data.length
          let start = 0, end = that.pageSize
          let length = that.tmpList.length
          let ans = (end < length) ? end : length
          that.tableData = that.tmpList.slice(start, end)
        })
      },
      deep: true,
      immediate: true
    }
  },
}
</script>
