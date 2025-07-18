<?php

namespace Hostinger\AiTheme;

defined( 'ABSPATH' ) || exit;

class Assets {
    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'frontend_styles' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
        add_action( 'enqueue_block_editor_assets', array( $this, 'frontend_styles' ), 999 );
    }

    /**
     * Check if current locale is Japanese
     * @return bool
     */
    private function is_japanese_locale(): bool {
        $japanese_locales = array( 'ja', 'ja_JP' );

        return in_array( get_locale(), $japanese_locales, true );
    }

    /**
     * Enqueue frontend styles
     * @return void
     */
    public function frontend_styles(): void {
        wp_enqueue_style(
            'hostinger-ai-style',
            get_stylesheet_directory_uri() . '/assets/css/style.min.css',
            [],
            wp_get_theme()->get( 'Version' ),
        );

        if( !is_admin() ) {
            wp_add_inline_style(
                'hostinger-ai-style',
                '.hostinger-ai-fade-up { opacity: 0; }'
            );
        }

        // Enqueue Japanese font if needed
        if ( $this->is_japanese_locale() ) {
            wp_enqueue_style(
                'noto-sans',
                'https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@100;300;400;500;700;900&display=swap',
                ['hostinger-ai-style'],
                wp_get_theme()->get( 'Version' )
            );

            $japanese_font_css = "
                body, 
                .editor-styles-wrapper, 
                .block-editor-block-list__layout,
                .wp-block-paragraph,
                .wp-block-heading {
                    font-family: 'Noto Sans JP', -apple-system, BlinkMacSystemFont,
                        'Hiragino Sans', 'Hiragino Kaku Gothic ProN', 'Segoe UI',
                        'Yu Gothic UI', Meiryo, sans-serif;
                    font-weight: 400;
                    -webkit-font-smoothing: antialiased;
                    -moz-osx-font-smoothing: grayscale;
                }
            ";

            wp_add_inline_style( 'noto-sans', wp_strip_all_tags( $japanese_font_css ) );
        }
    }

    /**
     * @return void
     */
    public function frontend_scripts(): void {
        wp_enqueue_script(
            'hostinger-ai-scripts',
            get_stylesheet_directory_uri() . '/assets/js/front-scripts.min.js',
            [
                'jquery',
                'wp-i18n',
            ],
            wp_get_theme()->get( 'Version' ),
            true,
        );
    }
}