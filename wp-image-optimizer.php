<?php
/*
Plugin Name: WP Image Optimizer
Plugin URI: https://codext.de/wp-image-optimizer
Description: Automatically resize, optimize, and compress images after they are uploaded to your WordPress site.
Version: 1.0.0
Author: Codext GmbH
Author URI: https://codext.de
License: MIT
Text Domain: codext-wp-image-optimizer
*/

// Activation and deactivation hooks
register_activation_hook(__FILE__, 'wpio_activate');
register_deactivation_hook(__FILE__, 'wpio_deactivate');

function wpio_activate()
{
    // Perform any setup required when the plugin is activated
}

function wpio_deactivate()
{
    // Perform any cleanup required when the plugin is deactivated
}

// Admin menu and settings page
add_action('admin_menu', 'wpio_add_admin_menu');
add_action('admin_init', 'wpio_settings_init');

function wpio_add_admin_menu()
{
    add_options_page('WP Image Optimizer', 'WP Image Optimizer', 'manage_options', 'wp_image_optimizer', 'wpio_options_page');
    # add_media_page('Bulk Optimize', 'Bulk Optimize', 'manage_options', 'wpio_bulk_optimize', 'wpio_bulk_optimize_page');
}

function wpio_settings_init()
{
    register_setting('wpio_settings', 'wpio_options');

    add_settings_section(
        'wpio_settings_section',
        __('Image Resizing and Compression Settings', 'wp-image-optimizer'),
        'wpio_settings_section_callback',
        'wpio_settings'
    );

    add_settings_field(
        'wpio_max_width',
        __('Max Width', 'wp-image-optimizer'),
        'wpio_max_width_render',
        'wpio_settings',
        'wpio_settings_section'
    );

    add_settings_field(
        'wpio_max_height',
        __('Max Height', 'wp-image-optimizer'),
        'wpio_max_height_render',
        'wpio_settings',
        'wpio_settings_section'
    );

    add_settings_field(
        'wpio_compression_quality',
        __('Compression Quality', 'wp-image-optimizer'),
        'wpio_compression_quality_render',
        'wpio_settings',
        'wpio_settings_section'
    );
}

function wpio_max_width_render()
{
    $options = get_option('wpio_options');
?>
    <input type="number" name="wpio_options[wpio_max_width]" value="<?php echo $options['wpio_max_width']; ?>" min="0" step="1">
<?php
}

function wpio_max_height_render()
{
    $options = get_option('wpio_options');
?>
    <input type="number" name="wpio_options[wpio_max_height]" value="<?php echo $options['wpio_max_height']; ?>" min="0" step="1">
<?php
}

function wpio_compression_quality_render()
{
    $options = get_option('wpio_options');
?>
    <input type="number" name="wpio_options[wpio_compression_quality]" value="<?php echo $options['wpio_compression_quality']; ?>" min="0" max="100" step="1">
<?php
}

function wpio_settings_section_callback()
{
    echo __('Configure the image resizing and compression settings for the plugin.', 'wp-image-optimizer');
}

function wpio_options_page()
{
?>
    <form action="options.php" method="post">
        <h2>WP Image Optimizer</h2>
        <?php
        settings_fields('wpio_settings');
        do_settings_sections('wpio_settings');
        submit_button();
        ?>
    </form>
<?php
}

// Image resizing and compression
add_filter('wp_handle_upload', 'wpio_process_image', 10, 2);

function wpio_process_image($upload, $context)
{
    require_once 'vendor/autoload.php'; 
    $options = get_option('wpio_options');
    $max_width = isset($options['wpio_max_width']) ? (int) $options['wpio_max_width'] : 0;
    $max_height = isset($options['wpio_max_height']) ? (int) $options['wpio_max_height'] : 0;
    $compression_quality = isset($options['wpio_compression_quality']) ? (int) $options['wpio_compression_quality'] : 85;

    $image = new \Intervention\Image\ImageManager(['driver' => 'gd']);
    $img = $image->make($upload['file']);

    if ($max_width > 0 || $max_height > 0) {
        $img->resize($max_width, $max_height, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
    }

    $img->save($upload['file'], $compression_quality);

    return $upload;
}

