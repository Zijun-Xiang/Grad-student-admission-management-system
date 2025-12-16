package com.rabbiter.si.entity;

import org.apache.ibatis.type.Alias;

/**
 * @Author:

 */

@Alias("CourseTeacherInfo")
public class CourseTeacherInfo {
    private Integer cid;
    private Integer tid;
    private String cname;
    private String tname;
    private Integer ccredit;
    private Float grade;

    @Override
    public String toString() {
        return "CourseTeacherInfo{" +
                "cid=" + cid +
                ", tid=" + tid +
                ", cname='" + cname + '\'' +
                ", tname='" + tname + '\'' +
                ", ccredit=" + ccredit +
                ", grade=" + grade +
                '}';
    }

    public Integer getCid() {
        return cid;
    }

    public void setCid(Integer cid) {
        this.cid = cid;
    }

    public Integer getTid() {
        return tid;
    }

    public void setTid(Integer tid) {
        this.tid = tid;
    }

    public String getCname() {
        return cname;
    }

    public void setCname(String cname) {
        this.cname = cname;
    }

    public String getTname() {
        return tname;
    }

    public void setTname(String tname) {
        this.tname = tname;
    }

    public Integer getCcredit() {
        return ccredit;
    }

    public void setCcredit(Integer ccredit) {
        this.ccredit = ccredit;
    }

    public Float getGrade() {
        return grade;
    }

    public void setGrade(Float grade) {
        this.grade = grade;
    }

    public CourseTeacherInfo() {
    }

    public CourseTeacherInfo(Integer cid, Integer tid, String cname, String tname, Integer ccredit, Float grade) {
        this.cid = cid;
        this.tid = tid;
        this.cname = cname;
        this.tname = tname;
        this.ccredit = ccredit;
        this.grade = grade;
    }
}
