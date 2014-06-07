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
require_once QA_INCLUDE_DIR.'qa-app-blobs.php';

class qa_html_theme_layer extends qa_html_theme_base {

	function doctype() {	
		
			if (strpos($this->request,'user/') !== false && strpos($this->request,'articles') !== false)
    		$this->request = 'user-articles';
			
		/*
			ADAPT USER PAGES AND SUBPAGES
		*/
		if($this->template == 'user' || 
			$this->template == 'user-wall' || 
			$this->template == 'user-activity' || 
			$this->template == 'user-questions' || 
			$this->template == 'user-answers' || 
			$this->request == 'user-articles') {
						
			$handle=qa_request_part(1);
			if (!strlen($handle)) {
				$handle=qa_get_logged_in_handle();
				qa_redirect(isset($handle) ? ('user/'.$handle) : 'users');				
			}
			$identifier=QA_FINAL_EXTERNAL_USERS ? $userid : $handle;
			list($useraccount, $userprofile, $userfields, $usermessages, $userpoints, $userlevels, $navcategories, $userrank)=
				    qa_db_select_with_pending(
				    QA_FINAL_EXTERNAL_USERS ? null : qa_db_user_account_selectspec($handle, false),
				    QA_FINAL_EXTERNAL_USERS ? null : qa_db_user_profile_selectspec($handle, false),
				    QA_FINAL_EXTERNAL_USERS ? null : qa_db_userfields_selectspec(),
				    QA_FINAL_EXTERNAL_USERS ? null : qa_db_recent_messages_selectspec(null, null, $handle, false, qa_opt_if_loaded('page_size_wall')),
				    qa_db_user_points_selectspec($identifier),
					qa_db_user_levels_selectspec($identifier, QA_FINAL_EXTERNAL_USERS, true),
					qa_db_category_nav_selectspec(null, true),
					qa_db_user_rank_selectspec($identifier)
			);
			$userid=$useraccount['userid'];
			$loginuserid=qa_get_logged_in_userid();
			
			if ($this->template == 'user') {
				
				// ADAPT FORM CONTENTS
				/*$this->content['form_activity']['fields']['activity'] = array('type'=>'static', 
						'label'=>'Recent Activity',
						'value'=>'<a href="'.$handle.'/activity">show</a>');*/
						// ADD PRIVATE MESSAGE LINK AFTER MEMBERSHIP DURATION
				if (qa_opt('allow_private_messages') && 
					isset($loginuserid) && 
					($loginuserid!=$userid) && 
					!($useraccount['flags'] & 
					QA_USER_FLAGS_NO_MESSAGES)) {
					$this->content['form_profile']['fields']['duration']['value'].=strtr(qa_lang_html('profile/send_private_message'), array(
						'^1' => '<a href="'.qa_path_html('message/'.$handle).'">',
						'^2' => '</a>',
					));
				}
			}
			$site_url = qa_opt('qa_site_url');
			// RENEW THE SUB-NAVIGATION
			unset($this->content['navigation']['sub']);
			$this->content['navigation']['sub']['account'] = array(	'label' => 'User '.$handle,
							'url' => $site_url.'/user/'.$handle,
							'selected' => $this->template == 'user' ? 1 : 0);
			$this->content['navigation']['sub']['wall'] = array('label' => $handle.'\'s Wall',
							'url' => $site_url.'/user/'.$handle.'/wall',
							'selected' => $this->template == 'user-wall' ? 1 : 0);			
			$this->content['navigation']['sub']['activity'] = array('label' => qa_lang('qa_blog_lang/nav_activity'),
							'url' => $site_url.'/user/'.$handle.'/activity',
							'selected' => $this->template == 'user-activity' ? 1 : 0);
			$this->content['navigation']['sub']['questions'] = array('label' => qa_lang('qa_blog_lang/nav_questions'),
							'url' => $site_url.'/user/'.$handle.'/questions',
							'selected' => $this->template == 'user-questions' ? 1 : 0);
			$this->content['navigation']['sub']['answers'] = array('label' => qa_lang('qa_blog_lang/nav_answers'),
							'url' => $site_url.'/user/'.$handle.'/answers',
							'selected' => $this->template == 'user-answers' ? 1 : 0);
			$this->content['navigation']['sub']['articles'] = array('label' => qa_lang('qa_blog_lang/nav_articles'),
							'url' => $site_url.'/user/'.$handle.'/articles',
							'selected' => $this->request == 'user-articles' ? 1 : 0);
			$this->content['navigation']['sub']['newarticles'] = array('label' => qa_lang('qa_blog_lang/new_articles'),	
							'url' => $site_url.'/articles',
							'selected' => $this->request == 'articles' ? 1 : 0);
			
			if ($this->request == 'user-articles') {
				unset($this->content['title']);
				$this->content['title']= qa_lang('qa_blog_lang/title_recent')." $handle";				
				unset($this->content['suggest_next']);
				unset($this->content['error']);
				if ($this->request == 'user-articles') {
					$qa_content['custom']= "";
					$html = "";					
					$result = qa_db_query_sub("SELECT * FROM ^blog_posts WHERE userid =  '$userid' ORDER BY posted DESC");
					$i=0;
					while ($article = mysql_fetch_array($result)) {
					$i++;
					$html .= article_item($article['title'], $site_url.'/blog/'.$article['postid'].'/'.seoUrl2($article['title']).'/',$article['posted'],$article['views']);
					}
					if ($i ==0) $html = "<h3>".qa_lang('qa_blog_lang/oops')." $handle ".qa_lang('qa_blog_lang/no_post')."</h3>";
					$this->content['custom'] = $html;
				}
				
			}

		}
		/* 
			ADAPT PROFILE PAGES AND SUBPAGES
		*/
		else if($this->template == 'account' || $this->template == 'favorites' || $this->template == 'updates' || $this->request == 'gallery' || $this->request == 'articles') {
			// ADAPT FORM FOR DETAILS SUBPAGE

			// RENEW THE SUB-NAVIGATION
			unset($this->content['navigation']['sub']);
			$this->content['navigation']['sub']['account'] = array(	'label' => 'My Details',
					'url' => './account',
						'selected' => $this->template == 'account' ? 1 : 0);
			$this->content['navigation']['sub']['favorites'] = array('label' => 'My Favorites',
					'url' => './favorites',
							'selected' => $this->template == 'favorites' ? 1 : 0);			
			$this->content['navigation']['sub']['updates'] = array('label' => 'My Updates',
					'url' => './updates',
							'selected' => $this->template == 'updates' ? 1 : 0);
			$this->content['navigation']['sub']['articles'] = array('label' => 'My Articles',
					'url' => './articles',
							'selected' => $this->request == 'articles' ? 1 : 0);									
	
																	
		}
		
		/*
			ADAPT MEMBERS PAGE
		*/
		else if($this->template == 'users') { 
			require_once QA_INCLUDE_DIR.'qa-db-users.php';
			require_once QA_INCLUDE_DIR.'qa-db-selects.php';
			require_once QA_INCLUDE_DIR.'qa-app-format.php';	
			$start=qa_get_start();	
			$users=qa_db_select_with_pending(qa_db_top_users_selectspec($start, qa_opt_if_loaded('page_size_users')));
			$usercount=qa_opt('cache_userpointscount');
			$pagesize=qa_opt('page_size_users');
			$users=array_slice($users, 0, $pagesize);
			$usershtml=qa_userids_handles_html($users);
			// CHANGE TITLE
			$this->content['title']='Users';
			$this->content['ranking']=array(
			'items' => array(),
			'rows' => ceil($pagesize/qa_opt('columns_users')),
			'type' => 'users'
			);
			if (count($users)) {
				foreach ($users as $userid => $user) {
					$this->content['ranking']['items'][]=array(
						'label' =>
							(QA_FINAL_EXTERNAL_USERS
								? qa_get_external_avatar_html($user['userid'], qa_opt('avatar_users_size'), true)
								: qa_get_user_avatar_html($user['flags'], $user['email'], $user['handle'],
								$user['avatarblobid'], $user['avatarwidth'], $user['avatarheight'], qa_opt('avatar_users_size'), true)
							).' '.$usershtml[$user['userid']],
						'score' => qa_html(number_format($user['points'])),
					);
				}
	
			} 
			else
				$this->content['title']=qa_lang_html('main/no_active_users');
			$this->content['page_links']=qa_html_page_links(qa_request(), $start, $pagesize, $usercount, qa_opt('pages_prev_next'));
			// EMPTY SUB-NAVIGATION
			$this->content['navigation']['sub']=null;
		}
		if ($this->template == 'questions') {
			unset($this->content['navigation']['sub']);
			$this->content['navigation']['sub']['account'] = array('label' => 'My Details',
																	'url' => './account',
																	'selected' =>  0);
			//print_r ($this->content['navigation']);
		}
		
		if ($this->request == 'login') {
	            $this->content['form']['fields']['password']['note'] = '<a href="/forgot">I forgot my password</a> - <a href="/register">Register</a>';
            }

		qa_html_theme_base::doctype();
		
		
	
	}


}

function seoUrl2($string) {
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

function article_item($title,$link,$date,$views) {
	$vl = $views.' views';
	if ($views == 1) $vl = $views.' view';
	$date =new DateTime($date);
	$on = $date->format('Y.m.d');
	return '<div class="qa-q-list-item" id="q3"> 
			<div class="qa-q-item-stats"></span></span> </div> 
			<div class="qa-q-item-main"> <div class="qa-q-item-title"> <a href="'.$link.'">'.$title.'</a> </div> 
			<span class="qa-q-item-avatar-meta"> <span class="qa-q-item-meta">Posted on '.$on.' ('.$vl.')</span> 
			</span> </div> <div class="qa-q-item-clear"> </div> </div>';

}
