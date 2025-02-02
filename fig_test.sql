-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- 主机： localhost
-- 生成日期： 2025-02-02 21:21:28
-- 服务器版本： 5.6.50-log
-- PHP 版本： 8.0.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `fig_test`
--

-- --------------------------------------------------------

--
-- 表的结构 `attach`
--

CREATE TABLE `attach` (
  `id` int(11) UNSIGNED NOT NULL,
  `post_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '帖子id',
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户id',
  `width` mediumint(8) UNSIGNED NOT NULL DEFAULT '0',
  `filename` char(70) NOT NULL COMMENT '文件名',
  `type` char(7) NOT NULL DEFAULT '' COMMENT '文件类型',
  `created_at` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `comment`
--

CREATE TABLE `comment` (
  `id` int(11) UNSIGNED NOT NULL,
  `post_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '被回复的帖子ID',
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '回复者ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '回复时间',
  `content` varchar(200) NOT NULL DEFAULT '' COMMENT '回复内容'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- 表的结构 `favorite`
--

CREATE TABLE `favorite` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户id',
  `post_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '帖子id',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '收藏时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `likes`
--

CREATE TABLE `likes` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '点赞用户ID',
  `post_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '被赞帖子ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '点赞时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `medal`
--

CREATE TABLE `medal` (
  `id` int(11) UNSIGNED NOT NULL COMMENT '勋章id',
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户id',
  `name` char(20) NOT NULL DEFAULT '' COMMENT '勋章名',
  `desc` varchar(30) NOT NULL DEFAULT '' COMMENT '勋章说明',
  `icon` char(3) NOT NULL DEFAULT '' COMMENT '勋章图标名'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `post`
--

CREATE TABLE `post` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户id',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
  `images` smallint(6) UNSIGNED NOT NULL DEFAULT '0' COMMENT '图片数',
  `files` smallint(6) UNSIGNED NOT NULL DEFAULT '0' COMMENT '文件数',
  `content` varchar(255) NOT NULL DEFAULT '',
  `comments` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '回帖数',
  `likes` int(11) UNSIGNED DEFAULT '0' COMMENT '点赞数',
  `favorites` int(11) UNSIGNED DEFAULT '0' COMMENT '收藏数',
  `is_sticky` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否置顶',
  `is_lock` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否锁定'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `reward`
--

CREATE TABLE `reward` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户id',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '签到时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `tag`
--

CREATE TABLE `tag` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` char(30) CHARACTER SET utf8 NOT NULL COMMENT '标签名',
  `intro` varchar(200) CHARACTER SET utf8 NOT NULL COMMENT '标签介绍'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- 表的结构 `user`
--

CREATE TABLE `user` (
  `id` int(11) UNSIGNED NOT NULL,
  `group_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户组id',
  `username` char(32) NOT NULL DEFAULT '' COMMENT '用户名',
  `email` char(40) NOT NULL DEFAULT '' COMMENT '邮箱',
  `password` varchar(255) NOT NULL DEFAULT '' COMMENT '密码',
  `golds` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '金币',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '注册时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '更新时间',
  `avatar` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '头像最后更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 转储表的索引
--

--
-- 表的索引 `attach`
--
ALTER TABLE `attach`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- 表的索引 `comment`
--
ALTER TABLE `comment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- 表的索引 `favorite`
--
ALTER TABLE `favorite`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- 表的索引 `likes`
--
ALTER TABLE `likes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `post_id` (`post_id`);

--
-- 表的索引 `medal`
--
ALTER TABLE `medal`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `post`
--
ALTER TABLE `post`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- 表的索引 `reward`
--
ALTER TABLE `reward`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `updated_at` (`updated_at`);

--
-- 表的索引 `tag`
--
ALTER TABLE `tag`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `attach`
--
ALTER TABLE `attach`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `comment`
--
ALTER TABLE `comment`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `favorite`
--
ALTER TABLE `favorite`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `likes`
--
ALTER TABLE `likes`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `medal`
--
ALTER TABLE `medal`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '勋章id';

--
-- 使用表AUTO_INCREMENT `post`
--
ALTER TABLE `post`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `reward`
--
ALTER TABLE `reward`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tag`
--
ALTER TABLE `tag`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
