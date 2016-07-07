<?php
/**
 * Plugin Name: Dude last.fm feed
 * Plugin URL: https://www.dude.fi
 * Description: Fetches the latest scrobbles from last.fm user
 * Version: 0.1.0
 * Author: Timi Wahalahti / DUDE
 * Author URL: http://dude.fi
 * Requires at least: 4.4.2
 * Tested up to: 4.4.2
 *
 * Text Domain: dude-lastfm-feed
 * Domain Path: /languages
 */

if( !defined( 'ABSPATH' )  )
	exit();

Class Dude_Lastfm_Feed {
  private static $_instance = null;

  /**
   * Construct everything and begin the magic!
   *
   * @since   0.1.0
   * @version 0.1.0
   */
  public function __construct() {
    // Add actions to make magic happen
    add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
  } // end function __construct

  /**
   *  Prevent cloning
   *
   *  @since   0.1.0
   *  @version 0.1.0
   */
  public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'dude-lastfm-feed' ) );
	} // end function __clone

  /**
   *  Prevent unserializing instances of this class
   *
   *  @since   0.1.0
   *  @version 0.1.0
   */
  public function __wakeup() {
    _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'dude-lastfm-feed' ) );
  } // end function __wakeup

  /**
   *  Ensure that only one instance of this class is loaded and can be loaded
   *
   *  @since   0.1.0
   *  @version 0.1.0
	 *  @return  Main instance
   */
  public static function instance() {
    if( is_null( self::$_instance ) ) {
      self::$_instance = new self();
    }

    return self::$_instance;
  } // end function instance

  /**
   *  Load plugin localisation
   *
   *  @since   0.1.0
   *  @version 0.1.0
   */
  public function load_plugin_textdomain() {
    load_plugin_textdomain( 'dude-lastfm-feed', false, dirname( plugin_basename( __FILE__ ) ).'/languages/' );
  } // end function load_plugin_textdomain

	public function get_user_scrobbles( $username = '' ) {
		if( empty( $username ) )
			return;

		$transient_name = apply_filters( 'dude-lastfm-feed/user_scrobbles_transient', 'dude-lastfm-user-'.$username, $username );
		$scrobbles = get_transient( $transient_name );
	  if( !empty( $scrobbles ) || false != $scrobbles )
	    return $scrobbles;

		$parameters = array(
			'method'			=> 'user.getRecentTracks',
			'api_key'			=> apply_filters( 'dude-lastfm-feed/api_key', '' ),
			'format'			=> 'json',
			'user'				=> $username,
			'limit'				=> '5',
		);

		$response = self::_call_api( apply_filters( 'dude-lastfm-feed/user_scrobbles_parameters', $parameters ) );
		if( $response === FALSE )
			return;

		$response = apply_filters( 'dude-lastfm-feed/user_scrobbles', json_decode( $response['body'], true ) );
		set_transient( $transient_name, $response, apply_filters( 'dude-lastfm-feed/user_scrobbles_lifetime', '600' ) );

		return $response;
	} // end function get_users_scrobbles

	private function _call_api( $parameters = array() ) {
		if( empty( $parameters ) )
			return false;

		$parameters = http_build_query( $parameters );
		$response = wp_remote_get( 'http://ws.audioscrobbler.com/2.0/?'.$parameters );

		if( $response['response']['code'] !== 200 ) {
			self::_write_log( 'response status code not 200 OK, function: '.$parameters['endpoint'] );
			return false;
		}

		return $response;
	} // end function _call_api

	private function _write_log ( $log )  {
    if( true === WP_DEBUG ) {
      if( is_array( $log ) || is_object( $log ) ) {
        error_log( print_r( $log, true ) );
      } else {
        error_log( $log );
      }
    }
  } // end _write_log
} // end class Dude_Lastfm_Feed

function dude_lastfm_feed() {
  return new Dude_Lastfm_Feed();
} // end function dude_lastfm_feed
