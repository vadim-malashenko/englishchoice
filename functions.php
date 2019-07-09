<?php class Unit {
	
	const API_URL = '//api-maps.yandex.ru/2.1/?lang=%locale&amp;apikey=%key';
	const API_KEY = '';
	const FILTER_VALIDATE_FLOAT = ['filter' => FILTER_VALIDATE_REGEXP, 'options' => ['regexp' => '/[+-]*\d+([\.,]\d+)*/']];
	
	protected static $args = [
		'label' => 'Unit',
		'labels' => ['name' => 'Units', 'singular_name' => 'Unit', 'menu_name' => 'Unit', 'name_admin_bar' => 'Unit'],
		'supports' => ['title', 'editor'],
		'public' => TRUE,
		'publicly_queryable' => TRUE,
		'exclude_from_search' => FALSE,
		'hierarchical' => FALSE,
		'can_export' => TRUE,
		'has_archive' => FALSE,
		'show_ui' => TRUE,
		'show_in_menu' => TRUE,
		'show_in_admin_bar' => TRUE,
		'show_in_nav_menus' => TRUE,
		'show_in_rest' => FALSE,
		'menu_position' => 1,
		'menu_icon' => 'dashicons-format-gallery',
		'rewrite' => ['slug' => 'unit', 'with_front' => TRUE, 'pages' => FALSE, 'feeds' => FALSE],
		'capability_type' => 'post',
		'register_meta_box_cb' => [__CLASS__, 'register_metabox']
	];
	
	public static function init (array $args = NULL)
	{
		if ($args !== NULL)
			static::$args = $args + static::$args;
		
		\add_action ('init', [__CLASS__, 'register_type']);
		\add_action ('save_post_unit', [__CLASS__, 'save_post']);
		\add_action ('wp_enqueue_scripts', [__CLASS__, 'register_scripts']);
		
		\add_shortcode ('yandex_map', [__CLASS__, 'shortcode_yandex_map']);
	}
	
	public static function register_type ()
	{
		if (\is_wp_error ($error = \register_post_type ('unit', static::$args)))
			throw new \Exception ($error->get_error_message (), $error->get_error_code ());
	}
	
	public static function register_metabox (WP_Post $post)
	{
		\add_meta_box ('lola', 'Coordinates', [__CLASS__, 'render_metabox'], 'unit', 'normal', 'high');
	}
	
	public static function render_metabox ()
	{
		$post = static::unit_with_meta ();
		
		if ($post !== NULL) : ?>
		<input name="longitude" placeholder="Longitude" value="<?= $post->longitude; ?>">,&nbsp;
		<input name="latitude" placeholder="Latitude" value="<?= $post->latitude; ?>">
		<?php endif;
	}
	
	public static function save_post ()
	{
		if (empty ($_POST)) return;
		
		$post = static::unit_with_meta ();
		
		if ($post !== NULL) {
			
			$unit = (object) filter_input_array (INPUT_POST, ['longitude' => static::FILTER_VALIDATE_FLOAT, 'latitude' => static::FILTER_VALIDATE_FLOAT]);
			
			\update_post_meta( $post->ID, 'longitude', floatval( $unit->longitude ?: ( $unit->longitude ? $unit->longitude : 0 ) ) );
			\update_post_meta( $post->ID, 'latitude', floatval( $unit->latitude ?: ( $unit->latitude ? $unit->latitude : 0 ) ) );
		}
	}
	
	public static function register_scripts ()
	{
		$post = static::unit_with_meta ();
		
		if ($post !== NULL) {
			
			\wp_register_script ('ym.api', str_replace (['%locale', '%key'], [\get_locale (), static::API_KEY], static::API_URL));
			\wp_register_script ('ym.app', \get_stylesheet_directory_uri () . '/ym.js');
			\wp_localize_script ('ym.app', 'ym', ['unit' => $post]);
			\wp_enqueue_script ('ym.api');
			\wp_enqueue_script ('ym.app');
		}
		
		\wp_enqueue_style ('ym.style', \get_template_directory_uri () . '/style.css' );
	}
	
    	public static function unit_with_meta () : WP_Post
	{
		global $post;
		
		if ($post instanceof WP_Post and $post->post_type === 'unit') {
			
			$post->longitude = \get_post_meta( $post->ID, 'longitude', TRUE );
			$post->latitude = \get_post_meta( $post->ID, 'latitude', TRUE );
			
			return $post;
		}
		
		return NULL;
	}
	
	public static function shortcode_yandex_map ()
	{
		?><div id="map"></div><?php
	}
}

Unit::init ();
