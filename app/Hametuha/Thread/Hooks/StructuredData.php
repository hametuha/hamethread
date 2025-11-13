<?php

namespace Hametuha\Thread\Hooks;


use Hametuha\Pattern\Singleton;

/**
 * Structured Data controller.
 *
 * @package hamethread
 * @property PostType $post_type
 */
class StructuredData extends Singleton {

	/**
	 * Executed inside constructor.
	 */
	protected function init() {
		if ( apply_filters( 'hamethread_should_output_json_ld', true ) ) {
			add_action( 'wp_head', [ $this, 'output_json_ld' ], 20 );
		}
	}

	/**
	 * Render JSON LD
	 */
	public function output_json_ld() {
		if ( ! is_singular( $this->post_type->post_type ) ) {
			return;
		}
		$json = $this->get_json( get_queried_object() );
		if ( ! $json ) {
			return;
		}
		$json = json_encode( $json );
		if ( ! $json ) {
			return;
		}
		echo <<<HTML
<script type="application/ld+json">
{$json}
</script>
HTML;
	}

	/**
	 * Get data to display.
	 *
	 * @param \WP_Post $post
	 * @return array
	 */
	public function get_json( $post ) {
		if ( ! $this->post_type->is_supported( $post->post_type ) ) {
			return [];
		}
		$json           = [
			'@context'   => 'https://schema.org',
			'@type'      => 'QAPage',
			'mainEntity' => [
				'@type'       => 'Question',
				'name'        => get_the_title( $post ),
				'text'        => strip_tags( $post->post_content ),
				'answerCount' => get_comments_number( $post->ID ),
				'upvoteCount' => hamethread_upvote_count( $post ),
				'dateCreated' => mysql2date( \DateTime::ISO8601, $post->post_date_gmt ),
				'author'      => [
					'@type' => 'Person',
					'name'  => get_the_author_meta( 'display_name', $post->post_author ),
				],
			],
		];
		$best_answer    = hamethread_get_best_answer( $post );
		$best_answer_id = 0;
		if ( $best_answer ) {
			$json['mainEntity']['acceptedAnswer'] = $this->get_answer_json( $best_answer );
			$best_answer_id                       = $best_answer->comment_ID;
		}
		$suggested = [];
		$comments  = get_comments( [
			'post_id' => $post->ID,
			'status'  => 'approve',
		] );
		foreach ( $comments as $comment ) {
			if ( $best_answer_id === $comment->comment_ID ) {
				continue;
			}
			$suggested[] = $this->get_answer_json( $comment );
		}
		if ( $suggested ) {
			$json['mainEntity']['suggestedAnswer'] = $suggested;
		}
		return apply_filters( 'hamethread_json_ld', $json, $post );
	}

	/**
	 * Get comments.
	 *
	 * @param \WP_Comment $comment
	 * @return array
	 */
	private function get_answer_json( $comment ) {
		return [
			'@type'       => 'Answer',
			'text'        => $comment->comment_content,
			'dateCreated' => mysql2date( \DateTime::ISO8601, $comment->comment_date_gmt ),
			'upvoteCount' => hamethread_comment_upvoted_count( $comment ),
			'url'         => get_permalink( $comment->comment_post_ID ) . '#comment-' . $comment->comment_ID,
			'author'      => [
				'@type' => 'Person',
				'name'  => $comment->comment_author,
			],
		];
	}


	/**
	 * Getter
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'post_type':
				return PostType::get_instance();
			default:
				return null;
		}
	}
}
