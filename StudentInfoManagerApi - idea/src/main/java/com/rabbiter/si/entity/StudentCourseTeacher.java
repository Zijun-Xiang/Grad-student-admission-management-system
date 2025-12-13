package com.rabbiter.si.entity;

import org.apache.ibatis.type.Alias;

/**
 * @Author: 

 */

@Alias("StudentCourseTeacher")
public class StudentCourseTeacher {
    private Integer sctid;
    private Integer sid;
    private Integer cid;
    private Integer tid;
    private Float grade;
    private String term;

    @Override
    public String toString() {
        return "StudentCourseTeacher{" +
                "sctid=" + sctid +
                ", sid=" + sid +
                ", cid=" + cid +
                ", tid=" + tid +
                ", grade=" + grade +
                ", term='" + term + '\'' +
                '}';
    }

    public Integer getSctid() {
        return sctid;
    }

    public void setSctid(Integer sctid) {
        this.sctid = sctid;
    }

    public Integer getSid() {
        return sid;
    }

    public void setSid(Integer sid) {
        this.sid = sid;
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

    public Float getGrade() {
        return grade;
    }

    public void setGrade(Float grade) {
        this.grade = grade;
    }

    public String getTerm() {
        return term;
    }

    public void setTerm(String term) {
        this.term = term;
    }

    public StudentCourseTeacher() {
    }

    public StudentCourseTeacher(Integer sctid, Integer sid, Integer cid, Integer tid, Float grade, String term) {
        this.sctid = sctid;
        this.sid = sid;
        this.cid = cid;
        this.tid = tid;
        this.grade = grade;
        this.term = term;
    }
}
