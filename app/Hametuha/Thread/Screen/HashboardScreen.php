<?php

namespace Hametuha\Thread\Screen;


use Hametuha\Hashboard\Pattern\Screen;

class HashboardScreen extends Screen {

	protected $icon = 'forum';

	public function description( $page = '' ) {
		return __( 'List of threads which belong to you.', 'hamethread' );
	}

	public function slug() {
		return 'threads';
	}

	public function label() {
		return __( 'Threads', 'hamethread' );
	}

	/**
	 * Render Screen
	 *
	 * @param string $page Ignored.
	 */
	public function render( $page = '' ) {
		$locale = current( explode( '_', get_user_locale() ) );
		?>
		<div id="hamethread-list" v-cloak>
			<header class="hamethraed-list-meta">
				<form class="form-inline hamethread-list-meta-form">
					<div class="form-group">
						<select v-model="resolved" class="form-control" @change="update()">
							<?php
							foreach (
								[
									__( 'All Status', 'hamethread' )   => 0,
									__( 'Resolved', 'hamethread' )     => 1,
									__( 'Not Resolved', 'hamethread' ) => - 1,
								] as $label => $value
							) :
								?>
								<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="form-group">
						<div class="form-check">
							<input class="form-check-input" type="checkbox" v-model="private" id="hamethread-private" @change="update()">
							<label class="form-check-label" for="hamethread-private">
								<?php esc_html_e( 'Only private', 'hamethread' ); ?>
							</label>
						</div>
					</div>
					<div class="form-group">
						<div class="form-group">
							<div class="input-group">
								<?php
								printf(
									'<input type="search" v-model="s" class="form-control" placeholder="%1$s" aria-label="%1$s" aria-describedby="hamethread-search-btn"/>',
									esc_attr__( 'Search keyword...', 'hamethread' )
								)
								?>
								<div class="input-group-append">
									<button class="btn btn-outline-secondary" type="button" id="hamethread-search-btn" @click="update()">
										<?php esc_html_e( 'Search', 'hamethread' ); ?>
									</button>
								</div>
							</div>
						</div>
					</div>
				</form>
			</header>
			<div class="hamethread-list-body">
				<div v-if="threads.length">
					<ul class="hamethread-list-items list-group">
						<li class="hamethread-list-item list-group-item" v-for="thread in threads">
							<div class="d-flex w-100 justify-content-between">
								<h5 class="mb-2 mt-0">
									<i class="material-icons" v-if="'private' == thread.status">lock</i>
									<a :href="thread.link">{{thread.title}}</a>
								</h5>
								<small>{{thread.date_atom|moment('LL', '<?php echo esc_attr( $locale ); ?>')}}</small>
							</div>
							<p class="mb-1">
								<i class="material-icons">comment</i>
								{{thread.count.approved}}
								<span v-if="1 < thread.count.approved"><?php echo esc_html_x( 'comments', 'thread-comment-count', 'hamethread' ); ?></span>
								<span v-else><?php echo esc_html_x( 'comment', 'thread-comment-count', 'hamethread' ); ?></span>
								<span v-if="thread.resolved" class="ml-2 text-success">
									<i class="material-icons tet-success">check_circle</i> <?php esc_html_e( 'Resolved', 'hamethread' ); ?>
								</span>
							</p>
							<small class="text-muted">
								<i class="material-icons">access_time</i>
								<?php esc_html_e( 'Last Updated: ', 'hamethread' ); ?>
								{{thread.latest|moment('LLL')}}
							</small>
						</li>
					</ul>
					<div v-if="1 < total" class="mt-4">
						<hb-pagination :total="total" :current="current" @page-changed="pageChangeHandler"></hb-pagination>
					</div>
				</div>
				<div v-else class="alert alert-secondary">
					<?php esc_html_e( 'No thread is found for your criteria.', 'hamethread' ); ?>
				</div>
				<hb-loading :loading="loading"></hb-loading>
			</div>
		</div>
		<?php
	}


	public function head() {
		// Load styles.
		wp_enqueue_style( 'hamethread-hashboard' );
	}

	public function footer() {
		wp_enqueue_script( 'hamethread-hashboard' );
		// Load scripts.
	}
}
