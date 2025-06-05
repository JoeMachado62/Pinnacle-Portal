<?php

namespace HTML_Forms\Required_User_Logged_In;

use HTML_Forms\Form;

class Admin
{
    public function hook()
    {
        add_filter( 'hf_form_default_settings', array( $this, 'default_form_settings' ) );
        add_action( 'hf_output_form_settings', array( $this, 'form_settings' ) );
        add_action( 'hf_admin_output_form_messages', array( $this, 'output_message_settings' ) );
    }

	public function default_form_settings( $defaults ) {
		$defaults['require_user_logged_in'] = '0';
		return $defaults;
	}

    public function form_settings(Form $form)
    {
        ?>
        <tr valign="top">
            <th scope="row" colspan="2" class="hf-settings-header"><?php echo __( 'Visibility', 'html-forms-premium' ); ?></th>
        </tr>

        <tr valign="top">
            <th scope="row">
                <label for="hf_form_require_user_logged_in"><?php _e( 'Require Users to Log In', 'html-forms-premium' ); ?></label>
                <a target="_blank" tabindex="-1" class="html-forms-help" href="https://htmlformsplugin.com/kb/require-users-to-log-in/"><span class="dashicons dashicons-editor-help"></span></a>
            </th>
            <td>
                <label><input type="radio" name="form[settings][require_user_logged_in]" value="1" <?php checked( $form->settings['require_user_logged_in'], 1 ); ?>> <?php _e( 'Yes' ); ?></label> &nbsp;
                <label><input type="radio"  name="form[settings][require_user_logged_in]" value="0"  <?php checked( $form->settings['require_user_logged_in'], 0 ); ?>> <?php _e( 'No' ); ?></label>

                <p class="description"><?php _e( 'Select "Yes" to require users to be logged in to see the form.', 'html-forms-premium' ); ?></p>
            </td>
        </tr>
        <?php
    }

    public function output_message_settings($form)
    {
        ?>
        <tr valign="top">
            <th scope="row" colspan="2" class="hf-settings-header"><?php echo __( 'Visibility', 'html-forms-premium' ); ?></th>
        </tr>

        <tr valign="top">
            <th scope="row"><label for="hf_form_require_user_logged_in"><?php _e('User Log in Required', 'html-forms-premium'); ?></label></th>
            <td>
                <input type="text" class="widefat" id="hf_form_require_user_logged_in" name="form[messages][require_user_logged_in]" value="<?php echo esc_attr($form->messages['require_user_logged_in']); ?>" required />
                <p class="description"><?php _e('Message to show when users must be log in to see the form.', 'html-forms-premium'); ?></p>
            </td>
        </tr>
        <?php
    }
}
