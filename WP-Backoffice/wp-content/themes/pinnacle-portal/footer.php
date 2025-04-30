<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Pinnacle_Portal
 */

?>

    </div><!-- #content -->

    <footer id="colophon" class="site-footer py-5 mt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-md-4 mb-4 mb-md-0">
                    <div class="footer-info">
                        <h3 class="h4 mb-3"><?php bloginfo('name'); ?></h3>
                        <p class="text-muted"><?php bloginfo('description'); ?></p>
                        <div class="social-links mt-3">
                            <a href="#" class="social-link me-2" aria-label="Facebook"><i class="dashicons dashicons-facebook-alt"></i></a>
                            <a href="#" class="social-link me-2" aria-label="Twitter"><i class="dashicons dashicons-twitter"></i></a>
                            <a href="#" class="social-link me-2" aria-label="LinkedIn"><i class="dashicons dashicons-linkedin"></i></a>
                            <a href="#" class="social-link" aria-label="Instagram"><i class="dashicons dashicons-instagram"></i></a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4 mb-md-0">
                    <div class="footer-menu">
                        <h3 class="h4 mb-3"><?php esc_html_e('Quick Links', 'pinnacle-portal'); ?></h3>
                        <?php
                        wp_nav_menu(
                            array(
                                'theme_location' => 'footer',
                                'menu_id'        => 'footer-menu',
                                'depth'          => 1,
                                'menu_class'     => 'footer-links list-unstyled',
                            )
                        );
                        ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="footer-contact">
                        <h3 class="h4 mb-3"><?php esc_html_e('Contact Us', 'pinnacle-portal'); ?></h3>
                        <p><?php esc_html_e('If you have any questions or need assistance, please contact us.', 'pinnacle-portal'); ?></p>
                        <p><a href="mailto:info@pinnacleautofinance.com" class="text-decoration-none">info@pinnacleautofinance.com</a></p>
                        <p><a href="tel:+18005551234" class="text-decoration-none">(800) 555-1234</a></p>
                    </div>
                </div>
            </div>
            <div class="row border-top pt-4">
                <div class="col-md-12">
                    <div class="site-info d-flex flex-column flex-md-row justify-content-between align-items-center">
                        <div class="copyright mb-3 mb-md-0">
                            &copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. <?php esc_html_e('All rights reserved.', 'pinnacle-portal'); ?>
                        </div>
                        <div class="credits">
                            <a href="<?php echo esc_url(__('https://wordpress.org/', 'pinnacle-portal')); ?>">
                                <?php
                                /* translators: %s: CMS name, i.e. WordPress. */
                                printf(esc_html__('Proudly powered by %s', 'pinnacle-portal'), 'WordPress');
                                ?>
                            </a>
                            <span class="sep"> | </span>
                            <?php
                            /* translators: 1: Theme name, 2: Theme author. */
                            printf(esc_html__('Theme: %1$s by %2$s.', 'pinnacle-portal'), 'Pinnacle Portal', '<a href="https://pinnacleautofinance.com">Pinnacle Auto Finance</a>');
                            ?>
                        </div>
                    </div><!-- .site-info -->
                </div>
            </div>
        </div>
        <a href="#" id="back-to-top" class="back-to-top position-fixed rounded-circle d-flex justify-content-center align-items-center" aria-label="<?php esc_attr_e('Back to top', 'pinnacle-portal'); ?>">
            <i class="dashicons dashicons-arrow-up-alt2"></i>
        </a>
    </footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
