<template>
  <div  style="display: flex;flex-direction: column;align-items: center; ">
    <div style="color: black;font-size: 30px;font-weight: bolder;margin-bottom: 30px;">Selected Courses</div>
    <el-card >
     
      <el-table :data="tableData" border style="width: 100%">
        <el-table-column fixed prop="cid" label="Course ID" width="150">
        </el-table-column>
        <el-table-column prop="cname" label="Course Code" width="150">
        </el-table-column>
        <el-table-column prop="tid" label="Instructor ID" width="150">
        </el-table-column>
        <el-table-column prop="tname" label="Instructor Name" width="150">
        </el-table-column>
        <el-table-column prop="ccredit" label="Credits" width="150">
        </el-table-column>
        <el-table-column label="Actions" width="260" fixed="right">

          <template slot-scope="scope">
            <el-popconfirm confirm-button-text='Drop' cancel-button-text='Cancel' icon="el-icon-info" title="Drop this course?"
              @confirm="deleteSCT(scope.row)">
              <el-button slot="reference" type="danger">Drop Course</el-button>
            </el-popconfirm>
          </template>
        </el-table-column>
      </el-table>
      <el-pagination background layout="prev, pager, next" :total="total" :page-size="pageSize"
        @current-change="changePage">
      </el-pagination>
    </el-card>
  </div>
</template>

<script>
export default {
  methods: {
    deleteSCT(row) {
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
      axios.post('http://localhost:9451/SCT/deleteBySCT', sct).then(function (resp) {
        if (resp.data === true) {
          that.$message({
            showClose: true,
            message: 'Course dropped successfully',
            type: 'success'
          });
          window.location.reload()
        }
        else {
          that.$message({
            showClose: true,
            message: 'Drop failed, please contact the administrator',
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
  mounted() {
    console.log(11113)
    const sid = sessionStorage.getItem('sid')
    let term = sessionStorage.getItem('currentTerm')
    if(term == "" || term == null) {
       term = "null"
    }
    const that = this
    axios.get('http://localhost:9451/SCT/findBySid/' + sid + '/' + term).then(function (resp) {
      that.tmpList = resp.data
      that.total = resp.data.length
      let start = 0, end = that.pageSize
      let length = that.tmpList.length
      let ans = (end < length) ? end : length
      that.tableData = that.tmpList.slice(start, end)
    })
  },
}
</script>
