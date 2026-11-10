<?php
/**
 * Live Preview のデモデータ。
 *
 * 架空のノートアプリ「Driftnote」のサポートフォーラム。
 * 解決済み（ベストアンサー）・未解決・非公開・受付終了・返信なしのスレッドを一通り入れる。
 *
 * このファイルがマスター。編集したら次で blueprint.json に埋め込むこと:
 *   php <skill>/scripts/blueprint.php embed .
 */
require '/wordpress/wp-load.php';

// runPHP はログインユーザー無しで動く。権限チェックのある API（tax_input など）が黙って無視されるので、管理者として実行する。
wp_set_current_user( 1 );

use Hametuha\Thread\Hooks\BestAnswer;
use Hametuha\Thread\Hooks\SupportNotification;
use Hametuha\Thread\Rest\RestVote;

// Settings → Discussion.
update_option( 'hamethread_thread_description', 'Ask the Driftnote team and other users anything about the app.' );
update_option( 'hamethread_allow_private', 1 );
update_option( 'hamethread_best_answer_supported', array( 'thread' ) );

// The admin plays the support staff.
wp_update_user( array(
	'ID'           => 1,
	'display_name' => 'Driftnote Support',
	'nickname'     => 'Driftnote Support',
) );

// Fictional forum members.
$users = array();
foreach ( array(
	'maya'  => 'Maya Lindqvist',
	'tomas' => 'Tomás Okafor',
	'priya' => 'Priya Raman',
	'leo'   => 'Leo Hartmann',
) as $login => $name ) {
	$users[ $login ] = wp_insert_user( array(
		'user_login'   => $login,
		'user_email'   => $login . '@example.com',
		'user_pass'    => wp_generate_password(),
		'display_name' => $name,
		'role'         => 'subscriber',
	) );
}
$users['support'] = 1;

// Topics.
$topics = array();
foreach ( array( 'Getting Started', 'Sync & Backup', 'Billing', 'Bug Reports' ) as $topic ) {
	$term              = wp_insert_term( $topic, 'topic' );
	$topics[ $topic ] = $term['term_id'];
}

/**
 * GMT timestamp → post/comment date pair.
 */
$dates = function ( $timestamp ) {
	$gmt = gmdate( 'Y-m-d H:i:s', $timestamp );
	return array( get_date_from_gmt( $gmt ), $gmt );
};

/**
 * Insert a reply and return its ID.
 */
$reply = function ( $thread_id, $user, $content, $timestamp, $parent = 0 ) use ( $users, $dates ) {
	$author        = get_userdata( $users[ $user ] );
	list( $d, $g ) = $dates( $timestamp );
	return wp_insert_comment( array(
		'comment_post_ID'      => $thread_id,
		'comment_content'      => $content,
		'comment_type'         => 'comment',
		'comment_approved'     => 1,
		'comment_parent'       => $parent,
		'user_id'              => $author->ID,
		'comment_author'       => $author->display_name,
		'comment_author_email' => $author->user_email,
		'comment_date'         => $d,
		'comment_date_gmt'     => $g,
	) );
};

/**
 * Insert a thread and return its ID.
 */
$thread = function ( $args ) use ( $users, $topics, $dates ) {
	list( $d, $g ) = $dates( $args['time'] );
	$id            = wp_insert_post( array(
		'post_type'      => 'thread',
		'post_status'    => isset( $args['status'] ) ? $args['status'] : 'publish',
		'post_title'     => $args['title'],
		'post_name'      => isset( $args['slug'] ) ? $args['slug'] : '',
		'post_content'   => $args['content'],
		'post_author'    => $users[ $args['author'] ],
		'post_date'      => $d,
		'post_date_gmt'  => $g,
		'comment_status' => isset( $args['comments'] ) ? $args['comments'] : 'open',
	) );
	wp_set_object_terms( $id, array( $topics[ $args['topic'] ] ), 'topic' );
	return $id;
};

$hour = HOUR_IN_SECONDS;
$day  = DAY_IN_SECONDS;
$now  = time();

// 1. Landing page: resolved, best answer, nested replies, upvotes and followers.
$t0   = $now - 3 * $day;
$sync = $thread( array(
	'title'   => 'Notes are not syncing between my phone and laptop',
	'slug'    => 'notes-not-syncing',
	'author'  => 'maya',
	'topic'   => 'Sync & Backup',
	'time'    => $t0,
	'content' => "Since yesterday, notes I write on my phone never show up on my laptop. Both devices are signed in to the same account and the sync icon just keeps spinning.\n\nPhone: Android 15, Driftnote 4.2.1\nLaptop: macOS, Driftnote 4.2.0\n\nIs there anything I can try before reinstalling?",
) );
$r1 = $reply( $sync, 'tomas', 'Same here on Windows. It started after the laptop app asked me to update, but I postponed it.', $t0 + 2 * $hour );
$r2 = $reply( $sync, 'support', "Thanks for the details, Maya. Version 4.2.0 uses an older sync protocol that the server stopped accepting yesterday.\n\nPlease update the laptop app to 4.2.1 (Help → Check for Updates). Pending notes are kept locally and will sync right after the update, so you don't need to reinstall.", $t0 + 5 * $hour );
$reply( $sync, 'tomas', 'Updating fixed it for me too. Everything synced within a minute.', $t0 + 6 * $hour, $r2 );
$reply( $sync, 'maya', 'That was it! All my notes are on the laptop now. Thank you!', $t0 + 8 * $hour, $r2 );
$reply( $sync, 'priya', 'Good to know. Is there a way to get a warning when one device is on an outdated version?', $t0 + 1 * $day );
BestAnswer::get_instance()->mark_as_the_best( $r2 );
foreach ( array( 'maya', 'tomas', 'priya', 'leo' ) as $voter ) {
	RestVote::get_instance()->upvote( $r2, $users[ $voter ] );
}
RestVote::get_instance()->upvote( $r1, $users['priya'] );
foreach ( array( 'maya', 'tomas', 'priya', 'support' ) as $follower ) {
	SupportNotification::get_instance()->subscribe( $sync, $users[ $follower ] );
}

// 2. Resolved how-to.
$t0     = $now - 9 * $day;
$export = $thread( array(
	'title'   => 'How do I export all notes to Markdown?',
	'author'  => 'leo',
	'topic'   => 'Getting Started',
	'time'    => $t0,
	'content' => 'I want to keep a plain-text backup of everything. Is there a bulk export, or do I have to copy notes one by one?',
) );
$a = $reply( $export, 'support', 'Go to Settings → Data → Export and choose "Markdown (.zip)". Each notebook becomes a folder, and images are saved next to the notes.', $t0 + 3 * $hour );
$reply( $export, 'leo', 'Perfect, exactly what I needed.', $t0 + 4 * $hour, $a );
BestAnswer::get_instance()->mark_as_the_best( $a );

// 3. Open feature request with discussion.
$t0   = $now - 6 * $day;
$dark = $thread( array(
	'title'   => 'Feature request: dark mode for the web editor',
	'author'  => 'priya',
	'topic'   => 'Getting Started',
	'time'    => $t0,
	'content' => 'The desktop and mobile apps have a dark theme, but the web editor is always bright. Could it follow the system setting?',
) );
$d1 = $reply( $dark, 'leo', '+1. I write mostly at night and the web editor is painful.', $t0 + 1 * $hour );
$reply( $dark, 'support', "Thanks for the request! It's on our roadmap for the next quarter. We'll post an update in this thread.", $t0 + 1 * $day );
$reply( $dark, 'maya', 'In the meantime a browser extension that forces dark pages works reasonably well.', $t0 + 2 * $day, $d1 );
RestVote::get_instance()->upvote( $d1, $users['maya'] );
RestVote::get_instance()->upvote( $d1, $users['tomas'] );

// 4. Private thread: only the author, invited people and editors can read it.
$t0 = $now - 2 * $day;
$refund = $thread( array(
	'title'   => 'Refund for a duplicate subscription charge',
	'author'  => 'tomas',
	'topic'   => 'Billing',
	'status'  => 'private',
	'time'    => $t0,
	'content' => 'I was charged twice for the annual plan this month. Could you refund one of the payments? I can send the receipt numbers privately.',
) );
$reply( $refund, 'support', "Sorry about that, Tomás. We found the duplicate payment and issued a refund. It should appear on your statement within 5–10 business days.", $t0 + 4 * $hour );

// 5. Bug report closed for replies.
$t0     = $now - 20 * $day;
$upload = $thread( array(
	'title'    => 'Attachments over 20 MB fail to upload',
	'author'   => 'leo',
	'topic'    => 'Bug Reports',
	'time'     => $t0,
	'comments' => 'closed',
	'content'  => 'Uploading a 25 MB PDF stops at 99% and then shows "Upload failed". Smaller files work fine.',
) );
$u1 = $reply( $upload, 'support', 'Confirmed. The limit was accidentally lowered in 4.1.3. It is back to 100 MB in 4.1.4, released today.', $t0 + 1 * $day );
$reply( $upload, 'leo', 'Works now, thanks!', $t0 + 2 * $day, $u1 );
BestAnswer::get_instance()->mark_as_the_best( $u1 );

// 6. Unresolved question.
$t0     = $now - 1 * $day;
$search = $thread( array(
	'title'   => 'Search does not find words inside PDFs',
	'author'  => 'priya',
	'topic'   => 'Bug Reports',
	'time'    => $t0,
	'content' => 'I attached a few scanned PDFs to my notes, but searching for words in them returns nothing. Is PDF text search supported?',
) );
$reply( $search, 'maya', 'Text PDFs work for me. Scanned ones probably need OCR first?', $t0 + 3 * $hour );

// 7. Resolved: sharing.
$t0    = $now - 12 * $day;
$share = $thread( array(
	'title'   => 'Can I share a notebook with read-only access?',
	'author'  => 'maya',
	'topic'   => 'Getting Started',
	'time'    => $t0,
	'content' => 'I want my team to see our meeting notes without editing them.',
) );
$s1 = $reply( $share, 'support', 'Yes. Open the notebook, click Share, invite people and set their role to "Viewer".', $t0 + 2 * $hour );
BestAnswer::get_instance()->mark_as_the_best( $s1 );

// 8. No replies yet.
$thread( array(
	'title'   => 'Is there a list of keyboard shortcuts?',
	'author'  => 'tomas',
	'topic'   => 'Getting Started',
	'time'    => $now - 2 * $hour,
	'content' => 'I keep discovering shortcuts by accident. Is there a cheat sheet somewhere?',
) );

// Pretty permalinks for /thread/{slug}/.
flush_rewrite_rules();
