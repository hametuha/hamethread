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
	 * Dispatches to the appropriate method based on the structured data type setting.
	 *
	 * @param \WP_Post $post Post object.
	 * @return array
	 */
	public function get_json( $post ) {
		if ( ! $this->post_type->is_supported( $post->post_type ) ) {
			return [];
		}
		$type = get_option( AdminSetting::OPTION_STRUCTURED_DATA_TYPE, 'qa' );
		if ( 'discussion' === $type ) {
			return $this->get_discussion_json( $post );
		}
		return $this->get_qa_json( $post );
	}

	/**
	 * Get QAPage structured data.
	 *
	 * @param \WP_Post $post Post object.
	 * @return array
	 */
	private function get_qa_json( $post ) {
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
				'author'      => $this->get_person_json( get_the_author_meta( 'display_name', $post->post_author ), $post->post_author ),
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
	 * Get DiscussionForumPosting structured data.
	 *
	 * @param \WP_Post $post Post object.
	 * @return array
	 */
	private function get_discussion_json( $post ) {
		$json     = [
			'@context'             => 'https://schema.org',
			'@type'                => 'DiscussionForumPosting',
			'headline'             => get_the_title( $post ),
			'text'                 => strip_tags( $post->post_content ),
			'url'                  => get_permalink( $post ),
			'datePublished'        => mysql2date( \DateTime::ISO8601, $post->post_date_gmt ),
			'author'               => $this->get_person_json( get_the_author_meta( 'display_name', $post->post_author ), $post->post_author ),
			'interactionStatistic' => [
				'@type'                => 'InteractionCounter',
				'interactionType'      => 'https://schema.org/LikeAction',
				'userInteractionCount' => hamethread_upvote_count( $post ),
			],
		];
		$comments = get_comments( [
			'post_id' => $post->ID,
			'status'  => 'approve',
		] );
		if ( $comments ) {
			$json['comment'] = array_map( function ( $comment ) {
				return [
					'@type'         => 'Comment',
					'text'          => $comment->comment_content,
					'datePublished' => mysql2date( \DateTime::ISO8601, $comment->comment_date_gmt ),
					'author'        => $this->get_person_json( $comment->comment_author, $comment->user_id ),
					'url'           => get_permalink( $comment->comment_post_ID ) . '#comment-' . $comment->comment_ID,
				];
			}, $comments );
		}
		return apply_filters( 'hamethread_json_ld', $json, $post );
	}

	/**
	 * Get Person structured data.
	 *
	 * @param string $name    Display name.
	 * @param int    $user_id User ID.
	 * @return array
	 */
	private function get_person_json( $name, $user_id = 0 ) {
		$person = [
			'@type' => 'Person',
			'name'  => $name,
		];
		if ( $user_id ) {
			$person['url'] = get_author_posts_url( $user_id );
		}
		return $person;
	}

	/**
	 * Get answer data for QAPage.
	 *
	 * @param \WP_Comment $comment Comment object.
	 * @return array
	 */
	private function get_answer_json( $comment ) {
		return [
			'@type'       => 'Answer',
			'text'        => $comment->comment_content,
			'dateCreated' => mysql2date( \DateTime::ISO8601, $comment->comment_date_gmt ),
			'upvoteCount' => hamethread_comment_upvoted_count( $comment ),
			'url'         => get_permalink( $comment->comment_post_ID ) . '#comment-' . $comment->comment_ID,
			'author'      => $this->get_person_json( $comment->comment_author, $comment->user_id ),
		];
	}


	/**
	 * Getter
	 *
	 * @param string $name Property name.
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
