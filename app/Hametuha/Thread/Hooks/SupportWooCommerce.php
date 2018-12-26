<?php

namespace Hametuha\Thread\Hooks;


use Hametuha\Pattern\Singleton;

/**
 * WooCommerce Support Class
 *
 * @package hamethread
 */
class SupportWooCommerce extends Singleton {
	
	/**
	 * Constructor
	 */
	protected function init() {
		if ( ! function_exists( 'WC' ) ) {
			return;
		}
		add_filter( 'woocommerce_account_menu_items', [ $this, 'woocommerce_link' ], 20 );
		add_action( 'init', [ $this, 'add_endpoint' ] );
		add_action( 'init', [ $this, 'register_assets' ] );
		add_filter( 'query_vars', function( $vars ) {
			$vars[] = 'hamethread-support';
			return $vars;
		} );
		add_action( 'woocommerce_account_hamethread-support_endpoint', [ $this, 'render_support_list' ] );
		add_filter( 'hamethread_pagination', [ $this, 'paginate' ], 1, 2 );
	}
	
	/**
	 * Add Support list for WooCommerce
	 */
	public function woocommerce_link( $links ) {
		$new_links = [];
		foreach ( $links as $key => $link ) {
			if ( 'edit-address' === $key ) {
				$new_links[ 'support' ] = __( 'Support', 'hamethread' );
			}
			$new_links[ $key ] = $link;
		}
		return $new_links;
	}
	
	/**
	 * Register JS and CSS
	 */
	public function register_assets() {
		wp_register_style( 'hamethread-woocommerce', hamethread_asset_url() . '/css/hamethread-woocommerce.css', [], hamethread_version() );
	}
	
	/**
	 * Add endpoint.
	 */
	public function add_endpoint() {
		add_rewrite_endpoint( 'support', EP_ROOT | EP_PAGES, 'hamethread-support' );
	}
	
	/**
	 * Render support list.
	 */
	public function render_support_list() {
		$query_arg = [
			'post_type'      => 'thread',
			'author'         => get_current_user_id(),
			'post_status'    => [ 'publish', 'private' ],
			'orderby'        => [ 'date' => 'DESC' ],
			'posts_per_page' => 10,
		];
		global $wp_query;
		$paged = max( 1, get_query_var( 'hamethread-support' ) );
		if ( 1 < $paged ) {
			$query_arg['paged'] = $paged;
		}
		$query_arg = apply_filters( 'hamethread_threads_query_args', $query_arg, null );
		$query = new \WP_Query( $query_arg );
		wp_enqueue_style( 'hamethread-woocommerce' );
		hamethread_template( 'woocommerce', 'my-account', true, [ 'query' => $query ] );
	}
	
	/**
	 * Get pagination link
	 *
	 * @param string    $pagination
	 * @param \WP_Query $query
	 * @return string
	 */
	public function paginate( $pagination, $query ) {
		$link = paginate_links( [
			'base'      => untrailingslashit( wc_get_page_permalink( 'myaccount' ) ). '/support/%_%',
			'format'    => '%#%',
			'total'     => $query->max_num_pages,
			'prev_text' => '<i class="fa fa-chevron-left"></i>',
			'next_text' => '<i class="fa fa-chevron-right"></i>',
			'current'   => max( 1, get_query_var( 'hamethread-support' ) ),
			'type'      => 'array',
		] );
		if ( ! $link ) {
			return '';
		}
		ob_start();
		?>
		<nav aria-label="Page navigation example" class="mt-4">
			<ul class="pagination">
				<?php foreach ( $link as $l ) : ?>
					<li class="page-item<?php echo false === strpos( $l,  '<span' ) ? '' : ' active' ?>">
						<?php echo preg_replace( '/(?<!i) class=[\'"][^\'"]+[\'"]/u', ' class="page-link"', $l ) ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>
		<?php
		$pagination = ob_get_contents();
		ob_end_clean();
		return $pagination;
	}
}
