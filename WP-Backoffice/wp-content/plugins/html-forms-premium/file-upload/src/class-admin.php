<?php

namespace HTML_Forms\File_Upload;

class Admin
{
    private $plugin_file;
	private $settings = [];

    public function __construct($plugin_file)
    {
        $this->plugin_file = $plugin_file;
        $this->settings = hf_get_settings();
    }

    public function hook()
    {
        add_action('hf_admin_output_settings', array( $this, 'settings' ));
        add_action('admin_enqueue_scripts', array( $this, 'enqueue' ));
        add_action('hf_admin_output_form_messages', array( $this, 'output_message_settings' ));

        if (isset($this->settings['direct_links']) && $this->settings['direct_links']) {
            add_filter('hf_file_upload_use_direct_links', '__return_true');
        }
    }

    public function settings()
    {
        if (!isset($this->settings['direct_links'])) {
            $this->settings['direct_links'] = 0;
        }

        if (!isset($this->settings['media_library_uploads'])) {
            $this->settings['media_library_uploads'] = 1;
        }

        if (!isset($this->settings['maximum_filesize'])) {
            $this->settings['maximum_filesize'] = 0;
        }

		require dirname( __FILE__ ) . '/views/settings.php';
    }

    public function enqueue()
    {
        if (! isset($_GET['page']) || !isset($_GET['view']) || $_GET['page'] !== 'html-forms'  || $_GET['view'] !== 'edit') {
            return;
        }

        wp_enqueue_script('html-forms-file-upload', plugins_url('assets/dist/js/admin.js', $this->plugin_file), array( 'html-forms-admin' ), HF_PREMIUM_VERSION, true);
    }

    public function output_message_settings($form)
    {
        ?>
        <tr valign="top">
            <th scope="row" colspan="2" class="hf-settings-header"><?php echo __( 'File Uploads', 'html-forms-premium' ); ?></th>
        </tr>

        <tr valign="top">
            <th scope="row"><label for="hf_form_file_too_large"><?php _e('File Too Large', 'html-forms-premium'); ?></label></th>
            <td>
                <input type="text" class="widefat" id="hf_form_file_too_large" name="form[messages][file_too_large]" value="<?php echo esc_attr($form->messages['file_too_large']); ?>" required />
                <p class="description"><?php _e('Message to show when a file is uploaded that is too large.', 'html-forms-premium'); ?></p>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><label for="hf_form_file_upload_error"><?php _e('File Upload Error', 'html-forms-premium'); ?></label></th>
            <td>
                <input type="text" class="widefat" id="hf_form_file_too_large" name="form[messages][file_upload_error]" value="<?php echo esc_attr($form->messages['file_upload_error']); ?>" required />
                <p class="description"><?php _e('Message to show when a file upload error occurs.', 'html-forms-premium'); ?></p>
            </td>
        </tr>
        <?php
    }
}
