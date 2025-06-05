<?php

namespace HTML_Forms\Admin;

class Hcaptcha {
	public function hook() {
        add_filter( 'hf_ignored_field_names', array( $this, 'ignored_fields' ) ) ;
	}

    public function ignored_fields() {
        return array(
            'hcaptcha-widget-id',
            'g-recaptcha-response',
            'h-captcha-response',
            'html_forms_form_nonce',
        );
    }
}
