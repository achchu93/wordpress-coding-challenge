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
		$post_id    = get_the_ID();
		$post_types = get_post_types( [ 'public' => true ] );
		$class_name = $attributes['className'];
		ob_start();

		?>
		<div class="<?php echo esc_attr( $class_name ); ?>">
			<h2><?php echo esc_html__( 'Post Counts', 'site-counts' ); ?></h2>
			<ul>
			<?php
			foreach ( $post_types as $post_type_slug ) :
				$post_type_object = get_post_type_object( $post_type_slug );
				$post_type_query  = new WP_Query(
					[
						'post_type'              => $post_type_slug,
						'posts_per_page'         => 10,
						'update_post_meta_cache' => false,
						'update_post_term_cache' => false,

					]
				);
				$post_count = $post_type_query->found_posts;
				?>
				<li>
					<?php
					echo sprintf(
						/* translators: %1$d: post type count %2$s: post type name */
						esc_html__( 'There are %1$d %2$s', 'site-counts' ),
						esc_html( $post_count ),
						esc_html( $post_type_object->labels->name )
					);
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
			$query = new WP_Query(
				[
					'post_type'              => [ 'post', 'page' ],
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
				]
			);

			if ( $query->have_posts() ) :
				?>
				<h2><?php echo esc_html__( '5 posts with the tag of foo and the category of baz', 'site-counts' ); ?></h2>
				<ul>
					<?php
					foreach ( $query->posts as $post ) :

						if ( $post_id === $post->ID ) {
							continue;
						}
						?>
						<li><?php echo esc_html( $post->post_title ); ?></li>

					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php

		return ob_get_clean();
	}
}
