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

class qa_reshow
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
		return strpos($request, 'reshow') !== false;
	}
	
	public function process_request( $request )
	{
			
	$qa_content=qa_content_prepare();	
	
	$html = "";
	
	$postid = -1;
	if (isset($_GET['article'])) $postid = $_GET['article'];
	if (isset($postid)) {
		$result = qa_db_query_sub('SELECT * FROM ^blog_posts WHERE `postid` LIKE #', $postid);
		if ($row = mysql_fetch_array($result)) {
		if($row['userid']>= qa_get_logged_in_userid())
		{
			qa_db_query_sub('UPDATE ^blog_posts SET format=$ WHERE postid=#','markdown',$postid);
			header('location:'.qa_path_to_root().'/blog/'.$postid.'/'.seoUrl($row['title']));		
		
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