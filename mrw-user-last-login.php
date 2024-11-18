<?php
/**
 * Plugin Name:       MRW User Last Login
 * Description:       Saves user's last login to user meta and shows it on the All Users screen
 * Version:           1.0.0
 * Author:            Mark Root-Wiley, MRW Web Design
 * Author URI:        https://MRWweb.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mrwull
 * Tested up to:      6.7
 * PHP Version:       7.0
 *
 * @package mrw-user-last-login
 */

// TODO: text on multisite
// TODO: Autoexpire users over X days old
// TODO: (low priority, but easy) Make columns filterable

namespace MRW\UserLastLogin;
use DateTime;

add_filter( 'manage_users_columns', __NAMESPACE__ . '\add_user_info_columns' );
/**
 * Register two new columns for the Users list table
 *
 * @param arr $columns
 * @return arr $columns
 */
function add_user_info_columns( $columns ) {
    $columns[ 'registration_date' ] = esc_html__( 'Registration Date', 'mrwull' );
    $columns[ 'last_login' ] = esc_html__( 'Last Login', 'mrwull' );

    return $columns;
}

add_filter( 'manage_users_sortable_columns', __NAMESPACE__ . '\register_sortable_columns' );
/**
 * Registers the two new columns for the users Admin table
 *
 * @param array $columns
 * @return array $columns
 */
function register_sortable_columns( $columns ) {
    $columns['registration_date'] = array(
        'registration_date',
        false,
        __( 'Registered On', 'mrwull' ),
        __( 'Tabled ordered by registration date', 'mrwull' ),
        'desc'
    );

    $columns['last_login'] = array(
        'last_login',
        false,
        __( 'Last Login', 'mrwull' ),
        __( 'Tabled ordered by last login date', 'mrwull' ),
        'desc'
    );

    return $columns;
}


add_action( 'manage_users_custom_column', __NAMESPACE__ . '\show_user_info', 10, 3 );
/**
 * Show values for custom User list table columns
 *
 * @param str $value Value of column
 * @param str $column_name Column name
 * @param str $user_id ID of the user for the current row
 * @return str value to display in column
 */
function show_user_info( $value, $column_name, $user_id ) {
    if( ! in_array( $column_name, array( 'registration_date', 'last_login' ) ) ) {
        return $value;
    }

    $date_format = get_option( 'date_format' );
    $time_format = get_option( 'time_format' );
    $timezone_offset = get_option( 'gmt_offset' );
    $timezone_offset_in_seconds = $timezone_offset * HOUR_IN_SECONDS;
    $datetime_format = $date_format . ' ' . $time_format;
    $plugin_options = get_option( 'mrwull_options' );
    $before_date = new DateTime( $plugin_options['first_install_date_gmt'] );
    $before_date_l10n = date( esc_attr( $date_format ), $before_date->getTimestamp() + $timezone_offset_in_seconds );
    $user = get_userdata( $user_id );

    if ( $column_name === 'registration_date' ) {

        $value = date( esc_attr( $date_format ), strtotime( $user->user_registered ) + $timezone_offset_in_seconds );

    } elseif ( $column_name === 'last_login' ) {

        $last_login = get_user_meta($user_id, 'last_login_gmt', true);

        if ( ! empty( $last_login ) ) {

            $last_login = new DateTime( $last_login );  
            $value = date( esc_attr( $datetime_format ), $last_login->getTimestamp() + $timezone_offset_in_seconds );

        } else {
            $value = sprintf( esc_html__( 'Unknown (before %s)', 'mrwull' ), $before_date_l10n );
        }
    }

    return $value;
}

add_action( 'wp_login', __NAMESPACE__ . '\update_last_login' );
/**
 * Save current time of login when user logs in
 *
 * @param str $login User's username
 * @return void
 */
function update_last_login( $username ) {
    $user = get_user_by( 'login', $username );
    update_user_meta( $user->ID, 'last_login_gmt', \gmdate( 'Y-m-d H:i:s' ) );
}

add_action( 'pre_user_query', __NAMESPACE__ . '\orderby_user_last_login_gmt' );
/**
 * Make the Last Login column sortable
 *
 * @param WP_User_Query $query MySQL query string
 * @return void
 * 
 * @see https://jasonjalbuena.com/wordpress-add-and-sort-custom-column-in-users-admin-page/
 */
function orderby_user_last_login_gmt( $query ) {
	if( ! is_admin() ) {
		return;
    }

	$orderby = $query->get( 'orderby');

	if( 'last_login' == $orderby ) {
        global $wpdb;
		$query->query_from .= " LEFT OUTER JOIN $wpdb->usermeta AS alias ON ($wpdb->users.ID = alias.user_id) ";
        $query->query_where .= " AND alias.meta_key = 'last_login_gmt' ";
        $query->query_orderby = " ORDER BY alias.meta_value " . ($query->query_vars["order"] == "ASC" ? "asc " : "desc ");
	}
}

register_activation_hook( __FILE__, __NAMESPACE__ . '\set_plugin_install_date' );
/**
 * Activation function to set the plugin's first install date used for "Unknown" last login date
 *
 * @return void
 */
function set_plugin_install_date() {
    $plugin_options = get_option( 'mrwull_options' );
    
    if( isset( $plugin_options['first_install_date_gmt'] ) ) {
        return;
    }

    $plugin_options['first_install_date_gmt'] = gmdate( 'Y-m-d H:i:s' );

    update_option( 'mrwull_options', $plugin_options, false );
}
