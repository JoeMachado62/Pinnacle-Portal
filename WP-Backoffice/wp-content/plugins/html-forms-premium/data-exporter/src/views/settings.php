<?php defined('ABSPATH') or exit; ?>

<h2 class="title">
    <?php echo _e('Export Submissions', 'html-forms-premium'); ?>
    <a target="_blank" tabindex="-1" class="html-forms-help" href="https://htmlformsplugin.com/kb/export-submissions/"><span class="dashicons dashicons-editor-help"></span></a>
</h2>

<table class="form-table" role="presentation">
    <tbody>
        <tr valign="top">
            <th scope="row"><?php _e( 'Export Delimiter ', 'html-forms-premium' ); ?></th>
            <td>
                <select name="hf_settings[submissions_export_delimiter]">
                    <?php foreach ( $export_delimiters as $ed ) : ?>
                    <option value="<?php echo $ed; ?>"<?php echo (selected($settings['submissions_export_delimiter'], $ed)); ?>><?php echo $ed; ?></option>
                    <?php endforeach; ?>
                </select>

                <p class="description"><?php _e( 'Select the delimiter to use in the Submissions export file.', 'html-forms-premium' ); ?></p>
            </td>
        </tr>
    </tbody>
</table>