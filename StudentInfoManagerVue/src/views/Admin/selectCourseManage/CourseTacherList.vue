<template>
  <div>
    <el-table :data="tableData" border show-header stripe style="width: 100%">
      <el-table-column prop="cid" label="Course ID" width="150">
      </el-table-column>
      <el-table-column prop="cname" label="Course Code" width="150">
      </el-table-column>
      <el-table-column prop="tid" label="Instructor ID" width="150">
      </el-table-column>
      <el-table-column prop="tname" label="Instructor Name" width="150">
      </el-table-column>
      <el-table-column label="Actions" width="260" fixed="right">
        <template slot-scope="scope">
          <el-popconfirm confirm-button-text='Delete' cancel-button-text='Cancel' icon="el-icon-info" icon-color="red"
            title="Deletion cannot be undone" @confirm="deleteCourseTeacher(scope.row)">
            <el-button slot="reference" type="danger">Delete</el-button>
          </el-popconfirm>
        </template>
      </el-table-column>
    </el-table>
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
      const cid = row.cid
      const tid = row.tid
      const sid = sessionStorage.getItem('sid')
      const term = sessionStorage.getItem('currentTerm')
      const sct = {
        cid: cid,
        tid: tid,
        sid: sid,
        term: term
      }
      const that = this
      axios.post('http://localhost:9451/SCT/save', sct).then(function (resp) {
        if (resp.data === true) {
          that.$message({
            showClose: true,
            message: 'Course selected successfully',
            type: 'success'
          });
        }
        else {
          that.$message({
            showClose: true,
            message: 'Selection failed, please contact the administrator',
            type: 'error'
          });
        }
      })

    },
    deleteCourseTeacher(row) {
      const that = this
      axios.post('http://localhost:9451/courseTeacher/deleteById', row).then(function (resp) {
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
      })
    },
    changePage(page) {
      page = page - 1
      const that = this
      let start = page * that.pageSize, end = that.pageSize * (page + 1)
      let length = that.tmpList.length
      let ans = (end < length) ? end : length
      that.tableData = that.tmpList.slice(start, ans)
      console.log(that.tableData)
    },
  },

  data() {
    return {
      tableData: null,
      pageSize: 10,
      total: null,
      tmpList: null,
      type: sessionStorage.getItem('type')
    }
  },
  props: {
    ruleForm: Object
  },
  watch: {
    ruleForm: {
      handler(newRuleForm, oldRuleForm) {
        const that = this
        that.tmpList = null
        that.total = null
        that.tableData = null
        axios.post("http://localhost:9451/courseTeacher/findCourseTeacherInfo", newRuleForm).then(function (resp) {
          
          console.log(1333311)
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
