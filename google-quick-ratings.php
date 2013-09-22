<?php
/*
Plugin Name: Google Quick Ratings
Plugin URI: http://philipnewcomer.net/wordpress-plugins/google-quick-ratings/
Description: Allows a post author to add a simple 5-star rating to posts.
Version: 1.1
Author: Philip Newcomer
Author URI: http://philipnewcomer.net
License: GPL2
Text Domain: google-quick-ratings
*/

/*  Copyright 2012 Philip Newcomer (email: contact@philipnewcomer.net)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define( 'PN_APR_SETTINGS_KEY', 'pn_apr_settings' );
define( 'PN_APR_RATING_META_KEY', '_pn_apr_rating' );
define( 'PN_APR_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );



function pn_apr_get_defaults()
{
	$defaults = array(
		'label_text' => __( 'Rating:', 'google-quick-ratings' ),
		'position_on_post' => 'top', // Options: top, bottom, or shortcode
		'sprite_on_post' => 'star', // Options: star, star2, gradient, or heart
		'post_types_enabled' => array( 'post' ),
		'show_in_google_results' => true, // Activate star rating in Google search results
		'show_only_on_singular' => false
	);
	return $defaults;
}



function pn_apr_load_textdomain()
{
	load_plugin_textdomain( 'google-quick-ratings', null, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'pn_apr_load_textdomain' );



/* Load settings from database; If no settings have been saved, use default settings. */
function pn_apr_settings_init()
{
	// Make settings array global
	global $pn_apr_settings;
	
	// Load settings defaults
	$pn_apr_default_settings = pn_apr_get_defaults();
	
	// Load saved settings from the database. If there are no saved settings, save and use defaults
	if ( ! $pn_apr_settings = get_option( PN_APR_SETTINGS_KEY ) ) {
		update_option( PN_APR_SETTINGS_KEY, $pn_apr_default_settings );
		$pn_apr_settings = $pn_apr_default_settings;
	} else {
		$pn_apr_settings = array_merge( $pn_apr_default_settings, $pn_apr_settings );
	}
}
add_action( 'after_setup_theme', 'pn_apr_settings_init' );



function pn_apr_enqueue_css()
{
	wp_enqueue_style( 'google-quick-ratings', PN_APR_PLUGIN_DIR_URL . 'google-quick-ratings.css' );
}
add_action( 'wp_enqueue_scripts', 'pn_apr_enqueue_css' );



function get_author_post_rating( $post_id = null )
{	
	/* $rating = (int) get_post_meta( $post_id, PN_APR_RATING_META_KEY, true ); */
	$rating = get_post_meta( $post_id, PN_APR_RATING_META_KEY, true );
	return $rating;
}



function google_quick_ratings( $post_id = null, $return = false )
{
	global $pn_apr_settings;
	
	// if no post ID is sent, try to obtain it ourselves.
	if ( $post_id == null ) $post_id = get_the_ID();
	
	$rating = get_author_post_rating( $post_id );
	
	if ( $rating ) {
		
		$output = null;
		
		$output .= '<div class="google-quick-ratings">';
		
		$output .= '<span class="google-quick-ratings-label">' . esc_attr( $pn_apr_settings['label_text'] ) . '</span> ';
		
		if ( $pn_apr_settings['show_in_google_results'] == true ) {	
			$output .= '<span style="display: block; width: 65px; height: 13px; background: url(' . PN_APR_PLUGIN_DIR_URL . 'images/' . $pn_apr_settings['sprite_on_post'] . '-rating-sprite.png) 0 0;">
					<span class="rating-foreground" title="Note: ' . $rating . ' / 5" style="display: block; width: ' . $rating/5*100 . '%; height: 13px; background: url(' . PN_APR_PLUGIN_DIR_URL . 'images/' . $pn_apr_settings['sprite_on_post'] . '-rating-sprite.png) 0 -13px;"></span>
			</span>' ;
		} else {
			$output .= '<span style="display: block; width: 65px; height: 13px; background: url(' . PN_APR_PLUGIN_DIR_URL . 'images/' . $pn_apr_settings['sprite_on_post'] . '-rating-sprite.png) 0 0;">
				<span title="Note: ' . $rating . ' / 5" style="display: block; width: ' . $rating/5*100 . '%; height: 13px; background: url(' . PN_APR_PLUGIN_DIR_URL . 'images/' . $pn_apr_settings['sprite_on_post'] . '-rating-sprite.png) 0 -13px;"></span>
			</span>' ;
		}
			
		$output .= '</div><!-- .google-quick-ratings -->';
		
		if ( true == $return ) { return $output; }
	
		// We don't need to use "else" here, since calling return will automatically stop further execution of this function.
		echo $output;
		
	}
}



/* Add APR shortcode to top or bottom of post, depending on settings. I used shortcodes instead of directly prepending/appending the APR output to the content so that the post rating doesn't appear in post excerpts. That would be bad, because WordPress would strip out the APR star markup, leaving only the label text with no stars. */
function pn_apr_the_content_filter( $content )
{	
	global $pn_apr_settings;
	$post_type = get_post_type( get_the_ID() );
	
	// If the 'show only on single post' setting is turned on, respect that
	if ( $pn_apr_settings['show_only_on_singular'] == true && ! is_singular() )
		return $content;
	
	// Only add the rating shortcode if this post type is enabled in the settings. A user may have added a rating to a post, and then turned off the rating for that post type.
	if ( in_array( $post_type, $pn_apr_settings['post_types_enabled'] ) ) {
	
		if ( 'top' == $pn_apr_settings['position_on_post'] ) {
			$content = '[google-quick-ratings]' . $content; 
		} elseif ( 'bottom' == $pn_apr_settings['position_on_post'] ) {
			$content .= '[google-quick-ratings]';
		}
		
	}
	
	return $content;
}
add_filter( 'the_content', 'pn_apr_the_content_filter', 10 );



function pn_apr_shortcode()
{
	return google_quick_ratings( get_the_ID(), true );
}
add_shortcode( 'google-quick-ratings', 'pn_apr_shortcode' );



/* Add the APR meta box to the edit screen for all enabled post types */
function pn_apr_add_meta_boxes()
{
	global $pn_apr_settings;
	
	foreach ( $pn_apr_settings['post_types_enabled'] as $post_type_name ) {
		add_meta_box(
			'pn_apr_meta_box',
			__( 'Post Rating', 'google-quick-ratings' ),
			'pn_apr_meta_box_form',
			$post_type_name,
			'side',
			'default'
		);
	}
}
add_action( 'add_meta_boxes', 'pn_apr_add_meta_boxes' );



/* Output the content of the APR meta box */
function pn_apr_meta_box_form( $post )
{	
	wp_nonce_field( 'pn_apr_meta_box_nonce', 'pn_apr_meta_box_nonce_field' );
	
	$current_post_rating = get_post_meta( $post->ID, PN_APR_RATING_META_KEY, true );
	
	echo '<label for="pn_apr_rating">' . __( 'Choose a rating for this post:', 'google-quick-ratings' ) . '</label> ';
	echo '<select name="pn_apr_rating" id="pn_apr_rating">';
	echo '<option value="unrated"' . selected( $current_post_rating, 0, false ) . '>' . __( 'Unrated', 'google-quick-ratings' ) . '</option>';
	for ( $i = 0.5; $i <= 5; $i=$i+0.5 ) {
		echo '<option value="' . $i . '"' . selected( $current_post_rating, $i, false ) . '>' . sprintf( _n( '%1s Star', '%1s Stars', $i, 'google-quick-ratings' ), $i ) . '</option>';
	}
	echo '</select>';
}



function pn_apr_save_meta_box_data( $post_id )
{
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		return;
	
	if ( !isset( $_POST['pn_apr_meta_box_nonce_field'] ) || !wp_verify_nonce( $_POST['pn_apr_meta_box_nonce_field'], 'pn_apr_meta_box_nonce' ) )
		return;
	
	if ( !current_user_can( 'edit_post', $post_id ) )
		return;
	
	$post_rating = $_POST['pn_apr_rating'];
	
	if ( $post_rating == 'unrated' ) {
		// Delete the post rating custom field value if the post is unrated
		delete_post_meta( $post_id, PN_APR_RATING_META_KEY );
	} else {
		update_post_meta( $post_id, PN_APR_RATING_META_KEY, $post_rating );
	}
}
add_action( 'save_post', 'pn_apr_save_meta_box_data', 10, 2 );



function pn_apr_admin_init()
{
	register_setting( 'pn_apr_settings_fields', PN_APR_SETTINGS_KEY, 'pn_apr_settings_validation' );

	add_settings_section( 'google-search-results', __('Ratings on Google', 'google-quick-ratings'), '__return_false', 'pn_apr_settings' );
	add_settings_section( 'appearance', __('Ratings on Website', 'google-quick-ratings'), '__return_false', 'pn_apr_settings' );
	add_settings_section( 'position', __('Position on post', 'google-quick-ratings'), '__return_false', 'pn_apr_settings' );
	add_settings_section( 'post-types', __('Post Types', 'google-quick-ratings'), '__return_false', 'pn_apr_settings' );

	add_settings_field( 'show_in_google_results',  __('Ratings display on Google', 'google-quick-ratings'), 'pn_apr_settings_field_show_in_google_results', 'pn_apr_settings', 'google-search-results' );
	add_settings_field( 'position_on_post', __('Rating position on post', 'google-quick-ratings'), 'pn_apr_settings_field_position_on_post', 'pn_apr_settings', 'position' );
	add_settings_field( 'show_only_on_singular', null, 'pn_apr_settings_field_show_only_on_singular', 'pn_apr_settings', 'position' );
	add_settings_field( 'sprite_on_post', __('Text label', 'google-quick-ratings'), 'pn_apr_settings_field_label_text', 'pn_apr_settings', 'appearance' );
	add_settings_field( 'label_text', __('Icons', 'google-quick-ratings'), 'pn_apr_settings_sprite_on_post', 'pn_apr_settings', 'appearance' );
	add_settings_field( 'post_types', __('Post types enabled', 'google-quick-ratings'), 'pn_apr_settings_field_post_types', 'pn_apr_settings', 'post-types' );
}
add_action( 'admin_init', 'pn_apr_admin_init' );



/* Init the settings page */
function pn_apr_settings_page_init()
{
	add_options_page( __('Google Quick Ratings Settings', 'google-quick-ratings' ), __('Google Quick Ratings', 'google-quick-ratings' ), 'manage_options', 'google-quick-ratings', 'pn_apr_settings_page_content' );
}
add_action( 'admin_menu', 'pn_apr_settings_page_init' );



function pn_apr_settings_page_content()
{
?><div class="wrap">
	<div id="icon-options-general" class="icon32"><br></div>
	<h2><?php _e('Google Quick Ratings Settings', 'google-quick-ratings'); ?></h2>
		
	<form method="post" action="options.php">
		<?php settings_fields( 'pn_apr_settings_fields' ); ?>
		<?php do_settings_sections( 'pn_apr_settings' ); ?>
		<?php submit_button(); ?>
	</form>
	
	<h3><?php _e('Tip to insert post ratings', 'google-quick-ratings'); ?></h3>
	<p><?php printf( __('The post rating will be automatically added to your post if the setting is turned on above.<br /><br />To manually display the post rating anywhere in your post, use the shortcode %1$s.<br /><br />To manually display the post rating anywhere in your code, use the php function %2$s.', 'google-quick-ratings'), '<code>[google-quick-ratings]</code>', '<code>&lt;?php if(function_exists(\'google_quick_ratings\')) { google_quick_ratings(); } ?&gt;</code><br /><br />Also visit Data Highlighter tools of <a href="http://www.google.com/webmasters/">Google Webmaster</a> to accelerate your ratings indexation.'); ?></p>
	
	<?php
	/* For debugging: */
	//global $pn_apr_settings; echo '<h2>Settings Debugging Info</h2><h3>Current settings</h3><pre>'; print_r( $pn_apr_settings ); echo '</pre><h3>Saved settings</h3><pre>'; print_r( get_option( PN_APR_SETTINGS_KEY ) ); echo '</pre><h3>Default settings</h3><pre>'; print_r( pn_apr_get_defaults() ); echo '</pre>';
	?>
	
</div><?php
}



function pn_apr_settings_validation( $input )
{
	// Get defaults in case data isn't valid
	$defaults = pn_apr_get_defaults();
	
	// label_text: Text area
	
		$validated['label_text'] = wp_kses_post( (string) $input['label_text'] );
	
	// position_on_post: Radio button
	
		$position_on_post_valid_options = array(
			'top',
			'bottom',
			'shortcode'
		);
	
		if ( in_array( $input['position_on_post'], $position_on_post_valid_options ) ) {
		
			// Data is valid
			$validated['position_on_post'] = $input['position_on_post'];
		
		} else {
		
			// Data is not valid
			$validated['position_on_post'] = $defaults['position_on_post'];
		
		}
	
	// sprite_on_post: Radio button
	
		$sprite_on_post_valid_options = array(
			'star',
			'star2',
			'gradient',
			'heart'
		);
	
		if ( in_array( $input['sprite_on_post'], $sprite_on_post_valid_options ) ) {
		
			// Data is valid
			$validated['sprite_on_post'] = $input['sprite_on_post'];
		
		} else {
		
			// Data is not valid
			$validated['sprite_on_post'] = $defaults['sprite_on_post'];
		
		}


	// post_types_enabled: list of checkboxes
		
		$validated['post_types_enabled'] = array();
		
		if ( isset( $input['post_types_enabled'] ) ) {
			
			foreach ( $input['post_types_enabled'] as $current_post_type_name => $current_post_type_value ) {
				
				if ( $current_post_type_value == 'on' ) {
					
					$validated['post_types_enabled'][] = $current_post_type_name;
					
				}
				
			}
			
		}
	
	// show_only_on_singular: Checkbox

		if ( isset( $input['show_only_on_singular'] ) && $input['show_only_on_singular'] == 'on' ) {
			$validated['show_only_on_singular'] = true;
		} else {
			$validated['show_only_on_singular'] = false;
		}
		
		
	// show_in_google_results: Checkbox

		if ( isset( $input['show_in_google_results'] ) && $input['show_in_google_results'] == 'on' ) {
			$validated['show_in_google_results'] = true;
		} else {
			$validated['show_in_google_results'] = false;
		}
		
		
	
	// For debugging:
	//echo '<h3>Input</h3><pre>'; print_r( $input ); echo '</pre><h3>Validated</h3><pre>'; print_r( $validated ); echo '</pre><h3>Defaults</h3><pre>'; print_r( pn_apr_get_defaults() ); echo '</pre>'; die();
	
	return $validated;
}



function pn_apr_settings_field_position_on_post()
{
	global $pn_apr_settings;
?>
<input type="radio" name="pn_apr_settings[position_on_post]" id="pn_apr_settings_position_on_post_top"<?php checked( 'top', $pn_apr_settings['position_on_post'] ); ?> value="top" />
	<label for="pn_apr_settings_position_on_post_top"><?php _e('Top of post', 'google-quick-ratings'); ?></label><br />
<input type="radio" name="pn_apr_settings[position_on_post]" id="pn_apr_settings_position_on_post_bottom"<?php checked( 'bottom', $pn_apr_settings['position_on_post'] ); ?> value="bottom" />
	<label for="pn_apr_settings_position_on_post_bottom"><?php _e('Bottom of post', 'google-quick-ratings'); ?></label><br />
<input type="radio" name="pn_apr_settings[position_on_post]" id="pn_apr_settings_position_on_post_shortcode"<?php checked( 'shortcode', $pn_apr_settings['position_on_post'] ); ?> value="shortcode" />
	<label for="pn_apr_settings_position_on_post_shortcode"><?php _e("By shortcode / php function only (don't automatically add to post)", 'google-quick-ratings'); ?></label>
<?php
}


function pn_apr_settings_sprite_on_post()
{
	global $pn_apr_settings;
?>
<input type="radio" name="pn_apr_settings[sprite_on_post]" id="pn_apr_settings_sprite_on_post_star"<?php checked( 'star', $pn_apr_settings['sprite_on_post'] ); ?> value="star" />
	<label for="pn_apr_settings_sprite_on_post_star"><span style="display: inline-block; width: 65px; height: 13px; background: url(<?php echo PN_APR_PLUGIN_DIR_URL?>images/star-rating-sprite.png) 0 0;"><span class="rating-foreground" title="Note: 4/ 5" style="display: block; width: 80%; height: 13px; background: url(<?php echo PN_APR_PLUGIN_DIR_URL?>images/star-rating-sprite.png) 0 -13px;"></span></span></label><br />		
<input type="radio" name="pn_apr_settings[sprite_on_post]" id="pn_apr_settings_sprite_on_post_star2"<?php checked( 'star2', $pn_apr_settings['sprite_on_post'] ); ?> value="star2" />
	<label for="pn_apr_settings_sprite_on_post_star2"><span style="display: inline-block; width: 65px; height: 13px; background: url(<?php echo PN_APR_PLUGIN_DIR_URL?>images/star2-rating-sprite.png) 0 0;"><span class="rating-foreground" title="Note: 4/ 5" style="display: block; width: 80%; height: 13px; background: url(<?php echo PN_APR_PLUGIN_DIR_URL?>images/star2-rating-sprite.png) 0 -13px;"></span></span></label><br />
<input type="radio" name="pn_apr_settings[sprite_on_post]" id="pn_apr_settings_sprite_on_post_gradient"<?php checked( 'gradient', $pn_apr_settings['sprite_on_post'] ); ?> value="gradient" />
	<label for="pn_apr_settings_sprite_on_post_gradient"><span style="display: inline-block; width: 65px; height: 13px; background: url(<?php echo PN_APR_PLUGIN_DIR_URL?>images/gradient-rating-sprite.png) 0 0;"><span class="rating-foreground" title="Note: 4/ 5" style="display: block; width: 80%; height: 13px; background: url(<?php echo PN_APR_PLUGIN_DIR_URL?>images/gradient-rating-sprite.png) 0 -13px;"></span></span></label><br />
<input type="radio" name="pn_apr_settings[sprite_on_post]" id="pn_apr_settings_sprite_on_post_heart"<?php checked( 'heart', $pn_apr_settings['sprite_on_post'] ); ?> value="heart" />
	<label for="pn_apr_settings_sprite_on_post_heart"><span style="display: inline-block; width: 65px; height: 13px; background: url(<?php echo PN_APR_PLUGIN_DIR_URL?>images/heart-rating-sprite.png) 0 0;"><span class="rating-foreground" title="Note: 4/ 5" style="display: block; width: 80%; height: 13px; background: url(<?php echo PN_APR_PLUGIN_DIR_URL?>images/heart-rating-sprite.png) 0 -13px;"></span></span></label><br />
<?php
}


function pn_apr_settings_field_show_only_on_singular()
{
	global $pn_apr_settings;
?>
<input type="checkbox" name="pn_apr_settings[show_only_on_singular]" id="pn_apr_settings_show_only_on_singular" <?php checked( $pn_apr_settings['show_only_on_singular'] ); ?> />
<label for="pn_apr_settings_show_only_on_singular"><?php _e('Show only in singular post view', 'google-quick-ratings'); ?></label>
<p class="description"><?php printf( __('Turn this on if you want to hide the post rating on archive pages.', 'google-quick-ratings')); ?></p>
<?php
}


function pn_apr_settings_field_show_in_google_results()
{
	global $pn_apr_settings;
?>
<input type="checkbox" name="pn_apr_settings[show_in_google_results]" id="pn_apr_settings_show_in_google_results" <?php checked( $pn_apr_settings['show_in_google_results'] ); ?> />
<label for="pn_apr_settings_show_in_google_results"><?php _e('Show stars in Google search results', 'google-quick-ratings'); ?></label>
<p class="description"><?php _e('This will activate Google snippets, but Google may take some time to display ratings.', 'google-quick-ratings'); ?></p>
<?php
}



function pn_apr_settings_field_label_text()
{
	global $pn_apr_settings;
?>
<input type="text" name="pn_apr_settings[label_text]" id="pn_apr_settings_description_text" value="<?php echo esc_attr( $pn_apr_settings['label_text'] ); ?>" />
<?php
}



function pn_apr_settings_field_post_types()
{
	global $pn_apr_settings;
	
	$builtin_post_types = array( 'post', 'page' );
	$additional_post_types = get_post_types( array( 'public' => true, '_builtin' => false ) );
	$post_types_available = array_merge( $builtin_post_types, $additional_post_types );
	
	foreach ( $post_types_available as $post_type ) {
		echo '<div><input type="checkbox" name="pn_apr_settings[post_types_enabled][' . $post_type . ']" ' . checked( in_array( $post_type, $pn_apr_settings['post_types_enabled'] ), true, false) . ' id="pn_apr_settings_post_types_enabled-' . $post_type . '" />';
		echo ' <label for="pn_apr_settings_post_types_enabled-' . $post_type . '">' . $post_type . '</label></div>';
	}
}
