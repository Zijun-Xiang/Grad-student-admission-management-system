package com.rabbiter.si.entity;

import org.apache.ibatis.type.Alias;

/**
 * @Author: 

 */

@Alias("CourseTeacher")
public class CourseTeacher {
    private Integer ctid;
    private Integer cid;
    private Integer tid;
    private String term;

    @Override
    public String toString() {
        return "CourseTeacher{" +
                "ctid=" + ctid +
                ", cid=" + cid +
                ", tid=" + tid +
                ", term='" + term + '\'' +
                '}';
    }

    public Integer getCtid() {
        return ctid;
    }

    public void setCtid(Integer ctid) {
        this.ctid = ctid;
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

    public String getTerm() {
        return term;
    }

    public void setTerm(String term) {
        this.term = term;
    }

    public CourseTeacher() {
    }

    public CourseTeacher(Integer ctid, Integer cid, Integer tid, String term) {
        this.ctid = ctid;
        this.cid = cid;
        this.tid = tid;
        this.term = term;
    }
}
