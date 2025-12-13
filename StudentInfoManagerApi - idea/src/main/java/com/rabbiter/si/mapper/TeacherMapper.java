package com.rabbiter.si.mapper;

import com.rabbiter.si.entity.Teacher;
import org.apache.ibatis.annotations.Mapper;
import org.apache.ibatis.annotations.Param;
import org.springframework.stereotype.Repository;

import java.util.List;

/**
 * @Author: 
 */

@Repository
@Mapper
public interface TeacherMapper {
    List<Teacher> findAll();

    Teacher findById(@Param("tid") Integer tid);

    List<Teacher> findBySearch(@Param("tid") Integer tid, @Param("tname") String tname, @Param("fuzzy") Integer fuzzy);

    boolean updateById(@Param("teacher") Teacher teacher);

    boolean save(@Param("teacher") Teacher teacher);

    boolean deleteById(@Param("tid") Integer tid);

}
