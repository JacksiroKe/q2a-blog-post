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
require_once QA_INCLUDE_DIR.'app/blobs.php';
require_once QA_PLUGIN_DIR.'blog-post/qa-blog.php';
require_once QA_PLUGIN_DIR.'blog-post/qa-index.php';

class qa_html_theme_layer extends qa_html_theme_base {

	function nav_list($navigation, $class, $level=null)
	{
		if($this->template == 'user' || $this->template == 'user-wall' || $this->template == 'user-activity' || $this->template == 'user-questions' || $this->template == 'user-answers' || $this->request == 'user-articles') 
			{
					
			if ($class == 'nav-sub')
				$navigation['articles'] = array(
					  'label' => qa_lang('qa_blog_lang/nav_articles'),
					  'url' => qa_path_html('user/'.qa_request_part(1).'/articles'),
				);
			if($this->request == 'user/'.qa_request_part(1).'/articles') {
				$newnav = qa_admin_sub_navigation();
				$navigation = array_merge($newnav, $navigation);
				$navigation['user']['/'.qa_request_part(1).'/articles'] = true;
			
			}
		}
		if(count($navigation) > 1 ) qa_html_theme_base::nav_list($navigation, $class, $level=null);
	}
	
	function doctype() {	
		
			if (strpos($this->request,'user/') !== false && strpos($this->request,'articles') !== false)
    		$this->request = 'user-articles';
			if($this->template == 'user' ||  
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
			$result = qa_db_query_sub("SELECT COUNT(*) as total FROM ^blog_posts WHERE `userid` LIKE #", $userid);
					$countdata = mysql_fetch_assoc($result);
					$count = $countdata['total'];
				$resulted = qa_db_query_sub("SELECT COUNT(*) as total FROM ^blog_comments WHERE `userid` LIKE #", $userid);
					$counted = mysql_fetch_assoc($resulted);
					$counting = $counted['total'];
				$this->content['form_activity']['fields']['articles'] = array('type'=>'static', 
						'label'=> qa_lang('qa_blog_lang/blog_post'),
						'value'=>'<span class="qa-uf-user-q-posts">'.$count.'</span> 
								<span class="qa-form-wide-static">'.qa_lang('qa_blog_lang/articles').'</span>
								<span class="qa-uf-user-q-posts">'.$counting.'</span> 
								<span class="qa-form-wide-static">'.qa_lang('qa_blog_lang/comments').'</span>');
				if (qa_opt('allow_private_messages') && 
					isset($loginuserid) && 
					($loginuserid!=$userid) && 
					!($useraccount['flags'] & 
					QA_USER_FLAGS_NO_MESSAGES)) {
					$this->content['form_profile']['fields']['duration']['value'].=
					strtr(qa_lang_html('profile/send_private_message'), 
					array(
						'^1' => '<a href="'.qa_path_html('message/'.$handle).'">',
						'^2' => '</a>',
					));
					
				}
			} 
			
			if ($this->request == 'user-articles') {
				unset($this->content['title']);   
				$this->content['title']= qa_lang('qa_blog_lang/title_recent').$handle;
				$this->content['navigation']['sub']['profile'] = array(	'label' => qa_lang('qa_blog_lang/nav_user').$handle,
								'url' => qa_path_to_root().'user/'.$handle);
				$this->content['navigation']['sub']['account'] = array('label' => qa_lang('misc/nav_my_details'),
								'url' => qa_path_to_root().'account');			
				$this->content['navigation']['sub']['favorites'] = array('label' => qa_lang('misc/nav_my_favorites'),
								'url' => qa_path_to_root().'favorites');
				$this->content['navigation']['sub']['wall'] = array('label' => qa_lang('misc/nav_user_wall'),
								'url' => qa_path_to_root().'user/'.$handle.'/wall');			
				$this->content['navigation']['sub']['activity'] = array('label' => qa_lang('misc/nav_user_activity'),
								'url' => qa_path_to_root().'user/'.$handle.'/activity');
				$this->content['navigation']['sub']['questions'] = array('label' => qa_lang('misc/nav_user_qs'),
								'url' => qa_path_to_root().'user/'.$handle.'/questions');
				$this->content['navigation']['sub']['answers'] = array('label' => qa_lang('misc/nav_user_as'),
								'url' => qa_path_to_root().'user/'.$handle.'/answers');
				unset($this->content['suggest_next']);
				unset($this->content['error']);
				if ($this->request == 'user-articles') {
					$qa_content['custom']= ""; 
					$qa_content['navigation']['sub']= '';
					$html = "";	
					if(qa_get_logged_in_userid()== $userid)  {
					$result = qa_db_query_sub("SELECT * FROM ^blog_posts WHERE userid = '$userid' ORDER BY posted DESC");
					}
					else $result = qa_db_query_sub("SELECT * FROM ^blog_posts WHERE userid = '$userid' and format='markdown' ORDER BY posted DESC");
					$i=0;
					while ($article = mysql_fetch_array($result)) {
					$i++;
					$html .= article_item($article['title'],$article['postid'],qa_path_to_root().'blog/'.$article['postid'].'/'.seoUrl($article['title']),
							$article['posted'],$article['type'],$article['views'], $article['content'],$article['format']);
					}
					if ($i ==0) $html = "<h3>".qa_lang('qa_blog_lang/oops')." $handle ".qa_lang('qa_blog_lang/no_post')."</h3>";
					$this->content['custom'] = $html;
				}
				
			}

		}
		else if($this->template == 'account' ||
				$this->template == 'favorites' || 
				$this->template == 'updates' ||
				$this->request == 'articles') {
				unset($this->content['navigation']['sub']);
				$this->content['navigation']['sub']['account'] = array(	'label' => qa_lang('misc/nav_all_my_updates'),
						'url' => './account',
							'selected' => $this->template == 'account' ? 1 : 0);
				$this->content['navigation']['sub']['favorites'] = array('label' => qa_lang('misc/nav_my_favorites'),
						'url' => './favorites',
								'selected' => $this->template == 'favorites' ? 1 : 0);			
				$this->content['navigation']['sub']['updates'] = array('label' => qa_lang('misc/nav_my_content'),
						'url' => './updates',
								'selected' => $this->template == 'updates' ? 1 : 0);
				$this->content['navigation']['sub']['articles'] = array('label' => qa_lang('qa_blog_lang/my_articles'),
						'url' => './articles',
								'selected' => $this->request == 'articles' ? 1 : 0);
		}
		else if($this->template == 'users') { 
			require_once QA_INCLUDE_DIR.'db/users.php';
			require_once QA_INCLUDE_DIR.'db/selects.php';
			require_once QA_INCLUDE_DIR.'app/format.php';	
			$start=qa_get_start();	
			$users=qa_db_select_with_pending(qa_db_top_users_selectspec($start, qa_opt_if_loaded('page_size_users')));
			$usercount=qa_opt('cache_userpointscount');
			$pagesize=qa_opt('page_size_users');
			$users=array_slice($users, 0, $pagesize);
			$usershtml=qa_userids_handles_html($users);
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
								? qa_get_external_avatar_html($user['userid'], 
								qa_opt('avatar_users_size'), true)
								: qa_get_user_avatar_html($user['flags'], 
								$user['email'], 
								$user['handle'],
								$user['avatarblobid'], 
								$user['avatarwidth'], 
								$user['avatarheight'], 
								qa_opt('avatar_users_size'), 
								true)
							).' '.$usershtml[$user['userid']],
						'score' => qa_html(number_format($user['points'])),
					);
				}
	
			} 
			else
			$this->content['title']=qa_lang_html('main/no_active_users');
			$this->content['page_links']=qa_html_page_links(qa_request(), 
										$start, $pagesize, $usercount, qa_opt('pages_prev_next'));
			$this->content['navigation']['sub']=null;
		}
		if ($this->template == 'questions') {
			unset($this->content['navigation']['sub']);
			$this->content['navigation']['sub']['account'] = array('label' => 'My Details',	'url' => './account', 'selected' =>  0);
		}
		
		if ($this->request == 'login') {
	            $this->content['form']['fields']['password']['note'] = '<a href="/forgot">I forgot my password</a> - <a href="/register">Register</a>';
            }
		qa_html_theme_base::doctype();
	}
}


