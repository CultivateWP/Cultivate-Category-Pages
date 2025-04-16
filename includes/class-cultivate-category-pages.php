<?php
/**
 * Main class
 *
 * @package      Cultivate_Category_Pages
 * @author       CultivateWP
 * @since        1.1.0
 * @license      GPL-2.0+
**/

namespace Cultivate_Category_Pages;
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

final class Cultivate_Category_Pages {

	/**
	 * Instance of the class.
	 *
	 * @since 0.1.0
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * Supported taxonomies
	 * @var array
	 */
	public $supported_taxonomies;

	/**
	 * Supported CPT Archives
	 * @var array
	 */
	public $supported_cpt_archives;

	/**
	 * Post type
	 * @var string
	 */
	public $post_type;

	/**
	 * Block areas
	 * @var string
	 */
	public $block_areas;

	/**
	 * Cultivate Landing Pages Instance.
	 *
	 * @since 0.1.0
	 *
	 * @return Cultivate_Category_Pages
	 */
	public static function instance() {

		if( ! isset( self::$instance ) && ! ( self::$instance instanceof Cultivate_Category_Pages ) ) {
			self::$instance = new Cultivate_Category_Pages();
			self::$instance->load_textdomain();
			self::$instance->install();
			self::$instance->includes();

			add_action( 'cultivate_category_pages_install', [ self::$instance, 'supported_taxonomies' ], 4 );
			add_action( 'cultivate_category_pages_install', [ self::$instance, 'supported_cpt_archives' ], 4 );
			add_action( 'cultivate_category_pages_install', [ self::$instance, 'post_type' ], 4 );
			add_action( 'cultivate_category_pages_install', [ self::$instance, 'register_cpt' ] );
			add_action( 'cultivate_category_pages_install', 'flush_rewrite_rules' );

			add_action( 'init', [ self::$instance, 'supported_taxonomies' ], 4 );
			add_action( 'init', [ self::$instance, 'supported_cpt_archives' ], 4 );
			add_action( 'init', [ self::$instance, 'post_type' ], 4 );
			add_action( 'init', [ self::$instance, 'register_cpt' ], 12 );
			add_filter( 'body_class', [ self::$instance, 'body_class' ], 30 );
			add_filter( 'admin_body_class', [ self::$instance, 'admin_body_class' ] );
			add_action( 'acf/init', [ self::$instance, 'register_metabox' ] );
			add_action( 'admin_bar_menu', [ self::$instance, 'admin_bar_link_front' ], 90 );
			add_action( 'admin_bar_menu', [ self::$instance, 'admin_bar_link_back' ], 90 );
			add_filter( 'post_type_link', [ self::$instance, 'post_type_link' ], 10, 2 );
			add_filter( 'wpseo_sitemap_exclude_post_type', [ self::$instance, 'wpseo_sitemap_exclude' ], 10, 2 );
			add_action( 'enqueue_block_editor_assets', [ self::$instance, 'h1_warning' ] );
			add_action( 'wp_dashboard_setup', [ self::$instance, 'register_dashboard_widget' ] );
			add_action( 'wp_enqueue_scripts', [ self::$instance, 'enqueue_scripts' ] );
			
			// Theme locations
			$locations = apply_filters(
				'cultivate_pro/landing/theme_locations',
				[
					'genesis_before_while' => 20,
					'tha_content_while_before' => 20,
				]
			);
			foreach( $locations as $hook => $priority ) {
				add_action( $hook, [ self::$instance, 'show' ], $priority );
			}

			// Add 'the_content' filter
			add_filter( 'cultivate_pro/landing/the_content', 'the_content' );
		}
		return self::$instance;
	}

	/**
	 * Loads the plugin language files.
	 *
	 * @since 0.1.0
	 * @todo generate pot file
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'cultivate-category-pages', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Install procedure.
	 *
	 * @since 0.1.0
	 */
	public function install() {

		// When activated, run install.
		register_activation_hook(
			CULTIVATE_CATEGORY_PAGES_PLUGIN_FILE,
			function() {

				do_action( 'cultivate_category_pages_install' );

				// Set current version, to be referenced in future updates.
				update_option( 'cultivate_category_pages_version', CULTIVATE_CATEGORY_PAGES_VERSION );
			}
		);
	}

	/**
	 * Load includes.
	 *
	 * @since 0.1.0
	 */
	public function includes() {

		if( is_admin() ) {
			require CULTIVATE_CATEGORY_PAGES_PLUGIN_DIR . 'includes/updater/plugin-update-checker.php';
			$myUpdateChecker = PucFactory::buildUpdateChecker(
				'https://github.com/CultivateWP/Cultivate-Category-Pages/',
				__FILE__, //Full path to the main plugin file or functions.php.
				'cultivate-category-pages'
			);
		}
	}

	/**
	 * Supported Taxonomies
	 *
	 */
	function supported_taxonomies() {
		$this->supported_taxonomies = apply_filters( 'cultivate_pro/landing/taxonomies', [ 'category' ] );
	}

	/**
	 * Supported Post Type Archives
	 *
	 */
	function supported_cpt_archives() {
		$this->supported_cpt_archives = apply_filters( 'cultivate_pro/landing/cpt_archives', [] );
	}

	/**
	 * Post Type
	 *
	 */
	function post_type() {
		$this->post_type = 'cultivate_landing';
	}

	/**
	 * Register the custom post type
	 *
	 */
	function register_cpt() {

		$labels = [
			'name'               => __( 'Category Pages', 'cultivate-pro' ),
			'singular_name'      => __( 'Category Page', 'cultivate-pro' ),
			'add_new'            => __( 'Add New', 'cultivate-pro' ),
			'add_new_item'       => __( 'Add New Category Page', 'cultivate-pro' ),
			'edit_item'          => __( 'Edit Category Page', 'cultivate-pro' ),
			'new_item'           => __( 'New Category Page', 'cultivate-pro' ),
			'view_item'          => __( 'View Category Page', 'cultivate-pro' ),
			'search_items'       => __( 'Search Category Pages', 'cultivate-pro' ),
			'not_found'          => __( 'No Category Pages found', 'cultivate-pro' ),
			'not_found_in_trash' => __( 'No Category Pages found in Trash', 'cultivate-pro' ),
			'parent_item_colon'  => __( 'Parent Category Page:', 'cultivate-pro' ),
			'menu_name'          => __( 'Category Pages', 'cultivate-pro' ),
		];

		$args = [
			'labels'              => $labels,
			'hierarchical'        => false,
			'supports'            => [ 'title', 'editor', 'revisions', 'custom-fields' ],
			'public'              => false,
			'publicly_queryable'  => is_admin(),
			'show_ui'             => true,
			'show_in_rest'	      => true,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'query_var'           => true,
			'can_export'          => true,
			'rewrite'             => false,
			'menu_icon'           => $this->menu_icon(),
			'show_in_menu'		=> true,
		];

		register_post_type( $this->post_type, apply_filters( 'cultivate_pro/landing/post_type_args', $args ) );

	}

	/**
	 * Register metabox
	 *
	 */
	function register_metabox() {

		$taxonomies = $tax_fields = [];
		$default_term = !empty( $_GET['cultivate_term'] ) ? intval( $_GET['cultivate_term'] ) : false;
		$default_tax = !empty( $_GET['cultivate_tax'] ) ? esc_attr( $_GET['cultivate_tax'] ) : false;
		$tax = false;
		foreach( $this->supported_taxonomies as $i => $tax_slug ) {
			$tax = get_taxonomy( $tax_slug );
			$taxonomies[ $tax_slug ] = $tax->labels->singular_name;
			$default = $tax_slug === $default_tax ? $default_term : false;

			$tax_fields[] = [
				'key'					=> 'field_10' . $i,
				'label'					=> $tax->labels->name,
				'name'					=> 'be_connected_' . str_replace( '-', '_', $tax_slug ),
				'type'					=> 'taxonomy',
				'default_value'			=> $default,
				'taxonomy'				=> $tax_slug,
				'field_type'			=> 'select',
				'conditional_logic'		=> [
					[
						[
							'field'		=> 'field_5da8747adb0bf',
							'operator'	=> '==',
							'value'		=> $tax_slug,
						]
					]
				]
			];
		}

		$taxonomy_select_field = [[
			'key'		=> 'field_5da8747adb0bf',
			'label'		=> __( 'Taxonomy', 'cultivate-pro' ),
			'name'		=> 'be_connected_taxonomy',
			'type'		=> 'select',
			'choices'	=> $taxonomies,
			'default_value' => $default_tax,
		]];

		$settings = apply_filters( 'cultivate_pro/landing/field_group', [
			'title' => __( 'Appears On', 'cultivate-pro' ),
			'fields' => array_merge( $taxonomy_select_field, $tax_fields ),
			'location' => [
				[
					[
						'param' => 'post_type',
						'operator' => '==',
						'value' => $this->post_type,
					],
				],
			],
			'position' => 'side',
			'active' => true,
		] );

		if( ! empty( $settings ) )
			acf_add_local_field_group( $settings );
	}

	/**
	 * Body class
	 *
	 * @param array $classes Classes
	 */
	public function body_class( $classes ) {
		if ( $this->get_landing_id() ) {
			$classes[] = 'cultivate-category-page';
		}
		return $classes;
	}

	/**
	 * Admin body class
	 *
	 * @param string $classes Classes.
	 */
	public function admin_body_class( $classes ) {
		$screen = get_current_screen();
		if ( ! method_exists( $screen, 'is_block_editor' ) || ! $screen->is_block_editor() ) {
			return $classes;
		}

		$post_id = isset( $_GET['post'] ) ? intval( $_GET['post'] ) : false;
		if ( empty( $post_id ) || 'cultivate_landing' !== get_post_type( $post_id ) ) {
			return $classes;
		}

		$classes .= ' block-area ';
		return $classes;
	}

	/**
	 * Show landing page
	 *
	 */
	function show( $location = '' ) {
		if( ! $location ) {
			$location = $this->get_landing_id();
		}

		if( empty( $location ) ) {
			return;
		}

		if ( 1 < intval( get_query_var( 'paged' ) ) ) {
			return;
		}

		$post_status = current_user_can( 'manage_options' ) && isset( $_GET['preview'] ) ? 'all' : 'publish';
		$args = [ 'post_type' => $this->post_type, 'posts_per_page' => 1, 'post_status' => $post_status ];
		if( is_int( $location ) )
			$args['p'] = intval( $location );
		else
			$args['name'] = sanitize_key( $location );

		$loop = new \WP_Query( $args );

		if( $loop->have_posts() ): while( $loop->have_posts() ): $loop->the_post();
			$classes = [ 'block-area' ];
			$classes[] = 'block-area-' . sanitize_key( get_the_title() );

			echo '<div class="' . esc_attr( join( ' ', $classes ) ) . '">';
				global $post;
				echo apply_filters( 'cultivate_pro/landing/the_content', $post->post_content );
			echo '</div>';
			if( is_archive() && empty( $block_area ) ) {
				$title = __( 'Newest', 'cultivate-pro' ) . ' ' . get_the_archive_title();
				$title = apply_filters( 'cultivate_pro/landing/archive_title', $title );
				if( !empty( $title ) )
					echo '<header id="recent" class="archive-recent-header"><h2>' . $title . '</h2></header>';
			}
		endwhile; endif; wp_reset_postdata();
	}

	/**
	 * Get taxonomy
	 *
	 */
	function get_taxonomy() {
		$taxonomy = is_category() ? 'category' : ( is_tag() ? 'post_tag' : get_query_var( 'taxonomy' ) );
		if( !empty( $this->supported_taxonomies ) && in_array( $taxonomy, $this->supported_taxonomies ) )
			return $taxonomy;
		else
			return false;
	}

	/**
	 * Get Landing Page ID
	 *
	 */
	function get_landing_id() {

		if( is_post_type_archive() && in_array( get_post_type(), $this->supported_cpt_archives ) ) {
			$loop = new \WP_Query( [
				'post_type' => $this->post_type,
				'posts_per_page' => 99,
				'fields' => 'ids',
				'no_found_rows' => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'post_name__in' => [ 'cpt-' . get_post_type() ]
			]);

		} else {

			$taxonomy = $this->get_taxonomy();
			if( empty( $taxonomy ) || ! is_archive() )
				return false;

			$meta_key = 'be_connected_' . str_replace( '-', '_', $taxonomy );

			$post_status = current_user_can( 'manage_options' ) && isset( $_GET['preview'] ) ? 'all' : 'publish';
			$loop = new \WP_Query( [
				'post_type' => $this->post_type,
				'post_status' => $post_status,
				'posts_per_page' => 1,
				'fields' => 'ids',
				'no_found_rows' => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
				'meta_query' => [
					[
						'key' => $meta_key,
						'value' => get_queried_object_id(),
					]
				]
			] );
		}

		if( empty( $loop->posts ) )
			return false;
		else
			return $loop->posts[0];

	}

	/**
	 * Get term link
	 *
	 */
	function get_term_link( $archive_id = false ) {

		if( empty( $archive_id ) )
			return false;

		$taxonomy = get_post_meta( $archive_id, 'be_connected_taxonomy', true );
		$term = get_post_meta( $archive_id, 'be_connected_' . $taxonomy, true );

		if( empty( $term ) )
			return false;

		$term = get_term_by( 'term_id', $term, $taxonomy );
		return get_term_link( $term, $taxonomy );
	}

	/**
	 * Admin Bar Link, Frontend
	 *
	 */
	 function admin_bar_link_front( $wp_admin_bar ) {
		 $taxonomy = $this->get_taxonomy();
		 if( ! ( $taxonomy || is_post_type_archive( $this->supported_cpt_archives ) ) )
		 	return;

		if( ! ( is_user_logged_in() && current_user_can( 'manage_categories' ) ) )
			return;

		$archive_id = $this->get_landing_id();
		$icon = '<span style="display: block; float: left; margin: 5px 5px 0 0;">' . cultivate_category_pages()->icon( [ 'icon' => 'cultivatewp-menu', 'size' => 20, ] ) . '</span>';
		if( !empty( $archive_id ) ) {
			$wp_admin_bar->add_node( [
				'id' => 'cultivate_category_pages',
				'title' => $icon . __( 'Edit Category Page', 'cultivate-pro' ),
				'href'  => get_edit_post_link( $archive_id ),
			] );

		} else {
			$wp_admin_bar->add_node( [
				'id' => 'cultivate_category_pages',
				'title' => $icon . __( 'Add Category Page', 'cultivate-pro' ),
				'href'  => admin_url( 'post-new.php?post_type=' . $this->post_type . '&cultivate_tax=' . $taxonomy . '&cultivate_term=' . get_queried_object_id() )
			] );
		}
	 }

	/**
	 * Admin Bar Link, Backend
	 *
	 */
	function admin_bar_link_back( $wp_admin_bar ) {
		if( ! is_admin() )
			return;

		$screen = get_current_screen();
		if( empty( $screen->id ) || $this->post_type !== $screen->id )
			return;

		$archive_id = !empty( $_GET['post'] ) ? intval( $_GET['post'] ) : false;
		if( ! $archive_id )
			return;

		$term_link = $this->get_term_link( $archive_id );
		if( empty( $term_link ) )
			return;

		$icon = '<span style="display: block; float: left; margin: 5px 5px 0 0;">' . cultivate_category_pages()->icon( [ 'icon' => 'cultivatewp-menu', 'size' => 20 ] ) . '</span>';
		$wp_admin_bar->add_node( [
			'id'	=> 'cultivate_category_pages',
			'title'	=> $icon . __( 'View Category Page', 'cultivate-pro' ),
			'href'	=> $term_link,
		] );
	}

	/**
	 * Post Type Link
	 *
	 */
	function post_type_link( $link, $post ) {
		if( !empty( $post->post_type ) && $this->post_type === $post->post_type ) {
			$new_link = $this->get_term_link( $post->ID );
			if ( ! empty( $new_link ) && ! is_wp_error( $new_link ) ) {
				$link = $new_link;
			} else {
				$link = home_url();
			}
		}
		return $link;
	}

	/**
	 * Exclude landing pages from Yoast SEO sitemap.
	 *
	 * @param bool   $value Value.
	 * @param string $post_type Post Type.
	 */
	public function wpseo_sitemap_exclude( $value, $post_type ) {
		if ( $post_type === $this->post_type ) {
			$value = true;
		}
		return $value;
	}

	/**
	 * Icon
	 *
	 * @since 1.0.0
	 */
	public function icon( $atts = [] ) {

		$atts = shortcode_atts( [
			'icon'	=> false,
			'size'	=> 16,
			'class'	=> false,
		], $atts );

		if( empty( $atts['icon'] ) )
			return;

		$icon_path = CULTIVATE_CATEGORY_PAGES_PLUGIN_DIR . 'assets/icons/' . $atts['icon'] . '.svg';
		if( ! file_exists( $icon_path ) )
			return;

		$icon = file_get_contents( $icon_path );
		if( false !== $atts['size'] ) {
			$repl = sprintf( '<svg width="%d" height="%d" aria-hidden="true" role="img" focusable="false" ', $atts['size'], $atts['size'] );
			$svg  = preg_replace( '/^<svg /', $repl, trim( $icon ) ); // Add extra attributes to SVG code.
		} else {
			$svg = preg_replace( '/^<svg /', '<svg ', trim( $icon ) );
		}
		$svg  = preg_replace( "/([\n\t]+)/", ' ', $svg ); // Remove newlines & tabs.
		$svg  = preg_replace( '/>\s*</', '><', $svg ); // Remove white space between SVG tags.
		if( !empty( $atts['class'] ) )
			$svg = preg_replace( "/^<svg /", '<svg class="' . $atts['class'] . '"', $svg );
		return $svg;
	}

	/**
	 * Menu Icon
	 *
	 */
	function menu_icon() {
		$icon = $this->icon( [ 'icon' => 'cultivatewp-menu', 'size' => 20 ] );
		return 'data:image/svg+xml;base64,' . base64_encode( $icon );
	}

	/**
	 * Is active client
	 */
	function is_active_client() {
		$theme   = wp_get_theme();
		return 'CultivateWP' === $theme->get( 'Author' ) && 'Cultivate Builder' !== $theme->get( 'Name' );
	}

	/**
	 * H1 Warning
	 */
	function h1_warning() {
		$screen = get_current_screen();
		$display = apply_filters( 'cultivate_category_pages_h1_warning', 'post' === $screen->post_type );
		if ( $display ) {
			wp_enqueue_style( 'cultivate-category-pages-h1-warning', CULTIVATE_CATEGORY_PAGES_PLUGIN_URL . 'assets/css/h1-warning.css', [], CULTIVATE_CATEGORY_PAGES_VERSION );
		}
	}

	/**
	 * Register dashboard widget
	 */
	function register_dashboard_widget() {
		if ( ! apply_filters( 'cultivate_category_pages/disable_dashboard_widget', false ) ) {
			wp_add_dashboard_widget('cwp_notice', 'Recent tutorials by CultivateWP', [ self::$instance, 'dashboard_widget' ], null, null, 'side', 'high' );
		}
	}

	/**
	 * Dashboard Widget
	 */
	function dashboard_widget() {
		$rss = fetch_feed( 'https://cultivatewp.com/category/publishers/how-to/feed/' );

		if ( ! is_wp_error( $rss ) ) {
			// Get the 5 most recent items from the feed
			$items = $rss->get_items(0, 5);
	
			// Display the linked titles of the 5 most recent posts
			echo '<ul>';
			foreach ($items as $item) {
				echo '<li><a href="' . esc_url($item->get_permalink()) . '" target="_blank">' . esc_html($item->get_title()) . '</a></li>';
			}
			echo '</ul>';
		} else {
			echo '<p>Error fetching RSS feed.</p>';
		}

		echo '<p>View all tutorials in our <a href="https://cultivatewp.com/resource-center/getting-started/" target="_blank">Getting Started Guide</a></p>';

		if ( ! $this->is_active_client() ) {
			echo '<hr />';
			echo '<p>CultivateWP creates fast and beautiful websites for bloggers. Please <a href="https://cultivatewp.com/contact/" target="_blank">contact us</a> when you\'re ready to upgrade the design and functionality of your website.</p>';
		}
	}

	/**
	 * Enqueue Scripts
	 */
	function enqueue_scripts() {
		wp_enqueue_style( 'cultivate-category-pages-frontend', CULTIVATE_CATEGORY_PAGES_PLUGIN_URL . 'assets/css/frontend.css', [], CULTIVATE_CATEGORY_PAGES_VERSION );
	}

}
