package com.rabbiter.si.mapper;

import com.rabbiter.si.entity.Course;
import com.rabbiter.si.entity.CourseTeacher;
import com.rabbiter.si.entity.CourseTeacherInfo;
import org.apache.ibatis.annotations.Delete;
import org.apache.ibatis.annotations.Insert;
import org.apache.ibatis.annotations.Mapper;
import org.apache.ibatis.annotations.Param;
import org.springframework.stereotype.Repository;

import java.util.List;

/**
 * @Author: 
 */
@Repository
@Mapper
public interface CourseTeacherMapper {

    @Insert("INSERT INTO course_teacher (cid, tid, term) VALUES (#{cid}, #{tid}, #{term})")
    boolean insertCourseTeacher(@Param("cid") Integer cid,
                                @Param("tid") Integer tid,
                                @Param("term") String term);

    List<CourseTeacher> findBySearch(@Param("cid") Integer cid,
                                     @Param("tid") Integer tid,
                                     @Param("term") String term);

    List<Course> findMyCourse(@Param("tid") Integer tid,
                              @Param("term") String term);

    List<CourseTeacherInfo> findCourseTeacherInfo(@Param("tid") Integer tid,
                                                  @Param("tname") String tname,
                                                  @Param("tFuzzy") Integer tFuzzy,
                                                  @Param("cid") Integer cid,
                                                  @Param("cname") String cname,
                                                  @Param("cFuzzy") Integer cFuzzy);

    @Delete("DELETE FROM course_teacher WHERE cid = #{c.cid} AND tid = #{c.tid}")
    public boolean deleteById(@Param("c") CourseTeacher courseTeacher);
}
