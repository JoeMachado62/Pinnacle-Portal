<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package Pinnacle_Portal
 */

get_header();
?>

<main id="primary" class="site-main">
    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <?php
                while (have_posts()) :
                    the_post();

                    if ('deal' === get_post_type()) {
                        get_template_part('template-parts/content', 'deal');
                    } elseif ('dealer' === get_post_type()) {
                        get_template_part('template-parts/content', 'dealer');
                    } else {
                        get_template_part('template-parts/content', 'single');
                    }

                    // If comments are open or we have at least one comment, load up the comment template.
                    if (comments_open() || get_comments_number()) :
                        comments_template();
                    endif;

                    // Previous/next post navigation.
                    the_post_navigation(
                        array(
                            'prev_text' => '<span class="nav-subtitle">' . esc_html__('Previous:', 'pinnacle-portal') . '</span> <span class="nav-title">%title</span>',
                            'next_text' => '<span class="nav-subtitle">' . esc_html__('Next:', 'pinnacle-portal') . '</span> <span class="nav-title">%title</span>',
                        )
                    );

                endwhile; // End of the loop.
                ?>
            </div>
            <div class="col-md-4">
                <?php get_sidebar(); ?>
            </div>
        </div>
    </div>
</main><!-- #primary -->

<?php
get_footer();
