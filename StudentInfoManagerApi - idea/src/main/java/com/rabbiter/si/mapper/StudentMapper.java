package com.rabbiter.si.mapper;

import com.rabbiter.si.entity.Student;
import org.apache.ibatis.annotations.Mapper;
import org.apache.ibatis.annotations.Param;
import org.springframework.stereotype.Repository;

import java.util.List;

/**
 * @Author: 
 */

@Mapper
@Repository
public interface StudentMapper {

    List<Student> findAll();

    Student findById(@Param("sid") Integer sid);

    List<Student> findBySearch(@Param("student") Student student, @Param("fuzzy") Integer fuzzy);

    boolean updateById(@Param("student") Student student);

    boolean save(@Param("student") Student student);

    boolean deleteById(@Param("sid") Integer sid);

}
