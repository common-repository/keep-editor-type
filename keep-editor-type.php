<?php
/**
 *
 * Plugin Name:       Keep Editor Type
 * Plugin URI:        https://wordpress.org/plugins/keep-editor-type/
 * Description:       TinyMCE editor type keep for each post.
 * Version:           1.0.1
 * Author:            hana
 * Author URI:        https://hanabanadesign.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       keep-editor-type
 * Domain Path:       /languages
 */

// exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// check if class already exists
if( !class_exists('Kepp_Editor_Type') ) :

class Kepp_Editor_Type {
	private static $instance = null;


	/**
	 * __construct
	 *
	 * Description
	 *
	 * @type	function
	 * @date
	 * @since	1.0.0
	 *
	 * @param	none
	 * @return	void
	 */
	public function __construct()
	{
		/*
			load text domain
		*/
		load_plugin_textdomain(
			'keep-editor-type',
			false,
			plugin_basename( dirname( __FILE__ ) ) . '/languages'
		);

		/*
			action
		*/
		// Register method to be executed when plug-in is activated
		if ( function_exists('register_activation_hook') )
		{
			register_activation_hook(
				__FILE__,
				array( $this, 'activationHook' )
			);
		}// if
		// Register methods to be executed when the plug-in is stopped
		if ( function_exists('register_deactivation_hook') )
		{
			register_deactivation_hook(
				__FILE__,
				array( $this, 'deactivationHook' )
			);
		}// if
		// Register the method to be executed when the plugin is uninstalled
		if ( function_exists('register_uninstall_hook') )
		{
			register_uninstall_hook(
				__FILE__,
				'ket_uninstallHook'
			);
		}// if

		/*
			processing
		*/
		// at the beginning of all processing int action
		add_action( 'init', array( $this, 'init_action' ) );
		// css & js for admin area
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts_and_styles' ) );
	}// function __construct()



	/**
	* activationHook
	*
	* Method executed when plug-in is activated
	*
	* @return void
	*/
	public function activationHook( $network_wide )
	{
		if ( is_multisite() && $network_wide )
		{// If multisite && activate from network
			$sites = get_sites();
			foreach ( $sites as $site ) {
				// switch blog
				switch_to_blog( $site->blog_id );
					$this->do_set_default();
				// return blog
				restore_current_blog();
			}// foreach

		} else {// singlesite or (multisite & activate from each blog)
			$this->do_set_default();
		}
	}
	/**
	* deactivationHook
	*
	* Method executed when the plug-in is stopped
	*
	* @return void
	*/
	public function deactivationHook()
	{
		// Processing when the plug-in is stopped
	}

	/**
	* do_set_default
	*
	* Set defualt value
	*
	* @return void
	*/
	private function do_set_default()
	{
		// only first activation
		if ( !get_option('ket_installed') )
		{
			// set install flag
			update_option( 'ket_installed', '1' );
		}
	}


	/**
	*  init_action
	*
	*  Set Action & Filter Hook
	*
	*  @type
	*  @date
	*  @since	1.0.0
	*
	*  @param	none
	*  @return void
	*/
	public function init_action()
	{
		add_action( 'add_meta_boxes', array( $this, 'add_metabox_to_posts' ), 10, 2 );
		add_action( 'save_post', array( $this, 'save_post' ), 10, 1 );
		add_filter( 'hidden_meta_boxes', array( $this, 'hide_meta_box' ), 10, 2 );
		add_filter( 'wp_default_editor', array( $this, 'manage_default_editor_type' ) );
	}// function


	/**
	*  admin_scripts_and_styles
	*
	*  CSS & JS for Admin
	*
	*  @type	function
	*  @date
	*  @since	1.0.0
	*
	*  @param	$hook_suffix
	*  @return	void
	*/
	public function admin_scripts_and_styles( $hook_suffix )
	{
		global $post_type;// get current post type

		if ( ! in_array( $hook_suffix, array( 'post-new.php', 'post.php' ) ) )
		{
			return;
		}
		// get support post types
		$support_post_types = $this->get_support_post_types();
		// only support post types
		if ( ! in_array( $post_type, $support_post_types, true ) ) {
			return false;
		}

		// for editor
		wp_enqueue_script(
			'keep-editor-type',
			plugin_dir_url(__FILE__) . 'assets/admin/js/editor-type-admin.js',
			array('jquery'),
			true
		);
	}// end function


	/*
	*  add_metabox_to_posts
	*
	*  This function will adds a new metabox on our single post edit screen
	*
	*  @type	function
	*  @date	12/11/2017
	*  @since	1.0.0
	*
	*  @param	$post_type (string), $post(object)
	*  @return	n/a
	*/
	public function add_metabox_to_posts( $post_type, $post )
	{
		// get support post types
		$support_post_types = $this->get_support_post_types();

		add_meta_box(
			'ket-post-mb',
			__( 'Keep Editor Type Post Meta', 'keep-editor-type' ),
			array( $this, 'display_metabox_output' ),
			$support_post_types,
			'side',
			'default'
		);
	}// end function

	/*
	*  display_metabox_output
	*
	*  This function will metabox output function, displays our fields, prepopulating as needed
	*
	*  @type	function
	*  @date	12/11/2017
	*  @since	1.0.0
	*
	*  @param	$post(object)
	*  @return	n/a
	*/
	public function display_metabox_output( $post ){
		// nonce
		wp_nonce_field( 'ket_post_mb_nonce', 'ket_post_mb_nonce' );

		if ( ! $cf_editor_type = get_post_meta( $post->ID, '_ket_editor_type', true ) )
		{
			$cf_editor_type = '';
		}
		?>
		<div id="keep-editor-type" class="keep-editor-type">
			<input type="hidden" name="ket_editor_type" value="<?php echo esc_attr( $cf_editor_type ); ?>" id="ket_cf_editor_type" class="<?php echo esc_attr( $cf_editor_type ); ?>">
		</div><!-- /#keep-editor-type -->
		<?php
	}// end function

	/*
	*  hide_meta_box
	*
	*  defalut hide_meta_box
	*
	*  @type	function
	*  @date	12/11/2017
	*  @since	1.0.0
	*
	*  @param	$hidden(array), $screen(object)
	*  @return	(array)
	*/
	public function hide_meta_box( $hidden, $screen )
	{
		global $post_type;
		// get support post types
		$support_post_types = $this->get_support_post_types();
		// only support post types
		if ( ! in_array( $post_type, $support_post_types, true ) ) {
			return false;
		}

		$hidden[] = 'ket-post-mb';
		return $hidden;
	}



	/*
	*  save_post
	*
	*  This function will saving meta info (used for both traditional and quick-edit saves)
	*
	*  @type	function
	*  @date	12/11/2017
	*  @since	1.0.0
	*
	*  @param	$post_id (int)
	*  @return	n/a
	*/
	public function save_post( $post_id )
	{
		global $post;

		// get current post type
		$cr_post_type = get_post_type( $post_id );
		// get support post types
		$support_post_types = $this->get_support_post_types();
		// only support post types
		if ( ! in_array( $cr_post_type, $support_post_types, true ) ) {
			return false;
		}
		// check nonce set
		if( ! isset( $_POST['ket_post_mb_nonce'] ) ){
			return false;
		}
		// verify nonce
		if( ! wp_verify_nonce( $_POST['ket_post_mb_nonce'], 'ket_post_mb_nonce' ) ){
			return false;
		}
		//if not autosaving
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}


		// Make sure that it is set.
		if ( ! isset( $_POST['ket_editor_type'] ) ) {
			return;
		}
		// Sanitize
		$cr_editor_type = sanitize_text_field( $_POST['ket_editor_type'] );

	    // multiple post avoid
		remove_action( 'save_post', array( $this, 'save_post' ) );
		$last_editor_type = get_post_meta( $post_id, '_ket_editor_type', true );
		if ( empty( $last_editor_type ) || ( !empty( $last_editor_type ) && $last_editor_type !== $cr_editor_type ) )
		{
			update_post_meta( $post_id, '_ket_editor_type', esc_html( $cr_editor_type ) );
		}
		add_action( 'save_post', array( $this, 'save_post' ) );
	}// end function



	/*
	*  manage_default_editor_type
	*
	*  This function will set editor type.
	*
	*  @type	function
	*  @date	12/11/2017
	*  @since	1.0.0
	*
	*  @param	$post_id (int)
	*  @return	n/a
	*/
	public function manage_default_editor_type() {
		global $post_id;

		// get current post type
		$cr_post_type = get_post_type( $post_id );
		// get support post types
		$support_post_types = $this->get_support_post_types();
		// only support post types
		if ( ! in_array( $cr_post_type, $support_post_types, true ) ) {
			return false;
		}

		// get meta info & set editor type
		if( $last_editor_type = get_post_meta( $post_id, '_ket_editor_type', true ) )
		{
			$editor_type = esc_html( $last_editor_type );
		}

		return $editor_type;
	}// end function



	/* *********************************************
		Functions
	********************************************* */
	/*
	*  get_support_post_types
	*
	*  This function will get support editor post types.
	*
	*  @type	function
	*  @date	12/11/2017
	*  @since	1.0.0
	*
	*  @param	$post_id (int)
	*  @return	(array)
	*/
	/*
		Editorをサポートする投稿タイプの取得
	*/
	private function get_support_post_types()
	{
		$post_types = get_post_types( '', 'names' );
		$support = 'editor';

		foreach ( $post_types as $post_type ) {
			if ( ! post_type_supports( $post_type, $support ) ) {
				// If you do not support editor, exclude from post type
				unset( $post_types[$post_type] );
			}//if
		}
		return $post_types;
	}



	/* *********************************************
	********************************************* */
	//gets instance
	public static function getInstance()
	{
		if( is_null(self::$instance) )
		{
			self::$instance = new self();
		}
		return self::$instance;
	}
}//class Kepp_Editor_Type
// initialize
$Kepp_Editor_Type = Kepp_Editor_Type::getInstance();


/**
 * ket_uninstallHook
 *
 * Method executed when the plugin is deleted (uninstalled)
 *
 * @return void
*/
function ket_uninstallHook( $network_wide )
{
	$my_plugin = 'keep-editor-type/keep-editor-type.php';

	if ( is_multisite() )
	{
		// Delete option for all site
		$sites = get_sites();
		foreach ( $sites as $site ) {
			// switch blog
			switch_to_blog( $site->blog_id );

				if ( !$network_wide && in_array( $my_plugin, get_option('active_plugins') ) )
				{// If activate from each blog & not deactive
					// when re-install, still activate. and no option data.
					// do deactive plugin
					deactivate_plugins( plugin_basename( __FILE__ ) );
				}

				if ( get_option( 'ket_installed' ) === '1' )
				{
					// Delete post meta
					delete_post_meta_by_key( '_ket_editor_type' );
					// Delete installed flag
					delete_option( 'ket_installed' );
				}
			// return blog
			restore_current_blog();
		}// foreach
	} else {// 削除 通常
		// Delete post meta
		delete_post_meta_by_key( '_ket_editor_type' );
		// Delete installed flag
		delete_option( 'ket_installed' );
	}
}

// class_exists check
endif;
?>
