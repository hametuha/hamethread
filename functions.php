<?php
/**
 * Global functions
 *
 * @package hamethread
 */

use Hametuha\Thread\Hooks\BestAnswer;

/**
 * Get file path.
 *
 * @param string $name
 * @param string $slug
 *
 * @return string
 */
function hamethread_file_path( $name, $slug = '' ) {
	$existing_path = '';
	$files = [ $name . '.php' ];
	if ( $slug ) {
		$files[] = "{$name}-{$slug}.php";
	}
	foreach ( $files as $file ) {
		foreach ( [
			__DIR__ . '/template-parts',
			get_template_directory() . '/template-parts/hamethread',
			get_stylesheet_directory() . '/template-parts/hamethread',
		] as $dir ) {
			$path = $dir . '/' . $file;
			if ( file_exists( $path ) ) {
				$existing_path = $path;
			}
		}
	}
	$existing_path = apply_filters( 'hamethread_template', $existing_path, $name, $slug );
	return $existing_path;
}

/**
 * Load template.
 *
 * @param string $name
 * @param string $slug
 * @param bool   $echo
 * @param array  $args
 * @param array  $default
 * @return string
 */
function hamethread_template( $name, $slug = '', $echo = true, $args = [], $default = [] ) {
	global $posts, $post, $wp_did_header, $wp_query, $wp_rewrite, $wpdb, $wp_version, $wp, $id, $comment, $user_ID;
	$existing_path = hamethread_file_path( $name, $slug );
	if ( ! file_exists( $existing_path ) ) {
		return '';
	}
	// Extract args.
	$args = array_merge( $default, $args );
	if ( $args ) {
		extract( $args );
	}
	if ( ! $echo  ) {
		ob_start();
		include $existing_path;
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	} else {
		include $existing_path;
	}
}

/**
 * Get post type
 *
 * @return string
 */
function hamethread_post_type() {
	return \Hametuha\Thread\Hooks\PostType::get_instance()->post_type;
}

/**
 * Is comment recently editted.
 *
 * @param int $offset Initial value 7
 * @param object $post
 * @return boolean
 */
function hamethread_recently_commented( $offset = 7, $post = null ) {
	$latest_date = hamethread_get_latest_comment_date( $post );
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

/**
 * Is topic forced?
 *
 * @param int $user_id
 * @return bool
 */
function hamethread_topic_forced( $user_id ) {
	$force = true;
	return apply_filters( 'hamethread_force_topic', $user_id );
}

/**
 * Get topic list.
 *
 * @return array
 */
function hamethread_topics() {
	$topics = get_terms( [
		'hide_empty' => false,
		'taxonomy'   => \Hametuha\Thread\Hooks\PostType::get_instance()->taxonomy,
	] );
	return is_wp_error( $topics ) ? [] : (array) $topics;
}

/**
 * Load button
 *
 * @param int    $parent Post parent ID.
 * @paran string $label  Button label.
 */
function hamethread_button( $parent = 0, $label = '' ) {
	wp_enqueue_script( 'hamethread-thread' );
	wp_enqueue_style( 'hamethread' );
	$label = $label ?: __( 'Start new thread', 'hamethread' );
	hamethread_template( 'button-thread', '', true, [
		'parent'  => $parent,
		'label'   => $label,
		'private' => \Hametuha\Thread\Model\ThreadModel::can_start_private( get_current_user_id(), $parent ),
	] );
}

/**
 * Load button
 */
function hamethread_reply_button() {
	wp_enqueue_script( 'hamethread' );
	wp_enqueue_style( 'hamethread' );
	hamethread_template( 'button-comment' );
}

/**
 * Detect if user can start.
 *
 * @param int|null $user_id If null, means current user.
 * @return bool
 */
function hamethread_user_can_start( $user_id = null ) {
	return \Hametuha\Thread\Model\ThreadModel::can_post( $user_id );
}

/**
 * Detect if user can comment.
 *
 * @param null|int|WP_Post $post
 * @param null|null        $user_id
 *
 * @return bool
 */
function hamethread_user_can_comment( $post = null, $user_id = null ) {
	return \Hametuha\Thread\Model\CommentModel::can_comment( $post, $user_id );
}

/**
 * Detect if thread is resolved.
 *
 * @param null|int|WP_Post $post
 *
 * @return bool
 */
function hamethread_is_resolved( $post = null ) {
	$post = get_post( $post );
	return (bool) get_post_meta( $post->ID, '_thread_resolved', true );
}

/**
 * Get home URL.
 *
 * @param string $context
 * @return string
 */
function hamethread_home( $context = '' ) {
	return apply_filters( 'hamethread_home_url', get_post_type_archive_link( \Hametuha\Thread\Hooks\PostType::get_instance()->post_type ), $context );
}

/**
 * Get log
 *
 * @param null|int|WP_Post $post
 *
 * @return array
 */
function hamethread_edit_logs( $post = null ) {
	$post = get_post( $post );
	$logs = get_post_meta( $post->ID, '_hamethread_thread_log' );
	rsort( $logs );
	return $logs;
}

/**
 * Detect if user can start.
 *
 * @param int|null|WP_Post $post
 * @return bool
 */
function hamethread_current_user_can_comment( $post = null ) {
	$can = is_user_logged_in();
	return apply_filters( 'hamethread_user_can_comment', $can, $post  );
}

/**
 * Get comment reactions.
 *
 * @param WP_Comment $comment
 * @return array
 */
function hamethread_comment_actions( $comment ) {
	return apply_filters( 'hamethread_comment_actions', [
		'reply'  => sprintf(
			'<button class="hamethread-reply" data-path="comment/%d/new" data-reply-to="%d"><i class="fa fa-reply"></i> %s</button>',
			$comment->comment_post_ID,
			$comment->comment_ID,
			esc_html__( 'Reply', 'hamethread' )
		),
		'upvote' => sprintf(
			'<button class="hamethread-upvote%s" data-path="vote/%d"><i class="fa fa-thumbs-up"></i> %s</button>',
			\Hametuha\Thread\Rest\RestVote::get_instance()->is_voted( $comment->comment_ID, get_current_user_id() ) ? ' active' : '' ,
			$comment->comment_ID,
			esc_html__( 'Upvote', 'hamethread' )
		),
	], $comment );
}

/**
 * Check if comment is updated.
 *
 * @param WP_Comment $comment
 * @return bool
 */
function hamethread_is_edited( $comment ) {
	$comment = get_comment( $comment );
	$metas = get_comment_meta( $comment->comment_ID, '_comment_diff' );
	usort( $metas, function( $a, $b ) {
		if ( $a['updated'] === $b['updated'] ) {
			return 0;
		} else {
			return $a['updated'] < $b['updated'];
		}
	} );
	return ! empty( $metas );
}

/**
 * Get user roles.
 *
 * @param WP_Comment $comment
 *
 * @return string
 */
function hamethread_commentor_label( $comment ) {
	static $loaded = false;
	$user = get_userdata( $comment->user_id );
	$label = '';
	if ( $user ) {
		global $wp_roles;
		$role = current( $user->roles );
		if ( isset( $wp_roles->role_names[ $role ] ) ) {
			if ( ! $loaded ) {
				load_textdomain( 'default', WP_LANG_DIR . sprintf( '/admin-%s.mo', get_user_locale() ) );
				$loaded = true;
			}
			$label = translate_user_role( $wp_roles->role_names[ $role ] );
		}
	}
	if ( ! $label ) {
		$label = __( 'Guest', 'hamethread' );
	}
	$label = sprintf( '<span class="hamethread-comment-label">%s</span>', trim( wp_kses_post( $label ) ) );
	return apply_filters( 'hamethread_commentor_label', $label, $comment, $user );
}

/**
 * Get comment label class
 *
 * @param WP_Comment $comment
 * @param array|string $classes
 * @return string
 */
function hamethread_commentor_label_class( $comment, $classes ) {
	$classes = apply_filters( 'hamethread_commentor_label_class', (array) $classes, $comment );
	return esc_attr( implode( ' ', $classes ) );
}

/**
 * Get last comment updated.
 *
 * @param string           $no_comment Default '---'
 * @param null|int|WP_Post $post
 * @param string           $format Default WordPress setting.
 *
 * @return bool|int|string
 */
function hamethread_last_commented( $no_comment = '---', $post = null, $format = '' ) {
	$post = get_post( $post );
	if ( ! $format ) {
		$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
	}
	$comments = get_comments( [
		'post_id' => $post->ID,
		'number'  => 1,
	] );
	if ( ! $comments ) {
		return $no_comment;
	}
	foreach ( $comments as $comment ) {
		/** @var WP_Comment $comment */
		return mysql2date( $format, $comment->comment_date );
	}
}

/**
 * Display comment links
 *
 * @return string
 */
function hamethread_comment_links() {
	$link = paginate_comments_links( [
		'echo' => false,
		'type' => 'array',
	] );
	if ( $link ) {
		$link = array_map( function( $l ) {
			$classes = [ 'page-item' ];
			if ( false !== strpos( $l, 'current' ) ) {
				$classes[] = 'active';
			} elseif ( false !== strpos( $l, 'dot' ) ) {
				$classes[] = 'disabled';
			}

			return sprintf( '<li class="%s">%s</li>', implode( ' ', $classes ), $l );
		}, $link );
		array_unshift( $link, '<ul class="pagination">' );
		array_unshift( $link, sprintf( '<nav aria-label="%s" class="text-center">', esc_html__( 'Comments Pagination', 'hamethread' ) ) );
		array_push( $link, '</ul>' );
		array_push( $link, '</nav>' );
		$link = implode( "\n", $link );
	}
	return apply_filters( 'hamethread_comment_links', $link );
}

/**
 * Get upvoted count.
 *
 * @param null|int|WP_Post $post
 * @return int
 */
function hamethread_upvote_count( $post = null ) {
	global $wpdb;
	$post = get_post( $post );
	if ( ! $post ) {
		return 0;
	}
	$query = <<<SQL
		SELECT COUNT( cm.meta_id )
		FROM {$wpdb->commentmeta} AS cm
		LEFT JOIN {$wpdb->comments} AS c
		ON cm.comment_id = c.comment_ID
		WHERE cm.meta_key = '_user_upvote'
		  AND c.comment_post_ID = %d
SQL;
	return (int) $wpdb->get_var( $wpdb->prepare( $query, $post->ID ) );
}

/**
 * Get upvoted count.
 *
 * @param null|int|WP_Comment $comment
 * @return int
 */
function hamethread_comment_upvoted_count( $comment = null ) {
	$comment = get_comment( $comment );
	global $wpdb;
	$query = <<<SQL
		SELECT COUNT( meta_id ) FROM {$wpdb->commentmeta}
		WHERE comment_id = %d
		  AND meta_key   = '_user_upvote'
SQL;

	return (int) $wpdb->get_var( $wpdb->prepare( $query, $comment->comment_ID ) );
}

/**
 * Get login link
 *
 * @param string $redirect_to
 * @return string
 */
function hamethread_login_url( $redirect_to = '' ) {
	if ( function_exists( 'WC' ) ) {
		// WooCommerce
		$url = wc_get_page_permalink( 'myaccount' );
		if ( $redirect_to ) {
			$url = add_query_arg( [
				'redirect_to' => $redirect_to,
			], $url );
		}
	} else {
		$url = wp_login_url( $redirect_to );
	}
	return apply_filters( 'hamethread_login_url', $url, $redirect_to );
}
