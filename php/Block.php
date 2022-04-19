<?php
/**
 * Block class.
 *
 * @package SiteCounts
 */

namespace XWP\SiteCounts;

use WP_Block;
use WP_Query;

/**
 * The Site Counts dynamic block.
 *
 * Registers and renders the dynamic block.
 */
class Block {

	/**
	 * The Plugin instance.
	 *
	 * @var Plugin
	 */
	protected $plugin;

	/**
	 * Instantiates the class.
	 *
	 * @param Plugin $plugin The plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Adds the action to register the block.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_block' ] );
	}

	/**
	 * Registers the block.
	 */
	public function register_block() {
		register_block_type_from_metadata(
			$this->plugin->dir(),
			[
				'render_callback' => [ $this, 'render_callback' ],
			]
		);
	}

	/**
	 * Renders the block.
	 *
	 * @param array    $attributes The attributes for the block.
	 * @param string   $content    The block content, if any.
	 * @param WP_Block $block      The instance of this block.
	 * @return string The markup of the block.
	 */
	public function render_callback( $attributes, $content, $block ) {

		global $post;

		$post_types = get_post_types( [ 'public' => true ] );
		$class_name = isset( $attributes['className'] ) ? $attributes['className'] : '';

		if ( isset( $post ) ) {
			$post_id = $post->ID;
		} else {
			$post_id = isset( $_GET['post'] ) ? $_GET['post'] : 0; // phpcs:ignore
		}

		ob_start();

		?>
		<div class="<?php echo esc_attr( $class_name ); ?>">
			<h2><?php echo esc_html__( 'Post Counts', 'site-counts' ); ?></h2>
			<ul>
			<?php
			foreach ( $post_types as $post_type_slug ) :
				$post_type_object = get_post_type_object( $post_type_slug );
				$post_count       = wp_count_posts( $post_type_slug );
				?>
				<li>
					<?php
					if ( $post_count->publish > 0 ) {
						echo esc_html(
							sprintf(
								/* translators: 1: Post Count 2: Post Type */
								_n(
									'There is %1$d %2$s',
									'There are %1$d %2$s',
									intval( $post_count->publish ),
									'site-counts'
								),
								$post_count->publish,
								$post_count->publish > 1 ? esc_html( $post_type_object->labels->name ) : esc_html( $post_type_object->labels->singular_name )
							)
						);
					} else {
						echo sprintf(
							/* translators: %s: Post Type */
							esc_html__( 'There is no Posts for %s', 'site-counts' ),
							esc_html( $post_type_object->labels->name )
						);
					}
					?>
				</li>
			<?php endforeach; ?>
			</ul>
			<p>
				<?php
				echo sprintf(
					/* translators: %d post id */
					esc_html__( 'The current post ID is  %1$d', 'site-counts' ),
					esc_html( $post_id )
				);
				?>
			</p>

			<?php

			$recent_foo_baz_posts = wp_cache_get( 'recent_foo_baz_posts', 'recent_posts' );

			if ( false === $recent_foo_baz_posts ) {

				$foo_baz_query = new WP_Query(
					[
						'post_status'            => 'any',
						'date_query'             => [
							[
								'hour'    => 9,
								'compare' => '>=',
							],
							[
								'hour'    => 17,
								'compare' => '<=',
							],
						],
						'tag'                    => 'foo',
						'category_name'          => 'baz',
						'no_found_rows'          => true,
						'posts_per_page'         => 5,
						'update_post_meta_cache' => false,
						'update_post_term_cache' => false,
					]
				);

				if ( ! is_wp_error( $foo_baz_query ) && $foo_baz_query->have_posts() ) {

					$recent_foo_baz_posts = $foo_baz_query->posts;
					wp_cache_set( 'prefix_top_commented_posts', $recent_foo_baz_posts, 'recent_posts', 5 * MINUTE_IN_SECONDS );
				}
			}

			if ( false !== $recent_foo_baz_posts ) :
				?>
				<h2><?php echo esc_html__( '5 posts with the tag of foo and the category of baz', 'site-counts' ); ?></h2>
				<ul>
					<?php
					foreach ( $recent_foo_baz_posts as $recent_foo_baz_post ) :

						if ( $post_id === $recent_foo_baz_post->ID ) {
							continue;
						}
						?>
						<li><?php echo esc_html( $recent_foo_baz_post->post_title ); ?></li>

					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php

		return ob_get_clean();
	}
}
