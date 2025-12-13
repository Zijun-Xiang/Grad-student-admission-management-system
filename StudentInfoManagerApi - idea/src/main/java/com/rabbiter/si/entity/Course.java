package com.rabbiter.si.entity;

import org.apache.ibatis.type.Alias;

/**
 * @Author: 
 */

@Alias("Course")
public class Course {
    private Integer cid;
    private String cname;
    private Integer ccredit;

    @Override
    public String toString() {
        return "Course{" +
                "cid=" + cid +
                ", cname='" + cname + '\'' +
                ", ccredit=" + ccredit +
                '}';
    }

    public Integer getCid() {
        return cid;
    }

    public void setCid(Integer cid) {
        this.cid = cid;
    }

    public String getCname() {
        return cname;
    }

    public void setCname(String cname) {
        this.cname = cname;
    }

    public Integer getCcredit() {
        return ccredit;
    }

    public void setCcredit(Integer ccredit) {
        this.ccredit = ccredit;
    }

    public Course(Integer cid, String cname, Integer ccredit) {
        this.cid = cid;
        this.cname = cname;
        this.ccredit = ccredit;
    }

    public Course() {
    }
}
