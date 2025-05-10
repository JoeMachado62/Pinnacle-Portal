<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// IMPORTANT: You MUST define SECURE_AUTH_KEY and SECURE_AUTH_SALT in your wp-config.php
// For a more robust solution, consider storing the encryption key outside the web root
// or using a dedicated key management service. This is a basic implementation.

function paf_get_encryption_key() {
    // Use a combination of WordPress salts for a basic key.
    // For higher security, use a dedicated, randomly generated key stored securely.
    $key = SECURE_AUTH_KEY . NONCE_KEY; // Example
    return hash('sha256', $key, true); // Ensure it's 32 bytes for AES-256
}

function paf_get_encryption_iv() {
    // IV must be 16 bytes for AES-256-CBC
    // SECURE_AUTH_SALT might be too long, so we'll hash and truncate or use a fixed portion.
    $iv_base = SECURE_AUTH_SALT;
    return substr(hash('sha256', $iv_base, true), 0, 16);
}

function paf_encrypt_data( $data, $key = null, $iv = null ) {
    if ( empty($data) ) return $data;
    if ( is_null($key) ) $key = paf_get_encryption_key();
    if ( is_null($iv) ) $iv = paf_get_encryption_iv();

    if ( !function_exists('openssl_encrypt') ) {
        // Fallback or error if openssl is not available
        error_log('PAF Encryption Error: OpenSSL not available.');
        return base64_encode($data); // Basic encoding as fallback, not secure
    }

    $encrypted = openssl_encrypt( $data, 'aes-256-cbc', $key, 0, $iv );
    return base64_encode( $encrypted ); // Base64 encode to store in DB
}

function paf_decrypt_data( $encrypted_data, $key = null, $iv = null ) {
    if ( empty($encrypted_data) ) return $encrypted_data;
    if ( is_null($key) ) $key = paf_get_encryption_key();
    if ( is_null($iv) ) $iv = paf_get_encryption_iv();
    
    if ( !function_exists('openssl_decrypt') ) {
         error_log('PAF Decryption Error: OpenSSL not available.');
        return base64_decode($encrypted_data); // Basic decoding as fallback
    }

    $decoded_data = base64_decode( $encrypted_data );
    return openssl_decrypt( $decoded_data, 'aes-256-cbc', $key, 0, $iv );
}

// Helper to decrypt borrower data for display (assumes SSN and driversLicense are encrypted)
function paf_decrypt_borrower_data($borrower_json_string) {
    if (empty($borrower_json_string)) return [];
    $borrower_data = json_decode($borrower_json_string, true);
    if (empty($borrower_data) || !is_array($borrower_data)) return [];

    $key = paf_get_encryption_key();
    $iv = paf_get_encryption_iv();

    if (isset($borrower_data['personalInformation']['ssn'])) {
        $decrypted_ssn = paf_decrypt_data($borrower_data['personalInformation']['ssn'], $key, $iv);
        $borrower_data['personalInformation']['ssn_decrypted'] = $decrypted_ssn; // Store decrypted version for display
    }
    if (isset($borrower_data['personalInformation']['driversLicense'])) {
         $decrypted_dl = paf_decrypt_data($borrower_data['personalInformation']['driversLicense'], $key, $iv);
        $borrower_data['personalInformation']['driversLicense_decrypted'] = $decrypted_dl;
    }
    return json_encode($borrower_data); // Return JSON string for consistency or array if preferred
}

?>