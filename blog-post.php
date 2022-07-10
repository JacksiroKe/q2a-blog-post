<?php
/*
 Blog Post by Jack Siro
 https://github.com/JacksiroKe/q2a-blog-post
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
require_once QA_PLUGIN_DIR . 'q2a-blog-post/blog-base.php';
require_once QA_PLUGIN_DIR . 'q2a-blog-post/blog-db.php';
require_once QA_PLUGIN_DIR . 'q2a-blog-post/blog-format.php';

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
		$countslugs = count($request2);
		$userid = qa_get_logged_in_userid();
		$blog_cats = qa_db_select_with_pending(bp_db_cat_nav_selectspec(null, true, false, true));

		if (is_numeric($request1)) {
			$qa_content = $this->qa_blog_post($userid, substr($qa_request, 5, 200));
		}
		else {
			switch ($request1) {
				case 'write':
					$qa_content = $this->qa_blog_write($userid, $blog_cats);
					break;

				case 'edit':
					$qa_content = $this->qa_blog_edit($userid, $blog_cats, $request2[0]);
					break;

				case 'hide':
					$qa_content = $this->qa_blog_hide($userid, $request2[0]);
					break;

				case 'delete':
					$qa_content = $this->qa_blog_delete($userid, $request2[0]);
					break;

				default:
					$qa_content = $this->qa_blog_home($userid, $request2, $countslugs);
					break;
			}
		}
		$qa_content['navigation']['sub'] = bp_sub_navigation($qa_request, $blog_cats);
		return $qa_content;
	}

	public function qa_blog_write($userid, $blog_cats)
	{
		$in = array();
		$in['catid'] = qa_clicked('dowrite') ? qa_get_category_field_value('category') : qa_get('cat');
		$completetags = qa_db_popular_tags_selectspec(0, QA_DB_RETRIEVE_COMPLETE_TAGS);

		// Check for permission error
		$permiterror = qa_user_maximum_permit_error('bp_permit_post_p', QA_LIMIT_QUESTIONS);

		$qa_content = qa_content_prepare(false, array_keys(bp_cat_path($blog_cats, @$in['catid'])));

		if ($permiterror) {
			switch ($permiterror) {
				case 'login':
					$qa_content['error'] = qa_insert_login_links(qa_lang_html('bp_lang/write_must_login'), qa_request(), isset($followpostid) ? array('follow' => $followpostid) : null);
					break;

				case 'confirm':
					$qa_content['error'] = qa_insert_login_links(qa_lang_html('bp_lang/write_must_confirm'), qa_request(), isset($followpostid) ? array('follow' => $followpostid) : null);
					break;

				case 'limit':
					$qa_content['error'] = qa_lang_html('bp_lang/write_limit');
					break;

				case 'approve':
					$qa_content['error'] = strtr(qa_lang_html('bp_lang/write_must_be_approved'), array(
						'^1' => '<a href="' . qa_path_html('account') . '">',
						'^2' => '</a>',
					));
					break;

				default:
					$qa_content['error'] = qa_lang_html('users/no_permission');
					break;
			}
			return $qa_content;
		}

		// Process input
		$captchareason = qa_user_captcha_reason();

		$in['title'] = qa_get_post_title('title'); // allow title and tags to be posted by an external form
		$in['extra'] = qa_opt('extra_field_active') ? qa_post_text('extra') : null;
		if (qa_using_tags())
			$in['tags'] = qa_get_tags_field_value('tags');

		if (qa_clicked('dowrite')) {
			require_once QA_INCLUDE_DIR . 'app/post-create.php';

			$catids = array_keys(bp_cat_path($blog_cats, @$in['catid']));
			$userlevel = bp_user_level_for_cats($catids);

			$in['name'] = qa_opt('allow_anonymous_naming') ? qa_post_text('name') : null;
			$in['notify'] = strlen(qa_post_text('notify')) > 0;
			$in['email'] = qa_post_text('email');
			$in['queued'] = qa_user_moderation_reason($userlevel) !== false;

			qa_get_post_content('editor', 'content', $in['editor'], $in['content'], $in['format'], $in['text']);


			$errors = array();
			if (!qa_check_form_security_code('write', qa_post_text('code'))) {
				$errors['page'] = qa_lang_html('misc/form_security_again');
			}
			else {
				$filtermodules = qa_load_modules_with('filter', 'filter_article');
				foreach ($filtermodules as $filtermodule) {
					$oldin = $in;
					$filtermodule->filter_article($in, $errors, null);
					qa_update_post_text($in, $oldin);
				}

				if (qa_using_categories() && count($blog_cats) && (!qa_opt('bp_allow_no_blogcat')) && !isset($in['catid'])) {
					// check this here because we need to know count($blog_cats)
					$errors['catid'] = qa_lang_html('bp_lang/category_required');
				}
				elseif (qa_user_permit_error('bp_permit_post_p', null, $userlevel)) {
					$errors['catid'] = qa_lang_html('bp_lang/category_write_not_allowed');
				}

				if ($captchareason) {
					require_once QA_INCLUDE_DIR . 'app/captcha.php';
					qa_captcha_validate_post($errors);
				}

				if (empty($errors)) {
					// check if the article is already posted
					$testTitleWords = implode(' ', qa_string_to_words($in['title']));
					$testContentWords = implode(' ', qa_string_to_words($in['content']));
					$recentArticles = qa_db_select_with_pending(bp_db_ps_selectspec(null, 'created', 0, null, null, false, true, 5));

					foreach ($recentArticles as $article) {
						if (!$article['hidden']) {
							$pTitleWords = implode(' ', qa_string_to_words($article['title']));
							$pContentWords = implode(' ', qa_string_to_words($article['content']));

							if ($pTitleWords == $testTitleWords && $pContentWords == $testContentWords) {
								$errors['page'] = qa_lang_html('bp_lang/duplicate_content');
								break;
							}
						}
					}
				}

				if (empty($errors)) {
					$cookieid = isset($userid) ? qa_cookie_get() : qa_cookie_get_create(); // create a new cookie if necessary
					$title = qa_block_words_replace($in['title'], qa_get_block_words_preg());
					$slug = qa_slugify($title, qa_opt('q_urls_remove_accents'), qa_opt('q_urls_title_length'));
					$permalink = gmdate('Y/m/d', time()) . '/' . $slug;

					$postid = bp_post_create($userid, qa_get_logged_in_handle(), $cookieid,
						$title, $in['content'], $permalink, $in['format'], $in['text'], isset($in['tags']) ? qa_tags_to_tagstring($in['tags']) : '',
						$in['notify'], $in['email'], $in['catid'], $in['extra'], $in['queued'], $in['name']);
					qa_redirect(bp_p_request($permalink));
				}
			}
		}

		$qa_content['title'] = qa_lang('bp_lang/write_article');
		$qa_content['error'] = @$errors['page'];

		$editorname = isset($in['editor']) ? $in['editor'] : qa_opt('bp_blog_editor');
		$editor = qa_load_editor(@$in['content'], @$in['format'], $editorname);

		$field = qa_editor_load_field($editor, $qa_content, @$in['content'], @$in['format'], 'content', 12, false);
		$field['label'] = qa_lang_html('bp_lang/content_label');
		$field['error'] = qa_html(@$errors['content']);

		$custom = qa_opt('bp_show_custom_write') ? trim(qa_opt('bp_custom_write')) : '';

		$qa_content['form'] = array(
			'tags' => 'name="write" method="post" action="' . qa_self_html() . '"',
			'style' => 'tall',

			'fields' => array(
				'custom' => array(
					'type' => 'custom',
					'note' => $custom,
				),

				'title' => array(
					'label' => qa_lang_html('bp_lang/title_label'),
					'tags' => 'name="title" id="title" autocomplete="off"',
					'value' => qa_html(@$in['title']),
					'error' => qa_html(@$errors['title']),
				),

				'similar' => array(
					'type' => 'custom',
					'html' => '<span id="similar"></span>',
				),

				'content' => $field,
			),

			'buttons' => array(
				'write' => array(
					'tags' => 'onclick="qa_show_waiting_after(this, false); ' .
					(method_exists($editor, 'update_script') ? $editor->update_script('content') : '') . '"',
					'label' => qa_lang_html('bp_lang/post_button'),
				),
			),

			'hidden' => array(
				'editor' => qa_html($editorname),
				'code' => qa_get_form_security_code('write'),
				'dowrite' => '1',
			),
		);

		if (!strlen($custom))
			unset($qa_content['form']['fields']['custom']);

		if (qa_opt('do_ask_check_qs') || qa_opt('do_example_tags')) {
			$qa_content['form']['fields']['title']['tags'] .= ' onchange="qa_title_change(this.value);"';

			if (strlen(@$in['title'])) {
				$qa_content['script_onloads'][] = 'qa_title_change(' . qa_js($in['title']) . ');';
			}
		}

		if (qa_using_categories() && count($blog_cats)) {
			$field = array(
				'label' => qa_lang_html('bp_lang/blogcat_label'),
				'error' => qa_html(@$errors['catid']),
			);

			bp_set_up_cat_field($qa_content, $field, 'category', $blog_cats, $in['catid'], true, true, null, false);
			if (!qa_opt('allow_no_category'))
				$field['options'][''] = '';
			qa_array_insert($qa_content['form']['fields'], 'content', array('category' => $field));
		}

		if (qa_opt('extra_field_active')) {
			$field = array(
				'label' => qa_html(qa_opt('extra_field_prompt')),
				'tags' => 'name="extra"',
				'value' => qa_html(@$in['extra']),
				'error' => qa_html(@$errors['extra']),
			);

			qa_array_insert($qa_content['form']['fields'], null, array('extra' => $field));
		}

		if (qa_using_tags()) {
			$field = array(
				'error' => qa_html(@$errors['tags']),
			);

			qa_set_up_tag_field($qa_content, $field, 'tags', isset($in['tags']) ? $in['tags'] : array(), array(),
				qa_opt('do_complete_tags') ? array_keys($completetags) : array(), qa_opt('page_size_ask_tags'));

			qa_array_insert($qa_content['form']['fields'], null, array('tags' => $field));
		}

		if (!isset($userid) && qa_opt('allow_anonymous_naming')) {
			qa_set_up_name_field($qa_content, $qa_content['form']['fields'], @$in['name']);
		}

		bp_setup_notify_fields($qa_content, $qa_content['form']['fields'], 'P', qa_get_logged_in_email(),
			isset($in['notify']) ? $in['notify'] : qa_opt('notify_users_default'), @$in['email'], @$errors['email']);

		if ($captchareason) {
			require_once QA_INCLUDE_DIR . 'app/captcha.php';
			qa_set_up_captcha_field($qa_content, $qa_content['form']['fields'], @$errors, qa_captcha_reason_note($captchareason));
		}

		$qa_content['focusid'] = 'title';
		return $qa_content;
	}

	public function qa_blog_edit($userid, $blog_cats, $articleid)
	{
		$findpost = qa_bp_post_find_by_postid($articleid);
		if (!count($findpost))
			return $this->bp_article_not_found();

		$article = qa_db_select_with_pending(
			bp_db_full_post_selectspec($userid, $articleid),
		);

		$in = array();
		$in['catid'] = qa_clicked('dosave') ? qa_get_category_field_value('category') : qa_get('cat');
		$completetags = qa_db_popular_tags_selectspec(0, QA_DB_RETRIEVE_COMPLETE_TAGS);

		// Check for permission error
		$permiterror = qa_user_maximum_permit_error('bp_permit_post_p', QA_LIMIT_QUESTIONS);

		$qa_content = qa_content_prepare(false, array_keys(bp_cat_path($blog_cats, @$in['catid'])));

		if ($permiterror) {
			switch ($permiterror) {
				case 'login':
					$qa_content['error'] = qa_insert_login_links(qa_lang_html('bp_lang/write_must_login'), qa_request(), isset($followpostid) ? array('follow' => $followpostid) : null);
					break;

				case 'confirm':
					$qa_content['error'] = qa_insert_login_links(qa_lang_html('bp_lang/write_must_confirm'), qa_request(), isset($followpostid) ? array('follow' => $followpostid) : null);
					break;

				case 'limit':
					$qa_content['error'] = qa_lang_html('bp_lang/write_limit');
					break;

				case 'approve':
					$qa_content['error'] = strtr(qa_lang_html('bp_lang/write_must_be_approved'), array(
						'^1' => '<a href="' . qa_path_html('account') . '">',
						'^2' => '</a>',
					));
					break;

				default:
					$qa_content['error'] = qa_lang_html('users/no_permission');
					break;
			}
			return $qa_content;
		}

		// Process input
		$captchareason = qa_user_captcha_reason();

		$in['title'] = $article['title'];
		$in['content'] = $article['content'];
		$in['catid'] = $article['catid'];

		$in['extra'] = qa_opt('extra_field_active') ? qa_post_text('extra') : null;
		if (qa_using_tags())
			$in['tags'] = qa_get_tags_field_value('tags');

		if (qa_clicked('dosave')) {
			require_once QA_INCLUDE_DIR . 'app/post-create.php';

			$catids = array_keys(bp_cat_path($blog_cats, @$in['catid']));
			$userlevel = bp_user_level_for_cats($catids);

			$in['name'] = qa_opt('allow_anonymous_naming') ? qa_post_text('name') : null;
			$in['notify'] = strlen(qa_post_text('notify')) > 0;
			$in['email'] = qa_post_text('email');
			$in['queued'] = qa_user_moderation_reason($userlevel) !== false;

			qa_get_post_content('editor', 'content', $in['editor'], $in['content'], $in['format'], $in['text']);

			$errors = array();
			if (!qa_check_form_security_code('edit-' . $articleid, qa_post_text('code'))) {
				$errors['page'] = qa_lang_html('misc/form_security_again');
			}
			else {
				$filtermodules = qa_load_modules_with('filter', 'filter_article');
				foreach ($filtermodules as $filtermodule) {
					$oldin = $in;
					$filtermodule->filter_article($in, $errors, null);
					qa_update_post_text($in, $oldin);
				}

				if (qa_using_categories() && count($blog_cats) && (!qa_opt('bp_allow_no_blogcat')) && !isset($in['catid'])) {
					// check this here because we need to know count($blog_cats)
					$errors['catid'] = qa_lang_html('bp_lang/category_required');
				}
				elseif (qa_user_permit_error('bp_permit_post_p', null, $userlevel)) {
					$errors['catid'] = qa_lang_html('bp_lang/category_write_not_allowed');
				}

				if ($captchareason) {
					require_once QA_INCLUDE_DIR . 'app/captcha.php';
					qa_captcha_validate_post($errors);
				}

				if (empty($errors)) {
					// check if the article is already posted
					$testTitleWords = implode(' ', qa_string_to_words($in['title']));
					$testContentWords = implode(' ', qa_string_to_words($in['content']));
					$recentArticles = qa_db_select_with_pending(bp_db_ps_selectspec(null, 'created', 0, null, null, false, true, 5));

					foreach ($recentArticles as $article) {
						if (!$article['hidden']) {
							$pTitleWords = implode(' ', qa_string_to_words($article['title']));
							$pContentWords = implode(' ', qa_string_to_words($article['content']));

							if ($pTitleWords == $testTitleWords && $pContentWords == $testContentWords) {
								$errors['page'] = qa_lang_html('bp_lang/duplicate_content');
								break;
							}
						}
					}
				}

				if (empty($errors)) {
					$in['text'] = qa_viewer_text($in['content'], $in['format']);

					$title = qa_block_words_replace($in['title'], qa_get_block_words_preg());
					$slug = qa_slugify($title, qa_opt('q_urls_remove_accents'), qa_opt('q_urls_title_length'));
					$permalink = gmdate('Y/m/d', $article['created']) . '/' . $slug;

					bp_post_update($article, $userid, $in['title'], $permalink, $in['content'], $in['format'], $in['text'], isset($in['tags']) ? qa_tags_to_tagstring($in['tags']) : '', $in['notify'], $in['email'], $in['catid'], $in['extra'], $in['queued'], $in['name']);
					qa_redirect(bp_p_request($permalink));
				}
			}
		}

		$qa_content['title'] = qa_lang('bp_lang/edit_article') . $article['title'];
		$qa_content['error'] = @$errors['page'];

		$editorname = isset($in['editor']) ? $in['editor'] : qa_opt('bp_blog_editor');
		$editor = qa_load_editor(@$in['content'], @$in['format'], $editorname);

		$field = qa_editor_load_field($editor, $qa_content, @$in['content'], @$in['format'], 'content', 12, false);
		$field['label'] = qa_lang_html('bp_lang/content_label');
		$field['error'] = qa_html(@$errors['content']);

		$custom = qa_opt('bp_show_custom_write') ? trim(qa_opt('bp_custom_write')) : '';

		$qa_content['form'] = array(
			'tags' => 'name="write" method="post" action="' . qa_self_html() . '"',
			'style' => 'tall',

			'fields' => array(
				'custom' => array(
					'type' => 'custom',
					'note' => $custom,
				),

				'title' => array(
					'label' => qa_lang_html('bp_lang/title_label'),
					'tags' => 'name="title" id="title" autocomplete="off"',
					'value' => qa_html(@$in['title']),
					'error' => qa_html(@$errors['title']),
				),

				'similar' => array(
					'type' => 'custom',
					'html' => '<span id="similar"></span>',
				),

				'content' => $field,
			),

			'buttons' => array(
				'save' => array(
					'tags' => 'onclick="qa_show_waiting_after(this, false); ' .
					(method_exists($editor, 'update_script') ? $editor->update_script('content') : '') . '"',
					'label' => qa_lang_html('main/save_button'),
				),

				'cancel' => array(
					'tags' => 'name="docancel"',
					'label' => qa_lang_html('main/cancel_button'),
				),
			),

			'hidden' => array(
				'code' => qa_get_form_security_code('edit-' . $articleid),
				'editor' => qa_html($editorname),
				'dosave' => '1',
			),
		);

		if (!strlen($custom))
			unset($qa_content['form']['fields']['custom']);

		if (qa_opt('do_ask_check_qs') || qa_opt('do_example_tags')) {
			$qa_content['form']['fields']['title']['tags'] .= ' onchange="qa_title_change(this.value);"';

			if (strlen(@$in['title'])) {
				$qa_content['script_onloads'][] = 'qa_title_change(' . qa_js($in['title']) . ');';
			}
		}

		if (qa_using_categories() && count($blog_cats)) {
			$field = array(
				'label' => qa_lang_html('bp_lang/blogcat_label'),
				'error' => qa_html(@$errors['catid']),
			);

			bp_set_up_cat_field($qa_content, $field, 'category', $blog_cats, $in['catid'], true, true, null, false);
			if (!qa_opt('allow_no_category'))
				$field['options'][''] = '';
			qa_array_insert($qa_content['form']['fields'], 'content', array('category' => $field));
		}

		if (qa_opt('extra_field_active')) {
			$field = array(
				'label' => qa_html(qa_opt('extra_field_prompt')),
				'tags' => 'name="extra"',
				'value' => qa_html(@$in['extra']),
				'error' => qa_html(@$errors['extra']),
			);

			qa_array_insert($qa_content['form']['fields'], null, array('extra' => $field));
		}

		if (qa_using_tags()) {
			$field = array(
				'error' => qa_html(@$errors['tags']),
			);

			qa_set_up_tag_field($qa_content, $field, 'tags', isset($in['tags']) ? $in['tags'] : array(), array(),
				qa_opt('do_complete_tags') ? array_keys($completetags) : array(), qa_opt('page_size_ask_tags'));

			qa_array_insert($qa_content['form']['fields'], null, array('tags' => $field));
		}

		if (!isset($userid) && qa_opt('allow_anonymous_naming')) {
			qa_set_up_name_field($qa_content, $qa_content['form']['fields'], @$in['name']);
		}

		bp_setup_notify_fields($qa_content, $qa_content['form']['fields'], 'P', qa_get_logged_in_email(),
			isset($in['notify']) ? $in['notify'] : qa_opt('notify_users_default'), @$in['email'], @$errors['email']);

		if ($captchareason) {
			require_once QA_INCLUDE_DIR . 'app/captcha.php';
			qa_set_up_captcha_field($qa_content, $qa_content['form']['fields'], @$errors, qa_captcha_reason_note($captchareason));
		}

		$qa_content['focusid'] = 'title';
		return $qa_content;
	}

	public function qa_blog_hide($userid, $articleid)
	{
		$findpost = qa_bp_post_find_by_postid($articleid);
		if (!count($findpost))
			return $this->bp_article_not_found();

		$article = qa_db_select_with_pending(
			bp_db_full_post_selectspec($userid, $articleid),
		);
		bp_post_hide($articleid, $userid);
		qa_redirect(bp_p_request($article['permalink']));
	}

	public function qa_blog_delete($userid, $articleid)
	{
		$findpost = qa_bp_post_find_by_postid($articleid);
		if (!count($findpost))
			return $this->bp_article_not_found();

		bp_post_delete($articleid);
		qa_redirect('blog');
	}

	public function qa_blog_home($userid, $catslugs, $countslugs)
	{
		$sort = ($countslugs && !QA_ALLOW_UNINDEXED_QUERIES) ? null : qa_get('sort');
		$start = qa_get_start();
		$userid = qa_get_logged_in_userid();

		// Get list of blog_posts, plus category information
		switch ($sort) {
			case 'hot':
				$selectsort = 'hotness';
				break;

			case 'votes':
				$selectsort = 'netvotes';
				break;

			case 'replys':
				$selectsort = 'ccount';
				break;

			case 'views':
				$selectsort = 'views';
				break;

			default:
				$selectsort = 'created';
				break;
		}

		list($blog_posts, $blog_cats, $catid) = qa_db_select_with_pending(
			bp_db_ps_selectspec($userid, $selectsort, $start, $catslugs, null, false, false, qa_opt_if_loaded('page_size_qs')),
			bp_db_cat_nav_selectspec($catslugs, false, false, true),
			$countslugs ? bp_db_slugs_to_cat_id_selectspec($catslugs) : null
		);

		if ($countslugs) {
			if (!isset($catid)) {
				return include QA_INCLUDE_DIR . 'qa-page-not-found.php';
			}

			$categorytitlehtml = qa_html($blog_cats[$catid]['title']);
			$nonetitle = qa_lang_html_sub('bp_lang/no_articles_in_x', $categorytitlehtml);

		}
		else {
			$nonetitle = qa_lang_html('bp_lang/no_articles_found');
		}

		$categorypathprefix = QA_ALLOW_UNINDEXED_QUERIES ? 'blog/' : null; // this default is applied if sorted not by recent
		$feedpathprefix = null;
		$linkparams = array('sort' => $sort);

		switch ($sort) {
			case 'hot':
				$sometitle = $countslugs ? qa_lang_html_sub('bp_lang/hot_qs_in_x', $categorytitlehtml) : qa_lang_html('bp_lang/hot_qs_title');
				$feedpathprefix = qa_opt('feed_for_hot') ? 'hot' : null;
				break;

			case 'votes':
				$sometitle = $countslugs ? qa_lang_html_sub('bp_lang/voted_qs_in_x', $categorytitlehtml) : qa_lang_html('bp_lang/voted_qs_title');
				break;

			case 'replys':
				$sometitle = $countslugs ? qa_lang_html_sub('bp_lang/replyed_qs_in_x', $categorytitlehtml) : qa_lang_html('bp_lang/replyed_qs_title');
				break;

			case 'views':
				$sometitle = $countslugs ? qa_lang_html_sub('bp_lang/viewed_qs_in_x', $categorytitlehtml) : qa_lang_html('bp_lang/viewed_qs_title');
				break;

			default:
				$linkparams = array();
				$sometitle = $countslugs ? qa_lang_html_sub('bp_lang/recent_qs_in_x', $categorytitlehtml) : qa_lang_html('bp_lang/recent_ps_title');
				$categorypathprefix = 'blog/';
				$feedpathprefix = qa_opt('feed_for_blog') ? 'blog_posts' : null;
				break;
		}

		$qa_content = bp_b_list_page_content(
			$blog_posts, // blog_posts
			(strlen(qa_opt('bp_page_size')) > 0) ? qa_opt('bp_page_size') : 10, // blog_posts per page
			$start, // start offset
			$countslugs ? $blog_cats[$catid]['pcount'] : qa_opt('cache_pcount'), // total count
			$sometitle, // title if some blog_posts
			$nonetitle, // title if no blog_posts
			$blog_cats, // blog_cats for navigation
			$catid, // selected category id
			true, // show article counts in category navigation
			$categorypathprefix, // prefix for links in category navigation
			$feedpathprefix, // prefix for RSS feed paths
			$countslugs ? bp_html_suggest_ps_tags(qa_using_tags()) : bp_html_suggest_write($catid), // suggest what to do next
			$linkparams, // extra parameters for page links
			$linkparams // category nav params
		);
		return $qa_content;
	}

	public function qa_blog_post($userid, $permalink)
	{
		require_once QA_INCLUDE_DIR . 'app/cookies.php';
		require_once QA_INCLUDE_DIR . 'db/selects.php';
		require_once QA_INCLUDE_DIR . 'util/sort.php';
		require_once QA_INCLUDE_DIR . 'app/captcha.php';
		require_once QA_PLUGIN_DIR . 'q2a-blog-post/blog-view.php';
		require_once QA_PLUGIN_DIR . 'q2a-blog-post/blog-article.php';
		require_once QA_INCLUDE_DIR . 'app/updates.php';
		$qa_content = qa_content_prepare();

		$findpost = qa_bp_post_find_by_permalink($permalink);
		if (!count($findpost))
			return $this->bp_article_not_found();

		$articleid = $findpost[0];
		$cookieid = qa_cookie_get();
		$pagestate = qa_get_state();

		$articleData = qa_db_select_with_pending(
			bp_db_full_post_selectspec($userid, $articleid),
			bp_db_full_child_posts_selectspec($userid, $articleid),
			bp_db_full_c_child_posts_selectspec($userid, $articleid),
			bp_db_post_parent_p_selectspec($articleid),
			bp_db_post_close_post_selectspec($articleid),
			bp_db_post_duplicates_selectspec($articleid),
			bp_db_post_meta_selectspec($articleid, 'qa_q_extra'),
			bp_db_cat_nav_selectspec($articleid, true, true, true),
			isset($userid) ? qa_db_is_favorite_selectspec($userid, QA_ENTITY_QUESTION, $articleid) : null
		);
		list($article, $childposts, $cchildposts, $parentarticle, $closepost, $duplicateposts, $extravalue, $categories, $favorite) = $articleData;

		if ($article['basetype'] != 'P')
			$article = null;
		if (isset($article)) {
			$p_request = bp_p_request($permalink);
			if (trim($p_request, '/') !== trim(qa_request(), '/'))
				qa_redirect($p_request);

			$article['extra'] = $extravalue;

			$comments = bp_page_p_load_as($article, $childposts);
			$replysfollows = bp_page_p_load_c_follows($article, $childposts, $cchildposts, $duplicateposts);

			$article = $article + bp_page_p_post_rules($article, null, null, $childposts + $duplicateposts); // array union

			if ($article['selchildid'] && (@$comments[$article['selchildid']]['type'] != 'A'))
				$article['selchildid'] = null; // if selected comment is hidden or somehow not there, consider it not selected

			foreach ($comments as $key => $comment) {
				$comments[$key] = $comment + bp_page_p_post_rules($comment, $article, $comments, $achildposts);
				$comments[$key]['isselected'] = ($comment['postid'] == $article['selchildid']);
			}

			foreach ($replysfollows as $key => $replyfollow) {
				$parent = ($replyfollow['parentid'] == $articleid) ? $article : @$comments[$replyfollow['parentid']];
				$replysfollows[$key] = $replyfollow + bp_page_p_post_rules($replyfollow, $parent, $replysfollows, null);
			}
		}
		if (!isset($article))
			return $this->bp_article_not_found();

		if (!$article['viewable']) {
			$qa_content = qa_content_prepare();

			if ($article['queued'])
				$qa_content['error'] = qa_lang_html('bp_lang/p_waiting_approval');
			elseif ($article['flagcount'] && !isset($article['lastuserid']))
				$qa_content['error'] = qa_lang_html('bp_lang/p_hidden_flagged');
			elseif ($article['authorlast'])
				$qa_content['error'] = qa_lang_html('bp_lang/p_hidden_author');
			else
				$qa_content['error'] = qa_lang_html('bp_lang/p_hidden_other');

			$qa_content['suggest_next'] = qa_html_suggest_qs_tags(qa_using_tags());

			return $qa_content;
		}
		$permiterror = qa_user_post_permit_error('bp_permit_view_p_page', $article, null, false);

		if ($permiterror && (qa_is_human_probably() || !qa_opt('allow_view_q_bots'))) {
			$qa_content = qa_content_prepare();
			$topage = bp_p_request($article['permalink']);

			switch ($permiterror) {
				case 'login':
					$qa_content['error'] = qa_insert_login_links(qa_lang_html('bp_lang/view_p_must_login'), $topage);
					break;

				case 'confirm':
					$qa_content['error'] = qa_insert_login_links(qa_lang_html('bp_lang/view_p_must_confirm'), $topage);
					break;

				case 'approve':
					$qa_content['error'] = strtr(qa_lang_html('bp_lang/view_p_must_be_approved'), array(
						'^1' => '<a href="' . qa_path_html('cccount') . '">',
						'^2' => '</a>',
					));
					break;

				default:
					$qa_content['error'] = qa_lang_html('users/no_permission');
					break;
			}
			return $qa_content;
		}

		$captchareason = qa_user_captcha_reason(qa_user_level_for_post($article));
		$usecaptcha = ($captchareason != false);

		$pagestart = qa_get_start();
		$showid = qa_get('show');
		$pageerror = null;
		$formtype = null;
		$formpostid = null;
		$jumptoanchor = null;
		$replysall = null;

		if (substr($pagestate, 0, 13) == 'showreplys-') {
			$replysall = substr($pagestate, 13);
			$pagestate = null;

		}
		elseif (isset($showid)) {
			foreach ($replysfollows as $reply) {
				if ($reply['postid'] == $showid) {
					$replysall = $reply['parentid'];
					break;
				}
			}
		}

		//if (qa_is_http_post() || strlen($pagestate)) require QA_PLUGIN_DIR . 'q2a-blog-post/blog-view.php';

		$formrequested = isset($formtype);

		if (!$formrequested && $article['answerbutton']) {
			$immedoption = qa_opt('show_a_form_immediate');

			if ($immedoption == 'always' || ($immedoption == 'if_no_as' && !$article['isbyuser']))
				$formtype = 'a_add'; // show comment form by default
		}


		// Get information on the users referenced
		$usershtml = qa_userids_handles_html(array_merge(array($article), $comments, $replysfollows), true);

		// Prepare content for theme
		$qa_content = qa_content_prepare(true, array_keys(bp_cat_path($categories, $article['catid'])));

		if (isset($userid) && !$formrequested)
			$qa_content['favorite'] = qa_favorite_form(QA_ENTITY_QUESTION, $articleid, $favorite,
				qa_lang($favorite ? 'article/remove_q_favorites' : 'article/add_q_favorites'));

		if (isset($pageerror))
			$qa_content['error'] = $pageerror; // might also show voting error set in qa-index.php

		elseif ($article['queued'])
			$qa_content['error'] = $article['isbyuser'] ? qa_lang_html('bp_lang/p_your_waiting_approval') : qa_lang_html('bp_lang/p_waiting_your_approval');

		if ($article['hidden'])
			$qa_content['hidden'] = true;

		qa_sort_by($replysfollows, 'created');

		// Prepare content for the article...
		if ($formtype == 'q_edit') { // ...in edit mode
			$qa_content['title'] = qa_lang_html($article['editable'] ? 'article/edit_q_title' :
				(qa_using_categories() ? 'article/recat_q_title' : 'article/retag_q_title'));
			$qa_content['form_q_edit'] = qa_page_q_edit_q_form($qa_content, $article, @$qin, @$qerrors, $completetags, $categories);
			$qa_content['q_view']['raw'] = $article;

		}
		else { // ...in view mode
			$qa_content['q_view'] = bp_page_p_article_view($article, $parentarticle, $closepost, $usershtml, $formrequested);

			if (array_key_exists('title', $qa_content['q_view']))
				$qa_content['title'] = $qa_content['q_view']['title'];
			else
				$qa_content['title'] = qa_lang_html('bp_lang/blog_post_title');

			$qa_content['description'] = qa_html(qa_shorten_string_line(qa_viewer_text($article['content'], $article['format']), 150));

			$categorykeyword = @$categories[$article['catid']]['title'];

			$qa_content['keywords'] = qa_html(implode(',', array_merge(
				(qa_using_categories() && strlen($categorykeyword)) ? array($categorykeyword) : array(),
				qa_tagstring_to_tags($article['tags'])
			))); // as far as I know, META keywords have zero effect on search rankings or listings, but many people have asked for this
		}

		$microdata = qa_opt('use_microdata');
		if ($microdata) {
			$qa_content['head_lines'][] = '<meta itemprop="name" content="' . qa_html($qa_content['q_view']['raw']['title']) . '">';
			$qa_content['html_tags'] .= ' itemscope itemtype="http://schema.org/QAPage"';
			$qa_content['main_tags'] = ' itemscope itemtype="http://schema.org/Question"';
		}

		if ($formtype == 'a_edit') {
			$qa_content['a_form'] = bp_page_p_edit_c_form($qa_content, 'a' . $formpostid, $comments[$formpostid],
				$article, $comments, $replysfollows, @$aeditin[$formpostid], @$aediterrors[$formpostid]);

			$qa_content['a_form']['c_list'] = bp_page_p_reply_follow_list($article, $comments[$formpostid],
				$replysfollows, true, $usershtml, $formrequested, $formpostid);

			$jumptoanchor = 'a' . $formpostid;

		}

		if ($formtype == 'q_close') {
			$qa_content['q_view']['c_form'] = qa_page_q_close_q_form($qa_content, $article, 'close', @$closein, @$closeerrors);
			$jumptoanchor = 'close';

		}
		elseif (($formtype == 'c_add' && $formpostid == $articleid) || ($article['commentbutton'] && !$formrequested)) { // ...to be added
			$qa_content['q_view']['c_form'] = qa_page_q_add_c_form($qa_content, $article, $article, 'c' . $articleid,
				$captchareason, @$cnewin[$articleid], @$cnewerrors[$articleid], $formtype == 'c_add');

			if ($formtype == 'c_add' && $formpostid == $articleid) {
				$jumptoanchor = 'c' . $articleid;
				$replysall = $articleid;
			}

		}
		elseif ($formtype == 'c_edit' && @$replysfollows[$formpostid]['parentid'] == $articleid) { // ...being edited
			$qa_content['q_view']['c_form'] = qa_page_q_edit_c_form($qa_content, 'c' . $formpostid, $replysfollows[$formpostid],
				@$ceditin[$formpostid], @$cediterrors[$formpostid]);

			$jumptoanchor = 'c' . $formpostid;
			$replysall = $articleid;
		}

		$qa_content['q_view']['c_list'] = bp_page_p_reply_follow_list($article, $article, $replysfollows,
			$replysall == $articleid, $usershtml, $formrequested, $formpostid); // ...for viewing


		// Prepare content for existing comments (could be added to by Ajax)

		$qa_content['a_list'] = array(
			'tags' => 'id="a_list"',
			'as' => array(),
		);

		// sort according to the site preferences

		if (qa_opt('sort_comments_by') == 'votes') {
			foreach ($comments as $commentid => $comment)
				$comments[$commentid]['sortvotes'] = $comment['downvotes'] - $comment['upvotes'];

			qa_sort_by($comments, 'sortvotes', 'created');

		}
		else {
			qa_sort_by($comments, 'created');
		}

		// further changes to ordering to deal with queued, hidden and selected comments

		$countfortitle = $article['ccount'];
		$nextposition = 10000;
		$commentposition = array();

		foreach ($comments as $commentid => $comment) {
			if ($comment['viewable']) {
				$position = $nextposition++;

				if ($comment['hidden'])
					$position += 10000;

				elseif ($comment['queued']) {
					$position -= 10000;
					$countfortitle++; // include these in displayed count

				}
				elseif ($comment['isselected'] && qa_opt('show_selected_first'))
					$position -= 5000;

				$commentposition[$commentid] = $position;
			}
		}

		asort($commentposition, SORT_NUMERIC);

		// extract IDs and prepare for pagination

		$commentids = array_keys($commentposition);
		$countforpages = count($commentids);
		$pagesize = qa_opt('page_size_q_as');

		// see if we need to display a particular comment

		if (isset($showid)) {
			if (isset($replysfollows[$showid]))
				$showid = $replysfollows[$showid]['parentid'];

			$position = array_search($showid, $commentids);

			if (is_numeric($position))
				$pagestart = floor($position / $pagesize) * $pagesize;
		}

		// set the canonical url based on possible pagination

		$qa_content['canonical'] = qa_path_html(qa_q_request($article['postid'], $article['title']),
			($pagestart > 0) ? array('start' => $pagestart) : null, qa_opt('site_url'));

		// build the actual comment list

		$commentids = array_slice($commentids, $pagestart, $pagesize);

		foreach ($commentids as $commentid) {
			$comment = $comments[$commentid];

			if (!($formtype == 'a_edit' && $formpostid == $commentid)) {
				$a_view = qa_page_q_comment_view($article, $comment, $comment['isselected'], $usershtml, $formrequested);

				// Prepare content for replys on this comment, plus add or edit reply forms

				if (($formtype == 'c_add' && $formpostid == $commentid) || ($comment['commentbutton'] && !$formrequested)) { // ...to be added
					$a_view['c_form'] = qa_page_q_add_c_form($qa_content, $article, $comment, 'c' . $commentid,
						$captchareason, @$cnewin[$commentid], @$cnewerrors[$commentid], $formtype == 'c_add');

					if ($formtype == 'c_add' && $formpostid == $commentid) {
						$jumptoanchor = 'c' . $commentid;
						$replysall = $commentid;
					}

				}
				elseif ($formtype == 'c_edit' && @$replysfollows[$formpostid]['parentid'] == $commentid) { // ...being edited
					$a_view['c_form'] = qa_page_q_edit_c_form($qa_content, 'c' . $formpostid, $replysfollows[$formpostid],
						@$ceditin[$formpostid], @$cediterrors[$formpostid]);

					$jumptoanchor = 'c' . $formpostid;
					$replysall = $commentid;
				}

				$a_view['c_list'] = qa_page_q_reply_follow_list($article, $comment, $replysfollows,
					$replysall == $commentid, $usershtml, $formrequested, $formpostid); // ...for viewing

				// Add the comment to the list

				$qa_content['a_list']['as'][] = $a_view;
			}
		}

		if ($article['basetype'] == 'P') {
			$qa_content['a_list']['title_tags'] = 'id="a_list_title"';

			if ($countfortitle > 0) {
				$split = $countfortitle == 1
					? qa_lang_html_sub_split('article/1_comment_title', '1', '1')
					: qa_lang_html_sub_split('article/x_comments_title', $countfortitle);

				if ($microdata) {
					$split['data'] = '<span itemprop="commentCount">' . $split['data'] . '</span>';
				}
				$qa_content['a_list']['title'] = $split['prefix'] . $split['data'] . $split['suffix'];
			}
			else
				$qa_content['a_list']['title_tags'] .= ' style="display:none;" ';
		}

		if (!$formrequested) {
			$qa_content['page_links'] = qa_html_page_links(qa_request(), $pagestart, $pagesize, $countforpages, qa_opt('pages_prev_next'), array(), false, 'a_list_title');
		}


		// Some generally useful stuff

		if (qa_using_categories() && count($categories)) {
			$qa_content['navigation']['cat'] = bp_cat_navigation($categories, $article['catid']);
		}

		if (isset($jumptoanchor)) {
			$qa_content['script_onloads'][] = array(
				'qa_scroll_page_to($("#"+' . qa_js($jumptoanchor) . ').offset().top);'
			);
		}


		// Determine whether this request should be counted for page view statistics.
		// The lastviewip check is now part of the hotness query in order to bypass caching.

		if (qa_opt('do_count_q_views') && !$formrequested && !qa_is_http_post() && qa_is_human_probably() &&
		(!$article['views'] || (
		// if it has more than zero views, then it must be different IP & user & cookieid from the creator
		(@inet_ntop($article['createip']) != qa_remote_ip_address() || !isset($article['createip'])) &&
		($article['userid'] != $userid || !isset($article['userid'])) &&
		($article['cookieid'] != $cookieid || !isset($article['cookieid']))
		))
		) {
			$qa_content['inc_views_postid'] = $articleid;
		}

		return $qa_content;
	}

	function bp_article_not_found()
	{
		qa_set_template('not-found');
		$custom_title = (strlen(qa_opt('bp_blog_title')) > 3) ? ' - ' . qa_opt('bp_blog_title') : '';
		$qa_content = qa_content_prepare();
		$qa_content['title'] = qa_lang_html('bp_lang/article_not_found') . $custom_title;
		$qa_content['error'] = qa_lang_html('bp_lang/article_not_found_page');
		$qa_content['suggest_next'] = bp_html_suggest_ps_tags(qa_using_tags());
		return $qa_content;
	}
}
