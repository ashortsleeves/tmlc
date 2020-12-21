<?php // phpcs:ignore
/**
 * Footer for all Snaspshot pages.
 *
 * @package snapshot
 */

$footer_nav_links = array(
	array(
		'href' => 'https://premium.wpmudev.org/hub/',
		'name' => __( 'The Hub', 'snapshot' ),
	),
	array(
		'href' => 'https://premium.wpmudev.org/projects/category/plugins/',
		'name' => __( 'Plugins', 'snapshot' ),
	),
	array(
		'href' => 'https://premium.wpmudev.org/roadmap/',
		'name' => __( 'Roadmap', 'snapshot' ),
	),
	array(
		'href' => 'https://premium.wpmudev.org/hub/support',
		'name' => __( 'Support', 'snapshot' ),
	),
	array(
		'href' => 'https://premium.wpmudev.org/docs/',
		'name' => __( 'Docs', 'snapshot' ),
	),
	array(
		'href' => 'https://premium.wpmudev.org/hub/community/',
		'name' => __( 'Community', 'snapshot' ),
	),
	array(
		'href' => 'https://premium.wpmudev.org/academy/',
		'name' => __( 'Academy', 'snapshot' ),
	),
	array(
		'href' => 'https://premium.wpmudev.org/terms-of-service/',
		'name' => __( 'Terms of Service', 'snapshot' ),
	),
	array(
		'href' => 'https://incsub.com/privacy-policy/',
		'name' => __( 'Privacy Policy', 'snapshot' ),
	),
);

/* translators: %s - icon */
$footer_text = sprintf( __( 'Made with %s by WPMU DEV', 'snapshot' ), ' <i class="sui-icon-heart"></i>' );
?>

<div class="sui-footer"><?php echo wp_kses_post( $footer_text ); ?></div>

<ul class="sui-footer-nav">
	<?php foreach ( $footer_nav_links as $footer_nav_link ) : ?>
		<li><a href="<?php echo esc_url( $footer_nav_link['href'] ); ?>" target="_blank"><?php echo esc_html( $footer_nav_link['name'] ); ?></a></li>
	<?php endforeach; ?>
</ul>
<ul class="sui-footer-social">
	<li>
		<a href="https://www.facebook.com/wpmudev" target="_blank">
			<i class="sui-icon-social-facebook" aria-hidden="true"></i>
			<span class="sui-screen-reader-text">Facebook</span>
		</a>
	</li>
	<li>
		<a href="https://twitter.com/wpmudev" target="_blank">
			<i class="sui-icon-social-twitter" aria-hidden="true"></i>
			<span class="sui-screen-reader-text">Twitter</span>
		</a>
	</li>
	<li>
		<a href="https://www.instagram.com/wpmu_dev/" target="_blank">
			<i class="sui-icon-instagram" aria-hidden="true"></i>
			<span class="sui-screen-reader-text">Instagram</span>
		</a>
	</li>
</ul>