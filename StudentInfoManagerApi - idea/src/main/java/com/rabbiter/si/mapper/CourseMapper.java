package com.rabbiter.si.mapper;

import com.rabbiter.si.entity.Course;
import org.apache.ibatis.annotations.Mapper;
import org.apache.ibatis.annotations.Param;
import org.springframework.stereotype.Repository;

import java.util.List;

/**
 * @Author: 
 */

@Repository
@Mapper
public interface CourseMapper {
    List<Course> findBySearch(@Param("cid") Integer cid,
                              @Param("cname") String cname, @Param("fuzzy") Integer fuzzy,
                              @Param("lowBound") Integer lowBound, @Param("highBound") Integer highBound);

    boolean insertCourse(@Param("course") Course course);

    boolean updateById(@Param("course") Course course);

    boolean deleteById(@Param("cid") Integer cid);
}
