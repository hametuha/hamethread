<?php

namespace Hametuha\Thread\Pattern;

/**
 * Check if user is rushing.
 *
 * @package hamethread
 */
class RushDetector {
	/**
	 * Get condition to detect user is rushing.
	 *
	 * @param int $user_id
	 * @return array
	 */
	protected static function get_rushing_condition( $user_id = 0 ) {
		$default = [
			'limit'   => 5,
			'minutes' => 5
		];
		return wp_parse_args( (array) apply_filters( 'hamethread_rushing_condition', $default, $user_id ), $default );
	}
	
	/**
	 * Is user rushing?
	 *
	 * @param int $user_id
	 *
	 * @return string
	 */
	public static function rushing_message( $user_id = 0 ) {
		$condition = self::get_rushing_condition( $user_id );
		return sprintf( __( 'You posted more than %1$d comments in %2$d minutes. Please be patient.', 'hamethread' ), $condition['limit'], $condition['minutes'] );
	}
	
	/**
	 * Check if user is rushing.
	 *
	 * @todo Implement rush guard.
	 * @param int $user_id
	 *
	 * @return bool
	 */
	public static function is_user_rushing( $user_id ) {
		$activities = (array) get_user_meta( $user_id, 'hamethread_activity' );
		$condition = self::get_rushing_condition( $user_id );
		$old = current_time( 'timestamp' ) - $condition['minutes'] * 60;
		$activities = array_filter( $activities, function( $time ) use ( $old ) {
			// Drop old one.
			return $old < $time;
		} );
		$rushing = count( $activities ) >= $condition['limit'];
		return (bool) apply_filters( 'hameturead_is_user_rushing', $rushing, $user_id, $activities );
	}
	
	/**
	 * Save activity time.
	 *
	 * @param int $user_id
	 */
	public static function record_rush_time( $user_id ) {
		// Save time.
		$activities = (array) get_user_meta( $user_id, 'hamethread_activity' );
		$activities[] = current_time( 'timestamp' );
		$condition = self::get_rushing_condition( $user_id );
		$activities = array_slice( $activities, -1 * $condition['limit'], $condition['limit'] );
		update_user_meta( $user_id, 'hamethread_activity', $activities );
	}
}
