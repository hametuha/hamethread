<?php

namespace Hametuha\Thread;

use cli\Table;
use Hametuha\Thread\Hooks\AutoClose;
use Hametuha\Thread\Hooks\BestAnswer;
use Hametuha\Thread\Hooks\PostType;

/**
 * Command utility for hamethread.
 *
 * @package hamethread
 */
class Command extends \WP_CLI_Command {

	/**
	 * Get list
	 *
	 * @synopsis <diff>
	 * @param array $arg
	 */
	public function closable( $arg ) {
		list( $diff ) = $arg;
		$diff         = absint( $diff );
		$time         = current_time( 'timestamp', true ) + ( 60 * 60 * 24 * $diff );
		$posts        = AutoClose::get_instance()->get_post_to_close( $time );
		if ( ! $posts ) {
			\WP_CLI::error( 'No thread to automatically close.' );
		}
		$table = new Table();
		$table->setHeaders( [ '#', 'Title', 'Comments', 'Time to close', 'URL' ] );
		$meta_key = AutoClose::get_instance()->close_key;
		$table->setRows( array_map( function ( $post ) use ( $meta_key ) {
			return [
				$post->ID,
				get_the_title( $post ),
				number_format_i18n( get_comments_number( $post->ID ) ),
				get_date_from_gmt( date_i18n( 'Y-m-d H:i:s', (int) get_post_meta( $post->ID, $meta_key, true ) ), 'Y-m-d H:i:s' ),
				rawurldecode( get_permalink( $post ) ),
			];
		}, $posts ) );
		$table->display();
		\WP_CLI::line( '' );
		\WP_CLI::success( sprintf(
			__( '%1$s will be automatically closed %2$s', 'hamethread' ),
			sprintf( _n( '%d thread', '%d threads', count( $posts ), 'hamethread' ), count( $posts ) ),
			$diff ? sprintf( _n( 'in %d day', 'in %d days', $diff, 'hamethread' ), $diff ) : __( 'just now', 'hamethread' )
		) );
	}

	/**
	 * Close related threads.
	 *
	 * @synopsis [<diff>]
	 * @param $args
	 */
	public function close( $args ) {
		$diff   = isset( $args[0] ) ? $args[0] : 0;
		$closed = AutoClose::get_instance()->do_cron( $diff );
		\WP_CLI::success( sprintf( __( 'Automatically closed: %d', 'hamethread' ), $closed ) );
	}

	/**
	 * Generate dummy data for testing.
	 *
	 * ## OPTIONS
	 *
	 * <author>
	 * : User ID of the thread author.
	 *
	 * [<count>]
	 * : Number of threads to create. Default 10.
	 *
	 * ## EXAMPLES
	 *
	 *     wp thread dummy 2
	 *     wp thread dummy 2 20
	 *
	 * @synopsis <author> [<count>]
	 * @param array $args
	 */
	public function dummy( $args ) {
		$author_id = absint( $args[0] );
		$count     = isset( $args[1] ) ? absint( $args[1] ) : 10;

		// Validate author.
		$author = get_user_by( 'ID', $author_id );
		if ( ! $author ) {
			\WP_CLI::error( sprintf( 'User ID %d not found.', $author_id ) );
		}

		// Get all existing users for commenters.
		$all_users = get_users( [ 'fields' => 'ID' ] );
		if ( empty( $all_users ) ) {
			\WP_CLI::error( 'No users found in database.' );
		}
		\WP_CLI::log( sprintf( 'Thread author: %s (ID: %d)', $author->display_name, $author_id ) );
		\WP_CLI::log( sprintf( 'Available commenters: %d users', count( $all_users ) ) );

		// Sample data for generating content.
		$titles = [
			'投資信託の選び方について教えてください',
			'確定申告の期限はいつまでですか？',
			'住宅ローンの借り換えは得ですか？',
			'NISAとiDeCoどちらがおすすめ？',
			'副業の税金について質問です',
			'相続税の基礎控除について',
			'ふるさと納税の仕組みを教えて',
			'株式投資の始め方',
			'保険の見直しタイミングは？',
			'老後資金はいくら必要？',
			'教育費の貯め方について',
			'クレジットカードの選び方',
			'ポイント投資は意味がある？',
			'外貨預金のリスクについて',
			'不動産投資を始めたい',
		];

		$contents = [
			'初心者なのでわかりやすく教えていただけると助かります。',
			'ネットで調べてもよくわからなかったので質問させてください。',
			'友人に聞いたのですが、本当でしょうか？',
			'専門家の方のご意見をお聞きしたいです。',
			'具体的な数字を交えて教えていただけると嬉しいです。',
		];

		$comment_texts = [
			'ご質問ありがとうございます。詳しく説明しますね。',
			'私の経験からお答えします。',
			'参考になれば幸いです。',
			'補足させていただきます。',
			'別の視点からもお伝えしますね。',
			'実際に試してみた結果をお伝えします。',
			'専門家として回答いたします。',
			'同じ悩みを持っていたので共有します。',
		];

		// Create threads.
		\WP_CLI::log( sprintf( 'Creating %d threads...', $count ) );
		$post_type     = PostType::get_instance()->post_type;
		$created_posts = 0;

		for ( $i = 0; $i < $count; $i++ ) {
			$title   = $titles[ array_rand( $titles ) ] . '（' . ( $i + 1 ) . '）';
			$content = $contents[ array_rand( $contents ) ];
			$status  = ( wp_rand( 1, 10 ) <= 2 ) ? 'private' : 'publish'; // 20% private

			$post_id = wp_insert_post( [
				'post_type'    => $post_type,
				'post_title'   => $title,
				'post_content' => $content,
				'post_status'  => $status,
				'post_author'  => $author_id,
				'post_date'    => gmdate( 'Y-m-d H:i:s', strtotime( sprintf( '-%d days', wp_rand( 0, 30 ) ) ) ),
			] );

			if ( is_wp_error( $post_id ) ) {
				\WP_CLI::warning( sprintf( 'Failed to create thread: %s', $title ) );
				continue;
			}

			$created_posts++;

			// Add random comments (0-5) from random existing users.
			$comment_count = wp_rand( 0, 5 );
			$comment_ids   = [];
			for ( $j = 0; $j < $comment_count; $j++ ) {
				$commenter_id = $all_users[ array_rand( $all_users ) ];
				$commenter    = get_user_by( 'ID', $commenter_id );
				$comment_id   = wp_insert_comment( [
					'comment_post_ID'  => $post_id,
					'comment_author'   => $commenter->display_name,
					'comment_email'    => $commenter->user_email,
					'user_id'          => $commenter_id,
					'comment_content'  => $comment_texts[ array_rand( $comment_texts ) ],
					'comment_approved' => 1,
					'comment_date'     => gmdate( 'Y-m-d H:i:s', strtotime( sprintf( '-%d hours', wp_rand( 0, 720 ) ) ) ),
				] );
				if ( $comment_id ) {
					$comment_ids[] = $comment_id;
				}
			}

			// 30% chance to mark as resolved.
			if ( wp_rand( 1, 10 ) <= 3 ) {
				update_post_meta( $post_id, '_thread_resolved', current_time( 'mysql', true ) );

				// If resolved and has comments, 50% chance to mark a best answer.
				if ( ! empty( $comment_ids ) && wp_rand( 1, 2 ) === 1 ) {
					$ba_comment_id = $comment_ids[ array_rand( $comment_ids ) ];
					update_comment_meta( $ba_comment_id, BestAnswer::BA_KEY, current_time( 'timestamp', true ) );
				}
			}

			if ( $created_posts % 10 === 0 ) {
				\WP_CLI::log( sprintf( '  Created %d threads...', $created_posts ) );
			}
		}

		\WP_CLI::success( sprintf(
			'Created %d threads for user %s (ID: %d)',
			$created_posts,
			$author->display_name,
			$author_id
		) );
	}
}
