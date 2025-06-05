<?php

namespace HTML_Forms\Premium\Licensing;

use Exception;

class Admin {

	protected $plugin_slug;
	protected $plugin_file;
	protected $plugin_version;
	protected $plugin_basename;
	protected $api_url;
	protected $network_activated = false;

	/**
	 * @param string $plugin_slug
	 * @param string $plugin_file
	 * @param string $plugin_version
	 * @param string $api_url
	 */
	public function __construct( $plugin_slug, $plugin_file, $plugin_version, $api_url ) {
		$this->plugin_slug = $plugin_slug;
		$this->plugin_file = $plugin_file;
		$this->plugin_version = $plugin_version;
		$this->plugin_basename = plugin_basename( $plugin_file );

		$this->api_url = $api_url;

		if( is_multisite() ) {
			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			}

			$this->network_activated = is_plugin_active_for_network( $this->plugin_basename );
		}
	}

	public function add_hooks() {
		$license = _hf_load_license();
        
		add_action( 'hf_admin_action_save_license', array( $this, 'process_form' ) );
		add_action( 'hf_admin_output_misc_settings', array( $this, 'show_form' ) );
        add_action( $this->plugin_slug . '_license_check', array( $this, 'check_license_status' ) );
        add_action( 'after_plugin_row_' . $this->plugin_basename, array( $this, 'after_plugin_row' ), 10, 2 );

        if ($license->activated === true && $license->system == 'my') {
    		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'add_plugin_update_data' ) );
	    	add_filter( 'plugins_api', array( $this, 'get_plugin_info' ), 20, 3 );
        }
	}

	private function get_api_client() {
		$license = _hf_load_license();
		return new Client( $this->api_url, $license->key );
	}

	/**
	 * @param object $license
	 */
	private function update_license( $license ) {
		$this->network_activated ? update_site_option( 'html-forms-premium_license', (array) $license, false ) : update_option( 'html-forms-premium_license', (array) $license, false );
	}

	public function check_license_status() {
		$license = _hf_load_license();

		// don't poll if license not currently activated
		if(!$license->activated) {
			return;
		}

        if ($license->system == 'my') {
            try {
                $client = $this->get_api_client();
                $remote_license = $client->request('GET', '/license');
                $license->activated = $remote_license->valid;
            } catch(ApiException $e) {
                if( in_array( $e->getApiCode(), array( 'license_invalid', 'license_expired' ) ) ) {
                    $license->activated = false;
                    $license->token = '';
                }
            } catch(Exception $e) {
                // connection or parsing problem... uh oh
                // TODO: Write to debug log?
                return;
            }
        } elseif ($license->system == 'edd') {
            $api_params = [
                'edd_action' => 'check_license',
                'license' => $license->key,
                'item_id' => HF_STORE_PRODUCT_ID,
                'url' => home_url(),
            ];
        
            $response = wp_remote_post(HF_STORE_URL, ['body' => $api_params, 'timeout' => 15, 'sslverify' => false]);
        
            if (is_wp_error($response)) {
                return false;
            }
        
            $remote_license = json_decode(wp_remote_retrieve_body($response));
            if (isset($remote_license->license) && $remote_license->license == 'valid') {
                $license->activated = true;
            } else {
                $license->activated = false;
            }
        }

		$this->update_license($license);
	}

	private function redirect_with_message( $key, $code = '', $details = '' ) {
		$redirect_url = $_POST['_redirect_to'];

		$args = array( 'msg' => $key );
		if( ! empty( $code ) ) {
			$args['msg_code'] = urlencode( $code );
		}

		if( ! empty( $details ) ) {
			$args['msg_detail'] = urlencode( $details );
		}

		$redirect_url = add_query_arg( $args, $redirect_url );
		wp_safe_redirect( $redirect_url );
		exit;
	}

	private function activate_license() {
		$license = _hf_load_license();

        if ($license->system == 'my') {
            $client = $this->get_api_client();

            try {
                $data = $client->request('POST', '/license/activations', array( 'site_url' => get_option('home') ) );
            } catch( ApiException $e ) {
                $this->redirect_with_message( 'activation_error', $e->getApiCode(), $e->getApiMessage() );
                exit;
            } catch( Exception $e ) {
                $this->redirect_with_message( 'activation_error', $e->getCode(), $e->getMessage() );
                exit;
            }
    
            $license->token = $data->token;
            $license->activated = true;
            $this->update_license( $license );
    
            $this->redirect_with_message( 'activation_success' );
            exit;
        } elseif ($license->system == 'edd') {
            $this->edd_license('activate_license', $license);
        }
	}

	private function deactivate_license() {
        $redirect_url = $_POST['_redirect_to'];
		$license = _hf_load_license();

        if ($license->system == 'my') {
            $client = $this->get_api_client();

            try {
                $client->request('DELETE', '/license/activations/' . $license->token);
            } catch( ApiException $e ) {
                $this->redirect_with_message( 'deactivation_error', $e->getApiCode(), $e->getApiMessage() );
                exit;
            } catch( Exception $e ) {
                $this->redirect_with_message( 'deactivation_error', $e->getCode(), $e->getMessage() );
                exit;
            }

            $license->token = '';
            $license->activated = false;
            $this->update_license( $license );

            $this->redirect_with_message( 'deactivation_success' );
            exit;
        } elseif ($license->system == 'edd') {
            $this->edd_license('deactivate_license', $license);
        }
	}

	public function process_form() {
		// @NOTE this should probably be dynamic
		$action = trim( $_POST['action'] );
		$license = _hf_load_license();
		$previous_license_key = $license->key;
		$new_license_key = trim( $_POST['hf_premium_license_key'] );

		if( $previous_license_key !== $new_license_key ) {
			// auto-activate license if license key was empty
			if( $previous_license_key === '' ) {
				$action = 'activate';
			}

			// set new license key
			$license->key = $new_license_key;

            // set system
            $license->system = _hf_license_system($license);

            // clear out any old activation token
			$this->update_license( $license );
		}

		switch( $action ) {
            case 'Deactivate':
                $this->deactivate_license();
                break;

            case 'Activate':
            case 'Save License Key':
                $this->activate_license();
                break;
		}

		// schedule daily license check
		if( ! wp_next_scheduled( $this->plugin_slug . '_license_check' ) ) {
			wp_schedule_event( time(), 'daily', $this->plugin_slug . '_license_check' );
		};
	}

	/**
	 * @return object
	 */
	private function fetch_plugin() {
		static $data;

		if( $data === null ) {
			$client = $this->get_api_client();
			try {
				$resource = sprintf( '/plugins/%s?format=wp', $this->plugin_slug );
				$data = $client->request( 'GET', $resource );
				$data->sections = (array) $data->sections;
				if( ! empty( $data->banners ) ) {
					$data->banners = (array) $data->banners;
				}

				if (! empty($data->contributors)) {
					$data->contributors = (array) $data->contributors;
					foreach ($data->contributors as $key => $value) {
						$data->contributors[$key] = (array) $value;
					}
				}

				// add activation token to download URL's
				$license = _hf_load_license();
				if( ! empty( $license->token ) ) {
					$data->package = add_query_arg( array( 'token' => $license->token ), $data->package );
					$data->download_link = $data->package;
				} else {
					$data->sections['changelog'] = '<div class="notice notice-warning"><p>' . sprintf( 'You will need to <a href="%s">activate your plugin license</a> to install this update.', admin_url( 'admin.php?page=html-forms-settings' ) ) . '</p></div>' . $data->sections['changelog'];
					$data->upgrade_notice = 'You will need to activate your plugin license to install this update.';
					$data->package = '';
					$data->download_link = '';
				}
			} catch( Exception $e ) {
				$data = (object) array();
			}
		}

		return $data;
	}

	public function show_form() {
		$message = isset( $_GET['msg'] ) ? $_GET['msg'] : '';
		$message_detail = isset( $_GET['msg_detail'] ) ? $_GET['msg_detail'] : '';
		$message_code = isset( $_GET['msg_code'] ) ? $_GET['msg_code'] : '';
		$license = _hf_load_license();
		require dirname( __FILE__ ) . '/views/license-form.php';
	}

	public function add_plugin_update_data( $update_data ) {
		// WP is funky sometimes, so make sure we're dealing with the right thang.
		if( empty( $update_data ) || ! isset( $update_data->response ) ) {
			return $update_data;
		}

		$plugin_data = $this->fetch_plugin();
		if( version_compare( $this->plugin_version, $plugin_data->new_version, '<' ) ) {
			$plugin = plugin_basename(HF_PREMIUM_PLUGIN_FILE);
			$plugin_data->plugin = $plugin;
			$update_data->response[ $plugin ] = $plugin_data;
		}

		return $update_data;
	}

	public function get_plugin_info( $data, $action = '', $args = null ) {
		if( $action !== 'plugin_information' ) {
			return $data;
		}

		// if this is our plugin slug, fetch info
		if( $args->slug === $this->plugin_slug ) {
			return $this->fetch_plugin();
		}

		return $data;
	}

	public function after_plugin_row() {
		$license = _hf_load_license();
        
		if( $license->activated ) {
			return;
		}

		require __DIR__ . '/views/plugins-row.php';
	}

    private function edd_license($edd_action, $license)
    {
        $redirect_with_message_type = ($edd_action == 'activate_license') ? 'activation' : 'deactivation';

        $api_params = [
            'edd_action' => $edd_action,
            'license' => $license->key,
            'item_id' => urlencode(HF_STORE_PRODUCT_ID),
            'url' => home_url(),
        ];

        // Call the custom API.
        $response = wp_remote_post(HF_STORE_URL, ['timeout' => 15, 'sslverify' => false, 'body' => $api_params]);

        // make sure the response came back okay
        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            $get_error_message = $response->get_error_message();
            $message = (is_wp_error($response) && ! empty($get_error_message)) ? $response->get_error_message() : __('An error occurred, please try again.', 'html-forms-premium');
            
            $this->redirect_with_message( $redirect_with_message_type.'_error', '', $message );
            exit;
        } else {
            $license_data = json_decode(wp_remote_retrieve_body($response));

            if (false === $license_data->success) {
                switch ($license_data->error) {
                    case 'expired':
                        $message_code = 'license_expired';
                        $message = sprintf(
                            __('Your license key expired on %s.', 'html-forms-premium'),
                            date_i18n(get_option('date_format'), strtotime($license_data->expires, current_time('timestamp')))
                        );
                        break;

                        $message_code = '';
                    case 'revoked':
                        $message = __('Your license key has been disabled.', 'html-forms-premium');
                        break;

                    case 'disabled':
                        $message_code = '';
                        $message = __('Disabled license.', 'html-forms-premium');
                        break;

                    case 'missing':
                        $message_code = '';
                        $message = __('Invalid license.', 'html-forms-premium');
                        break;

                    case 'invalid':
                    case 'site_inactive':
                        $message_code = '';
                        $message = __('Your license is not active for this URL.', 'html-forms-premium');
                        break;

                    case 'item_name_mismatch':
                        $message_code = '';
                        $message = __('This appears to be an invalid license key for HTML Forms Premium.', 'html-forms-premium');
                        break;

                    case 'no_activations_left':
                        $message_code = 'license_at_limit';
                        $message = __('Your license key has reached its activation limit.', 'html-forms-premium');
                        break;

                    default:
                        $message_code = '';
                        $message = __('An error occurred, please try again.', 'html-forms-premium');
                        break;
                }

                $this->redirect_with_message( $redirect_with_message_type.'_error', $message_code, $message );
                exit;
            } else {
                if ($edd_action == 'deactivate_license') {
                    $license->key = '';
                    $license->activated = false;
                    $this->update_license( $license );
                } else {
                    $license->activated = true;
                }

                $this->update_license( $license );
                
                $this->redirect_with_message( $redirect_with_message_type.'_success' );
                exit;
            }
        }
    }
}
