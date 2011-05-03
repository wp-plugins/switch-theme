<?php

	/*

		Plugin Name: Switch Theme
		Plugin URI: http://wordpress.org/extend/plugins/switch-theme
		Version: 1.0
		
		Network: True
		
		Author: Tom Lynch
		Author URI: http://tomlynch.co.uk
		
		Description: Switch Theme allows WordPress Network Administrators to automatically switch all the themes on their WordPress installation to a different theme.
		
		License: GPLv3
		
		Copyright (C) 2011 Tom Lynch

	    This program is free software: you can redistribute it and/or modify
	    it under the terms of the GNU General Public License as published by
	    the Free Software Foundation, either version 3 of the License, or
	    (at your option) any later version.
	
	    This program is distributed in the hope that it will be useful,
	    but WITHOUT ANY WARRANTY; without even the implied warranty of
	    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	    GNU General Public License for more details.
	
	    You should have received a copy of the GNU General Public License
	    along with this program.  If not, see <http://www.gnu.org/licenses/>.
		
	*/
	
	class SwitchTheme {
		var $theme_panel_hook;
	
		function __construct() {
			add_filter( 'network_admin_plugin_action_links_' . plugin_basename( __FILE__ ), array( &$this, 'filter_plugin_action_links' ), 10, 2 );
			add_action( 'network_admin_menu', array( &$this, 'register_network_admin_menu' ) );
			add_filter( 'contextual_help', array(&$this, 'register_contextual_help'), 10, 2 );
		}
		
		function register_contextual_help( $help, $screen ) {
			if ( $screen == $this->theme_panel_hook . '-network' ) {
				$help = '<p>From this panel you can automatically switch all the blogs on your WordPress Installation to a different theme.</p>
					<p>To do this you need to choose a theme from the list below, and if your absolutely sure you want to change all blogs to use this theme from now on, then press "Switch" and Theme Switch will automatically go through all the blogs changing their settings.</p>
					<p>Please be aware if you have a large number of sites it can take a while to finish updating all the blog settings.</p>
					<p>
						<strong>For more information:</strong>
					</p>
					<p>
						<a href="http://wordpress.org/extend/plugins/switch-theme" target="_blank">Switch Theme Homepage</a>
					</p>
					<p>
						<a href="http://wordpress.org/tags/switch-theme" target="_blank">Switch Theme Forum</a>
					</p>';
			}
			return $help;
		}
		
		function filter_plugin_action_links( $links ) {
				array_unshift($links, '<a href="' . network_admin_url('themes.php') . '?page=switch-theme">Switch Theme</a>');
			return $links;
		}
		
		function register_network_admin_menu() {
			$this->theme_panel_hook = add_theme_page('Switch Theme', 'Switch Theme', 'manage_network_options', 'switch-theme', array( &$this, 'register_network_admin_page' ) );
		}
		
		function register_network_admin_page() {
			global $wpdb;
			
			if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'switchtheme' ) && $_POST['theme'] != '' && current_user_can( 'manage_network_options' ) ) {
				$blogs = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs;" );
				
				$themes = get_themes();
				$theme = $themes[$_POST['theme']];
				
				foreach ( $blogs as $blog_id ) {
					switch_to_blog( $blog_id );
					switch_theme( $theme['Template'], $theme['Stylesheet'] );
				}
				
				restore_current_blog();
				
				$done = true;
			}
			
			?>
				<div class="wrap">
					<div id="icon-themes" class="icon32"></div>
					<h2>Switch Theme</h2>
					<? if ( current_user_can( 'manage_network_options' ) ): ?>
						<? if (isset( $done )): ?>
							<div id="message" class="updated"><p>All blogs switched to the <?= $theme['Name'] ?> theme.</p></div>
						<? endif ?>
						<form method="post" action="themes.php?page=switch-theme">
							<input type="hidden" id="_wpnonce" name="_wpnonce" value="<?= wp_create_nonce( 'switchtheme' ) ?>">
							<p>Switch Theme will permanently change all blogs to use the selected theme, be careful!!</p>
							<table class="form-table">
								<tbody>
									<tr valign="top">
										<th scope="row">Theme</th>
										<td>
											<select name="theme">
												<option value="" selected="selected">Choose a theme</option>
												<? foreach ( get_themes() as $key => $theme ): ?>
													<option value="<?= $key ?>"><?= $theme['Name'] ?></option>
												<? endforeach ?>
											</select>
										</td>
									</tr>
								</tbody>
							</table>
							<p class="submit">
								<input type="submit" class="button-primary" value="Switch">
							</p>
						</form>
					<? else: ?>
						<p>You do not have permission to use Switch Theme.</p>
					<? endif ?>
				</div>
			<?
		}
	}
	
	if ( function_exists( 'is_multisite' ) && is_multisite() ) {
		$SwitchTheme = new SwitchTheme();
	} else {
		if ( ! function_exists( 'deactivate_plugins' ) )
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			
		deactivate_plugins( __FILE__ );

		wp_die( 'This Switch Theme can only be used with on a WordPress installation in multisite mode.' );
	}

?>