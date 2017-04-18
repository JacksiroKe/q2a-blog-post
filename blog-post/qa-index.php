<?php
/*
	Plugin Name: Blog Post
	Plugin URI: http://websmata.tujuane.net/qtoa/blog
	Plugin Description: The Blog module allows registered users to maintain an online journal, or blog. The blog entries are displayed by creation time in descending order.
	Plugin Version: 3.0
	Plugin Date: 2014-04-01
	Plugin Author: Jackson Siro
	Plugin Author URI: http://question2answer.org/qa/user/jaxila
	Plugin License: GPLv3
	Plugin Minimum Question2Answer Version: 1.5
	Plugin Update Check URI: 

*/

if ( !defined('QA_VERSION') )
{
	header('Location: ../../');
	exit;
	

}

/* MAJOR FUNCTIONS OF THE BLOG PLUGIN */

function CommentsHits($postid) {
	$result = qa_db_query_sub('SELECT * FROM ^blog_posts WHERE `postid` LIKE #', $postid);
	$row = mysql_fetch_array($result);
	$strviews = qa_lang('qa_blog_lang/post_views');
	$views = $row['views'];
	$strviews = qa_lang('qa_blog_lang/post_view');
	if ($row['views'] == 0) $strviews = qa_lang('qa_blog_lang/post_views');
	else if ($row['views'] > 1) $strviews = qa_lang('qa_blog_lang/post_views');
	
	$getcomment = qa_db_query_sub("SELECT COUNT(*) as total FROM ^blog_comments WHERE `parentid` LIKE #", $postid);
	$countdata = mysql_fetch_assoc($getcomment);
	
	$comment = $countdata['total'].qa_lang('qa_blog_lang/post_comment');
	if ($countdata['total'] == 0) $comment = $countdata['total'].qa_lang('qa_blog_lang/post_comments');
	else if ($countdata['total'] > 1) $comment = $countdata['total'].qa_lang('qa_blog_lang/post_comments');
	
	return '<span class="qa-form-light-button qa-form-light-button-comment">
			'.$comment.'</span>
			<span class="qa-form-light-button qa-form-light-button-reshow">
			'.$row['views'].$strviews.'</span>';
}

function get_image($string) {
	$regex = "/\<img.+src\s*=\s*\"([^\"]*)\"[^\>]*\>/Us";
	if ($image = preg_match_all($regex, $string, $matches)) {	
	return $matches[1][0];
	}
	else 
		return AuthorAvatar($row['userid'],140,5,5,20);
}
			
function article_item_with_author($title,$link,$content,$author,$date,$views,$type,$postid) {
	$regex = "/\<img.+src\s*=\s*\"([^\"]*)\"[^\>]*\>/Us";
	if ($image = preg_match_all($regex, $content, $matches)) {	
	$image = '<img src="'.$matches[1][0].'"  style="float:left;width:150px;height:150px;
	margin:5px;border-radius:5px;"/>';			
	}
	else $image = '';
	return '<div class="bp_article_item" title="'.substr(strip_tags($content),0,1000).'">
			<h2><a href="'.$link.'">'.$title.'</a></h2><a href="'.$link.'">'.$image.'
			</a><p>'.substr(strip_tags($content),0,qa_opt('qa_blog_content_max')).'
			. . . <strong><a href="'.$link.'">Read more</a></strong></p>'.
			qa_lang('qa_blog_lang/posted_in').CategoryName($type).
			qa_lang('qa_blog_lang/by').AuthorName($author).DateAndTime($date).CommentsHits($postid).'<br></div>';
			
}
function ArticleAdmin($postid) {
	return '<form name="comment" method="post" action="'.qa_self_html().'">' .
			'<div style="float:right;">' .
			'<input name="approve" value=" Approve " title="Approve this article" class="qa-form-light-button qa-form-light-button-approve" type="submit">' .
			'<input name="reject" value=" Reject " title="Reject this article" class="qa-form-light-button qa-form-light-button-reject" type="submit">' .
			'<input name="edit" value=" Edit " title="Edit this article" class="qa-form-light-button qa-form-light-button-edit" type="submit">' .
			'<input name="hide" value=" Hide " title="Hide this article" class="qa-form-light-button qa-form-light-button-hide" type="submit">' .
			'<input name="delete" value=" Delete " title="Delete this article" class="qa-form-light-button qa-form-light-button-delete" type="submit">' .
			'</div>' .
			'</form>';

}
function ArticleButton($postid) {
	$result = qa_db_query_sub('SELECT * FROM ^blog_posts WHERE `postid` LIKE #', $postid);
	$row = mysql_fetch_array($result);
	
	$format = $row['format'];
	
	$comment = '<form style="display:inline!important;" action="#leave_a_comment">
				<input title="comment this article" class="qa-form-light-button qa-form-light-button-comment" type="submit" 
				value="Comment"></form>';
	if (qa_request_part(0) == 'user') $comment = '';
	else if (qa_request_part(0) == 'admin') $comment = '';
	
	$flag = '<form style="display:inline!important;" method="post" action="'.qa_path_to_root().'flag?article='.$postid.'">
				<input title="flag this article" class="qa-form-light-button qa-form-light-button-flag" type="submit" 
				value="Flag" onclick="qa_show_waiting_after(this, false);"></form>';
	$edit = '<form style="display:inline!important;" method="post" action="'.qa_path_to_root().'edit?article='.$postid.'">
				<input title="Edit this article" class="qa-form-light-button qa-form-light-button-edit" type="submit" 
				value="Edit" onclick="qa_show_waiting_after(this, false);"></form>';
	$close = '<form style="display:inline!important;" method="post" action="'.qa_path_to_root().'close?article='.$postid.'">
				<input title="close this article" class="qa-form-light-button qa-form-light-button-close" type="submit" 
				value="Close"></form>';
	$hide = '<form style="display:inline!important;" method="post" action="'.qa_path_to_root().'hide?article='.$postid.'">
				<input title="hide this article" class="qa-form-light-button qa-form-light-button-hide" type="submit" 
				value="Hide" onclick="qa_show_waiting_after(this, false);"></form>';	
	if ($format == 'hidden') $hide = '';
	
	$reshow = '<form style="display:inline!important;" method="post" action="'.qa_path_to_root().'reshow?article='.$postid.'">
				<input title="reshow this article" class="qa-form-light-button qa-form-light-button-reshow" type="submit" 
				value="Reshow" onclick="qa_show_waiting_after(this, false);"></form>';
	if ($format == 'markdown') $reshow = '';
	
	$delete = '<form style="display:inline!important;" method="post" action="'.qa_path_to_root().'delete?article='.$postid.'">
				<input title="delete this article" class="qa-form-light-button qa-form-light-button-delete" type="submit" 
				value="Delete" onclick="qa_show_waiting_after(this, false);"></form>';
	if(qa_get_logged_in_userid()== $row['userid'] || qa_get_logged_in_level()>=QA_USER_LEVEL_MODERATOR)  {
		
	return '<div style="float:right"><div></div>'.$edit.$hide.$reshow.$delete.'</div>';
		}
			return '';

}

function ArticleStatus($postid) {
		$result = qa_db_query_sub('SELECT * FROM ^blog_posts WHERE `postid` LIKE #', $postid);
		$row = mysql_fetch_array($result);
		$format = $row['format'];
		$status = '<span style="background:green;padding-right:5px;border-radius:0px 5px 0px 0px;"> PUBLISHED </span>';
		if ($format == 'draft') 
		$status = '<span style="background:blue;padding-right:5px;border-radius:0px 5px 0px 0px;"> DRAFT </span>';
		else if ($format == 'hidden') 
		$status = '<span style="background:gray;padding-right:5px;border-radius:0px 5px 0px 0px;"> HIDDEN </span>';
		else if ($format == 'moderate') 
		$status = '<span style="background:red;padding-right:5px;border-radius:0px 5px 0px 0px;"> PENDING </span>';
		
		$state = '<span style="padding-left:7px;background:black;border-radius:0px 0px 0px 5px;
		padding-right:5px;"> Status:</span>';
		if(qa_get_logged_in_userid()== $row['userid'] || qa_get_logged_in_level()>=QA_USER_LEVEL_MODERATOR)  {
		return '<span style="color:white;float:right;
		font-size:12px;margin-left:10px;margin:-top:3px;font-weight:bold;">'.$state.$status.' </span>';
		}
		else return '';
}
function articleAuthor($title,$author,$content,$link,$date,$type,$postid) 
	{		
			
	return '<div><h2><a href="'.$link.'">'.$title.'</a></h2><p>
			<div style="border-bottom:1px dotted #000";>
			'.AuthorAvatar($author,35,30,1,5).substr(strip_tags($content),0,175).'</div>'
			.qa_lang('qa_blog_lang/written_by').AuthorName($author).qa_lang('qa_blog_lang/in').
			CategoryName($type).DateAndTime($date).CommentsHits($postid).'<br>'
			.ArticleStatus($postid).ArticleButton($postid).'</p></div>';
	}
function CategoryName($type){
	
	$category = qa_opt('qa_blog_cat_1');	
	if ($type == 2) $category = qa_opt('qa_blog_cat_2');
	else if ($type == 3) $category = qa_opt('qa_blog_cat_3');
	else if ($type == 4) $category = qa_opt('qa_blog_cat_4');
	else if ($type == 5) $category = qa_opt('qa_blog_cat_5');
	
	return ' <a href="'.qa_path_to_root().'blog?category='.$type.'">'.$category.'</a> ';
}

function article_item($title,$postid,$link,$date,$type,$views,$content,$format) {
	$date =new DateTime($date);
	$posted = $date->format('D d M, Y').' at '.$date->format(' H:i').' in ';	
	return '<div class="bp_article_item">
			<h2><a href="'.$link.'">'.$title.'</a></h2>
			<p">'.substr(strip_tags($content),0,285).
			' . . . <strong><a href="'.$link.'">Read more</a></strong><br><hr>'
			.qa_lang('qa_blog_lang/written_on').$posted.CategoryName($type).CommentsHits($postid).'<br>'
			.ArticleStatus($postid).ArticleButton($postid).'</p></div>';

}

function seoUrl($string) {
	    $string = strtolower($string);
		$string = preg_replace("/[^a-z0-9_\s-]/", "", $string);
		$string = preg_replace("/[\s-]+/", " ", $string);
	    $string = preg_replace("/[\s_]/", "_", $string);
	    return $string;
}
function handleLink($id) {
	$result = qa_db_query_sub('SELECT * FROM ^users WHERE userid=#',$id);
	if ($row = mysql_fetch_array($result)) {
		return '<a href="/user/'.$row['handle'].'">@'.$row['handle'].'</a>';
	}
	return 'anonymous';
}

function DateAndTime($date) {
	$DateAndTime ='<abbr class="timeago" title="' .$date. '"></abbr>
					<script>jQuery(document).ready(function($){$("abbr.timeago").timeago()});</script>';
	return $DateAndTime;
}

function AuthorName($id) {
	$queryName = qa_db_read_one_assoc( qa_db_query_sub('SELECT content
	FROM `^userprofile`	WHERE `userid`='.$id.' AND title="name" LIMIT 0,#;', $id), true );
	$RealName = $queryName['content'];
	$result = qa_db_query_sub('SELECT * FROM ^users WHERE userid=#',$id);
	$row = mysql_fetch_array($result); 
	$UserName = $row['handle'];
	if (isset($queryName['content'])) {
		return ' <a href="'.qa_path_to_root().'user/'.$row['handle'].'">'.$RealName.'</a> ';}
	return ' <a href="'.qa_path_to_root().'user/'.$UserName.'">@'.$UserName.'</a> ';
}
function AuthorHandle($id) {
	$queryName = qa_db_read_one_assoc( qa_db_query_sub('SELECT content
	FROM `^userprofile`	WHERE `userid`='.$id.' AND title="name" LIMIT 0,#;', $id), true );
	$result = qa_db_query_sub('SELECT * FROM ^users WHERE userid=#',$id);
	$row = mysql_fetch_array($result);
	if (isset($queryName['content'])) {
		return $queryName['content'];
	}
	return qa_logged_in_handle();
}

function AuthorLoc($id) {
	$queryLoc = qa_db_read_one_assoc( qa_db_query_sub('SELECT content
	FROM `^userprofile`	WHERE `userid`='.$id.' AND title="location" LIMIT 0,#;', $id), true );
	$result = qa_db_query_sub('SELECT * FROM ^users WHERE userid=#',$id);
	$row = mysql_fetch_array($result); 
	if (isset($queryLoc['content'])) {
			return '<span> from '.$queryLoc['content'].'</span>';	
	}
	return '';
}
function AuthorWeb($id) {
	$queryWeb = qa_db_read_one_assoc( qa_db_query_sub('SELECT content
	FROM `^userprofile`	WHERE `userid`='.$id.' AND title="website" LIMIT 0,#;', $id), true );
	$result = qa_db_query_sub('SELECT * FROM ^users WHERE userid=#',$id);
	$row = mysql_fetch_array($result);
	if (isset($queryWeb['content'])){
		return '<br><strong>Website:</strong> <a target="_blank" href="http://'.$queryWeb['content'].'"/>'.$queryWeb['content']. '</a>';	
		}
	return '';
}
function AuthorBio($id) {
	$queryBio = qa_db_read_one_assoc( qa_db_query_sub('SELECT content
			FROM `^userprofile`	WHERE `userid`='.$id.' AND title="about" LIMIT 0,#;', $id), true );
	$result = qa_db_query_sub('SELECT * FROM ^users WHERE userid=#',$id);
	$row = mysql_fetch_array($result);
	if (isset($queryBio['content'])){
		return '<hr style="color:#fff;"><strong>About me:</strong> '.substr(strip_tags($queryBio['content']),0,200).'<hr style="color:#fff;">';	
		}	
	return '';
}			

			//$title = $name.$loc;
function AuthorAvatar($id,$px,$border_radius,$border,$margin){
	$result = qa_db_query_sub('SELECT * FROM ^users WHERE userid=#',$id);
	if ($row = mysql_fetch_array($result)) {
		if (isset($row['avatarblobid'])){
		$avatar= '<a href="'.qa_path_to_root().'user/'.$row['handle'].'"><img class="bp_avatar" height="'.$px.'" width="'.$px.'" 
			style="float:left;border-radius:'.$border_radius.'px;border:'.$border.'px double #000; margin-right:'.$margin.'px;"
			src="?qa=image&qa_blobid='.$row['avatarblobid'].'&qa_size=200"/></a>';
					}
		else $avatar= '<a href="'.qa_path_to_root().'user/'.$row['handle'].'"><img class="bp_avatar" height="'.$px.'" width="'.$px.'" 
		style="float:left;border-radius:'.$border_radius.'px;border:'.$border.'px double #000; margin-right:'.$margin.'px;"
			src="?qa=image&qa_blobid='.qa_opt('avatar_default_blobid').'&qa_size=200"/></a>';
		return $avatar;
	}
}
function AuthorInfo($id){
	$row = mysql_fetch_array(qa_db_query_sub('SELECT * FROM ^blog_posts WHERE `postid` LIKE #', qa_request_part(1)));
	return '<div class="bp_author_info">'
					.AuthorAvatar($row['userid'],140,5,5,20).'
					<strong>'.AuthorName($row['userid']).AuthorLoc($row['userid']).'</strong>
					</span>'.AuthorBio($row['userid']).'<br>				
					<strong>Blog Activity: </strong>'.
					ArticlesActivity($row['userid']).qa_lang('qa_blog_lang/articles_no').
					CommentsActivity($row['userid']).qa_lang('qa_blog_lang/comments_no').
					AuthorWeb($row['userid']).'</div>';

}
function AuthorAvatar1($id,$px){
	$result = qa_db_query_sub('SELECT * FROM ^users WHERE userid=#',$id);
	if ($row = mysql_fetch_array($result)) {
		if (isset($row['avatarblobid'])){
		$avatar= '<img class="bp_avatar1" height="'.$px.'" width="'.$px.'" style="float:right;"
					src="?qa=image&qa_blobid='.$row['avatarblobid'].'&qa_size=200"/>';
					}
		else $avatar= '<img class="bp_avatar1" height="'.$px.'" width="'.$px.'" style="float:right;"
			src="?qa=image&qa_blobid='.qa_opt('avatar_default_blobid').'&qa_size=200"/>';
		return $avatar;
	}
}
function ArticlesActivity($userid) {
	$result = qa_db_query_sub("SELECT COUNT(*) as total FROM ^blog_posts WHERE `userid` LIKE #", $userid);
	$countdata = mysql_fetch_assoc($result);
	return $countdata['total'];
}

function CommentsActivity($userid) {
	$result = qa_db_query_sub("SELECT COUNT(*) as total FROM ^blog_comments WHERE `userid` LIKE #", $userid);
	$countdata = mysql_fetch_assoc($result);
	return $countdata['total'];
}

function handlLink($id) {
	$result = qa_db_query_sub('SELECT * FROM ^users WHERE userid=#',$id);
	if ($row = mysql_fetch_array($result)) {
		return '<a href="/user/'.$row['handle'].'">@'.$row['handle'].'</a>';
	}
	return 'anonymous';
}
function articleError($postid){
	$result = qa_db_query_sub('SELECT * FROM ^blog_posts WHERE `postid` LIKE #', $postid);
	$row = mysql_fetch_array($result);
	if(qa_get_logged_in_userid()== $row['userid'] || qa_get_logged_in_level()>=QA_USER_LEVEL_MODERATOR)  {
	
	$error = '';	
	if ($row['format'] == 'hidden') $error = 'This Article is been hidden';
	return $error;
	}
		return '';
}

function markdownSupport(){	return '<style>	.wmd-button > span { background-image: url("./qa-plugin/markdown/pagedown/wmd-buttons.png") }.wmd-button-bar {width: 100%;padding: 5px 0;} .wmd-input {width: 600px;height: 250px;margin: 0 0 10px;padding: 2px;border: 1px solid #ccc;} .wmd-preview {width: 600px;margin: 10px 0;padding: 8px;	border: 2px dashed #ccc;} .qa-q-view-content pre,.qa-a-item-content pre,.wmd-preview pre {overflow: auto;	width: 600px;max-height: 400px;padding: 0;border-width: 1px 1px 1px 3px;border-style: solid;border-color: #ddd;background-color: #eee;}	pre code {display: block;padding: 8px;}	.wmd-button-row {position: relative;margin: 0;padding: 0;height: 20px;}	.wmd-spacer {width: 1px;height: 20px;margin-left: 14px;position: absolute;background-color: Silver;	display: inline-block;list-style: none;}	.wmd-button {width: 20px;height: 20px;padding-left: 2px;padding-right: 3px;position: absolute;display: inline-block;list-style: none;cursor: pointer;}	.wmd-button > span {background-repeat: no-repeat;background-position: 0px 0px;	width: 20px;height: 20px;display: inline-block;} .wmd-spacer1 {left: 50px;} .wmd-spacer2 {left: 175px;}	.wmd-spacer3 {left: 300px;}	.wmd-prompt-background {background-color: #000;} .wmd-prompt-dialog {border: 1px solid #999;background-color: #f5f5f5;}	.wmd-prompt-dialog > div {font-size: 0.8em;}	.wmd-prompt-dialog > form > input[type="text"] {border: 1px solid #999;color: black;}	.wmd-prompt-dialog > form > input[type="button"] {border: 1px solid #888;font-size: 11px;font-weight: bold;} </style>'; }