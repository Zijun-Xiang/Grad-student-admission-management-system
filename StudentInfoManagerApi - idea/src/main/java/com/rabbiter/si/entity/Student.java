package com.rabbiter.si.entity;

import org.apache.ibatis.type.Alias;

/**
 * @Author: 

 */

@Alias("Student")
public class Student {
    private Integer sid;
    private String sname;
    private String password;

    @Override
    public String toString() {
        return "Student{" +
                "sid=" + sid +
                ", sname='" + sname + '\'' +
                ", password='" + password + '\'' +
                '}';
    }

    public Integer getSid() {
        return sid;
    }

    public void setSid(Integer sid) {
        this.sid = sid;
    }

    public String getSname() {
        return sname;
    }

    public void setSname(String sname) {
        this.sname = sname;
    }

    public String getPassword() {
        return password;
    }

    public void setPassword(String password) {
        this.password = password;
    }

    public Student() {
    }

    public Student(Integer sid, String sname, String password) {
        this.sid = sid;
        this.sname = sname;
        this.password = password;
    }
}
