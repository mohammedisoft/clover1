
<?php
/** 
*wp-content\plugins\dashboard-qahwtea\templates\settings-page.php

*/
if (!defined('ABSPATH')) {
    exit;
}

// Display Settings Page
function dq_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php echo __('Dashboard Qahwtea Settings', 'dashboard-qahwtea'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('dq_settings_group');
            do_settings_sections('dashboard-qahwtea');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register Settings
function dq_register_settings() {
    register_setting('dq_settings_group', 'dq_notification_enabled');

    add_settings_section(
        'dq_main_section',
        __('Main Settings', 'dashboard-qahwtea'),
        function () {
            echo __('Customize Dashboard Qahwtea settings below.', 'dashboard-qahwtea');
        },
        'dashboard-qahwtea'
    );

    add_settings_field(
        'dq_notification_enabled',
        __('Enable Notifications', 'dashboard-qahwtea'),
        function () {
            $value = get_option('dq_notification_enabled', 'yes');
            echo '<input type="checkbox" name="dq_notification_enabled" value="yes" ' . checked($value, 'yes', false) . '>';
        },
        'dashboard-qahwtea',
        'dq_main_section'
    );
}
add_action('admin_init', 'dq_register_settings');

// Register Settings Page in Menu
function dq_register_settings_page() {
    add_submenu_page(
        'dashboard-qahwtea',
        __('Settings', 'dashboard-qahwtea'),
        __('Settings', 'dashboard-qahwtea'),
        'manage_options',
        'dq_settings',
        'dq_settings_page'
    );
}
add_action('admin_menu', 'dq_register_settings_page');
