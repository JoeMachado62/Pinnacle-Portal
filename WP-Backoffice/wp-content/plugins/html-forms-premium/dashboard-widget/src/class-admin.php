<?php

namespace HTML_Forms\Dashboard_Widget;

use HTML_Forms\Form;

class Admin
{
    public function hook()
    {
        add_action('wp_dashboard_setup', array( $this, 'widget' ) );
    }

    public function widget( )
    {
        wp_add_dashboard_widget(
            'html_forms_dashboard_widget',
            __('HTML Forms', 'html-forms-premium'),
            array( $this, 'content' ),
        );
    }

    public function content( )
    {
        $forms = hf_get_forms(['orderby' => 'post_title', 'order' => 'ASC']);

        if (empty($forms)) {
            echo sprintf( __( 'No forms found. <a href="%s">Would you like to create one now</a>?', 'html-forms-premium' ), admin_url( 'admin.php?page=html-forms-add-form' ) );
            return;
        }
        ?>
        <table class="widefat">
            <thead>
                <tr>
                    <th scope="col"><?php _e('Form', 'html-forms-premium'); ?></th>
                    <th scope="col"><?php _e('Unread', 'html-forms-premium'); ?></th>
                    <th scope="col"><?php _e('Total', 'html-forms-premium'); ?></th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($forms as $f) : ?>
                <?php
			        $link = admin_url('admin.php?page=html-forms&view=edit&tab=submissions&form_id='.$f->ID);
                    $unread = 0;
                    $total = hf_count_form_submissions($f->ID);

                    $unread_submissions = get_post_meta( $f->ID, '_hf_unseen_submissions', true );
                    if (!empty($unread_submissions)) {
                        $unread_submissions = (array) $unread_submissions;
                        $unread_submissions = array_values($unread_submissions);
                        $unread_submissions = array_filter($unread_submissions);
                        $unread = count($unread_submissions);
                    }
                ?>
                <tr>
                    <td><a href="<?php echo $link; ?>"><?php echo ($unread > 0 ? '<strong>'.$f->title.'</strong>' : $f->title); ?></a></td>
                    <td><a href="<?php echo $link; ?>"><?php echo ($unread > 0 ? '<strong>'.$unread.'</strong>' : $unread); ?></a></td>
                    <td><a href="<?php echo $link; ?>"><?php echo ($unread > 0 ? '<strong>'.$total.'</strong>' : $total); ?></a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
}
