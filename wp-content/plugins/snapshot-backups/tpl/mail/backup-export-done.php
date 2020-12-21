<?php // phpcs:ignore
/**
 * Backup export mail template.
 *
 * @package snapshot
 */

$assets = new \WPMUDEV\Snapshot4\Helper\Assets();

$header_image       = $assets->get_asset( 'img/mail-header.png' );
$wpmudev_logo_image = $assets->get_asset( 'img/mail-wpmudev-logo.png' );
?>
<html>
	<head>
		<meta charset="utf-8">
	</head>
	<body style="background-color: #f2f2f2;">
		<table style="width: 600px; margin: 0 auto; font-family: sans-serif; border-spacing: 0;">
			<tr>
				<td style="height: 50px; font-size: 14px; color: #a6a6a6;" align="right"></td>
			</tr>
			<tr style="background-color: #a5a0d2;">
				<td style="height: 150px; border-radius: 4px 4px 0 0; padding: 0; background-size: 130px; background-position: center bottom; background-repeat: no-repeat; background-image: url(<?php echo esc_attr( $header_image ); ?>);">
				</td>
			</tr>
			<tr style="background-color: #ffffff;">
				<td style="padding: 40px;">
					<p style="padding: 0 20px; color: #333333; font-size: 18px; line-height: 25px;">Hi<?php echo $name ? esc_html( ' ' . $name . ',' ) : '!'; ?><br><br>Here's an export of your requested backup which can be downloaded using the link below. This link will expire in seven&nbsp;days, but don't worry, your backup won't expire until 50&nbsp;days from its creation.</p>
				</td>
			</tr>
			<tr style="background-color: #ffffff;">
				<td align="center">
					<a href="<?php echo esc_attr( $export_link ); ?>" style="height: 44px; width: 270px; display: block; border-radius: 4px; background-color: #17a8e3; font-size: 16px; line-height: 44px; color: #ffffff; text-decoration: none;">DOWNLOAD BACKUP</a>
				</td>
			</tr>
			<tr style="background-color: #ffffff;">
				<td style="padding: 40px;">
					<p style="padding: 0 20px; color: #333333; font-size: 18px; line-height: 25px;">Stay protected,<br><br>Snapshot<br><span style="font-size: 14px;">WPMU DEV Backup Hero</span></p>
				</td>
			</tr>
			<tr style="background-color: #ffffff;">
				<td style="height: 60px; border-radius: 0 0 4px 4px;"></td>
			</tr>

			<tr>
				<td style="padding: 50px 0 30px;" align="center"><img width="120" src="<?php echo esc_attr( $wpmudev_logo_image ); ?>"></td>
			</tr>
			<tr>
				<td style="padding: 0; font-size: 14px; font-style: italic; color: #666666; line-height: 20px; text-align: center;" align="center">Everything You Need For WordPress.<br>One place, one low price, unlimited sites.</td>
			</tr>
			<tr>
				<td style="padding: 40px 10px 70px; font-size: 10px; color: #aaaaaa;" align="center">INCSUB PO BOX 163, ALBERT PARK, VICTORIA.3206 AUSTRALIA</td>
			</tr>
		</table>
	</body>
</html>