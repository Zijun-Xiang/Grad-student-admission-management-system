/*
 Navicat Premium Data Transfer

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 80019
 Source Host           : localhost:3306
 Source Schema         : student_info_manager

 Target Server Type    : MySQL
 Target Server Version : 80019
 File Encoding         : 65001

 Date: 20/03/2025 18:32:13
*/

SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for course
-- ----------------------------
DROP TABLE IF EXISTS `course`;
CREATE TABLE `course`  (
  `cid` int(0) NOT NULL AUTO_INCREMENT,
  `cname` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `ccredit` tinyint(0) NULL DEFAULT NULL,
  PRIMARY KEY (`cid`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 12 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of course
-- ----------------------------
INSERT INTO `course` VALUES (7, '数据结构与算法', 6);
INSERT INTO `course` VALUES (8, '离散数学', 3);
INSERT INTO `course` VALUES (9, '计算机网络', 5);
INSERT INTO `course` VALUES (10, '计算机组成原理', 5);
INSERT INTO `course` VALUES (11, 'Java程序设计', 10);

-- ----------------------------
-- Table structure for student
-- ----------------------------
DROP TABLE IF EXISTS `student`;
CREATE TABLE `student`  (
  `sid` int(0) NOT NULL AUTO_INCREMENT,
  `sname` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `password` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  PRIMARY KEY (`sid`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 24 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of student
-- ----------------------------
INSERT INTO `student` VALUES (1, '陈小明', '123456');
INSERT INTO `student` VALUES (2, '张小四', '123456');
INSERT INTO `student` VALUES (3, '李小四', '123456');
INSERT INTO `student` VALUES (4, '彭小辉', '123456');
INSERT INTO `student` VALUES (6, '林小霞', '123456');
INSERT INTO `student` VALUES (7, '董小超', '123456');
INSERT INTO `student` VALUES (8, '王二小', '123456');
INSERT INTO `student` VALUES (9, '张小千', '123456');
INSERT INTO `student` VALUES (10, '李小万', '123456');
INSERT INTO `student` VALUES (14, '陈小柳', '123456');
INSERT INTO `student` VALUES (21, '庄小亮', '123456');
INSERT INTO `student` VALUES (22, '钟小平', '123456');
INSERT INTO `student` VALUES (23, '李小豪', '123456');

-- ----------------------------
-- Table structure for teacher
-- ----------------------------
DROP TABLE IF EXISTS `teacher`;
CREATE TABLE `teacher`  (
  `tid` int(0) NOT NULL AUTO_INCREMENT,
  `tname` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `password` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  PRIMARY KEY (`tid`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 14 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of teacher
-- ----------------------------
INSERT INTO `teacher` VALUES (1, 'admin', '123456');
INSERT INTO `teacher` VALUES (4, '张三', '123456');
INSERT INTO `teacher` VALUES (13, '李四', '123456');


-- ----------------------------
-- Table structure for course_teacher
-- ----------------------------
DROP TABLE IF EXISTS `course_teacher`;
CREATE TABLE `course_teacher`  (
  `ctid` int(0) NOT NULL AUTO_INCREMENT,
  `cid` int(0) NULL DEFAULT NULL,
  `tid` int(0) NULL DEFAULT NULL,
  `term` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  PRIMARY KEY (`ctid`) USING BTREE,
  INDEX `cid`(`cid`) USING BTREE,
  INDEX `tid`(`tid`) USING BTREE,
  CONSTRAINT `ct_ibfk_1` FOREIGN KEY (`cid`) REFERENCES `course` (`cid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `ct_ibfk_2` FOREIGN KEY (`tid`) REFERENCES `teacher` (`tid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 10 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of course_teacher
-- ----------------------------
INSERT INTO `course_teacher` VALUES (5, 8, 4, '2025上学期');
INSERT INTO `course_teacher` VALUES (6, 7, 4, '2025上学期');
INSERT INTO `course_teacher` VALUES (7, 10, 13, '2025上学期');
INSERT INTO `course_teacher` VALUES (8, 9, 13, '2025上学期');
INSERT INTO `course_teacher` VALUES (9, 11, 4, '2025上学期');

-- ----------------------------
-- Table structure for student_course_teacher
-- ----------------------------
DROP TABLE IF EXISTS `student_course_teacher`;
CREATE TABLE `student_course_teacher`  (
  `sctid` int(0) NOT NULL AUTO_INCREMENT,
  `sid` int(0) NULL DEFAULT NULL,
  `cid` int(0) NULL DEFAULT NULL,
  `tid` int(0) NULL DEFAULT NULL,
  `grade` float NULL DEFAULT NULL,
  `term` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`sctid`) USING BTREE,
  INDEX `sid`(`sid`) USING BTREE,
  INDEX `tid`(`tid`) USING BTREE,
  INDEX `cid`(`cid`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 18 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of student_course_teacher
-- ----------------------------
INSERT INTO `student_course_teacher` VALUES (10, 2, 8, 4, 1, '2025上学期');
INSERT INTO `student_course_teacher` VALUES (11, 2, 10, 13, NULL, '2025上学期');
INSERT INTO `student_course_teacher` VALUES (12, 2, 7, 4, NULL, '2025上学期');
INSERT INTO `student_course_teacher` VALUES (13, 4, 8, 4, 10, '2025上学期');
INSERT INTO `student_course_teacher` VALUES (14, 4, 7, 4, NULL, '2025上学期');
INSERT INTO `student_course_teacher` VALUES (15, 4, 10, 13, NULL, '2025上学期');
INSERT INTO `student_course_teacher` VALUES (17, 1, 8, 4, 59, '2025上学期');


SET FOREIGN_KEY_CHECKS = 1;
