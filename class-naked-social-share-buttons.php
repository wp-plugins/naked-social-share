<?php

/**
 * Handles displaying the buttons and fetching the follower information.
 *
 * @package   naked-social-share
 * @copyright Copyright (c) 2015, Ashley Evans
 * @license   GPL2+
 */
class Naked_Social_Share_Buttons {

	/**
	 * The object of the current post.
	 *
	 * @var WP_Post
	 * @access public
	 * @since  1.0.0
	 */
	public $post;

	/**
	 * The permalink of the current post.
	 *
	 * @var string
	 * @access public
	 * @since  1.0.0
	 */
	public $url;

	/**
	 * The array of saved share numbers.
	 *
	 * @var array
	 * @access public
	 * @since  1.0.0
	 */
	public $share_numbers = array();

	/**
	 * Whether or not we should cache the social numbers.
	 *
	 * @var bool Set to false to disable the caching
	 * @access public
	 * @since  1.0.0
	 */
	public $cache = true;

	/**
	 * How in seconds long we should cache the social share numbers.
	 *
	 * @var int
	 * @access public
	 * @since  1.0.0
	 */
	public $cache_time = 7200; // 3 hours in seconds

	/**
	 * The settings from the options panel.
	 *
	 * @var array
	 * @access public
	 * @since  1.0.0
	 */
	public $settings;

	/**
	 * Constructor function
	 *
	 * Sets the post object and loads the saved share numbers.
	 *
	 * @param null|object $post
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function __construct( $post = null ) {
		$this->post = $post;

		// If no post object was provided, use the current post.
		if ( $this->post == null ) {
			global $post;
			$this->post = $post;
		}

		// Load the settings.
		$naked_social_share = Naked_Social_Share();
		$this->settings     = $naked_social_share->settings;

		$this->url = get_permalink( $this->post );

		if ( $this->settings['disable_counters'] != 1 ) {
			$this->share_numbers = get_post_meta( $this->post->ID, 'naked_shares_count', true );
			$this->share_numbers = $this->get_share_numbers();
		}
	}

	/**
	 * Changes the amount of time the share counts are
	 * cached for.
	 *
	 * @param int $time Cache time in seconds
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function set_cache_time( $time ) {
		$this->cache_time = $time;
	}

	/**
	 * Generates a random time, more or less 500 seconds.
	 *
	 * @param int $timeout Cache time in seconds
	 *
	 * @access public
	 * @since  1.0.0
	 * @return int Randomized cache time in seconds
	 */
	public function generate_cache_time( $timeout ) {
		$lower = $timeout - 500;
		$lower = ( $lower < 0 ) ? 0 : $lower;
		$upper = $timeout + 500;

		return rand( $lower, $upper );
	}

	/**
	 * Gets the social share numbers for each site.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return array
	 */
	public function get_share_numbers() {

		// If there are no saved share numbers, set them all to 0.
		if ( empty( $this->share_numbers ) || ! is_array( $this->share_numbers ) || ! count( $this->share_numbers ) || ! isset( $this->share_numbers['shares'] ) ) {
			$shares = array(
				'twitter'     => 0,
				'facebook'    => 0,
				'pinterest'   => 0,
				'stumbleupon' => 0,
				'google'      => 0,
				'linkedin'    => 0
			);
		} else {
			// If we don't want to cache the numbers, remove the expiry time.
			if ( $this->cache === false ) {
				$this->share_numbers['expire'] = false;
			}
			// If the cache hasn't expired, return the shares straight away.
			if ( is_numeric( $this->share_numbers['expire'] ) && $this->share_numbers['expire'] > time() ) {
				return $this->share_numbers['shares'];
			}

			$shares = $this->share_numbers['shares'];
		}

		/*
		 * Fetch the share numbers for Twitter if it's enabled.
		 */
		if ( array_key_exists( 'twitter', $this->settings['social_sites']['enabled'] ) ) {
			$twitter_url      = 'http://urls.api.twitter.com/1/urls/count.json?url=' . $this->url;
			$twitter_response = wp_remote_get( esc_url_raw( $twitter_url ) );
			// Make sure the response came back okay.
			if ( ! is_wp_error( $twitter_response ) && wp_remote_retrieve_response_code( $twitter_response ) == 200 ) {
				$twitter_body = json_decode( wp_remote_retrieve_body( $twitter_response ) );

				// If the results look okay, update them.
				if ( $twitter_body->count && is_numeric( $twitter_body->count ) ) {
					$shares['twitter'] = $twitter_body->count;
				}
			}
		} else {
			$shares['twitter'] = 0;
		}

		/*
		 * Fetch the share numbers for Facebook if it's enabled.
		 */
		if ( array_key_exists( 'facebook', $this->settings['social_sites']['enabled'] ) ) {
			//$params            = 'select total_count, comment_count, share_count, like_count from link_stat where url = "' . $this->url . '"';
			//$facebook_url      = 'http://graph.facebook.com/fql?q=' . $params;
			$facebook_url      = sprintf( 'https://api.facebook.com/method/links.getStats?urls=%s&format=json', $this->url );
			$facebook_response = wp_remote_get( esc_url_raw( $facebook_url ) );
			// Make sure the response came back okay.
			if ( ! is_wp_error( $facebook_response ) && wp_remote_retrieve_response_code( $facebook_response ) == 200 ) {
				$facebook_body = json_decode( wp_remote_retrieve_body( $facebook_response ) );

				// If the results look good, let's update them.
				if ( $facebook_body && is_array( $facebook_body ) && array_key_exists( '0', $facebook_body ) ) {
					$shares['facebook'] = $facebook_body[0]->total_count;
				}
			}
		} else {
			$shares['facebook'] = 0;
		}

		/*
		 * Fetch the share numbers for Pinterest if it's enabled.
		 */
		if ( array_key_exists( 'pinterest', $this->settings['social_sites']['enabled'] ) ) {
			$pinterest_url      = 'http://api.pinterest.com/v1/urls/count.json?callback=receiveCount&url=' . $this->url;
			$pinterest_response = wp_remote_get( esc_url_raw( $pinterest_url ) );
			// Make sure the response came back okay.
			if ( ! is_wp_error( $pinterest_response ) && wp_remote_retrieve_response_code( $pinterest_response ) == 200 ) {
				// Remove the annoying repsonseCode() stuff
				$pinterest_body = json_decode( preg_replace( "/[^(]*\((.*)\)/", "$1", wp_remote_retrieve_body( $pinterest_response ) ) );
				// Get the count
				if ( $pinterest_body->count && is_numeric( $pinterest_body->count ) ) {
					$shares['pinterest'] = $pinterest_body->count;
				}
			}
		} else {
			$shares['pinterest'] = 0;
		}

		/*
		 * Fetch the share numbers for StumbleUpon if it's enabled.
		 */
		if ( array_key_exists( 'pinterest', $this->settings['social_sites']['enabled'] ) ) {
			$stumble_url      = 'http://www.stumbleupon.com/services/1.01/badge.getinfo?url=' . $this->url;
			$stumble_response = wp_remote_get( esc_url_raw( $stumble_url ) );
			// Make sure the response came back okay.
			if ( ! is_wp_error( $stumble_response ) && wp_remote_retrieve_response_code( $stumble_response ) == 200 ) {
				$stumble_body = json_decode( wp_remote_retrieve_body( $stumble_response ) );
				if ( $stumble_body->result && method_exists( $stumble_body->result, 'views' ) && $stumble_body->result->views && is_numeric( $stumble_body->result->views ) ) {
					$shares['stumbleupon'] = $stumble_body->result->views;
				}
			}
		} else {
			$shares['stumbleupon'] = 0;
		}

		/*
		 * Fetch the share numbers for Google+ if it's enabled.
		 */
		if ( array_key_exists( 'google', $this->settings['social_sites']['enabled'] ) ) {
			$shares['google'] = $this->get_plus_ones( $this->url );
		} else {
			$shares['google'] = 0;
		}

		/*
		 * Fetch the share numbers for LinkedIn if it's enabled.
		 */
		if ( array_key_exists( 'linkedin', $this->settings['social_sites']['enabled'] ) ) {
			$linked_url      = 'https://www.linkedin.com/countserv/count/share?url=' . $this->url . '&format=json';
			$linked_response = wp_remote_get( esc_url_raw( $linked_url ) );
			// Make sure the response came back okay.
			if ( ! is_wp_error( $linked_response ) && wp_remote_retrieve_response_code( $linked_response ) == 200 ) {
				$linked_body = json_decode( wp_remote_retrieve_body( $linked_response ) );
				if ( $linked_body->count && is_numeric( $linked_body->count ) ) {
					$shares['linkedin'] = $linked_body->count;
				}
			}
		} else {
			$shares['linkedin'] = 0;
		}

		/*
		 * Put together the final share numbers.
		 */

		$final_shares = array(
			'shares' => $shares,
			'expire' => time() + $this->generate_cache_time( $this->cache_time )
		);

		// Update the numbers and expiry time in the meta data.
		update_post_meta( $this->post->ID, 'naked_shares_count', $final_shares );

		// Update the variable here.
		$this->share_numbers = $final_shares;

		// Return the numbers.
		return $shares;

	}

	/**
	 * GetPlusOnesByURL()
	 *
	 * Get the numeric, total count of +1s from Google+ users for a given URL.
	 *
	 * @author          Stephan Schmitz <eyecatchup@gmail.com>
	 * @copyright       Copyright (c) 2014 Stephan Schmitz
	 * @license         http://eyecatchup.mit-license.org/  MIT License
	 * @link            <a href="https://gist.github.com/eyecatchup/8495140">Source</a>.
	 * @link            <a href="http://stackoverflow.com/a/13385591/624466">Read more</a>.
	 *
	 * @param   $url    string  The URL to check the +1 count for.
	 *
	 * @return  int          The total count of +1s.
	 */
	public function get_plus_ones( $url ) {
		if ( empty( $url ) ) {
			return 0;
		}

		! filter_var( $url, FILTER_VALIDATE_URL ) &&
		die( sprintf( 'PHP said, "%s" is not a valid URL.', $url ) );

		foreach ( array( 'apis', 'plusone' ) as $host ) {
			$ch = curl_init( sprintf( 'https://%s.google.com/u/0/_/+1/fastbutton?url=%s',
				$host, urlencode( $url ) ) );
			curl_setopt_array( $ch, array(
				CURLOPT_FOLLOWLOCATION => 1,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_SSL_VERIFYPEER => 0,
				CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 6.1; WOW64) ' .
				                          'AppleWebKit/537.36 (KHTML, like Gecko) ' .
				                          'Chrome/32.0.1700.72 Safari/537.36'
			) );
			$response = curl_exec( $ch );
			$curlinfo = curl_getinfo( $ch );
			curl_close( $ch );

			if ( 200 === $curlinfo['http_code'] && 0 < strlen( $response ) ) {
				break 1;
			}
			$response = 0;
		}

		if ( ! isset( $response ) || empty( $response ) ) {
			return 0;
		}

		preg_match_all( '/window\.__SSR\s\=\s\{c:\s(\d+?)\./', $response, $match, PREG_SET_ORDER );

		return ( 1 === sizeof( $match ) && 2 === sizeof( $match[0] ) ) ? intval( $match[0][1] ) : 0;
	}

	/**
	 * Gets the URL of the featured image or the first image found in
	 * the post.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return string URL to featured image or empty string if none is found
	 */
	public function get_featured_image_url() {

		// Get the featured image if it exists.
		if ( has_post_thumbnail( $this->post ) ) {
			$image = wp_get_attachment_image_src( get_post_thumbnail_id( $this->post->ID ), 'full' );

			return $image[0];
		}

		// See if we can find an image in the post.
		if ( preg_match_all( '|<img.*?src=[\'"](.*?)[\'"].*?>|i', $this->post->post_content, $matches ) ) {
			$first_img = $matches[1][0];

			// First image is empty, return an empty string.
			if ( empty( $first_img ) ) {
				return '';
			}

			// Return the first image we've found.
			return $first_img;
		}

		return '';

	}

	/**
	 * Displays the markup for the social share buttons.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function display_share_markup() {
		$twitter_handle = $this->settings['twitter_handle'];
		$social_sites   = $this->settings['social_sites'];
		?>
		<div class="naked-social-share">
			<ul>
				<?php foreach ( $social_sites['enabled'] as $key => $site_name ) { ?>
					<?php switch ( $key ) {
						case 'twitter' :
							?>
							<li class="nss-twitter">
								<a href="http://www.twitter.com/intent/tweet?url=<?php echo urlencode( get_permalink( $this->post ) ) ?><?php echo ( ! empty( $twitter_handle ) ) ? '&via=' . $twitter_handle : ''; ?>&text=<?php echo $this->get_title(); ?>" target="_blank"><i class="fa fa-twitter"></i>
									<span class="nss-site-name"><?php _e( 'Twitter', 'naked-social-share' ); ?></span>
									<?php if ( $this->settings['disable_counters'] != 1 ) : ?>
										<span class="nss-site-count"><?php echo array_key_exists( 'twitter', $this->share_numbers ) ? $this->share_numbers['twitter'] : 0; ?></span>
									<?php endif; ?>
								</a>
							</li>
							<?php
							break;

						case 'facebook' :
							?>
							<li class="nss-facebook">
								<a href="http://www.facebook.com/sharer/sharer.php?u=<?php echo get_permalink( $this->post ); ?>&t=<?php echo $this->get_title(); ?>" target="_blank"><i class="fa fa-facebook"></i>
									<span class="nss-site-name"><?php _e( 'Facebook', 'naked-social-share' ); ?></span>
									<?php if ( $this->settings['disable_counters'] != 1 ) : ?>
										<span class="nss-site-count"><?php echo array_key_exists( 'facebook', $this->share_numbers ) ? $this->share_numbers['facebook'] : 0; ?></span>
									<?php endif; ?>
								</a>
							</li>
							<?php
							break;

						case 'pinterest' :
							?>
							<li class="nss-pinterest">
								<a href="http://pinterest.com/pin/create/button/?url=<?php echo get_permalink( $this->post ); ?>&media=<?php echo $this->get_featured_image_url(); ?>&description=<?php echo $this->get_title(); ?>" target="_blank"><i class="fa fa-pinterest"></i>
									<span class="nss-site-name"><?php _e( 'Pinterest', 'naked-social-share' ); ?></span>
									<?php if ( $this->settings['disable_counters'] != 1 ) : ?>
										<span class="nss-site-count"><?php echo array_key_exists( 'pinterest', $this->share_numbers ) ? $this->share_numbers['pinterest'] : 0; ?></span>
									<?php endif; ?>
								</a>
							</li>
							<?php
							break;

						case 'stumbleupon' :
							?>
							<li class="nss-stumbleupon">
								<a href="http://www.stumbleupon.com/submit?url=<?php echo get_permalink( $this->post ); ?>&title=<?php echo $this->get_title(); ?>" target="_blank"><i class="fa fa-stumbleupon"></i>
									<span class="nss-site-name"><?php _e( 'StumbleUpon', 'naked-social-share' ); ?></span>
									<?php if ( $this->settings['disable_counters'] != 1 ) : ?>
										<span class="nss-site-count"><?php echo array_key_exists( 'stumbleupon', $this->share_numbers ) ? $this->share_numbers['stumbleupon'] : 0; ?></span>
									<?php endif; ?>
								</a>
							</li>
							<?php
							break;

						case 'google' :
							?>
							<li class="nss-google">
								<a href="https://plus.google.com/share?url=<?php echo get_permalink( $this->post ); ?>" target="_blank"><i class="fa fa-google-plus"></i>
									<span class="nss-site-name"><?php _e( 'Google+', 'naked-social-share' ); ?></span>
									<?php if ( $this->settings['disable_counters'] != 1 ) : ?>
										<span class="nss-site-count"><?php echo array_key_exists( 'google', $this->share_numbers ) ? $this->share_numbers['google'] : 0; ?></span>
									<?php endif; ?>
								</a>
							</li>
							<?php
							break;

						case 'linkedin' :
							?>
							<li class="nss-linkedin">
								<a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode( get_permalink( $this->post ) ); ?>&title=<?php echo $this->get_title(); ?>&source=<?php echo urlencode( get_bloginfo( 'name' ) ); ?>" target="_blank"><i class="fa fa-linkedin"></i>
									<span class="nss-site-name"><?php _e( 'LinkedIn', 'naked-social-share' ); ?></span>
									<?php if ( $this->settings['disable_counters'] != 1 ) : ?>
										<span class="nss-site-count"><?php echo array_key_exists( 'linkedin', $this->share_numbers ) ? $this->share_numbers['linkedin'] : 0; ?></span>
									<?php endif; ?>
								</a>
							</li>
							<?php
							break;
					}
				} ?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Gets the title of the post, decodes the HTML entities
	 * and urlencodes it for use in a URL.
	 *
	 * @param bool $urlencode
	 *
	 * @access public
	 * @since  1.0.6
	 * @return string
	 */
	public
	function get_title(
		$urlencode = true
	) {
		$title_raw     = get_the_title( $this->post );
		$title_decoded = html_entity_decode( $title_raw );

		if ( $urlencode != true ) {
			return $title_decoded;
		}

		return urlencode( $title_decoded );
	}

}