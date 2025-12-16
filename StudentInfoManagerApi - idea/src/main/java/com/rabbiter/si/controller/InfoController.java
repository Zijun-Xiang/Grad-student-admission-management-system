package com.rabbiter.si.controller;

import org.springframework.beans.factory.annotation.Value;
import org.springframework.web.bind.annotation.CrossOrigin;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import java.util.Calendar;

/**
 * @Author:
 */

@RestController
@RequestMapping("/info")
@CrossOrigin("*")
public class InfoController {
    private final boolean FORBID_COURSE_SELECTION = false;

    @RequestMapping("/getCurrentTerm")
    public String getCurrentTerm() {

        // 获取当前年份和月份
        Calendar calendar = Calendar.getInstance();
        int year = calendar.get(Calendar.YEAR);
        int month = calendar.get(Calendar.MONTH) + 1; // Calendar.MONTH 是从0开始的, 所以 +1

        // 判断当前月份
        String term;
        if (month >= 7) {
            term = year + 1 + "上学期";
        } else {
            term = year + "下学期";
        }
        return term;
    }

    @RequestMapping("/getForbidCourseSelection")
    public boolean getForbidCourseSelection() {
        return FORBID_COURSE_SELECTION;
    }

}
