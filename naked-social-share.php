<?php
/*
 * Plugin Name: Naked Social Share
 * Plugin URI: https://shop.nosegraze.com/product/naked-social-share/
 * Description: Simple, unstyled social share icons for theme designers.
 * Version: 1.1.0
 * Author: Nose Graze
 * Author URI: https://www.nosegraze.com
 * License: GPL2
 * Text Domain: naked-social-share
 * Domain Path: lang
 *
 * @package   naked-social-share
 * @copyright Copyright (c) 2015, Ashley Evans
 * @license   GPL2+
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * Include the main plugin class.
 */
include_once plugin_dir_path( __FILE__ ) . 'class-naked-social-share.php';

/**
 * Loads the whole plugin.
 *
 * @since 1.0.0
 * @return Naked_Social_Share
 */
function Naked_Social_Share() {
	$instance = Naked_Social_Share::instance( __FILE__, '1.1.0' );

	return $instance;
}

Naked_Social_Share();

/**
 * The main function used for displaying the share markup.
 * This can be placed in your theme template file.
 *
 * @since 1.0.0
 * @return void
 */
function naked_social_share_buttons() {
	$share_obj = new Naked_Social_Share_Buttons();
	$share_obj->display_share_markup();
}