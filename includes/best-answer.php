<?php
/**
 * Best answer related functions.
 *
 * @package hamethread
 */


use Hametuha\Thread\Hooks\AdminSetting;
use Hametuha\Thread\Hooks\BestAnswer;

/**
 * Get best answer
 *
 * @param null|int|WP_Post $post
 * @return null|WP_Comment
 */
function hamethread_get_best_answer( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return null;
	}
	foreach ( get_comments( [
		'post_id' => $post->ID,
		'number'  => 1,
		'meta_query' => [
			[
				'key'     => BestAnswer::BA_KEY,
				'value'   => 0,
				'compare' => '>',
				'type'    => 'numeric'
			]
		],
	] ) as $comment ) {
		return $comment;
	}
	return null;
}



/**
 * Detect if best answer feature is enabled for post type.
 *
 * @param string $post_type
 * @return bool
 */
function hamethread_best_answer_supported( $post_type ) {
	$post_types = (array) get_option( AdminSetting::OPTION_POST_TYPE, [] );
	return in_array( $post_type, $post_types );
}



/**
 * Does the post have the best answer?
 *
 * @param null|int|WP_Post $post
 * @return bool
 */
function hamethread_has_best_answer( $post = null ) {
	return (bool) hamethread_get_best_answer( $post );
}



/**
 * @param $comment
 * @return bool|WP_Error
 */
function hamethread_is_best_answer( $comment ) {
	$comment = get_comment( $comment );
	if ( ! $comment ) {
		return BestAnswer::get_instance()->no_comment();
	}
	return (bool) get_comment_meta( $comment->comment_ID, BestAnswer::BA_KEY, true );
}



/**
 * Get best answer date.
 *
 * @param string              $format
 * @param null|int|WP_Comment $comment
 * @return string
 */
function hamethread_best_answered_date( $format = '', $comment = null ) {
	$comment = get_comment( $comment );
	if ( ! $comment ) {
		return '';
	}
	$time = get_comment_meta( $comment->comment_ID, BestAnswer::BA_KEY, true );
	if ( ! $time ) {
		return '';
	}
	if ( $format ) {
		$format = get_option( 'date_format' );
	}
	return get_date_from_gmt( date_i18n( 'Y-m-d H:i:s', $time ), $format );
}
