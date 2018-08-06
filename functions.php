<?php
/**
 * Global functions
 *
 * @package hamethread
 */

/**
 * Get post type
 *
 * @return string
 */
function hamethread_post_type() {
	return \Hametuha\Thread\Hooks\PostType::get()->post_type;
}

/**
 * Is comment recently editted.
 *
 * @param int $offset Initial value 7
 * @param object $post
 * @return boolean
 */
function hamethread_recently_commented( $offset = 7, $post = null ) {
	$latest_date = get_latest_comment_date( $post );
	if ( !$latest_date ) {
		return false;
	}
	return (boolean) ( ( time() - strtotime( $latest_date ) ) < 60 * 60 * 24 * $offset );
}

/**
 * Get thread count.
 *
 * @global wpdb $wpdb
 * @param int $user_id
 * @return int
 */
function hamethread_get_author_thread_count( $user_id ) {
	global $wpdb;
	$sql = <<<EOS
		SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_author = %d AND post_type = %s AND post_status = 'publish'
EOS;
	return (int) $wpdb->get_var( $wpdb->prepare( $sql, $user_id, hamethread_post_type() ) );
}

/**
 * Get latest comment date.
 *
 * @global wpdb $wpdb
 * @param object $post
 * @return string
 */
function hamethread_get_latest_comment_date( $post = null ) {
	global $wpdb;
	$post = get_post( $post );
	$sql = <<<EOS
		SELECT comment_date FROM {$wpdb->comments}
		WHERE comment_post_ID = %d
		LIMIT 1
EOS;
	return $wpdb->get_var( $wpdb->prepare( $sql, $post->ID ) );
}

/**
 * Get user responded thread count.
 *
 * @global wpdb $wpdb
 * @param int $user_id
 * @return int
 */
function hamethread_get_author_response_count( $user_id ) {
	global $wpdb;
	$sql = <<<EOS
		SELECT COUNT(comment_ID) FROM {$wpdb->comments} AS c
		INNER JOIN {$wpdb->posts} AS p
		ON c.comment_post_ID = p.ID
		WHERE p.post_type = 'thread' AND c.user_id = %d
EOS;
	return (int) $wpdb->get_var( $wpdb->prepare( $sql, $user_id ) );
}
