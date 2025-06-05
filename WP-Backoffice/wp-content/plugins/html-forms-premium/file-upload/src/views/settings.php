<?php defined('ABSPATH') or exit; ?>

<h2 class="title">
    <?php echo _e('File Uploads', 'html-forms-premium'); ?>
    <a target="_blank" tabindex="-1" class="html-forms-help" href="https://htmlformsplugin.com/kb/file-uploads/"><span class="dashicons dashicons-editor-help"></span></a>
</h2>

<table class="form-table" role="presentation">
    <tbody>
        <tr valign="top">
            <th scope="row">
                <?php _e( 'Direct Links', 'html-forms-premium' ); ?>
            </th>
            <td>
                <label><input type="radio" name="hf_settings[direct_links]" value="1" <?php checked( $this->settings['direct_links'], 1 ); ?>> <?php _e( 'Yes' ); ?></label> &nbsp;
                <label><input type="radio"  name="hf_settings[direct_links]" value="0"  <?php checked( $this->settings['direct_links'], 0 ); ?>> <?php _e( 'No' ); ?></label>

                <p class="description"><?php _e( 'Select "Yes" to turn on direct links to actual files instead of links to the file in the WordPress Media Library.', 'html-forms-premium' ); ?></p>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row">
                <?php _e( 'Media Library Uploads', 'html-forms-premium' ); ?>
            </th>
            <td>
                <label><input type="radio" name="hf_settings[media_library_uploads]" value="1" <?php checked( $this->settings['media_library_uploads'], 1 ); ?>> <?php _e( 'Yes' ); ?></label> &nbsp;
                <label><input type="radio"  name="hf_settings[media_library_uploads]" value="0"  <?php checked( $this->settings['media_library_uploads'], 0 ); ?>> <?php _e( 'No' ); ?></label>

                <p class="description"><?php _e( 'Select "Yes" to include uploaded files in the WordPress Media Library.', 'html-forms-premium' ); ?></p>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row">
                <?php _e( 'Maximum File Size', 'html-forms-premium' ); ?>
            </th>
            <td>
                <input type="number" class="medium-text" min="0" name="hf_settings[maximum_filesize]" value="<?php echo esc_attr( $this->settings['maximum_filesize'] ); ?>" />
                <p class="description"><?php _e( 'Leave empty or enter <code>0</code> for no maximum file size.', 'html-forms-premium' ); ?></p>
                <p class="description"><?php _e( 'File size measured in bytes. 1000000 bytes = 1MB.', 'html-forms-premium' ); ?></p>
            </td>
        </tr>
    </tbody>
</table>