<?php

/**
 * class-naked-social-share.php
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

		// If no post object was provided, use the current post.
		if ( empty( $post ) ) {
			global $post;
			$this->post = $post;
		}

		$this->url           = get_permalink( $this->post );
		$this->share_numbers = $this->get_share_numbers();

		// Load the settings.
		$naked_social_share = Naked_Social_Share();
		$this->settings     = $naked_social_share->settings;
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
				'stumbleupon' => 0
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
		 * Fetch the share numbers for Twitter.
		 */
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

		/*
		 * Fetch the share numbers for Facebook.
		 */
		$params            = 'select total_count, comment_count, share_count, like_count from link_stat where url = "' . $this->url . '"';
		$facebook_url      = esc_url_raw( 'http://graph.facebook.com/fql?q=' . $params );
		$facebook_response = wp_remote_get( $facebook_url );
		// Make sure the response came back okay.
		if ( ! is_wp_error( $facebook_response ) && wp_remote_retrieve_response_code( $facebook_response ) == 200 ) {
			$facebook_body = json_decode( wp_remote_retrieve_body( $facebook_response ) );

			// If the results look good, let's update them.
			if ( $facebook_body->data && is_array( $facebook_body->data ) && count( $facebook_body->data ) && ( $facebook_body->data[0]->total_count ) ) {
				$shares['facebook'] = $facebook_body->data[0]->total_count;
			}
		}

		/*
		 * Fetch the share numbers for Pinterest.
		 */
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

		/*
		 * Fetch the share numbers for StumbleUpon.
		 */
		$stumble_url      = 'http://www.stumbleupon.com/services/1.01/badge.getinfo?url=' . $this->url;
		$stumble_response = wp_remote_get( esc_url_raw( $stumble_url ) );
		// Make sure the response came back okay.
		if ( ! is_wp_error( $stumble_response ) && wp_remote_retrieve_response_code( $stumble_response ) == 200 ) {
			$stumble_body = json_decode( wp_remote_retrieve_body( $stumble_response ) );
			if ( $stumble_body->result && $stumble_body->result->views && is_numeric( $stumble_body->result->views ) ) {
				$shares['stumbleupon'] = $stumble_body->result->views;
			}
		}

		$final_shares = array(
			'shares' => $shares,
			'expire' => time() + $this->generate_cache_time( $this->cache_time )
		);

		// Update the numbers and expiry time in the meta data.
		update_post_meta( get_the_ID(), 'naked_shares_count', $final_shares );

		// Update the variable here.
		$this->share_numbers = $final_shares;

		// Return the numbers.
		return $shares;

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
		if ( has_post_thumbnail() ) {
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
		?>
		<div class="naked-social-share">
			<ul>
				<li>
					<a href="http://www.twitter.com/intent/tweet?url=<?php echo urlencode( get_permalink() ) ?><?php echo ( ! empty( $twitter_handle ) ) ? '&via=' . $twitter_handle : ''; ?>&text=<?php echo urlencode( get_the_title() ) ?>" target="_blank"><i class="fa fa-twitter"></i>
						<?php _e( 'Twitter', 'naked-social-share' ); ?>
						<span><?php echo $this->share_numbers['twitter']; ?></span></a>
				</li>
				<li>
					<a href="http://www.facebook.com/sharer/sharer.php?u=<?php the_permalink(); ?>&t=<?php the_title(); ?>" target="_blank"><i class="fa fa-facebook"></i>
						<?php _e( 'Facebook', 'naked-social-share' ); ?>
						<span><?php echo $this->share_numbers['facebook']; ?></span></a>
				</li>
				<li>
					<a href="http://pinterest.com/pin/create/button/?url=<?php the_permalink(); ?>&media=<?php echo $this->get_featured_image_url(); ?>&description=<?php echo urlencode( get_the_title() ); ?>" target="_blank"><i class="fa fa-pinterest"></i>
						<?php _e( 'Pinterest', 'naked-social-share' ); ?>
						<span><?php echo $this->share_numbers['pinterest']; ?></span></a>
				</li>
				<li>
					<a href="http://www.stumbleupon.com/submit?url=<?php the_permalink(); ?>&title=<?php echo urlencode( get_the_title() ); ?>" target="_blank"><i class="fa fa-stumbleupon"></i>
						<?php _e( 'StumbleUpon', 'naked-social-share' ); ?>
						<span><?php echo $this->share_numbers['stumbleupon']; ?></span></a>
				</li>
			</ul>
		</div>
	<?php
	}

}