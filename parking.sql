/*
 Navicat Premium Dump SQL

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 90200 (9.2.0)
 Source Host           : localhost:3306
 Source Schema         : parking

 Target Server Type    : MySQL
 Target Server Version : 90200 (9.2.0)
 File Encoding         : 65001

 Date: 06/07/2025 01:16:36
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for parking_cards
-- ----------------------------
DROP TABLE IF EXISTS `parking_cards`;
CREATE TABLE `parking_cards`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `card_id` varchar(24) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `license_plate` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `entry_time` datetime NULL DEFAULT NULL,
  `slot_number` int NULL DEFAULT NULL,
  `is_paid` tinyint(1) NULL DEFAULT 0,
  `is_qrscan` bit(1) NOT NULL DEFAULT b'0',
  `is_ready` bit(1) NULL DEFAULT b'0',
  `expire_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `card_id`(`card_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 176 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for parking_slots
-- ----------------------------
DROP TABLE IF EXISTS `parking_slots`;
CREATE TABLE `parking_slots`  (
  `slot_number` int NOT NULL,
  `is_occupied` tinyint(1) NULL DEFAULT 0,
  PRIMARY KEY (`slot_number`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Triggers structure for table parking_cards
-- ----------------------------
DROP TRIGGER IF EXISTS `after_delete_parking_card`;
delimiter ;;
CREATE TRIGGER `after_delete_parking_card` AFTER DELETE ON `parking_cards` FOR EACH ROW BEGIN
    UPDATE parking_slots
    SET is_occupied = 0
    WHERE slot_number = OLD.slot_number;
END
;;
delimiter ;

SET FOREIGN_KEY_CHECKS = 1;
