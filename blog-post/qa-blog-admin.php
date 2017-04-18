<?php
/*
	Plugin Name: Blog Post
	Plugin URI: https://github.com/JackSiro/Q2A-Blog-Post-Plugin
	Plugin Description: The Blog module allows registered users to maintain an online journal, or blog. The blog entries are displayed by creation time in descending order.
	Plugin Version: 3.0
	Plugin Date: 2014-04-01
	Plugin Author: Jackson Siro
	Plugin Author URI: https://github.com/JackSiro
	Plugin License: GPLv3
	Plugin Minimum Question2Answer Version: 1.7
	Plugin Update Check URI: 

*/

	require_once QA_INCLUDE_DIR.'db/admin.php';
	require_once QA_INCLUDE_DIR.'db/maxima.php';
	require_once QA_INCLUDE_DIR.'db/selects.php';
	require_once QA_INCLUDE_DIR.'app/options.php';
	require_once QA_INCLUDE_DIR.'app/admin.php';

class qa_html_theme_layer extends qa_html_theme_base {
	
	var $plugin_directory;
	var $plugin_url;
	function qa_html_theme_layer($template, $content, $rooturl, $request)
	{
		global $qa_layers;
		$this->plugin_directory = $qa_layers['Blog Settings']['directory'];
		$this->plugin_url = $qa_layers['Blog Settings']['urltoroot'];
		qa_html_theme_base::qa_html_theme_base($template, $content, $rooturl, $request);
	}
	
	function nav_list($navigation, $class, $level=null)
	{
		if($this->template=='admin') {
			if ($class == 'nav-sub') {
				$navigation['blog'] = array('label' => 'Blog Settings','url' => qa_path_html('admin/blog'));
				$navigation['blogm'] = array('label' => 'Blog Moderate','url' => qa_path_html('admin/blogm'));
				$navigation['blogw'] = array('label' => 'Blog Widget','url' => qa_path_html('admin/blogw'));
				$navigation['blogs'] = array('label' => 'Blog Stats','url' => qa_path_html('admin/blogs'));
				}
			if($this->request == 'admin/blog') {
				$newnav = qa_admin_sub_navigation();
				$navigation = array_merge($newnav, $navigation);
				$navigation['admin']['blog'] = true; }
			else if($this->request == 'admin/blogm') {
				$newnav = qa_admin_sub_navigation();
				$navigation = array_merge($newnav, $navigation);
				$navigation['admin']['blogm'] = true; }
			else if($this->request == 'admin/blogw') {
				$newnav = qa_admin_sub_navigation();
				$navigation = array_merge($newnav, $navigation);
				$navigation['admin']['blogw'] = true;	}
			else if($this->request == 'admin/blogs') {
				$newnav = qa_admin_sub_navigation();
				$navigation = array_merge($newnav, $navigation);
				$navigation['admin']['blogs'] = true;	}
		}
		
		if(count($navigation) > 1 ) qa_html_theme_base::nav_list($navigation, $class, $level=null);	
	}
	
	function doctype(){
		global $qa_request;
		if ( ($qa_request == 'admin/blog') and (qa_get_logged_in_level()>=QA_USER_LEVEL_ADMIN) ) {
			$this->template="blog";
			$this->content['navigation']['sub'] = qa_admin_sub_navigation();
			$this->content['navigation']['sub']['blog'] = array('label' => 'Blog Settings',  'url' => qa_path_html('admin/blog'),  'selected' => 'selected');
			$this->content['navigation']['sub']['blogm'] = array('label' => 'Blog Moderate',  'url' => qa_path_html('admin/blogm'));
			$this->content['navigation']['sub']['blogs'] = array('label' => 'Blog Stats',  'url' => qa_path_html('admin/blogs'));
			$this->content['navigation']['sub']['blogw'] = array('label' => 'Blog Widget',  'url' => qa_path_html('admin/blogw'));
			
			$this->content['site_title']="Administrator!";
			$this->content['error']="";
			$this->content['suggest_next']="";
			$this->content['title']="Blog Post Settings";
					
			$saved=false;		
			
			$editors= array(
				'Basic Editor',
				'WYSIWYG Editor',
				'Markdown Editor',
				'CKEditor4');
			$permissions= array(
				'Administrators',
				'Administrators and Moderators',
				'Users whose emails is confirmed',
				'Registered Users');
			if (qa_clicked('qa_blog_save')) {
				qa_opt('qa_blog_title', qa_post_text('qa_blog_title'));
				qa_opt('qa_blog_tagline', qa_post_text('qa_blog_tagline'));
				qa_opt('qa_tagline_scrol', (bool)qa_post_text('qa_tagline_scrol'));
				qa_opt('qa_blog_cat_1', qa_post_text('qa_blog_cat_1'));
				qa_opt('qa_blog_cat_2', qa_post_text('qa_blog_cat_2'));
				qa_opt('qa_blog_cat_3', qa_post_text('qa_blog_cat_3'));
				qa_opt('qa_blog_cat_4', qa_post_text('qa_blog_cat_4'));
				qa_opt('qa_blog_cat_5', qa_post_text('qa_blog_cat_5'));
				qa_opt('qa_blog_editor', @$editors[(int)qa_post_text('qa_blog_editor_field')]);
				qa_opt('qa_blog_editor_index', (int)qa_post_text('qa_blog_editor_field'));				      
				qa_opt('qa_blog_spam_post', @$permissions[(int)qa_post_text('qa_blog_spam_post_field')]);
				qa_opt('qa_blog_spam_post_index', (int)qa_post_text('qa_blog_spam_post_field'));
				qa_opt('qa_blog_spam_view', @$permissions[(int)qa_post_text('qa_blog_spam_view_field')]);
				qa_opt('qa_blog_spam_view_index', (int)qa_post_text('qa_blog_spam_view_field'));
				qa_opt('qa_blog_spam_edit', @$permissions[(int)qa_post_text('qa_blog_spam_edit_field')]);
				qa_opt('qa_blog_spam_edit_index', (int)qa_post_text('qa_blog_spam_edit_field'));
				qa_opt('qa_blog_rules', qa_post_text('qa_blog_rules'));	
				qa_opt('qa_blog_content_max', (int)qa_post_text('qa_blog_content_max_field'));
				qa_opt('qa_blog_nof_articles', (int)qa_post_text('qa_blog_nof_articles_field'));
				qa_opt('qa_blog_home_avatar_show',  (bool)qa_post_text('qa_blog_home_avatar_show_field'));
				qa_opt('qa_blog_home_author_info_show',  (bool)qa_post_text('qa_blog_home_author_info_show_field'));
				qa_opt('qa_blog_home_avatar_size', (int)qa_post_text('qa_blog_home_avatar_size_field'));
				qa_opt('qa_blog_article_avatar_size', (int)qa_post_text('qa_blog_article_avatar_size_field'));
				qa_opt('qa_blog_comment_avatar_size', (int)qa_post_text('qa_blog_comment_avatar_size_field'));
				qa_opt('qa_blog_nof_comments', (int)qa_post_text('qa_blog_nof_comments_field'));
				$saved=true;			
			
			}
			
			$options= array(
				'ok' => $saved ? 'Blog settings saved' : null,
				'tags' => 'METHOD="POST" ACTION="'.qa_path_html(qa_request()).'"',
				'style' => 'wide', 
				'fields' => array(
				array(
				'type' => 'input',
				'label' => qa_lang('qa_blog_lang/blog_title'),
				'value' => qa_opt('qa_blog_title'),
				'tags' => 'name="qa_blog_title" id="qa_blog_title"',				
			),
				array(
				'type' => 'textarea',
				'label' => qa_lang('qa_blog_lang/blog_tagline'),
				'value' => qa_opt('qa_blog_tagline'),
				'rows' => 4,
				'tags' => 'name="qa_blog_tagline" id="qa_blog_tagline"',				
			),
				array(
				'type' => 'checkbox',
				'suffix' => qa_lang('qa_blog_lang/suffix3'),
				'label' => qa_lang('qa_blog_lang/tagline_scrol'),
				'value' => qa_opt('qa_tagline_scrol'),
				'tags' => 'name="qa_tagline_scrol" id="qa_tagline_scrol"',				
			),
				array(
				'type' => 'input',
				'label' => qa_lang('qa_blog_lang/cat_1'),
				'value' => qa_opt('qa_blog_cat_1'),
				'tags' => 'name="qa_blog_cat_1" id="qa_blog_cat_1"',
			),
				array(
				'type' => 'input',
				'label' => qa_lang('qa_blog_lang/cat_2'),
				'value' => qa_opt('qa_blog_cat_2'),
				'tags' => 'name="qa_blog_cat_2" id="qa_blog_cat_2"',
			),
				array(
				'type' => 'input',
				'label' => qa_lang('qa_blog_lang/cat_3'),
				'value' => qa_opt('qa_blog_cat_3'),
				'tags' => 'name="qa_blog_cat_3" id="qa_blog_cat_3"',
			),
				array(
				'type' => 'input',
				'label' => qa_lang('qa_blog_lang/cat_4'),
				'value' => qa_opt('qa_blog_cat_4'),
				'tags' => 'name="qa_blog_cat_4" id="qa_blog_cat_4"',
			),
				array(
				'type' => 'input',
				'label' => qa_lang('qa_blog_lang/cat_5'),
				'value' => qa_opt('qa_blog_cat_5'),
				'tags' => 'name="qa_blog_cat_5" id="qa_blog_cat_5"',
			),
				array(
				'label' => qa_lang('qa_blog_lang/blog_editor'),
				'tags' => 'name="qa_blog_editor_field" id="qa_blog_editor"',
				'type' => 'select',
				'options' => @$editors,
				'value' => @$editors[qa_opt('qa_blog_editor_index')],
			),
				array(
				'type' => 'textarea',
				'label' => qa_lang('qa_blog_lang/blog_rules'),
				'value' => qa_opt('qa_blog_rules'),
				'tags' => 'name="qa_blog_rules" id="qa_blog_rules"',
				'rows' => 4,
			),
				array(
				'label' => qa_lang('qa_blog_lang/content_max'),
				'suffix' => qa_lang('qa_blog_lang/suffix'),
				'type' => 'number',
				'value' => (int)qa_opt('qa_blog_content_max'),
				'tags' => 'name="qa_blog_content_max_field" id="qa_blog_content_max"',
				),
			array(
				'label' => qa_lang('qa_blog_lang/blog_spam_post'),
				'tags' => 'name="qa_blog_spam_post_field" id="qa_blog_spam_post"',
				'type' => 'select',
				'options' => @$permissions,
				'value' => @$permissions[qa_opt('qa_blog_spam_post_index')],
			),
			array(
				'label' => qa_lang('qa_blog_lang/blog_spam_view'),
				'tags' => 'name="qa_blog_spam_view_field" id="qa_blog_spam_view"',
				'type' => 'select',
				'options' => @$permissions,
				'value' => @$permissions[qa_opt('qa_blog_spam_view_index')],
			), 
			array(
				'label' => qa_lang('qa_blog_lang/blog_spam_edit'),
				'tags' => 'name="qa_blog_spam_edit_field" id="qa_blog_spam_edit"',
				'type' => 'select',
				'options' => @$permissions,
				'value' => @$permissions[qa_opt('qa_blog_spam_edit_index')],
			),
			array(
				'label' => qa_lang('qa_blog_lang/home_avatar'),
				'type' => 'checkbox',
				'value' => qa_opt('qa_blog_home_avatar_show'),
				'tags' => 'name="qa_blog_home_avatar_show_field" id="qa_blog_home_avatar_show"',
				),
			array(
				'label' => qa_lang('qa_blog_lang/home_avatar_size'),
				'suffix' => qa_lang('qa_blog_lang/home_avatar_suffix'),
				'type' => 'number',
				'value' => (int)qa_opt('qa_blog_home_avatar_size'),
				'tags' => 'name="qa_blog_home_avatar_size_field" id="qa_blog_home_avatar_size"',
				),				
			array(
				'label' => qa_lang('qa_blog_lang/nof_articles'),
				'type' => 'number',
				'value' => (int)qa_opt('qa_blog_nof_articles'),
				'tags' => 'name="qa_blog_nof_articles_field" id="qa_blog_nof_articles"',
				),
			array(
				'label' => qa_lang('qa_blog_lang/author_info_show'),
				'type' => 'checkbox',
				'value' => qa_opt('qa_blog_home_author_info_show'),
				'tags' => 'name="qa_blog_home_author_info_show_field" id="qa_blog_home_author_info_show"',
				),
			array(
				'label' => qa_lang('qa_blog_lang/article_avatar'),
				'suffix' => qa_lang('qa_blog_lang/article_avatar_suffix'),
				'type' => 'number',
				'value' => (int)qa_opt('qa_blog_article_avatar_size'),
				'tags' => 'name="qa_blog_article_avatar_size_field" id="qa_blog_article_avatar_size"',
				),
			array(
				'label' => qa_lang('qa_blog_lang/nof_comments'),
				'type' => 'number',
				'value' => (int)qa_opt('qa_blog_nof_comments'),
				'tags' => 'name="qa_blog_nof_comments_field" id="qa_blog_nof_comments"',
				),
			array(
				'label' => qa_lang('qa_blog_lang/comment_avatar'),
				'suffix' => qa_lang('qa_blog_lang/comment_avatar_suffix'),
				'type' => 'number',
				'value' => (int)qa_opt('qa_blog_comment_avatar_size'),
				'tags' => 'name="qa_blog_comment_avatar_size_field" id="qa_blog_comment_avatar_size"',
				),
			),
				
				'buttons' => array(
					array(
						'label' => 'Save Changes',
						'tags' => 'name="qa_blog_save"',
					),
					
					array(
						'label' => 'Reset to Defaults',
						'tags' =>   'name="qa_blog_reset"',
					),
					
				),
			);
			$this->content['form']=$options;
			$this->content['custom']= '<p>If you think this plugin is great and helps you on your site please donate some $5 to $30 to my paypal account: <a href="mailto:smataweb@gmail.com">smataweb@gmail.com</a></p>';
		}
		
		else if ( ($qa_request == 'admin/blogm') and (qa_get_logged_in_level()>=QA_USER_LEVEL_ADMIN) ) {
			$this->template="blogm";
			$this->content['navigation']['sub'] = qa_admin_sub_navigation();
			$this->content['navigation']['sub']['blog'] = array('label' => 'Blog Settings',  'url' => qa_path_html('admin/blog'));
			$this->content['navigation']['sub']['blogm'] = array('label' => 'Blog Moderate',  'url' => qa_path_html('admin/blogm'),  'selected' => 'selected');
			$this->content['navigation']['sub']['blogs'] = array('label' => 'Blog Stats',  'url' => qa_path_html('admin/blogs'));
			$this->content['navigation']['sub']['blogw'] = array('label' => 'Blog Widget',  'url' => qa_path_html('admin/blogw'));
			
			$this->content['site_title']="Administrator!";
			$this->content['error']="";
			$this->content['suggest_next']="";
			$this->content['title']="Blog Post Moderate";
			$html = 'Sorry '.qa_get_logged_in_handle().', this is a premium feature! You can upgrade/purchase the premium blog post plugin to enjoy this service.
					<br>'; 
			
			
			$this->content['error']= $html;
			$this->content['custom'] = '<form action="http://siro.me.ke/store"><center>
									<input class="qa-form-tall-button qa-form-tall-button-save" type="submit" 
									value="Upgrade your Blog Post NOW! >>">
									</center></form><br>';
			
		}
				
		else if ( ($qa_request == 'admin/blogs') and (qa_get_logged_in_level()>=QA_USER_LEVEL_ADMIN) ) {
			$this->template="blogs";
			$this->content['navigation']['sub'] = qa_admin_sub_navigation();
			$this->content['navigation']['sub']['blog'] = array('label' => 'Blog Settings',  'url' => qa_path_html('admin/blog'));
			$this->content['navigation']['sub']['blogm'] = array('label' => 'Blog Moderate',  'url' => qa_path_html('admin/blogm'));
			$this->content['navigation']['sub']['blogs'] = array('label' => 'Blog Stats',  'url' => qa_path_html('admin/blogs'),  'selected' => 'selected');
			$this->content['navigation']['sub']['blogw'] = array('label' => 'Blog Widget',  'url' => qa_path_html('admin/blogw'));
			
			$this->content['site_title']="Administrator!";
			$this->content['error']="";
			$this->content['suggest_next']="";
			$this->content['title']="Blog Post Statistics";
			$this->content['custom']='';
			
		}
		qa_html_theme_base::doctype();
	}
}
