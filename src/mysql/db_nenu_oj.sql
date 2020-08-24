CREATE DATABASE `db_nenu_oj` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

USE `db_nenu_oj`;

CREATE TABLE `t_contest` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
  `title` varchar(64) NOT NULL DEFAULT '' COMMENT '比赛标题',
  `description` text COMMENT '比赛描述',
  `announcement` text COMMENT '比赛通知',
  `is_private` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否是私有比赛(0:否;1:是)',
  `start_time` datetime DEFAULT NULL COMMENT '开始时间',
  `end_time` datetime DEFAULT NULL COMMENT '结束时间',
  `penalty` tinyint(1) NOT NULL DEFAULT '20' COMMENT '错误提交的罚时',
  `lock_board_time` datetime DEFAULT NULL COMMENT '封榜时间',
  `gold` int(10) NOT NULL DEFAULT '0' COMMENT '金奖数目',
  `silver` int(10) NOT NULL DEFAULT '0' COMMENT '银奖数目',
  `bronze` int(10) NOT NULL DEFAULT '0' COMMENT '铜奖数目',
  `hide_others` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否隐藏他人提交状态(0:否;1:是)',
  `owner_id` int(10) NOT NULL DEFAULT '0' COMMENT '比赛创建者id（对应user表中的id）',
  `manager` varchar(64) NOT NULL DEFAULT '' COMMENT '用户name（对应user表中的username）',
  `password` varchar(64) NOT NULL DEFAULT '' COMMENT '比赛密码',
  PRIMARY KEY (`id`), KEY `idx_title` (`title`),
  KEY `idx_owner_id` (`owner_id`),
  KEY `idx_manager` (`manager`),
  KEY `idx_is_private` (`is_private`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='比赛表';

CREATE TABLE `t_contest_problem` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
  `contest_id` int(10) NOT NULL DEFAULT '0' COMMENT '比赛id（对应contest表中的id）',
  `problem_id` int(10) NOT NULL DEFAULT '0' COMMENT '题目id（对应problem表中的id）',
  `lable` varchar(10) NOT NULL DEFAULT '' COMMENT '题目编号（A,B,C,D……）',
  `total_submit` int(10) NOT NULL DEFAULT '0' COMMENT '总提交数',
  `total_ac` int(10) NOT NULL DEFAULT '0' COMMENT '总通过数',
  PRIMARY KEY (`id`),
  KEY `idx_contest_id` (`contest_id`),
  KEY `idx_problem_id` (`problem_id`),
  KEY `idx_lable` (`lable`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='比赛题目表';

CREATE TABLE `t_contest_user` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
  `contest_id` int(10) NOT NULL DEFAULT '0' COMMENT '比赛id（对应contest表中的id）',
  `user_id` int(10) NOT NULL DEFAULT '0' COMMENT '用户id（对应user表中的id）',
  `is_star` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否打星(0:否;1:是)',
  PRIMARY KEY (`id`),
  KEY `idx_contest_id` (`contest_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_star` (`is_star`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='比赛用户表';

CREATE TABLE `t_discuss` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
  `contest_id` int(10) NOT NULL DEFAULT '0' COMMENT '比赛id（对应contest表中的id）',
  `created_at` datetime DEFAULT NULL COMMENT '发表时间',
  `username` varchar(64) NOT NULL DEFAULT '' COMMENT '用户name（对应user表中的username）',
  `replied_at` datetime DEFAULT NULL COMMENT '回复时间',
  `replied_user` varchar(64) NOT NULL DEFAULT '' COMMENT '回复的用户name（对应user表中的username）',
  `replied_num` int(10) NOT NULL DEFAULT '0',
  `title` varchar(64) NOT NULL DEFAULT '' COMMENT '标题',
  `priority` int(10) NOT NULL DEFAULT '0' COMMENT '置顶优先级',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='讨论表';

CREATE TABLE `t_discuss_reply` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
  `discuss_id` int(10) NOT NULL DEFAULT '0',
  `parent_id` int(10) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL COMMENT '发表时间',
  `content` text NOT NULL COMMENT '内容',
  `reply_at` varchar(64) NOT NULL DEFAULT '',
  `username` varchar(64) NOT NULL DEFAULT '' COMMENT '用户name（对应user表中的username）',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='讨论回复表';

CREATE TABLE `t_language_type` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
  `language` varchar(64) NOT NULL DEFAULT '' COMMENT '语言名称',
  PRIMARY KEY (`id`),
  KEY `idx_language` (`language`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='编程语言表';

INSERT INTO t_language_type(id, language)
VALUES (1, 'GNU C++'), (2, 'GNU C++11'), (3, 'Java'), (4, 'Python 2'), (5, 'Python 3');

CREATE TABLE `t_problem` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
  `title` varchar(64) NOT NULL DEFAULT '' COMMENT '题目标题',
  `description` longtext NOT NULL COMMENT '题目描述',
  `input` text NOT NULL COMMENT '输入描述',
  `output` text NOT NULL COMMENT '输出描述',
  `sample_in` text NOT NULL COMMENT '输入样例',
  `sample_out` text NOT NULL COMMENT '输出样例',
  `hint` text NOT NULL COMMENT '提示',
  `source` varchar(64) NOT NULL DEFAULT '' COMMENT '题目来源',
  `author` varchar(64) NOT NULL DEFAULT '' COMMENT '作者',
  `time_limit` int(10) NOT NULL DEFAULT '0' COMMENT '时间限制',
  `memory_limit` int(10) NOT NULL DEFAULT '0' COMMENT '内存限制',
  `total_submit` int(10) NOT NULL DEFAULT '0' COMMENT '总提交数',
  `total_ac` int(10) NOT NULL DEFAULT '0' COMMENT '总通过数',
  `total_wa` int(10) NOT NULL DEFAULT '0' COMMENT '总答案错误数',
  `total_re` int(10) NOT NULL DEFAULT '0' COMMENT '总运行时错误数',
  `total_ce` int(10) NOT NULL DEFAULT '0' COMMENT '总编译错误数',
  `total_tle` int(10) NOT NULL DEFAULT '0' COMMENT '总超时错误数',
  `total_mle` int(10) NOT NULL DEFAULT '0' COMMENT '总超内存错误数',
  `total_pe` int(10) NOT NULL DEFAULT '0' COMMENT '总格式错误数',
  `total_ole` int(10) NOT NULL DEFAULT '0' COMMENT '总输出超限错误数',
  `total_rf` int(10) NOT NULL DEFAULT '0' COMMENT '总限制函数错误数',
  `is_special_judge` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否是特殊判定题目(0:否;1:是)',
  `is_hide` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否隐藏(0:否;1:是)',
  PRIMARY KEY (`id`),
  KEY `idx_title` (`title`),
  KEY `idx_source` (`source`),
  KEY `idx_is_hide` (`is_hide`)
) ENGINE=InnoDB AUTO_INCREMENT=1000 DEFAULT CHARSET=utf8 COMMENT='题目表';

CREATE TABLE `t_status` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
  `problem_id` int(10) NOT NULL DEFAULT '0' COMMENT '题目id（对应problem表中的id）',
  `source` mediumtext NOT NULL COMMENT '源代码',
  `result` varchar(50) DEFAULT NULL COMMENT '判定结果',
  `time_used` int(10) DEFAULT NULL COMMENT '所用时间',
  `memory_used` int(10) DEFAULT NULL COMMENT '所用内存',
  `submit_time` datetime DEFAULT NULL COMMENT '提交时间',
  `contest_id` int(10) NOT NULL DEFAULT '0' COMMENT '比赛id（对应contest表中的id）',
  `user_id` int(10) NOT NULL DEFAULT '0' COMMENT '用户id（对应user表中的id）',
  `language_id` int(10) NOT NULL DEFAULT '0' COMMENT '语言id（对应language表中的id）',
  `ce_info` text COMMENT 'CE提示信息',
  `is_shared` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否共享代码(0:否;1:是)',
  PRIMARY KEY (`id`),
  KEY `idx_problem_id` (`problem_id`),
  KEY `idx_language_id` (`language_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_result` (`result`),
  KEY `idx_contest_id` (`contest_id`),
  KEY `idx_submit_time` (`submit_time`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='状态表';

CREATE TABLE `t_user` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
  `username` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT '用户名',
  `nickname` varchar(64) NOT NULL DEFAULT '' COMMENT '昵称',
  `avatar` varchar(76) NOT NULL DEFAULT '' COMMENT '头像',
  `password` varchar(64) NOT NULL DEFAULT '' COMMENT '密码',
  `signature` varchar(1024) DEFAULT NULL,
  `email` varchar(64) DEFAULT NULL COMMENT '邮箱',
  `school` varchar(64) NOT NULL DEFAULT '' COMMENT '学校',
  `total_submit` int(10) NOT NULL DEFAULT '0' COMMENT '总提交数',
  `total_ac` int(10) NOT NULL DEFAULT '0' COMMENT '总通过数',
  `solved_problem` int(10) NOT NULL DEFAULT '0' COMMENT '通过的题目数',
  `register_time` datetime DEFAULT NULL COMMENT '注册时间',
  `last_login` datetime DEFAULT NULL,
  `is_root` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否是管理员(0:否;1:是)',
  `ip_addr` varchar(64) DEFAULT NULL COMMENT '上次登录ip地址',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_rank` (`solved_problem`,`total_submit`,`username`),
  KEY `idx_solved_problem` (`solved_problem`),
  KEY `idx_total_submit` (`total_submit`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='用户表';

