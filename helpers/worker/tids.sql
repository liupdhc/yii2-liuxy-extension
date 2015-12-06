/*
Navicat MySQL Data Transfer

Source Server         : 192.168.0.216
Source Server Version : 50536
Source Host           : 192.168.0.216:33306
Source Database       : oppo_sns_common

Target Server Type    : MYSQL
Target Server Version : 50536
File Encoding         : 65001

Date: 2015-11-18 19:25:38
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for tids
-- ----------------------------
DROP TABLE IF EXISTS `tids`;
CREATE TABLE `tids` (
  `category` varchar(50) NOT NULL DEFAULT '' COMMENT 'ID类别',
  `value` bigint(20) unsigned DEFAULT '0' COMMENT 'ID增长值',
  PRIMARY KEY (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
