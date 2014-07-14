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
		
		$qa_content=qa_content_prepare();
		$cat = -1;
		if (isset($_GET['category'])) $cat = $_GET['category'];
			$qa_content['navigation']['sub'] = array();
		$qa_content['navigation']['sub']['all'] = array(	'label' => qa_lang('qa_blog_lang/nav_all'),
				'url' => qa_path_to_root().'/blog',
				'selected' => isset($_GET['category']) ? 0 : 1);
		$qa_content['navigation']['sub']['cat1'] = array('label' => $category_1,
				'url' => qa_path_to_root().'/blog?category=1',
				'selected' => $cat == 1 ? 1 : 0);			
		$qa_content['navigation']['sub']['cat2'] = array('label' => $category_2,
				'url' => qa_path_to_root().'/blog?category=2',
				'selected' => $cat == 2 ? 1 : 0);
		$qa_content['navigation']['sub']['cat3'] = array('label' => $category_3,
				'url' => qa_path_to_root().'/blog?category=3',
				'selected' => $cat == 3 ? 1 : 0);
		$qa_content['navigation']['sub']['cat4'] = array('label' => $category_4,
				'url' => qa_path_to_root().'/blog?category=4',
				'selected' => $cat == 4 ? 1 : 0);
		$qa_content['navigation']['sub']['cat5'] = array('label' => $category_5,
				'url' => qa_path_to_root().'/blog?category=5',
				'selected' => $cat == 5 ? 1 : 0);
		$qa_content['navigation']['sub']['post'] = array('label' => qa_lang('qa_blog_lang/nav_post'),
				'url' => qa_path_to_root().'/articles');	

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
			$views = $row['views'];
			if ($row['views'] == 1) $strviews = qa_lang('qa_blog_lang/post_views');
			$author =  handleLinkForID($row['userid']); 
			if ($row['userid'] == 0) $author = qa_lang('qa_blog_lang/userid_null');
			$user = $row['userid'];
			$date = $row['posted'];
			$date =new DateTime($date);
			$on = $date->format('Y.m.d');
			$at = $date->format('H:i');
			$parentid = $postid;
			$result = qa_db_query_sub("SELECT COUNT(*) as total FROM ^blog_comments WHERE `parentid` LIKE #", $parentid);
			$countdata = mysql_fetch_assoc($result);
			$count = $countdata['total'];
			$delete = "<a href='".qa_path_to_root()."/edit/".$postid."'/>
			<img src='".qa_opt('qa_site_url')."/qa-plugin/blog-post/images/delete.png'> Delete </a>";
			$edit = "<a href='".qa_path_to_root()."/edit/".$postid."'/>
			<img src='".qa_opt('qa_site_url')."/qa-plugin/blog-post/images/edit.png'> Edit </a>";
			$flag = "<a href='#'/>
			<img src='".qa_opt('qa_site_url')."/qa-plugin/blog-post/images/edit.png'> Flag </a>";
			$comments = qa_lang('qa_blog_lang/post_comments');
			$queryName = qa_db_read_one_assoc( qa_db_query_sub('SELECT content
											FROM `^userprofile`
											WHERE `userid`='.$user.'
											AND title="name"
											LIMIT 0,#;', $user), true );
			$name = (isset($queryName['content']) && trim($queryName['content'])!='') ? $queryName['content'] : $author;
			$result = qa_db_query_sub('SELECT * FROM ^users WHERE userid=#',$user);
				if ($row = mysql_fetch_array($result)) {
				$fullname = '<a href="/user/'.$row['handle'].'">'.$name.'</a>';	
				}
			if(qa_is_logged_in())
				{
			$html .= "<hr>
					<span style='float:left;padding-left:10px'>
					".qa_lang('qa_blog_lang/posted_by')." ".$fullname. " ".qa_lang('qa_blog_lang/on')."
					".$on." ".qa_lang('qa_blog_lang/at')." ".$at."</span>
					<span style='float:right;padding-right:10px;'>
					".$edit." . ".$delete." |
					<img src='".qa_path_to_root()."/qa-plugin/blog-post/images/comment.png'>".$count." ".$comments."
					<img src='".qa_path_to_root()."/qa-plugin/blog-post/images/hits.jpg'>".$views." ".$strviews."</span>
					<br>";
					}
			else 
					$html .= "<hr>
					<span style='float:left;padding-left:10px'>
					".qa_lang('qa_blog_lang/posted_by')." ".$fullname. " ".qa_lang('qa_blog_lang/on')."
					".$on." ".qa_lang('qa_blog_lang/at')." ".$at."</span>
					<span style='float:right;padding-right:10px;'>".$count." ".$comments." | ".$row['views']." ".$strviews."</span>
					<br>";
			
			 $html .= "<h2>Comments features is not available in free version</h2>";
			$parentid = qa_request_part(1);
			$result = qa_db_query_sub("SELECT * FROM ^blog_comments WHERE parentid =  '$parentid' ");	
			$i=0;
			while ($blob = mysql_fetch_array($result)) {
			$i++;
			$html .= "<p> ".$blob['comment']."</span><br>".qa_lang('qa_blog_lang/comment')."
					".$author." ".qa_lang('qa_blog_lang/on')." ".$on." ".qa_lang('qa_blog_lang/at')."
					".$at."</p>";}
			if ($i==0) $html .= '<h3>No Comments yet</h3>';
		}
		else $html = qa_lang('qa_blog_lang/post_null');
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
		$qa_content['navigation']['sub']['cat4'] = array('label' => $category_4,
				'url' => './blog?category=4',
				'selected' => $cat == 4 ? 1 : 0);
		$qa_content['navigation']['sub']['cat5'] = array('label' => $category_5,
				'url' => './blog?category=5',
				'selected' => $cat == 5 ? 1 : 0);
		$qa_content['navigation']['sub']['post'] = array('label' => qa_lang('qa_blog_lang/nav_post'),
				'url' => './articles');
		
		
		$html = qa_opt('qa_blog_tagline').'<hr>';
		$page = 1;
	if (isset($_GET['page'])) $page = $_GET['page'];
		$limit = 10;
	if (isset($_GET['category'])) 
		$result = qa_db_query_sub("SELECT * FROM ^blog_posts WHERE type=# WHERE format='markdown'
		ORDER BY posted DESC LIMIT #,#",$cat,($page-1)*$limit,$limit);
	else 
		$result = qa_db_query_sub("SELECT * FROM ^blog_posts  WHERE format='markdown' ORDER BY posted DESC LIMIT #,#",($page-1)*$limit,$limit);
		$i=0;
		while ($article = mysql_fetch_array($result)) {
			$i++;
			$author = $article['userid'];
			if ($article['userid'] == 0) $author = qa_lang('qa_blog_lang/userid_null');
			$html .= article_item_with_author($article['title'],
			''.qa_path_to_root().'/blog/'.$article['postid'].'/'.seoUrl($article['title']).'/',
			$article['content'],$author,$article['posted'],$article['views'],
			$article['type'],$article['postid']);
			
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
	    $string = strtolower($string);
		$string = preg_replace("/[^a-z0-9_\s-]/", "", $string);
		$string = preg_replace("/[\s-]+/", " ", $string);
	    $string = preg_replace("/[\s_]/", "-", $string);
	    return $string;
}

function article_item_with_author($title,$link,$content,$author,$date,$views,$type,$postid) {
	
	$category_1 = qa_opt('qa_blog_cat_1');
	$category_2 = qa_opt('qa_blog_cat_2');
	$category_3 = qa_opt('qa_blog_cat_3');
	$category_4 = qa_opt('qa_blog_cat_4');
	$category_5 = qa_opt('qa_blog_cat_5');
	$editor =qa_get_logged_in_userid();
	$level=qa_get_logged_in_level();
	
	if($level>=QA_USER_LEVEL_MODERATOR) {
	$edit = '<a href="'.qa_opt('qa_site_url').'/edit/'.$postid.'"/>
	<img src="'.qa_opt('qa_site_url').'/qa-plugin/blog-post/images/edit.png"> Edit</a>';
	}
	else $edit = '';
	$user = $author;
	$max = qa_opt('qa_blog_content_max');
	$cut = strip_tags($content);
	$body = substr($cut,0,$max);
	$more = ' . . . <strong><a href="'.$link.'">Read more</a></strong>'; 
	$parentid = $postid;
	$result = qa_db_query_sub("SELECT COUNT(*) as total FROM ^blog_comments WHERE `parentid` LIKE #", $parentid);
	$countdata = mysql_fetch_assoc($result);
	$count = $countdata['total'];
	$comments = qa_lang('qa_blog_lang/post_comments');
	$cl = $count.$comments;
	if ($author !== '') $author = handleLinkForID($author);
	$vl = $views.qa_lang('qa_blog_lang/post_views');
	if ($views == 1) $vl = $views.qa_lang('qa_blog_lang/post_views');
	$date =new DateTime($date);
	$on = $date->format('d M, Y');
	$at = $date->format('H:i');
	$category = $category_1;	
	if ($type == 2) $category = $category_2;
	else if ($type == 3) $category = $category_3;
	else if ($type == 4) $category = $category_4;
	else if ($type == 5) $category = $category_5;
	$category = '<a href="./blog?category='.$type.'">'.$category.'</a>';	
	$queryName = qa_db_read_one_assoc( qa_db_query_sub('SELECT content
											FROM `^userprofile`
											WHERE `userid`='.$user.'
											AND title="name"
											LIMIT 0,#;', $user), true );
	$name = (isset($queryName['content']) && trim($queryName['content'])!='') ? $queryName['content'] : $author;
	$result = qa_db_query_sub('SELECT * FROM ^users WHERE userid=#',$user);
	if ($row = mysql_fetch_array($result)) {
		$fullname = '<a href="/user/'.$row['handle'].'">'.$name.'</a>';		
		$pic = '<a href="user/'.$row['handle'].'">
					<img style="border-radius:20px;" src="?qa=image&qa_blobid='.$row['avatarblobid'].'&qa_size=100"/></a>';
		$nopic = '<a href="user/'.$row['handle'].'">
			<img style="border-radius:20px;" src="?qa=image&qa_blobid='.qa_opt('avatar_default_blobid').'&qa_size=100"/></a>';
		$avatar = (isset($row['avatarblobid']) && trim($pic)!='') ? $pic : $nopic;
		}
	
	return '<div>
				<h2><a href="'.$link.'">'.$title.'</a></h2>
					</table><table width="700">
					<tr><td  width="150">
					<p><center>'.$avatar.'<br><strong>'.$fullname.' </strong></center></p>
					</td><td valign="top">
						<p>'.$body.$more.'</p>
					<table>
					<tr><td></td><td>
						Posted in '.$category.' '.$on.' | 
						<img src="'.qa_opt('qa_site_url').'/qa-plugin/blog-post/images/comment.png"> '.$cl.'
						<img src="'.qa_opt('qa_site_url').'/qa-plugin/blog-post/images/hits.jpg"> '.$vl.' 						
						'.$edit.'
						</td></tr></table></td></tr>
				</table><br>
			</div>';
	
		
}

function handleLinkForID($id) {
	$result = qa_db_query_sub('SELECT * FROM ^users WHERE userid=#',$id);
	if ($row = mysql_fetch_array($result)) {
		return '<a href="/user/'.$row['handle'].'">@'.$row['handle'].'</a>';
	}
	return 'anonymous';
} ?>
