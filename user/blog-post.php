<?php
/*
 Blog Post by Jack Siro
 https://github.com/JaxiroKe/q2a-blog-post
 Description: Blog Post Plugin database checker and user pages manager
 */

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../../');
	exit;
}

require_once QA_INCLUDE_DIR . 'db/users.php';
require_once QA_INCLUDE_DIR . 'util/string.php';
require_once QA_INCLUDE_DIR . 'app/users.php';
require_once QA_INCLUDE_DIR . 'app/blobs.php';
require_once QA_PLUGIN_DIR . 'q2a-blog-post/core/blog-base.php';
require_once QA_PLUGIN_DIR . 'q2a-blog-post/core/blog-db.php';
require_once QA_PLUGIN_DIR . 'q2a-blog-post/core/blog-format.php';
require_once QA_PLUGIN_DIR . 'q2a-blog-post/article/article-actions.php';
require_once QA_PLUGIN_DIR . 'q2a-blog-post/article/article-edit.php';
require_once QA_PLUGIN_DIR . 'q2a-blog-post/article/article-feed.php';
require_once QA_PLUGIN_DIR . 'q2a-blog-post/article/article-viewer.php';
require_once QA_PLUGIN_DIR . 'q2a-blog-post/article/article-write.php';

class blog_post
{
	private $directory;
	private $urltoroot;
	private $user;
	private $dates;

	public function load_module($directory, $urltoroot)
	{
		$this->directory = $directory;
		$this->urltoroot = $urltoroot;
	}

	public function suggest_requests() // for display in admin INTerface

	{
		return array(
				array(
				'title' => 'Blog',
				'request' => 'blog',
				'nav' => 'M',
			),
		);
	}

	public function match_request($request)
	{
		return strpos($request, 'blog') !== false;
	}

	function init_queries($tableslc)
	{
		$tbl1 = qa_db_add_table_prefix('blog_cats');
		$tbl2 = qa_db_add_table_prefix('blog_posts');
		$tbl3 = qa_db_add_table_prefix('blog_users');

		if (in_array($tbl1, $tableslc) && in_array($tbl2, $tableslc) && in_array($tbl3, $tableslc))
			return null;

		return array(
			'CREATE TABLE IF NOT EXISTS ^blog_cats (
					`catid` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					`parentid` INT(10) UNSIGNED DEFAULT NULL,
					`title` VARCHAR(80) NOT NULL,
					`tags` VARCHAR(200) NOT NULL,
					`content` VARCHAR(800) NOT NULL DEFAULT \'\',
					`pcount` INT(10) UNSIGNED NOT NULL DEFAULT 0,
					`position` SMALLINT(5) UNSIGNED NOT NULL,
					`backpath` VARCHAR(804) NOT NULL DEFAULT \'\',
					PRIMARY KEY (`catid`),
					UNIQUE `parentid` (`parentid`, `tags`),
					UNIQUE `parentid_2` (`parentid`, `position`),
					KEY `backpath` (`backpath`(200))
				) ENGINE=InnoDB DEFAULT CHARSET=utf8',

			'CREATE TABLE IF NOT EXISTS ^blog_posts (
					`postid` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					`type` ENUM("P","C","D","P_HIDDEN","C_HIDDEN","P_QUEUED","C_QUEUED") NOT NULL,
					`parentid` INT(10) UNSIGNED DEFAULT NULL,
					`catid` INT(10) UNSIGNED DEFAULT NULL,
					`catidpath1` INT(10) UNSIGNED DEFAULT NULL,
					`catidpath2` INT(10) UNSIGNED DEFAULT NULL,
					`catidpath3` INT(10) UNSIGNED DEFAULT NULL,
					`ccount` SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0,
					`amaxvote` SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0,
					`selchildid` INT(10) UNSIGNED DEFAULT NULL,
					`closedbyid` INT(10) UNSIGNED DEFAULT NULL,
					`userid` INT(10) UNSIGNED DEFAULT NULL,
					`cookieid` bigINT(20) UNSIGNED DEFAULT NULL,
					`createip` VARBINARY(16) DEFAULT NULL,
					`lastuserid` INT(10) UNSIGNED DEFAULT NULL,
					`lastip` VARBINARY(16) DEFAULT NULL,
					`upvotes` SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0,
					`downvotes` SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0,
					`netvotes` SMALLINT(6) NOT NULL DEFAULT 0,
					`lastviewip` VARBINARY(16) DEFAULT NULL,
					`views` INT(10) UNSIGNED NOT NULL DEFAULT 0,
					`hotness` float DEFAULT NULL,
					`flagcount` tinyINT(3) UNSIGNED NOT NULL DEFAULT 0,
					`format` VARCHAR(20) CHARACTER SET ascii NOT NULL DEFAULT \'\',
					`created` DATETIME NOT NULL,
					`updated` DATETIME DEFAULT NULL,
					`updatetype` char(1) CHARACTER SET ascii DEFAULT NULL,
					`title` VARCHAR(800) DEFAULT NULL,
					`content` VARCHAR(18000) DEFAULT NULL,
					`permalink` VARCHAR(800) DEFAULT NULL,
					`tags` VARCHAR(800) DEFAULT NULL,
					`name` VARCHAR(40) DEFAULT NULL,
					`notify` VARCHAR(80) DEFAULT NULL,
					PRIMARY KEY (`postid`), 
					KEY `type` (`type`,`created`), 
					KEY `type_2` (`type`,`ccount`,`created`), 
					KEY `type_4` (`type`,`netvotes`,`created`), 
					KEY `type_5` (`type`,`views`,`created`), 
					KEY `type_6` (`type`,`hotness`), 
					KEY `type_7` (`type`,`amaxvote`,`created`), 
					KEY `parentid` (`parentid`,`type`), 
					KEY `userid` (`userid`,`type`,`created`), 
					KEY `selchildid` (`selchildid`,`type`,`created`), 
					KEY `closedbyid` (`closedbyid`), 
					KEY `catidpath1` (`catidpath1`,`type`,`created`), 
					KEY `catidpath2` (`catidpath2`,`type`,`created`), 
					KEY `catidpath3` (`catidpath3`,`type`,`created`), 
					KEY `catid` (`catid`,`type`,`created`), 
					KEY `createip` (`createip`,`created`), 
					KEY `updated` (`updated`,`type`), 
					KEY `flagcount` (`flagcount`,`created`,`type`), 
					KEY `catidpath1_2` (`catidpath1`,`updated`,`type`), 
					KEY `catidpath2_2` (`catidpath2`,`updated`,`type`), 
					KEY `catidpath3_2` (`catidpath3`,`updated`,`type`), 
					KEY `catid_2` (`catid`,`updated`,`type`), 
					KEY `lastuserid` (`lastuserid`,`updated`,`type`), 
					KEY `lastip` (`lastip`,`updated`,`type`),
					CONSTRAINT `blog_posts_ibfk_2` FOREIGN KEY (`parentid`) REFERENCES ^blog_posts (`postid`),
					CONSTRAINT `blog_posts_ibfk_3` FOREIGN KEY (`catid`) REFERENCES ^blog_cats (`catid`) ON DELETE SET NULL,
					CONSTRAINT `blog_posts_ibfk_4` FOREIGN KEY (`closedbyid`) REFERENCES ^blog_posts (`postid`),
					CONSTRAINT `blog_posts_ibfk_1` FOREIGN KEY (`userid`) REFERENCES ^users (`userid`) ON DELETE SET NULL				
				) ENGINE=InnoDB DEFAULT CHARSET=utf8',

			'CREATE TABLE IF NOT EXISTS ^blog_users (
					`userid` INT(10) UNSIGNED NOT NULL,
					`lastposted` DATETIME NOT NULL,
					`pcount` INT(10) UNSIGNED NOT NULL DEFAULT 0,
					`ccount` INT(10) UNSIGNED NOT NULL DEFAULT 0,
					`dcount` INT(10) UNSIGNED NOT NULL DEFAULT 0,
					`points` INT NOT NULL DEFAULT 0,
					`cselects` MEDIUMINT NOT NULL DEFAULT 0,
					`cselecteds` MEDIUMINT NOT NULL DEFAULT 0,
					`pupvotes` MEDIUMINT NOT NULL DEFAULT 0,
					`pdownvotes` MEDIUMINT NOT NULL DEFAULT 0,
					`cupvotes` MEDIUMINT NOT NULL DEFAULT 0,
					`cdownvotes` MEDIUMINT NOT NULL DEFAULT 0,
					`pvoteds` INT NOT NULL DEFAULT 0,
					`cvoteds` INT NOT NULL DEFAULT 0,
					`upvoteds` INT NOT NULL DEFAULT 0,
					`downvoteds` INT NOT NULL DEFAULT 0,
					`kickeduntil` DATETIME NOT NULL,
					PRIMARY KEY (`userid`),
					KEY `userid` (`userid`),
					KEY `points` (`points`),
					KEY `active` (`lastposted`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8',
		);
	}

	public function process_request($request)
	{
		global $qa_request;
		$qa_content = qa_content_prepare();
		$request1 = qa_request_part(1);
		$request2 = qa_request_parts(2);
		$userid = qa_get_logged_in_userid();
		$blog_cats = qa_db_select_with_pending(bp_db_cat_nav_selectspec(null, true, false, true));

		if (is_numeric($request1)) {
			$qa_content = qa_article_viewer($userid, substr($qa_request, 5, 200));
		}
		else {
			switch ($request1) {
				case 'write':
					$qa_content = qa_blog_write($userid, $blog_cats);
					break;

				case 'edit':
					$qa_content = qa_blog_edit($userid, $blog_cats, $request2[0]);
					break;

				case 'hide':
					$qa_content = qa_blog_hide($userid, $request2[0]);
					break;

				case 'delete':
					$qa_content = qa_blog_delete($userid, $request2[0]);
					break;

				default:
					$qa_content = qa_article_feed();
					break;
			}
		}
		$qa_content['navigation']['sub'] = bp_sub_navigation($qa_request, $blog_cats);
		return $qa_content;
	}

}
