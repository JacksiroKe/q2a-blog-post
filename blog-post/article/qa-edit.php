<?php
/*
	Plugin Name: Blog Post
	Plugin URI: http://tujuane.net/websmata/qtoa/plugins/12-blog-post.html
	Plugin Description: The Blog module allows registered users to maintain an online journal, or blog. The blog entries are displayed by creation time in descending order.
	Plugin Version: 3.0
	Plugin Date: 2014-04-01
	Plugin Author: Jackson Silla
	Plugin Author URI: http://question2answer.org/qa/user/jaxila
	Plugin License: GPLv3
	Plugin Minimum Question2Answer Version: 1.5
	Plugin Update Check URI: http://tujuane.net/websmata/qtoa/plugins/12-blog-post.html


*/

require_once QA_INCLUDE_DIR.'db/users.php';
require_once QA_INCLUDE_DIR.'app/format.php';
require_once QA_INCLUDE_DIR.'qa-base.php';
require_once QA_INCLUDE_DIR.'app/users.php';
require_once QA_PLUGIN_DIR.'blog-post/qa-index.php';

class qa_edit
{
	private $directory;
	private $urltoroot;


	public function load_module($directory, $urltoroot)
	{
		$this->directory = $directory;
		$this->urltoroot = $urltoroot;
	}

	public function match_request( $request )
	{
		return strpos($request, 'edit') !== false;
	}
	
	public function process_request( $request )
	{
			
	$qa_content=qa_content_prepare();	
	$qa_content['body_header']=markdownSupport();
	$html = "";
	
	$postid = -1;
	if (isset($_GET['article'])) $postid = $_GET['article'];
	if (isset($postid)) {
		$result = qa_db_query_sub('SELECT * FROM ^blog_posts WHERE `postid` LIKE #', $postid);
		if ($row = mysql_fetch_array($result)) {
		if($row['userid']>= qa_get_logged_in_userid())
		{
		
		if (qa_clicked('doedit'))
			
			{ 		
				$in=array();
				qa_get_post_content('editor', 'content', $in['editor'], $in['content'], $in['format'], $in['text']);
				$in['title']=qa_post_text('title');
				$in['category']=qa_post_text('category');
				if (strlen($in['title']) < 10 || strlen($in['content']) < 50 ||
				($in['category'] !== 'cat_0' && $in['category'] !== 'cat_1' && 	$in['category'] !== 'cat_2' && $in['category'] !== 'cat_3' && $in['category'] !== 'cat_4' && $in['category'] !== 'cat_5')) 
					
				{
				if (strlen($in['title']) < 10) $errors['title'] = qa_lang('qa_blog_lang/error_title');
				if (strlen($in['content']) < 50) $errors['content'] = qa_lang('qa_blog_lang/error_content');
				if ($in['category'] !== 'cat_0' && $in['category'] !== 'cat_1' && 	$in['category'] !== 'cat_2' && $in['category'] !== 'cat_3' && $in['category'] !== 'cat_4' && $in['category'] !== 'cat_5') $errors['type'] = 'Invalid category';
			
				} 
				else {
				$type = 0;
				if ($in['category'] === 'cat_1') $type = 1;
				else if ($in['category'] === 'cat_2') $type = 2;
				else if ($in['category'] === 'cat_3') $type = 3;
				else if ($in['category'] === 'cat_4') $type = 4;
				else if ($in['category'] === 'cat_5') $type = 5;

				$result = qa_db_query_sub('UPDATE ^blog_posts SET updated=NOW(), title=$, content=$, type=#, format=$ WHERE postid=#',
				$in['title'],$in['content'],$type,'markdown',$postid);
				header('location:'.qa_path_to_root().'/blog/'.$postid.'/'.seoUrl($article['title']));
				
			
					}
		
				}
	
		else	if (qa_clicked('doresetoptions')) {
				
			$in=array();
			qa_get_post_content('editor', 'content', $in['editor'], $in['content'], $in['format'], $in['text']);
			$in['title']=qa_post_text('title');
			$in['category']=qa_post_text('category');
			if (strlen($in['title']) < 10 || strlen($in['content']) < 50 ||
			($in['category'] !== 'cat_0' && $in['category'] !== 'cat_1' && 	$in['category'] !== 'cat_2' && $in['category'] !== 'cat_3' && $in['category'] !== 'cat_4' && $in['category'] !== 'cat_5')) 
 
					
			{
				if (strlen($in['title']) < 10) $errors['title'] = qa_lang('qa_blog_lang/error_title');
				if (strlen($in['content']) < 50) $errors['content'] = qa_lang('qa_blog_lang/error_content');
				if ($in['category'] !== 'cat_0' && $in['category'] !== 'cat_1' && 	$in['category'] !== 'cat_2' && $in['category'] !== 'cat_3' && $in['category'] !== 'cat_4' && $in['category'] !== 'cat_5') $errors['type'] = 'Invalid category';
			
			} 
			else {
				$type = 0;
				if ($in['category'] === 'cat_1') $type = 1;
				else if ($in['category'] === 'cat_2') $type = 2;
				else if ($in['category'] === 'cat_3') $type = 3;
				else if ($in['category'] === 'cat_4') $type = 4;
				else if ($in['category'] === 'cat_5') $type = 5;

				$result = qa_db_query_sub('UPDATE ^blog_posts SET updated=NOW(), title=$, content=$, type=#, format=$ WHERE postid=#',
				$in['title'],$in['content'],$type,'draft',$postid);
				header('location:'.qa_path_to_root().'/blog/'.$postid.'/'.seoUrl($article['title']));
			}
			} 
			else if (qa_clicked('dogoback'))	qa_redirect('blog');
			
			else if (qa_clicked('dosaveoptions')) {				
			$in=array();
			qa_get_post_content('editor', 'content', $in['editor'], $in['content'], $in['format'], $in['text']);
			$in['title']=qa_post_text('title');
			$in['category']=qa_post_text('category');
			if (strlen($in['title']) < 10 || strlen($in['content']) < 50 ||
			($in['category'] !== 'cat_0' && $in['category'] !== 'cat_1' && 	$in['category'] !== 'cat_2' && $in['category'] !== 'cat_3' && $in['category'] !== 'cat_4' && $in['category'] !== 'cat_5')) 
			{
				if (strlen($in['title']) < 10) $errors['title'] = qa_lang('qa_blog_lang/error_title');
				if (strlen($in['content']) < 50) $errors['content'] = qa_lang('qa_blog_lang/error_content');
				if ($in['category'] !== 'cat_0' && $in['category'] !== 'cat_1' && 	$in['category'] !== 'cat_2' && $in['category'] !== 'cat_3' && $in['category'] !== 'cat_4' && $in['category'] !== 'cat_5') $errors['type'] = 'Invalid category';
			
			} 
			else {
				$type = 0;
				if ($in['category'] === 'cat_1') $type = 1;
				else if ($in['category'] === 'cat_2') $type = 2;
				else if ($in['category'] === 'cat_3') $type = 3;
				else if ($in['category'] === 'cat_4') $type = 4;
				else if ($in['category'] === 'cat_5') $type = 5;

				$result = qa_db_query_sub('UPDATE ^blog_posts SET updated=NOW(), title=$, content=$, type=#, format=$ WHERE postid=#',
				$in['title'],$in['content'],$type,'hidden',$postid);
				header('location:'.qa_path_to_root().'/blog/'.$postid.'/'.seoUrl($row['title']));	
			}
			}
			else 	if (qa_clicked('docancel'))	{
				$result = qa_db_query_sub('DELETE FROM ^blog_posts WHERE postid=#', $postid);
				header('location:'.qa_path_to_root().'/blog/');
					}
						
					$qa_content['title'] = 'Editing: '.$row['title'];
					$editorname=isset($in['editor']) ? $in['editor'] : qa_opt('qa_blog_editor');
					$editor=qa_load_editor(@$in['content'], @$in['format'], $editorname);
					$field=qa_editor_load_field($editor, $qa_content, @$in['content'], @$in['format'], 'content', 12, false);
					$typeoptions = array('cat_1' => qa_opt('qa_blog_cat_1'), 'cat_2' => qa_opt('qa_blog_cat_2'),'cat_3' => qa_opt('qa_blog_cat_3'),'cat_4' => qa_opt('qa_blog_cat_4'),'cat_5' => qa_opt('qa_blog_cat_5'));
		
					$qa_content['form']=array(
						'tags' => 'name="edit" method="post" action="'.qa_self_html().'"',
								
						'style' => 'tall',
								
						'fields' => array(
										
						'title' => array(
						'label' => qa_lang('qa_blog_lang/post_title'),
						'tags' => 'name="title" id="title" autocomplete="off"',
						'value' => $row['title'],
						'error' => qa_html(@$errors['title']),
					),
					
					'category' => array(
						'label' => qa_lang('qa_blog_lang/post_cat'),
						'type' => 'select',
						'tags' => 'name="category"',
						'options' => $typeoptions,
						'error' => qa_html(@$errors['type']),
					),
			
					'similar' => array(
						'type' => 'custom',
						'html' => '<span id="similar"></span>',
					),
			
					'content' => array(
						'value' => $row['content'],
						'tags' => 'name="content"',
						'error'=> qa_html(@$errors['content']),
						'rows' => 8,
						),
					
					),
				
					'buttons' => array(
						'edit' => array(
						'tags' => 'name="doarticle" onclick="qa_show_waiting_after(this, false); '.
						(method_exists($editor, 'update_script') ? $editor->update_script('content') : '').'"',
						'label' => qa_lang('qa_blog_lang/update_button'),
						),
						
						'save' => array(
						'tags' => 'name="dosaveoptions" onclick="qa_show_waiting_after(this, false); '.
						(method_exists($editor, 'update_script') ? $editor->update_script('content') : '').'"',
						'label' => qa_lang('qa_blog_lang/draft_button'),
						),
						
						'hide' => array(
						'tags' => 'onclick="qa_show_waiting_after(this, false); '.
						(method_exists($editor, 'update_script') ? $editor->update_script('content') : '').'"',
						'label' => qa_lang('qa_blog_lang/hide_button')
						),
						
						'goback' => array(
						'tags' => 'name="dogoback"',
						'label' => qa_lang('qa_blog_lang/cancel_button')
						),
						
						'cancel' => array(
						'tags' => 'name="docancel"',
						'label' => qa_lang('qa_blog_lang/delete_button')
						),
					),
							
					'hidden' => array(
					'editor' => qa_html($editorname),
					'code' => qa_get_form_security_code('article'),
					'doedit' => '1',
						),	
					);

					$qa_content['custom2'] = $html;

		}
		else
		{
		$qa_content['title']= qa_lang('qa_blog_lang/title_error');
		$qa_content['error'] =  qa_lang('qa_blog_lang/edit_error').'<a href='.qa_path_to_root().'/blog/>
		'.qa_lang('qa_blog_lang/edit_error1').'</a>';
		$qa_content['custom2'] = qa_lang('qa_blog_lang/edit_note');
			}			
				return $qa_content;

			}
	
		}
	}

}