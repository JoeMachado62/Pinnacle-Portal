<?php

namespace HTML_Forms\Dashboard_Widget;

if (is_admin() && ( ! defined('DOING_AJAX') || ! DOING_AJAX )) {
    require __DIR__ . '/src/class-admin.php';
    $admin = new Admin();
    $admin->hook();
}