<?php
/*
	Blog Post by Jackson Siro
	https://github.com/JacksiroKe/Q2A-Blog-Post-Plugin

	Description: Basic and Database functions for the blog post plugin

*/

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../../');
	exit;
}

function qa_bp_user_find_by_userid($userid)
{
	return qa_db_read_all_values(qa_db_query_sub(
		'SELECT userid FROM ^blog_users WHERE userid=$',
		$userid
	));
}

function qa_bp_points_calculations()
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	require_once QA_INCLUDE_DIR . 'app/options.php';

	$options = qa_get_options(qa_db_points_option_names());

	return array(
		'qposts' => array(
			'multiple' => $options['points_multiple'] * $options['points_post_q'],
			'formula' => "COUNT(*) AS qposts FROM ^posts AS userid_src WHERE userid~ AND type='Q'",
		),

		'aposts' => array(
			'multiple' => $options['points_multiple'] * $options['points_post_a'],
			'formula' => "COUNT(*) AS aposts FROM ^posts AS userid_src WHERE userid~ AND type='A'",
		),

		'cposts' => array(
			'multiple' => 0,
			'formula' => "COUNT(*) AS cposts FROM ^posts AS userid_src WHERE userid~ AND type='C'",
		),

		'aselects' => array(
			'multiple' => $options['points_multiple'] * $options['points_select_a'],
			'formula' => "COUNT(*) AS aselects FROM ^posts AS userid_src WHERE userid~ AND type='Q' AND selchildid IS NOT NULL",
		),

		'aselecteds' => array(
			'multiple' => $options['points_multiple'] * $options['points_a_selected'],
			'formula' => "COUNT(*) AS aselecteds FROM ^posts AS userid_src JOIN ^posts AS questions ON questions.selchildid=userid_src.postid WHERE userid_src.userid~ AND userid_src.type='A' AND NOT (questions.userid<=>userid_src.userid)",
		),

		'qupvotes' => array(
			'multiple' => $options['points_multiple'] * $options['points_vote_up_q'],
			'formula' => "COUNT(*) AS qupvotes FROM ^uservotes AS userid_src JOIN ^posts ON userid_src.postid=^posts.postid WHERE userid_src.userid~ AND LEFT(^posts.type, 1)='Q' AND userid_src.vote>0",
		),

		'qdownvotes' => array(
			'multiple' => $options['points_multiple'] * $options['points_vote_down_q'],
			'formula' => "COUNT(*) AS qdownvotes FROM ^uservotes AS userid_src JOIN ^posts ON userid_src.postid=^posts.postid WHERE userid_src.userid~ AND LEFT(^posts.type, 1)='Q' AND userid_src.vote<0",
		),

		'aupvotes' => array(
			'multiple' => $options['points_multiple'] * $options['points_vote_up_a'],
			'formula' => "COUNT(*) AS aupvotes FROM ^uservotes AS userid_src JOIN ^posts ON userid_src.postid=^posts.postid WHERE userid_src.userid~ AND LEFT(^posts.type, 1)='A' AND userid_src.vote>0",
		),

		'adownvotes' => array(
			'multiple' => $options['points_multiple'] * $options['points_vote_down_a'],
			'formula' => "COUNT(*) AS adownvotes FROM ^uservotes AS userid_src JOIN ^posts ON userid_src.postid=^posts.postid WHERE userid_src.userid~ AND LEFT(^posts.type, 1)='A' AND userid_src.vote<0",
		),

		'cupvotes' => array(
			'multiple' => 0,
			'formula' => "COUNT(*) AS cupvotes FROM ^uservotes AS userid_src JOIN ^posts ON userid_src.postid=^posts.postid WHERE userid_src.userid~ AND LEFT(^posts.type, 1)='C' AND userid_src.vote>0",
		),

		'cdownvotes' => array(
			'multiple' => 0,
			'formula' => "COUNT(*) AS cdownvotes FROM ^uservotes AS userid_src JOIN ^posts ON userid_src.postid=^posts.postid WHERE userid_src.userid~ AND LEFT(^posts.type, 1)='C' AND userid_src.vote<0",
		),

		'qvoteds' => array(
			'multiple' => $options['points_multiple'],
			'formula' => "COALESCE(SUM(" .
				"LEAST(" . ((int)$options['points_per_q_voted_up']) . "*upvotes," . ((int)$options['points_q_voted_max_gain']) . ")" .
				"-" .
				"LEAST(" . ((int)$options['points_per_q_voted_down']) . "*downvotes," . ((int)$options['points_q_voted_max_loss']) . ")" .
				"), 0) AS qvoteds FROM ^posts AS userid_src WHERE LEFT(type, 1)='Q' AND userid~",
		),

		'avoteds' => array(
			'multiple' => $options['points_multiple'],
			'formula' => "COALESCE(SUM(" .
				"LEAST(" . ((int)$options['points_per_a_voted_up']) . "*upvotes," . ((int)$options['points_a_voted_max_gain']) . ")" .
				"-" .
				"LEAST(" . ((int)$options['points_per_a_voted_down']) . "*downvotes," . ((int)$options['points_a_voted_max_loss']) . ")" .
				"), 0) AS avoteds FROM ^posts AS userid_src WHERE LEFT(type, 1)='A' AND userid~",
		),

		'cvoteds' => array(
			'multiple' => $options['points_multiple'],
			'formula' => "COALESCE(SUM(" .
				"LEAST(" . ((int)$options['points_per_c_voted_up']) . "*upvotes," . ((int)$options['points_c_voted_max_gain']) . ")" .
				"-" .
				"LEAST(" . ((int)$options['points_per_c_voted_down']) . "*downvotes," . ((int)$options['points_c_voted_max_loss']) . ")" .
				"), 0) AS cvoteds FROM ^posts AS userid_src WHERE LEFT(type, 1)='C' AND userid~",
		),

		'upvoteds' => array(
			'multiple' => 0,
			'formula' => "COALESCE(SUM(upvotes), 0) AS upvoteds FROM ^posts AS userid_src WHERE userid~",
		),

		'downvoteds' => array(
			'multiple' => 0,
			'formula' => "COALESCE(SUM(downvotes), 0) AS downvoteds FROM ^posts AS userid_src WHERE userid~",
		),
	);
}
		
/*
	Omit PHP closing tag to help avoid accidental output
*/
