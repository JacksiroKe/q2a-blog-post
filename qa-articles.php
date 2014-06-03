<?php
/*
	Plugin Name: Blog Post
	Plugin URI: http://tujuane.net/websmata/qtoa/plugins/12-blog-post.html
	Plugin Description: The Blog module allows registered users to maintain an online journal, or blog. The blog entries are displayed by creation time in descending order.
	Plugin Version: 2.5
	Plugin Date: 2014-04-01
	Plugin Author: Jackson Silla
	Plugin Author URI: http://question2answer.org/qa/user/jaxila
	Plugin License: GPLv3
	Plugin Minimum Question2Answer Version: 1.5
	Plugin Update Check URI: http://tujuane.net/websmata/qtoa/plugins/12-blog-post.html


*/
require_once QA_INCLUDE_DIR.'qa-app-format.php';
require_once QA_INCLUDE_DIR.'qa-app-limits.php';
require_once QA_INCLUDE_DIR.'qa-db-selects.php';
require_once QA_INCLUDE_DIR.'qa-util-sort.php';


class qa_articles
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
		return $request == 'articles';
	}
	
	function init_queries( $tableslc )
	{
		$tbl1 = qa_db_add_table_prefix('blog_posts');
		$tbl2 = qa_db_add_table_prefix('blog_comments');

		if ( in_array($tbl1, $tableslc) && in_array($tbl2, $tableslc) )
		{
			return null;
		}
		
		return array(
			'CREATE TABLE IF NOT EXISTS ^blog_posts (
			  `postid` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `userid` int(10) unsigned NOT NULL,
			  `title` varchar(300),
			  `type` int(10) unsigned,
			  `content` varchar(60000),
			  `posted` datetime,
			  `views` int(10) unsigned,
			  `updated` datetime,
			  `tags` varchar(300) NOT NULL,
			  `notify` varchar(300) NOT NULL,
			  `format` varchar(100) NOT NULL,
			  PRIMARY KEY (`postid`),
			  KEY `posted` (`posted`),
			  KEY `updated` (`updated`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8',
			
			'CREATE TABLE IF NOT EXISTS ^blog_comments (
			  `postid` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `parentid` int(10) unsigned NOT NULL,
			  `posted` datetime NOT NULL,
			  `comment` varchar(60000) NOT NULL,
			  `userid` int(10) unsigned NOT NULL,
			  `updated` datetime,
			  `format` varchar(100) NOT NULL,
			  PRIMARY KEY (`postid`),
			  KEY `parentid` (`parentid`),
			  KEY `posted` (`posted`),
			  KEY `updated` (`updated`)
			 ) ENGINE=InnoDB  DEFAULT CHARSET=utf8',
		);

	}

	
	/*
		MAIN function: display the chat room, or run an AJAX request
	*/
	public function process_request( $request )
	{
			$category_1 = qa_opt('qa_blog_cat_1');
			$category_2 = qa_opt('qa_blog_cat_2');
			$category_3 = qa_opt('qa_blog_cat_3');
			$category_4 = qa_opt('qa_blog_cat_4');
			$category_5 = qa_opt('qa_blog_cat_5');			
		$qa_content=qa_content_prepare();
		
		$errors = array();
		
		if (qa_clicked('doarticle')) { 
			
			$in=array();
			qa_get_post_content('editor', 'content', $in['editor'], $in['content'], $in['format'], $in['text']);
			$in['title']=qa_post_text('title');
						$in['category']=qa_post_text('category');
			if (strlen($in['title']) < 10 || strlen($in['content']) < 50 ||
					($in['category'] !== 'cat_0' && 
					$in['category'] !== 'cat_1' && 
					$in['category'] !== 'cat_2' &&
					$in['category'] !== 'cat_3' )) 
					
			{
				if (strlen($in['title']) < 10) $errors['title'] = qa_lang('qa_blog_lang/error_title');
				if (strlen($in['content']) < 50) $errors['content'] = qa_lang('qa_blog_lang/error_content');
				if ($in['category'] !== 'cat_0' && 
					$in['category'] !== 'cat_1' && 
					$in['category'] !== 'cat_2' &&
					$in['category'] !== 'cat_3')
					$errors['type'] = 'Invalid category';
			} 
			else {
				$type = 0;
				if ($in['category'] === 'cat_1') $type = 1;
				else if ($in['category'] === 'cat_2') $type = 2;
				else if ($in['category'] === 'cat_3') $type = 3;

				$result = qa_db_query_sub('INSERT INTO ^blog_posts (postid, userid, posted, title, type, content, views,format) 
									VALUES (0,#,NOW(),$,#,$,0,$)',qa_get_logged_in_userid(),$in['title'],$type,$in['content'],'markdown');
			header('location:'.qa_path_to_root().'/user/'.qa_get_logged_in_handle().'/articles');
			}
		
		}
	
	if(qa_is_logged_in())
		{
			$qa_content['title'] = qa_lang('qa_blog_lang/articles_page');
			
			$userpostslink = '/user/'.qa_get_logged_in_handle().'/articles';
			
			$editorname=isset($in['editor']) ? $in['editor'] : qa_opt('editor_for_qs');
			$editor=qa_load_editor(@$in['content'], @$in['format'], $editorname);
			
			$field=qa_editor_load_field($editor, $qa_content, @$in['content'], @$in['format'], 'content', 12, false);
			$field['label']='';
			$field['error']=qa_html(@$errors['content']);
	
			$qa_content['custom']= qa_lang('qa_blog_lang/default_blog_tagline');

			$typeoptions = array('cat_1' => $category_1,
								 'cat_2' => $category_2,
							     'cat_3' => $category_3);
					
		
			$qa_content['form']=array(
				'tags' => 'name="ask" method="post" action="'.qa_self_html().'"',
								
				'style' => 'tall',
								
				'fields' => array(
										
					'title' => array(
						'label' => qa_lang('qa_blog_lang/post_title'),
						'tags' => 'name="title" id="title" autocomplete="off"',
						'value' => qa_html(@$in['title']),
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
			
					'content' => $field,
					
				),
				
				'buttons' => array(
					'ask' => array(
						'tags' => 'onclick="qa_show_waiting_after(this, false); '.
							(method_exists($editor, 'update_script') ? $editor->update_script('content') : '').'"',
						'label' => qa_lang('qa_blog_lang/post_button'),
					),
				),
				
				'hidden' => array(
					'editor' => qa_html($editorname),
					'code' => qa_get_form_security_code('article'),
					'doarticle' => '1',
					),
	
			);
			
			$html = "<h2>".qa_lang('qa_blog_lang/past_post')."</h2>";	
			$userid = qa_get_logged_in_userid();
			$result = qa_db_query_sub("SELECT * FROM ^blog_posts WHERE userid =  '$userid' ORDER BY posted DESC");
					
			$i=0;
			while ($blob = mysql_fetch_array($result)) {
			$i++;
			$html .= '<ul><li><h3><a href="blog/'.$blob['postid'].'/'.seoUrl3($blob['title']).'">'.$blob['title'].'</a><h3></li></ul>';
			}
			if ($i==0) $html .= qa_lang('qa_blog_lang/post_null');
			$html .='';
					
			$qa_content['custom2'] = $html;

		}
	else
	{
		$qa_content['error'] = qa_insert_login_links( qa_lang('qa_blog_lang/access_error'),$request );
	}			
		return $qa_content;

	}
	
	

}

function seoUrl3($string) {
	    //Unwanted:  {UPPERCASE} ; / ? : @ & = + $ , . ! ~ * ' ( )
	    $string = strtolower($string);
	    //Strip any unwanted characters
	    $string = preg_replace("/[^a-z0-9_\s-]/", "", $string);
	    //Clean multiple dashes or whitespaces
	    $string = preg_replace("/[\s-]+/", " ", $string);
	    //Convert whitespaces and underscore to dash
	    $string = preg_replace("/[\s_]/", "-", $string);
	    return $string;
}
