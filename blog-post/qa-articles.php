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
	Plugin Update Check URI: https://github.com/JackSiro/Q2A-Blog-Post-Plugin/master/qa-plugin.php

*/

require_once QA_INCLUDE_DIR.'app/format.php';
require_once QA_INCLUDE_DIR.'app/limits.php';
require_once QA_INCLUDE_DIR.'db/selects.php';
require_once QA_INCLUDE_DIR.'util/sort.php';


class qa_articles
{
	private $directory;
	private $urltoroot;


	public function load_module($directory, $urltoroot)
	{
		$this->directory = $directory;
		$this->urltoroot = $urltoroot;
	}
	
	public function suggest_requests() // for display in admin interface
	{
		return array(
			array(
				'title' => 'New Article',
				'request' => 'articles',
				'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
			),
		);
	}

	public function match_request( $request )
	{
		return $request == 'articles';
	}
	
		
	/*
		MAIN function: display the chat room, or run an AJAX request
	*/
	public function process_request( $request )
	{
						
		$qa_content=qa_content_prepare();
		$qa_content['body_header']=markdownSupport();
		$errors = array();
		if (qa_clicked('docancel'))	qa_redirect('blog');
		else if (qa_clicked('dosaveoptions')) {
				
				$in=array();
			qa_get_post_content('editor', 'content', $in['editor'], $in['content'], $in['format'], $in['text']);
			$in['title']=qa_post_text('title');
						$in['category']=qa_post_text('category');
			if (strlen($in['title']) < 10 || strlen($in['content']) < 50 ||
			($in['category'] !== 'cat_0' &&	$in['category'] !== 'cat_1' && $in['category'] !== 'cat_2' &&	$in['category'] !== 'cat_3' && $in['category'] !== 'cat_4' && $in['category'] !== 'cat_5')) 
			
					
			{
				if (strlen($in['title']) < 10) $errors['title'] = qa_lang('qa_blog_lang/error_title');
				if (strlen($in['content']) < 50) $errors['content'] = qa_lang('qa_blog_lang/error_content');
				if ($in['category'] !== 'cat_0' && $in['category'] !== 'cat_1' && $in['category'] !== 'cat_2' &&	$in['category'] !== 'cat_3' && $in['category'] !== 'cat_4' && $in['category'] !== 'cat_5')	$errors['type'] = 'Invalid category';
			} 
			else {
				$type = 0;
				if ($in['category'] === 'cat_1') $type = 1;
				else if ($in['category'] === 'cat_2') $type = 2;
				else if ($in['category'] === 'cat_3') $type = 3;
				else if ($in['category'] === 'cat_4') $type = 4;
				else if ($in['category'] === 'cat_5') $type = 5;

				qa_db_query_sub('INSERT INTO ^blog_posts (postid, userid, posted, title, type, content, views,format) 
				VALUES (0,#,NOW(),$,#,$,0,$)',qa_get_logged_in_userid(),$in['title'],$type,$in['content'],'markdown');
				header('location:'.qa_path_to_root().'/user/'.qa_get_logged_in_handle().'/articles');
			}
			}
		else if (qa_clicked('doarticle')) { 
			
			$in=array();
			qa_get_post_content('editor', 'content', $in['editor'], $in['content'], $in['format'], $in['text']);
			$in['title']=qa_post_text('title');
			$in['category']=qa_post_text('category');
			if (strlen($in['title']) < 10 || strlen($in['content']) < 50 ||
			($in['category'] !== 'cat_0' &&	$in['category'] !== 'cat_1' && $in['category'] !== 'cat_2' &&	$in['category'] !== 'cat_3' && $in['category'] !== 'cat_4' && $in['category'] !== 'cat_5')) 
			
					
			{
				if (strlen($in['title']) < 10) $errors['title'] = qa_lang('qa_blog_lang/error_title');
				if (strlen($in['content']) < 50) $errors['content'] = qa_lang('qa_blog_lang/error_content');
				if ($in['category'] !== 'cat_0' && $in['category'] !== 'cat_1' && $in['category'] !== 'cat_2' &&	$in['category'] !== 'cat_3' && $in['category'] !== 'cat_4' && $in['category'] !== 'cat_5')	$errors['type'] = 'Invalid category';
			} 
			else {
				$type = 0;
				if ($in['category'] === 'cat_1') $type = 1;
				else if ($in['category'] === 'cat_2') $type = 2;
				else if ($in['category'] === 'cat_3') $type = 3;
				else if ($in['category'] === 'cat_4') $type = 4;
				else if ($in['category'] === 'cat_5') $type = 5;
				
			$result = qa_db_query_sub('INSERT INTO ^blog_posts (postid, userid, posted, title, type, content, views,format) 
				VALUES (0,#,NOW(),$,#,$,0,$)',qa_get_logged_in_userid(),$in['title'],$type,$in['content'],'markdown');
			 header('location:'.qa_path_to_root().'/blog/'.$article['postid']);
			
			}
		
		}
	
	if(qa_is_logged_in())
		{
			$qa_content['title'] = qa_lang('qa_blog_lang/articles_page');
			
			$editorname=isset($in['editor']) ? $in['editor'] : qa_opt('qa_blog_editor');
			$editor=qa_load_editor(@$in['content'], @$in['format'], $editorname);
			
			$field=qa_editor_load_field($editor, $qa_content, @$in['content'], @$in['format'], 'content', 12, false);
			$field['label']='';
			$field['error']=qa_html(@$errors['content']);
	
			$qa_content['custom']= qa_lang('qa_blog_lang/default_blog_tagline');
			
			$cat_1 = qa_opt('qa_blog_cat_1');
			$cat_2 = qa_opt('qa_blog_cat_2');  
			$cat_3 = qa_opt('qa_blog_cat_3');   
			$cat_4 = qa_opt('qa_blog_cat_4');  
			$cat_5 = qa_opt('qa_blog_cat_5');
			/*
			if (!qa_opt('qa_blog_cat_2')) $cat_2 = '';  
			if (!qa_opt('qa_blog_cat_3')) $cat_3 = '';   
			if (!qa_opt('qa_blog_cat_4')) $cat_4 = '';  
			if (!qa_opt('qa_blog_cat_5')) $cat_5 = '';
			*/
			$typeoptions = array($cat_1, $cat_2,  $cat_3,   $cat_4,  $cat_5);
					
			$qa_content['form']=array(
				'tags' => 'name="blog" method="post" action="'.qa_self_html().'"',
								
				'style' => 'tall',
								
				'fields' => array(
										
					'title' => array(
						'label' => qa_lang('qa_blog_lang/post_title'),
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
				'post' => array(
				'tags' => 'name="doarticle" onclick="qa_show_waiting_after(this, false); '.
					(method_exists($editor, 'update_script') ? $editor->update_script('content') : '').'"',
				'label' => qa_lang('qa_blog_lang/post_button'),
				),
					
				'save' => array(
				'tags' => 'name="dosaveoptions" onclick="qa_show_waiting_after(this, false); '.
					(method_exists($editor, 'update_script') ? $editor->update_script('content') : '').'"',
				'label' => qa_lang('qa_blog_lang/draft_button'),
				),
				
				'cancel' => array(
						'tags' => 'name="docancel"',
						'label' => qa_lang('qa_blog_lang/cancel_button'),
					),
				),
				
				'hidden' => array(
					'editor' => qa_html($editorname),
					'code' => qa_get_form_security_code('article'),
					'doarticle' => '1',
					),
	
			);
			
			if (qa_opt('qa_blog_cat_1')) {
				$field=array(
					'label' => qa_lang('qa_blog_lang/post_cat'),
						'type' => 'select',
						'tags' => 'name="category"',
						'options' => $typeoptions,
						'error' => qa_html(@$errors['type']),
				);
				
				qa_array_insert($qa_content['form']['fields'], 'content', array('category' => $field));
				}
			//else $field['options']['']='';
			
			$html = "<h2>".qa_lang('qa_blog_lang/past_post')."</h2>";	
			$userid = qa_get_logged_in_userid();
			$result = qa_db_query_sub("SELECT * FROM ^blog_posts WHERE userid =  '$userid' ORDER BY posted DESC");
					
			$i=0;
			while ($blob = mysql_fetch_array($result)) {
			$i++;
			$html .= '<ul><li><h3><a href="blog/'.$blob['postid'].'/'.seoUrl($blob['title']).'">'.$blob['title'].'</a><h3></li></ul>';
			}
			if ($i==0) $html .= qa_lang('qa_blog_lang/post_null');
			$html .='';
					
			$qa_content['custom2'] = $html;

		}
	else
	{
		$qa_content['title']= qa_lang('qa_blog_lang/title_error');
		$qa_content['error'] = qa_insert_login_links( qa_lang('qa_blog_lang/access_error'),$request );
	}			
		return $qa_content;

	}
	
	

}
