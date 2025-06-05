<?php

namespace HTML_Forms\File_Upload;

class Frontend
{
	private $settings = [];

    public function __construct()
    {
        $this->settings = hf_get_settings();
    }

    public function hook()
    {
        add_filter('hf_process_form', array( $this, 'process' ), 10, 2);
        add_filter('hf_validate_form', array( $this, 'validate' ), 10, 3);

        if (isset($this->settings['direct_links']) && $this->settings['direct_links'] == true) {
            add_filter('hf_file_upload_use_direct_links', '__return_true');
        }
        
        if (isset($this->settings['media_library_uploads']) && $this->settings['media_library_uploads'] == false) {
            add_filter( 'hf_upload_add_to_media', '__return_false' );
        }

        if (isset($this->settings['maximum_filesize']) && is_numeric($this->settings['maximum_filesize']) && $this->settings['maximum_filesize'] > 0) {
            add_filter('hf_upload_max_filesize', function( $size ) {
                error_log($this->settings['maximum_filesize']);
                return $this->settings['maximum_filesize']; // size in bytes, 1000000 = 1MB
                }
            );
        }
    }

    public function validate($error_code, $form, $data)
    {
        foreach ($data as $field_name => $field_data) {
            if (! hf_is_file($field_data)) {
                continue;
            }

            $file = (object) $field_data;

            // other upload errors
            if ($file->error !== UPLOAD_ERR_OK) {
                return 'file_upload_error';
            }

            // validate file extension
            $file_ext = strtolower(pathinfo($file->name, PATHINFO_EXTENSION));
            if (in_array($file_ext, array( 'php', 'phar', 'exe' ))) {
                return 'file_invalid_extension';
            }

            // validate file size (<8 MB)
            if ($file->size > apply_filters('hf_upload_max_filesize', 8000000)) {
                return 'file_too_large';
            }
        }

        return $error_code;
    }

    public function process($form, $submission)
    {
        foreach ($submission->data as $field_name => $field_data) {
            if (! hf_is_file($field_data)) {
                continue;
            }

            // validate that file was uploaded through POST
            if (! is_uploaded_file($field_data['tmp_name'])) {
                unset($submission->data[$field_name]);
                continue;
            }

            $wp_upload_dir = wp_upload_dir();
            $hf_upload_dir = sprintf("%s/%s/%s", $wp_upload_dir['basedir'], 'html-forms', $form->slug);
            $hf_upload_url = sprintf("%s/%s/%s", $wp_upload_dir['baseurl'], 'html-forms', $form->slug);
            if (! is_dir($hf_upload_dir)) {
                mkdir($hf_upload_dir, 0755, true);
            }

            // move uploaded file to subdirectory in wp-uploads
            // use submission ID if submission storage is enabled. current date + time otherwise.
            $file_ext = strtolower(pathinfo($field_data['name'], PATHINFO_EXTENSION));

            // repeat logic for malicious file extensions in case validation return value was overridden by custom validation logic applied incorrectly
            if (in_array($file_ext, array( 'php', 'phar', 'exe' ))) {
                unset($submission->data[$field_name]);
                continue;
            }

            $id = ! empty($submission->id) ? $submission->id : gmdate('YmdHis');

            $new_filename = sprintf('%s_%s_%s.%s', $id, $field_name, uniqid(), $file_ext);
            $destination = sprintf("%s/%s", $hf_upload_dir, $new_filename);
            move_uploaded_file($field_data['tmp_name'], $destination);
            $submission->data[$field_name]['dir'] = $hf_upload_dir;
            $submission->data[$field_name]['name'] = $new_filename;
            $submission->data[$field_name]['url'] = $hf_upload_url . '/' . $new_filename;
            unset($submission->data[$field_name]['tmp_name']);

            // Create attachment so file appears in Media
            $add_to_media = apply_filters('hf_upload_add_to_media', true);
            if ($add_to_media) {
                $wp_filetype = wp_check_filetype($destination, null);
                $attachment = array(
                    'post_mime_type' => $wp_filetype['type'],
                    'post_title' => $new_filename,
                    'post_status' => 'private'
                );

                $attachment_id = wp_insert_attachment($attachment, $destination, 0);
                $submission->data[$field_name]['attachment_id'] = $attachment_id;

                // generate metadata so media editor can be used and files have thumbnails
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                wp_generate_attachment_metadata($attachment_id, $destination);
            }
        }
    }
}
