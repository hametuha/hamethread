<?php
/**
 * Pagination markup helper tests.
 *
 * @package hamethread
 */

/**
 * Tests for hamethread_pagination_html().
 *
 * Covers the markup produced for #48 (Bootstrap-independent pagination shared
 * by the comment list and the WooCommerce support list).
 */
class TestPagination extends WP_UnitTestCase {

	/**
	 * Empty or non-array input yields an empty string.
	 */
	public function test_empty_input() {
		$this->assertSame( '', hamethread_pagination_html( [] ) );
		$this->assertSame( '', hamethread_pagination_html( null ) );
		$this->assertSame( '', hamethread_pagination_html( '' ) );
	}

	/**
	 * Representative paginate_links() output is converted to hamethread markup.
	 */
	public function test_markup_and_state_classes() {
		$links = [
			'<a class="prev page-numbers" href="/page/1"><span class="dashicons dashicons-arrow-left-alt2"></span></a>',
			'<a class="page-numbers" href="/page/1">1</a>',
			'<span aria-current="page" class="page-numbers current">2</span>',
			'<span class="page-numbers dots">&hellip;</span>',
			'<a class="next page-numbers" href="/page/3"><span class="dashicons dashicons-arrow-right-alt2"></span></a>',
		];

		$html = hamethread_pagination_html( $links, 'My Pages' );

		// Wrapper markup.
		$this->assertStringContainsString( '<nav class="hamethread-pagination" aria-label="My Pages">', $html );
		$this->assertStringContainsString( '<ul class="hamethread-pagination-list">', $html );

		// State modifiers on the list items.
		$this->assertStringContainsString( 'hamethread-pagination-item is-prev', $html );
		$this->assertStringContainsString( 'hamethread-pagination-item is-current', $html );
		$this->assertStringContainsString( 'hamethread-pagination-item is-dots', $html );
		$this->assertStringContainsString( 'hamethread-pagination-item is-next', $html );

		// Link element classes are rewritten to ours.
		$this->assertStringContainsString( 'class="hamethread-pagination-link"', $html );

		// No Bootstrap / raw WordPress classes leak through on the link element.
		$this->assertStringNotContainsString( 'page-item', $html );
		$this->assertStringNotContainsString( 'class="pagination"', $html );
		$this->assertStringNotContainsString( 'class="page-numbers"', $html );

		// Inner dashicon markup (a nested element) is preserved.
		$this->assertStringContainsString( 'dashicons-arrow-left-alt2', $html );
		$this->assertStringContainsString( 'dashicons-arrow-right-alt2', $html );
	}

	/**
	 * The default aria-label is applied when none is given.
	 */
	public function test_default_label() {
		$html = hamethread_pagination_html( [ '<a class="page-numbers" href="/p/2">2</a>' ] );
		$this->assertStringContainsString( 'aria-label="Pagination"', $html );
	}
}
