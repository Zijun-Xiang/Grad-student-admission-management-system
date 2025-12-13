<template>
  <div style="display: flex;flex-direction: column;align-items: center; ">
    <div style="color: black;font-size: 30px;font-weight: bolder;margin-bottom: 30px;">Student List</div>
    <div >
    <el-table :data="tableData">
      <el-table-column fixed prop="sid" label="Student ID" width="150">
      </el-table-column>
      <el-table-column prop="sname" label="Name" width="120">
      </el-table-column>
      <el-table-column prop="password" label="Password" width="120">
      </el-table-column>
      <el-table-column label="Actions" width="260" fixed="right">
        <template slot-scope="scope">
          <el-popconfirm confirm-button-text='Delete' cancel-button-text='Cancel' icon="el-icon-info" icon-color="red"
            title="Deletion cannot be undone" @confirm="deleteStudent(scope.row)">
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
    deleteStudent(row) {
      const that = this
      axios.get('http://localhost:9451/student/deleteById/' + row.sid).then(function (resp) {
        if (resp.data === true) {
          that.$message({
            showClose: true,
            message: 'Deleted successfully',
            type: 'success'
          });
          console.log(that.tmpList === null)
          if (that.tmpList === null) {
            window.location.reload()
          }
          else {
            that.$router.push('/queryStudent')
          }
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
      if (this.tmpList === null) {
        const that = this
        axios.get('http://localhost:9451/student/findByPage/' + page + '/' + that.pageSize).then(function (resp) {
          that.tableData = resp.data
        })
      }
      else {
        let that = this
        let start = page * that.pageSize, end = that.pageSize * (page + 1)
        let length = that.tmpList.length
        let ans = end < length ? end : length
        that.tableData = that.tmpList.slice(start, ans)
      }
    },
    editor(row) {
      this.$router.push({
        path: '/editorStudent',
        query: {
          sid: row.sid
        }
      })
    }
  },

  data() {
    return {
      tableData: null,
      pageSize: 7,
      total: null,
      ruleForm: null,
      tmpList: null
    }
  },

  created() {
    if (this.tmpList !== null)
      this.tmpList = null
    const that = this
    // 是否从查询页跳转
    this.ruleForm = this.$route.query.ruleForm
    if (this.$route.query.ruleForm === undefined || (this.ruleForm.sid === null && this.ruleForm.sname === null)) {
      axios.get('http://localhost:9451/student/getLength').then(function (resp) {
        console.log("获取列表总长度: " + resp.data)
        that.total = resp.data
      })

      axios.get('http://localhost:9451/student/findByPage/0/' + that.pageSize).then(function (resp) {
        that.tableData = resp.data
      })
    }
    else {
      // 从查询页跳转并且含查询
      console.log('正在查询跳转数据')
      console.log(this.ruleForm)
      axios.post('http://localhost:9451/student/findBySearch', this.ruleForm).then(function (resp) {
        console.log('获取查询数据：')
        that.tmpList = resp.data
        that.total = resp.data.length
        console.log(that.tmpList)
        let start = 0, end = that.pageSize
        let length = that.tmpList.length
        let ans = end < length ? end : length
        that.tableData = that.tmpList.slice(start, ans)
      })
    }
  }
}
</script>
