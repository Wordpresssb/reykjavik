<?php
/**
 * Widget area in intro section (custom header).
 *
 * @package    Reykjavík
 * @copyright  WebMan Design, Oliver Juhas
 *
 * @since    1.0.0
 * @version  1.0.0
 */





// Requirements check

	if ( ! is_active_sidebar( 'intro' ) ) {
		return;
	}


?>

<div class="intro-widgets-container">
	<div class="intro-inner intro-widgets-inner">

		<aside id="intro-widgets" class="widget-area intro-widgets" role="complementary" aria-label="<?php echo esc_attr_x( 'Intro widgets', 'Sidebar aria label', 'reykjavik' ); ?>">

			<?php dynamic_sidebar( 'intro' ); ?>

		</aside>

	</div>
</div>
