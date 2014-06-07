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
require_once QA_INCLUDE_DIR.'qa-db-users.php';
require_once QA_INCLUDE_DIR.'qa-app-format.php';
require_once QA_INCLUDE_DIR.'qa-app-users.php';
require_once QA_INCLUDE_DIR.'qa-app-blobs.php';


class qa_blog
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
		// regular page request
		$qa_content=qa_content_prepare();
		$site_url = qa_opt('qa_site_url');
			$cat = -1;
		if (isset($_GET['category'])) $cat = $_GET['category'];
			$qa_content['navigation']['sub'] = array();
		$qa_content['navigation']['sub']['all'] = array(	'label' => qa_lang('qa_blog_lang/nav_all'),
				'url' => $site_url.'/blog',
				'selected' => isset($_GET['category']) ? 0 : 1);
		$qa_content['navigation']['sub']['cat1'] = array('label' => $category_1,
				'url' => $site_url.'/blog?category=1',
				'selected' => $cat == 1 ? 1 : 0);			
		$qa_content['navigation']['sub']['cat2'] = array('label' => $category_2,
				'url' => $site_url.'/blog?category=2',
				'selected' => $cat == 2 ? 1 : 0);
		$qa_content['navigation']['sub']['cat3'] = array('label' => $category_3,
				'url' => $site_url.'/blog?category=3',
				'selected' => $cat == 3 ? 1 : 0);
		$qa_content['navigation']['sub']['post'] = array('label' => qa_lang('qa_blog_lang/nav_post'),
				'url' => $site_url.'/articles');	

	$qa_content['title']= qa_opt('qa_blog_title');	
	
	$postid = qa_request_part(1);
	if (isset($postid)) {
		$result = qa_db_query_sub('SELECT * FROM ^blog_posts WHERE `postid` LIKE #', $postid);
		if ($row = mysql_fetch_array($result)) {
			qa_db_query_sub('UPDATE ^blog_posts SET Views = Views + 1 WHERE `postid` LIKE #', $postid);
			$qa_content['title']= $row['title'];
			$qa_content['custom']= "";
			$html = qa_viewer_html($row['content'],$row['format'],array('showurllinks' => 1));
			$strviews = qa_lang('qa_blog_lang/post_views');
			if ($row['views'] == 1) $strviews = qa_lang('qa_blog_lang/post_views');
			$author =  handleLinkForID($row['userid']); 
			if ($row['userid'] == 0) $author = qa_lang('qa_blog_lang/userid_null');
			$date = $row['posted'];
			$date =new DateTime($date);
			$on = $date->format('Y.m.d');
			$at = $date->format('H:i');
			$parentid = $postid;
			$result = qa_db_query_sub("SELECT COUNT(*) as total FROM ^blog_comments WHERE `parentid` LIKE #", $parentid);
			$countdata = mysql_fetch_assoc($result);
			$count = $countdata['total'];
			
			$html .= "<hr>
					<span style='float:left;padding-left:10px'>
					".qa_lang('qa_blog_lang/posted_on')." ".$author. " ".qa_lang('qa_blog_lang/on')."
					".$on." ".qa_lang('qa_blog_lang/at')." ".$at."</span>
					<span style='float:right;padding-right:10px;'>
					 ".$row['views']." ".$strviews."</span>
					<br>";
					
			$countdata = mysql_fetch_assoc($result);
			$count = $countdata['total'];
		}
		else $html .= qa_lang('qa_blog_lang/post_null');
	}
	else {
		$cat = -1;
		if (isset($_GET['category'])) $cat = $_GET['category'];
		$qa_content['navigation']['sub'] = array();
		$qa_content['navigation']['sub']['all'] = array(	'label' => qa_lang('qa_blog_lang/nav_all'),
				'url' => './blog',
				'selected' => isset($_GET['category']) ? 0 : 1);
		$qa_content['navigation']['sub']['cat1'] = array('label' => $category_1,
				'url' => './blog?category=1',
				'selected' => $cat == 1 ? 1 : 0);			
		$qa_content['navigation']['sub']['cat2'] = array('label' => $category_2,
				'url' => './blog?category=2',
				'selected' => $cat == 2 ? 1 : 0);
		$qa_content['navigation']['sub']['cat3'] = array('label' => $category_3,
				'url' => './blog?category=3',
				'selected' => $cat == 3 ? 1 : 0);
		$qa_content['navigation']['sub']['post'] = array('label' => qa_lang('qa_blog_lang/nav_post'),
				'url' => './articles');
		
		
		$html = qa_opt('qa_blog_tagline').'<hr>';
		$page = 1;
		if (isset($_GET['page'])) $page = $_GET['page'];
		$limit = 10;
		if (isset($_GET['category'])) $result = qa_db_query_sub("SELECT * FROM ^blog_posts WHERE type=# ORDER BY posted DESC LIMIT #,#",$cat,($page-1)*$limit,$limit);
		else $result = qa_db_query_sub("SELECT * FROM ^blog_posts ORDER BY posted DESC LIMIT #,#",($page-1)*$limit,$limit);
		$i=0;
		$site_url = qa_opt('qa_site_url');
		while ($article = mysql_fetch_array($result)) {
			$i++;
			$author = $article['userid'];
			if ($article['userid'] == 0) $author = qa_lang('qa_blog_lang/userid_null');
			$html .= article_item_with_author($article['title'],
			''.$site_url.'/blog/'.$article['postid'].'/'.seoUrl($article['title']).'/',
			$author,$article['posted'],$article['views'],$article['type']);
			
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
				if ($page > 1) $html .= '<li class="qa-page-links-item"> <a href="./questions?page='.($page-1).'" class="qa-page-prev">« prev</a> </li>';
				for ($i=0 ; $i<($count/$limit) ; $i++) {
					if ($page-1 == $i) $html .= '<li class="qa-page-links-item"><span class="qa-page-selected">'.($i+1).'</span></li>';
					else $html .= '<li class="qa-page-links-item"><a href="./blog?page='.($i+1).'" class="qa-page-link">'.($i+1).'</a></li>';
				}
				if ($page < $count/$limit) $html .= '<li class="qa-page-links-item"> <a href="./blog?page='.($page+1).'" class="qa-page-next">next »</a> </li></ul></div>';
				else $html .= '</ul></div>';
			}
			
		}
		$this->content['custom'] = $html;
		
	}	
		
	$qa_content['custom'] = $html;
	
	
	//print_r($qa_content['site_title']);
	
	return $qa_content;
	}
	
}

function seoUrl($string) {
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

function article_item_with_author($title,$link,$author,$date,$views,$type) {
	
	$category_1 = qa_opt('qa_blog_cat_1');
	$category_2 = qa_opt('qa_blog_cat_2');
	$category_3 = qa_opt('qa_blog_cat_3');
		
	if ($author !== '') $author =  'by '.handleLinkForID($author);
	$vl = $views.qa_lang('qa_blog_lang/post_views');
	if ($views == 1) $vl = $views.qa_lang('qa_blog_lang/post_views');
	$date =new DateTime($date);
	$on = $date->format('Y.m.d');
	$at = $date->format('H:i');
	
	$category = $category_1;
	if ($type == 2) $category = $category_2;
	else if ($type == 3) $category = $category_3;
	$category = '<a href="./blog?category='.$type.'">'.$category.'</a>';
	return '<div><h2><a href="'.$link.'">'.$title.'</a></div></h2>
			<pre>Posted on <u>'.$on.'</u> '.$author.' in '.$category.' ('.$vl.')</pre><br>';
	
		
}
function handleLinkForID($id) {
	$result = qa_db_query_sub('SELECT * FROM ^users WHERE userid=#',$id);
	if ($row = mysql_fetch_array($result)) {
		return '<a href="/user/'.$row['handle'].'">'.$row['handle'].'</a>';
	}
	return 'anonymous';
}
