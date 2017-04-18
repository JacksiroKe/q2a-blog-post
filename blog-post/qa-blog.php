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
require_once QA_INCLUDE_DIR.'app/users.php';
require_once QA_INCLUDE_DIR.'app/blobs.php';
require_once QA_PLUGIN_DIR.'blog-post/qa-index.php';
require_once QA_PLUGIN_DIR.'blog-post/slider/qa-slider.php';

class qa_blog
{
	private $directory;
	private $urltoroot;
	private $user;
	private $dates;

	private $optactive = 'blog_active';
	private $optkick = 'blog_kick_level';
	private $optcss = 'blog_hide_css';

	// TODO: get the proper language text, this is all a quick fix at the moment
	private $userlevels = array(
		'editor' => QA_USER_LEVEL_EDITOR,
		'mod'    => QA_USER_LEVEL_MODERATOR,
		'admin'  => QA_USER_LEVEL_ADMIN,
	);
	private $userlevels_text = array(
		'editor' => 'Editor',
		'mod'    => 'Moderator',
		'admin'  => 'Administrator',
	);

	public function load_module($directory, $urltoroot)
	{
		$this->directory = $directory;
		$this->urltoroot = $urltoroot;
	}

	public function suggest_requests() // for display in admin interface
	{
		return array(
			array(
				'title' => 'Blog',
				'request' => 'blog',
				'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
			),
		);
	}

	public function match_request( $request )
	{
		return strpos($request, 'blog') !== false;
	}
		
	function init_queries( $tableslc )
	{
		$tbl1 = qa_db_add_table_prefix('blog_posts');
		$tbl2 = qa_db_add_table_prefix('blog_comments');
		$tbl3 = qa_db_add_table_prefix('blog_users');

		if ( in_array($tbl1, $tableslc) && in_array($tbl2, $tableslc) && in_array($tbl3, $tableslc))
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
			 
			 'CREATE TABLE IF NOT EXISTS ^blog_users (
			  `userid` int(10) unsigned NOT NULL,
			  `lastposted` datetime NOT NULL,
			  `lastpolled` datetime NOT NULL,
			  `kickeduntil` datetime NOT NULL DEFAULT "2012-01-01 00:00:00",
			  PRIMARY KEY (`userid`),
			  KEY `active` (`lastpolled`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8',
		);
	}
	
	public function process_request( $request )
	{
		$qa_content=qa_content_prepare();
		$qa_content['css_src'][]=$this->urltoroot.'css/style.css';
		$qa_content['script_src'][] = '<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>';
		$qa_content['script_src'][] = $this->urltoroot.'js/qa-blog.js';
		$qa_content['script_src'][] = $this->urltoroot.'js/qa-comment.js';
		$cat = -1;
		if (isset($_GET['category'])) $cat = $_GET['category'];
		$qa_content['navigation']['sub']['all'] = array(	'label' => qa_lang('qa_blog_lang/nav_all'),
				'url' => qa_path_to_root().'blog','selected' => isset($_GET['category']) ? 0 : 1);
		$qa_content['navigation']['sub']['cat1'] = array('label' => qa_opt('qa_blog_cat_1'),
				'url' => qa_path_to_root().'blog?category=1', 'selected' => $cat == 1 ? 1 : 0);			
		$qa_content['navigation']['sub']['cat2'] = array('label' => qa_opt('qa_blog_cat_2'),
				'url' => qa_path_to_root().'blog?category=2', 'selected' => $cat == 2 ? 1 : 0);
		$qa_content['navigation']['sub']['cat3'] = array('label' => qa_opt('qa_blog_cat_3'),
				'url' => qa_path_to_root().'blog?category=3', 'selected' => $cat == 3 ? 1 : 0);
		$qa_content['navigation']['sub']['cat4'] = array('label' => qa_opt('qa_blog_cat_4'),
				'url' => qa_path_to_root().'blog?category=4','selected' => $cat == 4 ? 1 : 0);
		$qa_content['navigation']['sub']['cat5'] = array('label' => qa_opt('qa_blog_cat_5'),
				'url' => qa_path_to_root().'blog?category=5', 'selected' => $cat == 5 ? 1 : 0);
		$qa_content['navigation']['sub']['post'] = array('label' => qa_lang('qa_blog_lang/nav_post'),
				'url' => qa_path_to_root().'articles');

		$qa_content['title']= qa_opt('qa_blog_title');	
		
		
		$postid = qa_request_part(1);
		if (isset($postid)) { 
		$result = qa_db_query_sub('SELECT * FROM ^blog_posts WHERE `postid` LIKE #', $postid);
		if ($row = mysql_fetch_array($result)) {
			qa_db_query_sub('UPDATE ^blog_posts SET Views = Views + 1 WHERE `postid` LIKE #', $postid);
			
			$qa_content['title']= '<a  name="cancel_comment">'.$row['title'].'</a>';
			$qa_content['error'] =  articleError($postid);
			
			$qa_content['custom']= '';
			
			$html = '<div class="bp_article">'.qa_viewer_html($row['content'],$row['format'],array('showurllinks' => 1)).'</div>
					<div class="bp_article_info">
					'.qa_lang('qa_blog_lang/Posted_in').
					CategoryName($row['type']).qa_lang('qa_blog_lang/by').AuthorName($row['userid']).
					DateAndTime($row['posted']).CommentsHits($postid).ArticleButton($postid).'</div>'.
					AuthorInfo($row['userid']);
	
		}
		else $html = qa_lang('qa_blog_lang/post_null');
	}
	else {
		$cat = -1;
		if (isset($_GET['category'])) $cat = $_GET['category'];
		$qa_content['navigation']['sub']['all'] = array(	'label' => qa_lang('qa_blog_lang/nav_all'),
				'url' => qa_path_to_root().'blog','selected' => isset($_GET['category']) ? 0 : 1);
		$qa_content['navigation']['sub']['cat1'] = array('label' => qa_opt('qa_blog_cat_1'),
				'url' => qa_path_to_root().'blog?category=1', 'selected' => $cat == 1 ? 1 : 0);			
		$qa_content['navigation']['sub']['cat2'] = array('label' => qa_opt('qa_blog_cat_2'),
				'url' => qa_path_to_root().'blog?category=2', 'selected' => $cat == 2 ? 1 : 0);
		$qa_content['navigation']['sub']['cat3'] = array('label' => qa_opt('qa_blog_cat_3'),
				'url' => qa_path_to_root().'blog?category=3', 'selected' => $cat == 3 ? 1 : 0);
		$qa_content['navigation']['sub']['cat4'] = array('label' => qa_opt('qa_blog_cat_4'),
				'url' => qa_path_to_root().'blog?category=4','selected' => $cat == 4 ? 1 : 0);
		$qa_content['navigation']['sub']['cat5'] = array('label' => qa_opt('qa_blog_cat_5'),
				'url' => qa_path_to_root().'blog?category=5', 'selected' => $cat == 5 ? 1 : 0);
		$qa_content['navigation']['sub']['post'] = array('label' => qa_lang('qa_blog_lang/nav_post'),
				'url' => qa_path_to_root().'articles');
		$html ='';
				
		$page = 1;
		
			
		if (isset($_GET['page'])) $page = $_GET['page'];
			$limit = 10;
		if (isset($_GET['category'])) 
			$result = qa_db_query_sub("SELECT * FROM ^blog_posts WHERE type=# AND format='markdown'
			ORDER BY posted DESC LIMIT #,#",$cat,($page-1)*$limit,$limit);
		else 
			$result = qa_db_query_sub("SELECT * FROM ^blog_posts  WHERE format='markdown' ORDER BY posted DESC LIMIT #,#",($page-1)*$limit,$limit);
			$i=0;
		while ($article = mysql_fetch_array($result)) {
			$i++;
			$author = $article['userid'];
			if ($article['userid'] == 0) 
				$author = qa_lang('qa_blog_lang/userid_null');
			$html .= article_item_with_author($article['title'],
			''.qa_path_to_root().'blog/'.$article['postid'].'/'.seoUrl($article['title']).'/',
				$article['content'],$author,$article['posted'],$article['views'],$article['type'],$article['postid']);
			
		}
			
		if ($i==0) {
			$html = "<h4>".qa_lang('qa_blog_lang/posts_null')."</h4>";
		}
		else {
			if (isset($_GET['category'])) $result = qa_db_query_sub("SELECT COUNT(*) as total FROM ^blog_posts WHERE type=#",$cat);
			else $result = qa_db_query_sub("SELECT COUNT(*) as total FROM ^blog_posts");
			$countdata = mysql_fetch_assoc($result);
			$count = $countdata['total'];
			
			if ($count/$limit > 1) {
				$html .= '<br><br><div class="qa-page-links"> <span class="qa-page-links-label">Page: </span><ul class="qa-page-links-list">';
				if ($page > 1)
				$html .= '<li class="qa-page-links-item"> <a href="./questions?page='.($page-1).'" class="qa-page-prev">« prev</a> </li>';
				for ($i=0 ; $i<($count/$limit) ; $i++) {
					if ($page-1 == $i) $html .= '<li class="qa-page-links-item"><span class="qa-page-selected">'.($i+1).'</span></li>';
					else $html .= '<li class="qa-page-links-item"><a href="./blog?page='.($i+1).'" class="qa-page-link">'.($i+1).'</a></li>';
				}
				if ($page < $count/$limit)
				$html .= '<li class="qa-page-links-item"> <a href="./blog?page='.($page+1).'" class="qa-page-next">next »</a> </li></ul></div>';
				else $html .= '</ul></div>';
			}
			
		}
		$this->content['custom'] = $html;
		
	}	
		
	$qa_content['custom'] = $html;
	
	return $qa_content;
	}
	
}

