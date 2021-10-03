<?php

namespace WP_Shopify;

if (!defined('ABSPATH')) {
    exit();
}

use WP_Shopify\Options;
use WP_Shopify\Utils;
use WP_Shopify\Utils\Data as Utils_Data;

class Backend
{
    public $plugin_settings;
    public $DB_Settings_General;
    public $DB_Products;
    public $Data_Bridge;
    public $DB_Collections;

    public function __construct(
        $plugin_settings,
        $DB_Settings_General,
        $DB_Products,
        $DB_Collections,
        $Data_Bridge
    ) {
        $this->plugin_settings = $plugin_settings;
        $this->DB_Settings_General = $DB_Settings_General;
        $this->DB_Products = $DB_Products;
        $this->DB_Collections = $DB_Collections;
        $this->Data_Bridge = $Data_Bridge;
    }

    /*

     Checks for a valid admin page

     */
    public function is_valid_admin_page()
    {
        $screen = \get_current_screen();

        if (empty($screen)) {
            return false;
        }

        if (!is_admin()) {
            return false;
        }

        return $screen;
    }

    /*

     Checks for a valid admin page

     */
    public function get_screen_id()
    {
        $screen = $this->is_valid_admin_page();

        if (empty($screen)) {
            return false;
        }

        return $screen->id;
    }

    /*

     Checks for the correct admin page to load CSS

     */
    public function should_load_css()
    {
        if (!$this->is_valid_admin_page()) {
            return;
        }

        $screen_id = $this->get_screen_id();

        if (
            $this->is_admin_settings_page($screen_id) ||
            $this->is_admin_posts_page($screen_id) ||
            $this->is_admin_plugins_page($screen_id)
        ) {
            return true;
        }

        return false;
    }

    public function is_wizard_page()
    {
        return $this->get_screen_id() === 'dashboard_page_wpshopify-wizard' ||
            $this->get_screen_id() === 'admin_page_wpshopify-wizard';
    }

    public function is_plugin_specific_pages()
    {
        return $this->is_admin_settings_page($this->get_screen_id());
    }

    /*

     Checks for the correct admin page to load JS

     */
    public function should_load_js()
    {
        if (!$this->is_valid_admin_page()) {
            return;
        }

        $screen_id = $this->get_screen_id();

        // Might want to check these eventually
        // || $this->is_admin_posts_page($screen_id)

        if ($this->is_admin_settings_page($screen_id)) {
            return true;
        }

        return false;
    }

    /*

     Is wp posts page

     */
    public function is_admin_posts_page($current_admin_screen_id)
    {
        if (
            $current_admin_screen_id ===
                WP_SHOPIFY_COLLECTIONS_POST_TYPE_SLUG ||
            $current_admin_screen_id === WP_SHOPIFY_PRODUCTS_POST_TYPE_SLUG ||
            $current_admin_screen_id === 'edit-wps_products' ||
            $current_admin_screen_id === 'edit-wps_collections'
        ) {
            return true;
        }
    }

    /*

     Is wp plugins page

     */
    public function is_admin_plugins_page($current_admin_screen_id)
    {
        if ($current_admin_screen_id === 'plugins') {
            return true;
        }
    }

    /*

     Is plugin settings page

     */
    public function is_admin_settings_page($current_admin_screen_id = false)
    {
        if (
            Utils::str_contains($current_admin_screen_id, 'wp-shopify') ||
            Utils::str_contains($current_admin_screen_id, 'wpshopify')
        ) {
            return true;
        }
    }

    /*

     Admin styles

     */
    public function admin_styles()
    {
        if ($this->should_load_css()) {
            wp_enqueue_style('wp-color-picker');

            wp_enqueue_style(
                'animate-css',
                WP_SHOPIFY_PLUGIN_URL . 'admin/css/vendor/animate.min.css',
                [],
                filemtime(
                    WP_SHOPIFY_PLUGIN_DIR_PATH .
                        'admin/css/vendor/animate.min.css'
                )
            );

            wp_enqueue_style(
                'wpshopify' . '-styles-backend',
                WP_SHOPIFY_PLUGIN_URL . 'dist/admin.min.css',
                ['wp-color-picker', 'wp-components', 'animate-css'],
                filemtime(WP_SHOPIFY_PLUGIN_DIR_PATH . 'dist/admin.min.css')
            );
        }
    }

    public function replace_rest_protocol()
    {
        if (is_ssl()) {
            return str_replace("http://", "https://", get_rest_url());
        }

        return get_rest_url();
    }

    // TODO: Check the $hook variable for valid wps page 
    public function admin_scripts($hook)
    {
        if ($this->should_load_js() && !$this->is_wizard_page()) {
            global $wp_version;

            if (version_compare($wp_version, '5.4', '<')) {
                wp_die(
                    "Sorry, WP Shopify requires WordPress version 5.4 or higher. Please look through <a href=\"https://docs.wpshop.io/#/getting-started/requirements?utm_medium=plugin&utm_source=notice&utm_campaign=help\" target=\"_blank\">our requirements</a> page to learn more. Often times you can simply ask your webhost to upgrade for you. <br><br><a href=" .
                        admin_url('plugins.php') .
                        " class=\"button button-primary\">Back to plugins</a>."
                );
            }

            if (
                !function_exists('version_compare') ||
                version_compare(PHP_VERSION, '5.6.0', '<')
            ) {
                wp_die(
                    "Sorry, WP Shopify requires PHP version 5.6 or higher. Please look through <a href=\"https://docs.wpshop.io/#/getting-started/requirements?utm_medium=plugin&utm_source=notice&utm_campaign=help\" target=\"_blank\">our requirements</a> page to learn more. Often times you can simply ask your webhost to upgrade for you. <br><br><a href=" .
                        admin_url('plugins.php') .
                        " class=\"button button-primary\">Back to plugins</a>."
                );
            }


            wp_enqueue_script(
                'anime-js',
                WP_SHOPIFY_PLUGIN_URL . 'admin/js/vendor/anime.min.js',
                [],
                filemtime(
                    WP_SHOPIFY_PLUGIN_DIR_PATH . 'admin/js/vendor/anime.min.js'
                )
            );

            $runtime_url = WP_SHOPIFY_PLUGIN_URL . 'dist/runtime.73ad37.min.js';
            $vendors_admin_url =
                WP_SHOPIFY_PLUGIN_URL . 'dist/vendors-admin.73ad37.min.js';
            $main_url = WP_SHOPIFY_PLUGIN_URL . 'dist/admin.73ad37.min.js';

            wp_enqueue_script('wpshopify-runtime', $runtime_url, []);
            wp_enqueue_script(
                'wpshopify-vendors-admin',
                $vendors_admin_url,
                []
            );
            wp_enqueue_script(
                'wpshopify-admin',
                $main_url,
                [
                    'wp-blocks',
                    'wp-element',
                    'wp-editor',
                    'wp-components',
                    'wp-i18n',
                    'wpshopify-runtime',
                    'wpshopify-vendors-admin',
                ],
                '',
                true
            );

            wp_set_script_translations(
                'wpshopify-admin',
                'wpshopify',
                WP_SHOPIFY_PLUGIN_DIR . WP_SHOPIFY_LANGUAGES_FOLDER
            );

            // Global plugin JS settings
            $this->Data_Bridge->add_settings_script('wpshopify-admin', true);
        }
    }

    /*

   Registering the admin menu into the WordPress Dashboard menu.
   Adding a settings page to the Settings menu.

   */
    public function add_dashboard_menus()
    {

         $user = wp_get_current_user();

        if (apply_filters('wpshopify_show_dashboard', current_user_can('edit_pages'), $user)) {
            if (empty($this->plugin_settings['general'])) {
                $setting_lite_sync = true;
                $setting_is_syncing_posts = false;
            } else {
                $setting_lite_sync =
                    $this->plugin_settings['general']['is_lite_sync'];
                $setting_is_syncing_posts =
                    $this->plugin_settings['general']['is_syncing_posts'];
            }

            $plugin_name = WP_SHOPIFY_PLUGIN_NAME_FULL;


            global $submenu;

            $icon_svg =
                'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDIzLjAuNCwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPgo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IgoJIHZpZXdCb3g9IjAgMCAxMDAgMTAwIiBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCAxMDAgMTAwOyIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+CjxnPgoJPHBhdGggZD0iTTE4LjksMjYuOGM1LjIsMCw5LjksMi45LDEyLjMsNy42bDEwLDE5LjljMCwwLDQuMy02LjksOC40LTEzLjFsMC44LTEuMmwtNS43LTEyLjVjLTAuMi0wLjQsMC4xLTAuOCwwLjUtMC44aDEzCgkJYzUuNSwwLDEwLjQsMy4yLDEyLjYsOC4ybDguNSwxOS4ybDMuOC02LjFjMi40LTQsNS41LTkuMSw4LjEtMTIuOGwyLjItMy41Qzg2LjIsMTUsNjkuNSwzLjMsNTAuMiwzLjNjLTE3LjQsMC0zMi42LDkuNS00MC43LDIzLjUKCQlIMTguOXoiLz4KCTxwYXRoIGQ9Ik05NC42LDM1bC0yLjMsMy43bDAuMSwwbC0yNSw0MC4xYy0wLjUsMC42LTEuMywwLjgtMiwwLjRjLTAuNi0wLjQtMC44LTEuMy0wLjQtMS45bDQuNS03LjNjLTIuOSwwLjMtNS45LTEtNy4yLTRMNTEuOCw0MwoJCUwyOSw3OC43Yy0wLjIsMC4zLTAuNywwLjQtMSwwLjJsLTEtMC42Yy0wLjMtMC4yLTAuNC0wLjctMC4yLTFsNC41LTcuMmMtMi44LDAuMy01LjgtMS4xLTcuMS00bC0xNy0zNC44Yy0yLjYsNS44LTQsMTIuMi00LDE5CgkJYzAsMjYsMjEsNDcsNDcsNDdzNDctMjEsNDctNDdDOTcuMiw0NC45LDk2LjMsMzkuOCw5NC42LDM1eiIvPgo8L2c+Cjwvc3ZnPgo=';

            // Main menu
            add_menu_page(
                __($plugin_name, 'wpshopify'),
                __($plugin_name, 'wpshopify'),
                'edit_pages',
                'wpshopify',
                [$this, 'plugin_admin_page'],
                $icon_svg,
                null
            );

            add_submenu_page(
                'wpshopify',
                __('Connect', 'wpshopify'),
                __('Connect', 'wpshopify'),
                'edit_pages',
                'wps-connect',
                [$this, 'plugin_admin_page']
            );

            add_submenu_page(
                'wpshopify',
                __('Sync', 'wpshopify'),
                __('Sync', 'wpshopify'),
                'edit_pages',
                'wps-tools',
                [$this, 'plugin_admin_page']
            );

            add_submenu_page(
                'wpshopify',
                __('Settings', 'wpshopify'),
                __('Settings', 'wpshopify'),
                'edit_pages',
                'wps-settings',
                [$this, 'plugin_admin_page']
            );

            add_submenu_page(
                'wpshopify',
                __('Products', 'wpshopify'),
                __('Products', 'wpshopify'),
                'edit_pages',
                'edit.php?post_type=' . WP_SHOPIFY_PRODUCTS_POST_TYPE_SLUG,
                null
            );

            if (!empty($this->plugin_settings['general']['selective_sync_collections'])) {
               add_submenu_page(
                  'wpshopify',
                  __('Collections', 'wpshopify'),
                  __('Collections', 'wpshopify'),
                  'edit_pages',
                  'edit.php?post_type=' . WP_SHOPIFY_COLLECTIONS_POST_TYPE_SLUG,
                  null
               );
            }
            
            

            // add_submenu_page(
            //     'wpshopify',
            //     __('License', 'wpshopify'),
            //     __('License', 'wpshopify'),
            //     'edit_pages',
            //     'wps-license',
            //     [$this, 'plugin_admin_page']
            // );

            // add_submenu_page(
            //     'wpshopify',
            //     __('Extensions', 'wpshopify'),
            //     __('Extensions', 'wpshopify'),
            //     'edit_pages',
            //     'wps-extensions',
            //     [$this, 'plugin_admin_page']
            // );

            // add_submenu_page(
            //     'wpshopify',
            //     __('Info', 'wpshopify'),
            //     __('Info', 'wpshopify'),
            //     'edit_pages',
            //     'wps-help',
            //     [$this, 'plugin_admin_page']
            // );

            remove_submenu_page('wpshopify', 'wpshopify');

            add_submenu_page(
                null,
                __('Wizard', 'wpshopify'),
                __('Wizard', 'wpshopify'),
                'edit_pages',
                'wpshopify-wizard',
                function () {
                    echo '<div id="wpshopify-wizard"></div>';
                }
            );
        }
    }

    public function add_action_links($links)
    {
        $settings_link = admin_url("/admin.php?page=wps-connect");
        $settings_html_link =
            '<a href="' . esc_url($settings_link) . '">Settings</a>';
        $settings_link = [$settings_html_link];

        $settings_link[] =
            '<a href="' .
            esc_url(
                'https://wpshop.io/purchase?utm_medium=plugin&utm_source=action-link&utm_campaign=upgrade'
            ) .
            '" target="_blank">' .
            __('Upgrade to Pro', 'wpshopify') .
            '</a>';

        return array_merge($settings_link, $links);
    }

    /*

     Render the settings page for this plugin.

     */
    public function plugin_admin_page()
    {
        include_once WP_SHOPIFY_PLUGIN_DIR_PATH .
            'admin/partials/wps-admin-display.php';
    }

    /*

     Register / Update plugin options
     Currently only updating connection form

     */
    public function on_options_update()
    {
        register_setting(
            WP_SHOPIFY_SETTINGS_CONNECTION_OPTION_NAME,
            WP_SHOPIFY_SETTINGS_CONNECTION_OPTION_NAME,
            [$this, 'connection_form_validate']
        );
    }

    /*

     Validate connection form settings

     */
    public function connection_form_validate($input)
    {
        $valid = [];

        // Nonce
        $valid['nonce'] =
            isset($input['nonce']) && !empty($input['nonce'])
                ? sanitize_text_field($input['nonce'])
                : '';

        return $valid;
    }

    public function wps_admin_body_class($classes)
    {
        // If the settings aren't empty ...
        if (empty($this->plugin_settings['general'])) {
            return $classes;
        }

        $screen_id = $this->get_screen_id();

        if (
            $screen_id !== 'edit-wps_products' &&
            $screen_id !== 'edit-wps_collections'
        ) {
            return $classes;
        }

        if (!$this->plugin_settings['general']['is_syncing_posts']) {
            $classes .= ' wps-is-lite-sync ';
        }

        return $classes;
    }

    public function wps_posts_notice()
    {
        // If the settings aren't empty ...
        if (empty($this->plugin_settings['general'])) {
            return;
        }

        // If the right admin page ...
        $screen_id = $this->get_screen_id();

        if (
            $screen_id !== 'edit-wps_products' &&
            $screen_id !== 'edit-wps_collections'
        ) {
            return;
        }

        if (!$this->plugin_settings['general']['is_syncing_posts']) {
            echo '<div class="wps-posts-notice">
            <svg id="70bb2b30-be60-4de4-9e4b-86e23973444e" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="1143.11" height="875.78" viewBox="0 0 1143.11 875.78"><defs><linearGradient id="e5da6213-051c-4da8-b1b3-926fff123e76" x1="539.33" y1="763.29" x2="539.33" y2="57.83" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="gray" stop-opacity="0.25"/><stop offset="0.54" stop-color="gray" stop-opacity="0.12"/><stop offset="1" stop-color="gray" stop-opacity="0.1"/></linearGradient><linearGradient id="052f7d93-657d-41ac-9ec0-ae0276bba283" x1="1034.54" y1="812.84" x2="1034.54" y2="197.81" gradientTransform="matrix(-1, 0, 0, 1, 1347.17, 0)" xlink:href="#e5da6213-051c-4da8-b1b3-926fff123e76"/></defs><title>onboarding</title><ellipse cx="849.67" cy="829.19" rx="115.8" ry="28.95" fill="#415aff" opacity="0.1"/><path d="M63.7,616.77c-13.1,4.49-29,13.65-26.32,28.21,1.34,7.38,7.32,12.85,13,17.47a368.3,368.3,0,0,0,79.36,49.15c13.57,6.16,28,11.81,38.25,23.17s15,27.24,18.33,42.55a345.86,345.86,0,0,1,5.72,110.41c40.91.78,86.21-.14,115.16-29.61,17.33-17.65,26.62-43.83,47-57.49,13.59-9.11,30.34-11.37,46.52-13.27q114.87-13.43,230.93-16.26c32.42-.79,65.55-1.07,95.82-12.37s57.75-36,63.9-69.49c.95-5.16,1.57-10.85,5.1-14.45,3.15-3.23,7.87-4,12.28-4.57l265.89-36c14.56-2,29.63-4.13,41.89-12.36,11.86-8,19.86-20.78,27.51-33.24,17.53-28.52,35.72-63,23.58-95.61-9.71-26.06-35.81-41.07-60.75-50.39s-51.79-15.87-72.06-34.14c-30.84-27.8-37.68-73.95-46.37-115.36a675.43,675.43,0,0,0-33-109.89C943,151.89,927.85,121,905.27,96.61S851.62,54.93,820,56.17C778.7,57.81,742.37,90,701,86c-34.94-3.35-63.37-31.35-94.83-48.77C550.32,6.28,484.4,9,423.29,18.65c-64.8,10.21-130,28.08-182.5,68.27-12,9.2-23.48,19.82-30.53,33.66-10.8,21.23-9.87,47.1-15.28,70.69-21.8,95-138.68,134.63-162.71,228.94C23.73,453.69,30.05,490,45.72,520.94c16.85,33.31,50.7,47.51,68.05,76.1C102.17,607.63,79.06,611.5,63.7,616.77Z" transform="translate(-28.45 -12.11)" fill="#415aff" opacity="0.1"/><path d="M898.6,849s-10.48-72.2,21-119.72a110.5,110.5,0,0,0,18.68-67.38,185.42,185.42,0,0,0-5.1-33.61" transform="translate(-28.45 -12.11)" fill="none" stroke="#535461" stroke-miterlimit="10" stroke-width="2"/><path d="M952.92,598.2c-1.21,8.49-20.11,30.91-20.11,30.91S921,602.28,922.18,593.79a15.53,15.53,0,1,1,30.74,4.41Z" transform="translate(-28.45 -12.11)" fill="#415aff"/><path d="M971.6,648.87c-5.64,6.46-33.69,15-33.69,15s4.64-29,10.27-35.42a15.53,15.53,0,1,1,23.42,20.41Z" transform="translate(-28.45 -12.11)" fill="#415aff"/><path d="M959,729.07c-8.13,2.74-36.66-4-36.66-4s18.62-22.66,26.75-25.39A15.53,15.53,0,0,1,959,729.07Z" transform="translate(-28.45 -12.11)" fill="#415aff"/><path d="M935.76,787.61c-7.55,4.07-36.81,2.2-36.81,2.2s14.52-25.47,22.07-29.54a15.53,15.53,0,0,1,14.74,27.34Z" transform="translate(-28.45 -12.11)" fill="#415aff"/><path d="M905.4,660.9c5.24,6.79,32.74,17,32.74,17s-2.92-29.18-8.16-36a15.53,15.53,0,0,0-24.58,19Z" transform="translate(-28.45 -12.11)" fill="#415aff"/><path d="M879.12,729.7c7,4.91,36.31,6.41,36.31,6.41s-11.5-27-18.54-31.88a15.53,15.53,0,1,0-17.77,25.47Z" transform="translate(-28.45 -12.11)" fill="#415aff"/><path d="M862.75,800.08c6.1,6,34.71,12.45,34.71,12.45S890.68,784,884.57,778a15.53,15.53,0,1,0-21.82,22.1Z" transform="translate(-28.45 -12.11)" fill="#415aff"/><path d="M952.92,598.2c-1.21,8.49-20.11,30.91-20.11,30.91S921,602.28,922.18,593.79a15.53,15.53,0,1,1,30.74,4.41Z" transform="translate(-28.45 -12.11)" opacity="0.25"/><path d="M971.6,648.87c-5.64,6.46-33.69,15-33.69,15s4.64-29,10.27-35.42a15.53,15.53,0,1,1,23.42,20.41Z" transform="translate(-28.45 -12.11)" opacity="0.25"/><path d="M959,729.07c-8.13,2.74-36.66-4-36.66-4s18.62-22.66,26.75-25.39A15.53,15.53,0,0,1,959,729.07Z" transform="translate(-28.45 -12.11)" opacity="0.25"/><path d="M935.76,787.61c-7.55,4.07-36.81,2.2-36.81,2.2s14.52-25.47,22.07-29.54a15.53,15.53,0,0,1,14.74,27.34Z" transform="translate(-28.45 -12.11)" opacity="0.25"/><path d="M905.4,660.9c5.24,6.79,32.74,17,32.74,17s-2.92-29.18-8.16-36a15.53,15.53,0,0,0-24.58,19Z" transform="translate(-28.45 -12.11)" opacity="0.25"/><path d="M879.12,729.7c7,4.91,36.31,6.41,36.31,6.41s-11.5-27-18.54-31.88a15.53,15.53,0,1,0-17.77,25.47Z" transform="translate(-28.45 -12.11)" opacity="0.25"/><path d="M862.75,800.08c6.1,6,34.71,12.45,34.71,12.45S890.68,784,884.57,778a15.53,15.53,0,1,0-21.82,22.1Z" transform="translate(-28.45 -12.11)" opacity="0.25"/><path d="M899.46,846.71s-38.84-61.76-29.27-118A110.5,110.5,0,0,0,860,659.56a185.49,185.49,0,0,0-18.29-28.66" transform="translate(-28.45 -12.11)" fill="none" stroke="#535461" stroke-miterlimit="10" stroke-width="2"/><path d="M847.51,595.43c2.32,8.26-5.87,36.41-5.87,36.41s-21.7-19.72-24-28a15.53,15.53,0,1,1,29.9-8.44Z" transform="translate(-28.45 -12.11)" fill="#415aff"/><path d="M885.11,634.19c-2.53,8.19-24.71,27.37-24.71,27.37s-7.5-28.34-5-36.54a15.53,15.53,0,0,1,29.68,9.17Z" transform="translate(-28.45 -12.11)" fill="#415aff"/><path d="M906.07,712.63c-6.32,5.79-35.15,11.15-35.15,11.15s7.84-28.25,14.16-34a15.53,15.53,0,0,1,21,22.9Z" transform="translate(-28.45 -12.11)" fill="#415aff"/><path d="M908.57,775.55c-5.25,6.78-32.76,16.93-32.76,16.93s3-29.17,8.2-36a15.53,15.53,0,1,1,24.56,19Z" transform="translate(-28.45 -12.11)" fill="#415aff"/><path d="M829.47,672c7.54,4.09,36.8,2.26,36.8,2.26s-14.48-25.49-22-29.57A15.53,15.53,0,1,0,829.47,672Z" transform="translate(-28.45 -12.11)" fill="#415aff"/><path d="M833.32,745.56c8.42,1.64,35.8-8.85,35.8-8.85s-21.45-20-29.87-21.63a15.53,15.53,0,1,0-5.93,30.48Z" transform="translate(-28.45 -12.11)" fill="#415aff"/><path d="M846.87,816.54c8,3,36.78-2.69,36.78-2.69s-17.76-23.32-25.78-26.36a15.53,15.53,0,0,0-11,29Z" transform="translate(-28.45 -12.11)" fill="#415aff"/><polygon points="285.63 57.83 285.63 85.21 285.63 763.29 793.03 763.29 793.03 85.21 793.03 57.83 285.63 57.83" fill="url(#e5da6213-051c-4da8-b1b3-926fff123e76)"/><rect x="290.08" y="64.02" width="498.5" height="26.89" fill="#f6f7f9"/><g opacity="0.2"><rect x="290.08" y="64.02" width="498.5" height="26.89" fill="#415aff"/></g><rect x="290.08" y="90.91" width="498.5" height="666.19" fill="#f6f7f9"/><circle cx="303.19" cy="77.47" r="4.56" fill="#f6f7f9"/><circle cx="315.55" cy="77.47" r="4.56" fill="#f6f7f9"/><circle cx="327.91" cy="77.47" r="4.56" fill="#f6f7f9"/><g opacity="0.2"><rect x="379.05" y="131.98" width="320.55" height="12.55" fill="#415aff"/></g><rect x="657.01" y="131.98" width="42.59" height="12.55" fill="#415aff"/><g opacity="0.2"><rect x="337.61" y="203.47" width="403.44" height="144.49" fill="#415aff"/></g><rect x="450.83" y="256.13" width="232.71" height="12.55" fill="#f6f7f9"/><rect x="450.83" y="282.75" width="120.73" height="12.55" fill="#f6f7f9"/><rect x="657.01" y="320.77" width="42.59" height="12.55" fill="#415aff"/><circle cx="386.28" cy="275.71" r="22.81" fill="#f6f7f9"/><g opacity="0.2"><rect x="337.61" y="388.46" width="403.44" height="144.49" fill="#415aff"/></g><rect x="450.83" y="441.12" width="232.71" height="12.55" fill="#f6f7f9"/><rect x="450.83" y="467.74" width="120.73" height="12.55" fill="#f6f7f9"/><rect x="657.01" y="505.76" width="42.59" height="12.55" fill="#415aff"/><circle cx="386.28" cy="460.7" r="22.81" fill="#f6f7f9"/><g opacity="0.2"><rect x="337.61" y="573.45" width="403.44" height="144.49" fill="#415aff"/></g><rect x="450.83" y="626.11" width="232.71" height="12.55" fill="#f6f7f9"/><rect x="450.83" y="652.73" width="120.73" height="12.55" fill="#f6f7f9"/><rect x="657.01" y="690.75" width="42.59" height="12.55" fill="#415aff"/><circle cx="386.28" cy="645.69" r="22.81" fill="#f6f7f9"/><path d="M192,466.51c-3.87,17.61-10.85,39.31-10.85,39.31,6.9,8.05,37,9.49,37,9.49l.05-.06.47,0c.21,1.11.45,2.18.74,3.2q-.08,1.51-.15,3.06a8.35,8.35,0,0,0-.43,3.76,8.52,8.52,0,0,0,.22.84c-.72,15.94-1.23,32.94-.79,42.76a41.15,41.15,0,0,0,.85,8.28,29.81,29.81,0,0,1,.88,6.35c.49,14.37-3,38.51-3,38.51s.09.33.14.5c-.06.41-.1.65-.1.65a11.39,11.39,0,0,1,.51,3c.26,12.12-6.69,38.49-6.69,38.49s-5.62,40.55-4.82,56a35.62,35.62,0,0,0,.27,3.69c1.72,12.85,4,48.73,4,48.73a16.12,16.12,0,0,0,1.31,1.69c-.2.8-.44,1.61-.7,2.41a67.6,67.6,0,0,0-3.35,25.12c.07,1,.15,2.09.25,3.19,1.15,13.24,33.18,5,35.29,3.46.72-.53.21-5.33-.78-11.42l33,8.15s23.4,3.46,34.53,0,10.74-13-5.18-14.57c-11.15-1.08-20-11.46-24.37-17.59,1.69-4.61,14.36-39.6,16.69-61.26a306.66,306.66,0,0,1,7.39-41.52l2.49-32.23,4.42-19,5.27-25.12c5.95-18.42,7.77-49.21,7.77-49.21l4.6-30.21A58.48,58.48,0,0,0,330,505a111.68,111.68,0,0,1,12.75,3.41c7.2-5.6-3-59.56-3-59.56s-4.91-34.08-9.07-65.15c9.53-1.43,19.86.81,19.86.81l60.43,1.33a30.71,30.71,0,0,0,3.24-5.28h2.59a5.55,5.55,0,0,0,.75-1.46l1.07.2a89.37,89.37,0,0,1,11.56,3.51s19.06,2.3,16.18-17.69c-2.14-14.9-19.1-9.49-27.58-5.79l-1.07.49c-.08-.56-.13-.88-.13-.88l-2.73.5a13.2,13.2,0,0,0-.57-1.22s-29.64-1.15-53.67-4.75c-17.09-2.56-30-12.33-36.07-17.75-4.21-21.37-16.44-27.86-16.44-27.86-6.79-.34-12.91-3.33-16.67-5.64a35.23,35.23,0,0,1-3.9-2.7l-.66.38-.08-.07c4.07-3.78,8.29-7.25,12-10.16l1.67-1.29a37.07,37.07,0,0,0,30.28-51.91c-.16-.36-.35-.72-.52-1.07a8.32,8.32,0,0,0,7.34-7.23c.37-4.19-2.52-8.65-.63-12.41a12.06,12.06,0,0,0,.9-1.75,4.73,4.73,0,0,0,.14-1.76,12.39,12.39,0,0,0-5-9c-4.29-3.11-8.1-1.53-12.79-1.8-5.42-.31-10.79-1.62-16.13-2.5-6.13-1-12.41-1.68-18.53-.61s-12.11,4.07-15.66,9.17c-1.52,2.18-2.55,4.67-4,6.92-1.63,2.6-3.74,4.85-5.61,7.27-3.15,4.07-5.7,8.95-5.45,14.09.14,2.91,1.17,5.71,1.37,8.62a52.49,52.49,0,0,1-.6,8.1,35.46,35.46,0,0,0,.59,10.33,6.4,6.4,0,0,0,1.18,2.94,6.27,6.27,0,0,0,2.6,1.67,23.23,23.23,0,0,0,3.19,1,45.69,45.69,0,0,1-4.47,8.05l-1.62-1.68-4.38,6.84-.17-.46c-.21.43-.45.84-.7,1.25l-.45-1.25c-1.94,3.89-5.57,7.14-9.81,9.82-11.46,7.2-27.45,10.18-27.45,10.18-25.76,3.74-21,30.35-21,30.35s-10.93,47.48-14,76.83,8.34,48.05,8.34,48.05Zm122.85-86.78c1,3.27,1.88,6.37,2.63,9.08L317,389C316.45,386.49,315.7,383.27,314.88,379.73Zm-28.34-79.67-.14.09.13-.13ZM242.08,796.19c-1.26-7.53-3.14-16.62-4.3-22.05.82-2.67,1.91-6.75,3.19-12.82,2.88-13.61,4.6-61.77,4.6-61.77l6.72-40.46c6.14-15.54,7.48-34,7.48-34,2.47-6.52,8.18-46.43,9.09-52.84-.26,4.43-.88,15.65-1.21,28.11,0,.11,0,.23,0,.34-.25,9.41-.32,19.5,0,27.87v0c.09,2.35.21,4.58.37,6.64.05.53.08,1.08.1,1.62.72,21.7-9,49-9,49s-4.33,20.24-3.69,31.55a20.82,20.82,0,0,0,.86,6,10.61,10.61,0,0,1,.49,2.78c.18,10.82-6.21,33.53-7.84,39.1C245.38,772.6,242.73,791.31,242.08,796.19Z" transform="translate(-28.45 -12.11)" fill="url(#052f7d93-657d-41ac-9ec0-ae0276bba283)"/><path d="M277.73,766.66s11.22,20.34,27,21.86,16.16,11,5.13,14.45-34.22,0-34.22,0l-33.08-8.17s4-32.7,9.31-34.41S277.73,766.66,277.73,766.66Z" transform="translate(-28.45 -12.11)" fill="#4c4c78"/><path d="M237.42,767s8.37,37.64,6.28,39.17-33.84,9.69-35-3.43c-.1-1.09-.18-2.15-.24-3.16a66.64,66.64,0,0,1,3.32-24.9,20.63,20.63,0,0,0,1.29-7.68C212.52,761.72,237.42,767,237.42,767Z" transform="translate(-28.45 -12.11)" fill="#4c4c78"/><path d="M247.79,290.47s25.66,63,21.1,42.8c-3-13.42,16.51-31.23,30-41.7,6.87-5.34,12.19-8.77,12.19-8.77s-48.2-56.18-44.78-31.94a36.5,36.5,0,0,1-2.63,19.56A51.28,51.28,0,0,1,247.79,290.47Z" transform="translate(-28.45 -12.11)" fill="#ffb9b9"/><path d="M411.77,380a14.75,14.75,0,0,1,5.87.37,86.62,86.62,0,0,1,11.45,3.48s18.9,2.28,16-17.53c-2.12-14.78-18.92-9.42-27.33-5.74-2.86,1.25-4.76,2.31-4.76,2.31Z" transform="translate(-28.45 -12.11)" fill="#ffb9b9"/><path d="M411.77,380a14.75,14.75,0,0,1,5.87.37c2.05-5.19.49-17.48.16-19.79-2.86,1.25-4.76,2.31-4.76,2.31Z" transform="translate(-28.45 -12.11)" opacity="0.1"/><path d="M411.67,361.13l4.94-.91s2.54,17-.77,21.44h-5.69Z" transform="translate(-28.45 -12.11)" fill="#cbcdda"/><path d="M312.52,392.12s15.12-9,16.92,2.37,4.19,30.9,4.19,30.9l4.18,24.81,5.42,49.72-.76,8.46S326.4,503,322.6,504.86,315.75,484,315.75,484l-6.27-23.58-1-50Z" transform="translate(-28.45 -12.11)" fill="#4c4c78"/><path d="M312.52,392.12s15.12-9,16.92,2.37,4.19,30.9,4.19,30.9l4.18,24.81,5.42,49.72-.76,8.46S326.4,503,322.6,504.86,315.75,484,315.75,484l-6.27-23.58-1-50Z" transform="translate(-28.45 -12.11)" opacity="0.1"/><path d="M320.65,333.56s15.69,17.68,39.5,21.25,53.19,4.7,53.19,4.7,7.41,13.26-3.28,27.38l-59.89-1.32s-13.26-2.88-23.53,0Z" transform="translate(-28.45 -12.11)" fill="#4c4c78"/><path d="M320.65,333.56s15.69,17.68,39.5,21.25,53.19,4.7,53.19,4.7,7.41,13.26-3.28,27.38l-59.89-1.32s-13.26-2.88-23.53,0Z" transform="translate(-28.45 -12.11)" opacity="0.05"/><path d="M450.84,406.14" transform="translate(-28.45 -12.11)" fill="none" stroke="blue" stroke-miterlimit="10"/><path d="M266.23,269.87c5.85,12.51,16,21.74,30.76,21.74.65,0,1.28,0,1.92,0,6.87-5.34,12.19-8.77,12.19-8.77s-48.2-56.18-44.78-31.94C267.37,258.27,268.58,264.3,266.23,269.87Z" transform="translate(-28.45 -12.11)" opacity="0.1"/><path d="M260.25,253.73A36.74,36.74,0,1,0,297,217,36.66,36.66,0,0,0,260.25,253.73Z" transform="translate(-28.45 -12.11)" fill="#ffb9b9"/><path d="M237.8,478.24s81.94,7.23,85-3.23-8.36-38.78-8.36-38.78l3.66-39.55.14-1.52S308,352.38,308.34,349c.35-3.07-16.53-39-20-46.31l-.63-1.32-.65.38-.28.17-4,2.34-15.87-3.74-19.1-10L246,399.92Z" transform="translate(-28.45 -12.11)" fill="#cbcdda"/><path d="M280.39,302.66s5.61-1.52,7.8,4.09a57.66,57.66,0,0,0,3,7l2.19,13,4,20.53,2.94,91L299,454.48l-7.32-13-1.33-85v-10.6a76.71,76.71,0,0,0-6.1-26.56l-1-2.39-6.37-8.17Z" transform="translate(-28.45 -12.11)" opacity="0.1"/><path d="M281.53,301.52s5.61-1.52,7.8,4.09a57.66,57.66,0,0,0,3,7l2.19,13,4,20.53,2.95,91-1.34,16.16-7.32-13-1.33-85v-10.6a76.7,76.7,0,0,0-6.09-26.56l-1-2.39L278,307.61Z" transform="translate(-28.45 -12.11)" fill="#4c4c78"/><path d="M257.58,278s19.39,20.54,26.23,23c0,0-3.42,15.59-6.84,18.63,0,0-20.35-20.75-24-22.43l-3-7.39Z" transform="translate(-28.45 -12.11)" opacity="0.1"/><path d="M257.58,276.9s19.39,20.54,26.23,23c0,0-3.42,15.59-6.84,18.63,0,0-20.35-20.75-24-22.43l-3-7.39Z" transform="translate(-28.45 -12.11)" fill="#cbcdda"/><path d="M286.58,301.35l.2.53L293,318.54,304.1,350s.11.32.3.92c1.84,5.72,11.74,36.77,13.69,45.8l.14-1.52S308,352.38,308.34,349c.35-3.07-16.53-39-20-46.31-.55-.38-1-.7-1.28-.94Z" transform="translate(-28.45 -12.11)" opacity="0.1"/><path d="M287.71,301.33l6.42,17.21L305.24,350s.11.32.3.92c1.95,6.1,13.06,40.9,14,47.27,1,7,15,44.78,15,44.78l8,65.45c7.13-5.56-3-59-3-59s-11.41-79.28-14.11-105.65S308.1,309.6,308.1,309.6c-6.73-.34-12.79-3.3-16.52-5.59A35.28,35.28,0,0,1,287.71,301.33Z" transform="translate(-28.45 -12.11)" fill="#4c4c78"/><path d="M207.2,722.36c1.71,12.74,4,48.3,4,48.3,8,12,25.67,5.51,25.67,5.51s1.89-3.62,4.74-17.12,4.57-61.22,4.57-61.22l6.66-40.11c6.08-15.4,7.41-33.65,7.41-33.65,2.66-7,9.13-53.24,9.13-53.24s-2.67,41.45-1,63.31-8.74,51.34-8.74,51.34-5.9,27.57-2.85,36.12-7.61,43.92-7.61,43.92c.95,10.27,31.18,6.08,31.18,6.08s14.26-38.21,16.73-61.22a303.86,303.86,0,0,1,7.33-41.16l2.46-31.94,4.38-18.82,5.23-24.9c5.89-18.26,7.7-48.77,7.7-48.77l4.56-29.94c3-14.81-.87-32.17-3.47-41.29-1.12-3.93-2-6.34-2-6.34-2.62,1.86-17.62,4-25.38,5.81a44.16,44.16,0,0,1-13,1A288.83,288.83,0,0,1,227,463.44l-2-.6-.4,3.42-.94,8.16L222,488.89s-5.14,77.19-2.1,87.65-2.09,45.59-2.09,45.59c2.85,8.34-6.08,42.24-6.08,42.24S205.49,709.63,207.2,722.36Z" transform="translate(-28.45 -12.11)" opacity="0.1"/><path d="M207.2,721.22c1.71,12.74,4,48.3,4,48.3,8,12,25.67,5.51,25.67,5.51s1.89-3.62,4.74-17.12,4.57-61.22,4.57-61.22l6.66-40.11c6.08-15.4,7.41-33.65,7.41-33.65,2.66-7,9.13-53.24,9.13-53.24s-2.67,41.45-1,63.31-8.74,51.34-8.74,51.34-5.9,27.57-2.85,36.12-7.61,43.92-7.61,43.92c.95,10.27,31.18,6.08,31.18,6.08s14.26-38.21,16.73-61.22a303.86,303.86,0,0,1,7.33-41.16l2.46-31.94,4.38-18.82,5.23-24.9c5.89-18.26,7.7-48.77,7.7-48.77l4.56-29.95c3-14.8-.87-32.16-3.47-41.28-1.12-3.93-2-6.34-2-6.34-2.62,1.86-17.62,4-25.38,5.81a44.16,44.16,0,0,1-13,1A288.83,288.83,0,0,1,227,462.3l-2-.6-.4,3.42-.94,8.16L222,487.75s-5.14,77.19-2.1,87.65S217.84,621,217.84,621c2.85,8.34-6.08,42.24-6.08,42.24S205.49,708.49,207.2,721.22Z" transform="translate(-28.45 -12.11)" fill="#474463"/><path d="M260.25,253.73A36.66,36.66,0,0,0,265.12,272c5.72,1,11.81-.1,16.14-3.89,2.93-2.58,4.82-6.11,7.34-9.1a53.49,53.49,0,0,1,5.25-5.18,16.26,16.26,0,0,1,4.14-3,5.62,5.62,0,0,1,5-.06c2,1.13,2.72,3.64,3.86,5.65a7.54,7.54,0,0,0,4.21,3.67,4.46,4.46,0,0,0,5-1.71c1.24-2.14.33-5.48,2.42-6.78a12.6,12.6,0,0,1,1.7-.69c2.69-1.22,3-4.83,3.22-7.77.12-1.45.43-3.09,1.65-3.87s2.81-.39,4.26-.35a6,6,0,0,0,1.28-.11,36.74,36.74,0,0,0-70.33,14.89Z" transform="translate(-28.45 -12.11)" opacity="0.1"/><path d="M332.79,206a12.2,12.2,0,0,1,4.95,8.88,4.67,4.67,0,0,1-.13,1.75,13,13,0,0,1-.89,1.74c-1.88,3.72,1,8.14.62,12.3a8.18,8.18,0,0,1-8,7.18c-1.45,0-3-.43-4.26.35s-1.53,2.42-1.65,3.87c-.23,2.95-.53,6.56-3.22,7.77a14.11,14.11,0,0,0-1.7.7c-2.09,1.3-1.17,4.65-2.42,6.77a4.45,4.45,0,0,1-5,1.72,7.58,7.58,0,0,1-4.21-3.67c-1.15-2-1.84-4.52-3.86-5.65a5.65,5.65,0,0,0-5,.05,16.3,16.3,0,0,0-4.14,3,54.63,54.63,0,0,0-5.25,5.18c-2.52,3-4.41,6.52-7.35,9.1-5.52,4.85-14,5.33-20.8,2.57a6.36,6.36,0,0,1-2.58-1.65A6.46,6.46,0,0,1,256.7,265a35.54,35.54,0,0,1-.59-10.24,51.68,51.68,0,0,0,.6-8c-.19-2.88-1.22-5.66-1.36-8.54-.25-5.09,2.28-9.93,5.4-14,1.86-2.4,3.95-4.63,5.56-7.2,1.4-2.23,2.42-4.7,3.93-6.86,3.52-5,9.46-8,15.52-9.09s12.29-.4,18.37.6c5.29.88,10.61,2.17,16,2.48C324.76,204.43,328.54,202.86,332.79,206Z" transform="translate(-28.45 -12.11)" fill="#472727"/><path d="M287.71,301.33l6.42,17.21L305.24,350s.11.32.3.92l0,0-14-46.91A35.28,35.28,0,0,1,287.71,301.33Z" transform="translate(-28.45 -12.11)" opacity="0.1"/><path d="M218.32,498.21s-.67,20.81,4.94,26l14.92-28.14L225.64,490Z" transform="translate(-28.45 -12.11)" fill="#ffb9b9"/><path d="M183.48,505.81c6.85,8,36.64,9.41,36.64,9.41l29.53-46.48s7-4.85,20.09-26.67,12.69-38.64,12-51.19-9-48.63-9-48.63c-1.27-8.12-19.66-59-19.66-59-1.93,3.86-5.52,7.07-9.73,9.73C232,300.09,216.13,303,216.13,303c-25.53,3.71-20.82,30.09-20.82,30.09S195,435.66,196.6,447.92,183.48,505.81,183.48,505.81Z" transform="translate(-28.45 -12.11)" opacity="0.1"/><polygon points="189.49 490.09 190.34 497.69 200.71 493.89 208.31 482.39 202.61 477.92 189.49 490.09" fill="#cbcdda"/><path d="M218.3,472.73l5.42.55L276,478.5a84.69,84.69,0,0,0,25.12-1.23l24.23-4.85c-1.12-3.93-2-6.34-2-6.34-2.62,1.86-17.62,4-25.38,5.81a44.16,44.16,0,0,1-13,1A288.83,288.83,0,0,1,227,462.3l-2.36,2.82Z" transform="translate(-28.45 -12.11)" opacity="0.1"/><g opacity="0.1"><path d="M322.75,466.37a5.45,5.45,0,0,0,.51-.29l.05.14Z" transform="translate(-28.45 -12.11)"/><path d="M211.71,663.23s8.94-33.9,6.08-42.24c0,0,5.14-35.13,2.09-45.59s2.1-87.65,2.1-87.65l1.69-14.47,1-8.16.4-3.42s.68.22,2,.6l1.21.36L228,464l-.95,8.16-1.69,14.47s-5.13,77.18-2.1,87.65-2.08,45.59-2.08,45.59c2.85,8.34-6.08,42.24-6.08,42.24s-6.28,45.26-4.57,58,4,48.29,4,48.29c5.83,8.76,16.84,7.66,22.34,6.43-.06.15-.1.23-.1.23s-17.68,6.46-25.67-5.51c0,0-2.28-35.56-4-48.3S211.71,663.23,211.71,663.23Z" transform="translate(-28.45 -12.11)"/><path d="M256.77,720.46c-3-8.55,2.86-36.12,2.86-36.12s10.44-29.47,8.73-51.34c-.71-9.11-.66-21.61-.36-33.1,2.43-14.8,4.74-31.35,4.74-31.35s-2.67,41.44-.95,63.31-8.74,51.34-8.74,51.34-5.9,27.57-2.85,36.12-7.61,43.92-7.61,43.92c.76,8.23,20.3,7.18,28,6.43l-.29.79s-30.23,4.19-31.17-6.08C249.17,764.38,259.81,729,256.77,720.46Z" transform="translate(-28.45 -12.11)"/></g><path d="M182.34,505.81c6.85,8,36.64,9.41,36.64,9.41l29.52-46.48s7-4.85,20.1-26.67,12.69-38.64,12-51.19-9-48.63-9-48.63c-1.27-8.12-19.66-59-19.66-59-1.93,3.86-5.52,7.07-9.73,9.73C230.85,300.09,215,303,215,303c-25.53,3.71-20.82,30.09-20.82,30.09s-.28,102.53,1.29,114.79S182.34,505.81,182.34,505.81Z" transform="translate(-28.45 -12.11)" fill="#4c4c78"/><path d="M246.5,308.46l6.42,2.42-4.28,3,31.92,77c-.74-12.55-9-48.63-9-48.63-1.27-8.12-19.66-59-19.66-59-1.93,3.86-5.52,7.07-9.73,9.73Z" transform="translate(-28.45 -12.11)" opacity="0.1"/><polygon points="210.36 433.52 177.7 425.11 173.42 438.37 206.79 446.5 210.36 433.52" opacity="0.1"/><path d="M220.12,313.88s14.83,14,9.27,45.06-14.54,64.88-14.54,64.88L239,490.7s-16.83,17.54-25.1,19.67l-24.1-53.47s-11.26-18.54-8.27-47.62,13.83-76.15,13.83-76.15S195.31,304.47,220.12,313.88Z" transform="translate(-28.45 -12.11)" opacity="0.1"/><path d="M219,313.88s14.83,14,9.27,45.06-14.54,64.88-14.54,64.88L237.8,490.7S221,508.24,212.71,510.37l-24.1-53.47s-11.26-18.54-8.27-47.62,13.83-76.15,13.83-76.15S194.17,304.47,219,313.88Z" transform="translate(-28.45 -12.11)" fill="#4c4c78"/><path d="M237,492.12s-18.82,26-17.39,33,6.94,3.23,6.94,3.23L234,518.17l6.66-19S240,491,237,492.12Z" transform="translate(-28.45 -12.11)" opacity="0.1"/><path d="M239.33,492.12s-18.83,26-17.4,33,6.94,3.23,6.94,3.23l7.41-10.17,6.66-19S242.29,491,239.33,492.12Z" transform="translate(-28.45 -12.11)" opacity="0.1"/><path d="M238.18,492.12s-18.82,26-17.39,33,6.94,3.23,6.94,3.23l7.41-10.17,6.66-19S241.15,491,238.18,492.12Z" transform="translate(-28.45 -12.11)" fill="#474463"/></svg>
         <h1>👋 Almost ready!</h1>
         <p>You\'re just two steps away from having product and collection detail pages: <br><br>1. Turn on <a href="/wp-admin/admin.php?page=wps-settings&activesubnav=wps-admin-section-syncing">Create Product Detail Pages</a> from within the Syncing settings<br>
         2. Use the <a href="/wp-admin/admin.php?page=wps-tools">Sync Product & Collection Detail Pages</a> button under the plugin Tools</p>
         </div>';
        }
    }

    public function user_allowed_tracking()
    {
        return $this->plugin_settings['general']['allow_tracking'];
    }

    public function wpshopify_usage_tracking_analytics_head()
    {
        if (
            is_admin() &&
            $this->is_plugin_specific_pages() &&
            $this->user_allowed_tracking()
        ) {
            echo "<script async src='https://www.googletagmanager.com/gtag/js?id=UA-101619037-3'></script><script>window.dataLayer = window.dataLayer || [];function gtag(){dataLayer.push(arguments);}gtag('js', new Date());gtag('config', 'UA-101619037-3');</script><script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start': new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','GTM-NKB2G3Z');</script>";
        }
    }

    public function wpshopify_usage_tracking_analytics_footer()
    {
        if (
            is_admin() &&
            $this->is_plugin_specific_pages() &&
            $this->user_allowed_tracking()
        ) {
            echo "<noscript><iframe src='https://www.googletagmanager.com/ns.html?id=GTM-NKB2G3Z' height='0' width='0' style='display:none;visibility:hidden'></iframe></noscript>";
        }
    }

    /*
      
      Blocks JS

      */
    public function wpshopify_blocks_assets()
    {
        if (is_admin()) {
           
            $runtime_url = WP_SHOPIFY_PLUGIN_URL . 'dist/runtime.73ad37.min.js';
            $vendors_admin_url =
                WP_SHOPIFY_PLUGIN_URL . 'dist/vendors-admin.73ad37.min.js';
            $main_url = WP_SHOPIFY_PLUGIN_URL . 'dist/blocks.73ad37.min.js';

            wp_enqueue_script('wpshopify-runtime', $runtime_url, []);
            wp_enqueue_script(
                'wpshopify-vendors-admin',
                $vendors_admin_url,
                []
            );
            wp_enqueue_script('wpshopify-blocks', $main_url, [
                'wp-blocks',
                'wp-element',
                'wp-editor',
                'wp-components',
                'wp-i18n',
                'wpshopify-runtime',
                'wpshopify-vendors-admin',
            ]);

            wp_set_script_translations(
                'wpshopify-blocks',
                'wpshopify',
                WP_SHOPIFY_PLUGIN_DIR . WP_SHOPIFY_LANGUAGES_FOLDER
            );

            $this->Data_Bridge->add_settings_script('wpshopify-blocks', true);

            wp_enqueue_style(
                'wpshopify' . '-styles-frontend-all',
                WP_SHOPIFY_PLUGIN_URL . 'dist/public.min.css',
                [],
                filemtime(WP_SHOPIFY_PLUGIN_DIR_PATH . 'dist/public.min.css'),
                'all'
            );
        }
    }

    public function is_wizard_completed()
    {
        if (!isset($this->plugin_settings['general']['wizard_completed'])) {
            return false;
        }

        return $this->plugin_settings['general']['wizard_completed'];
    }

    /*
      
      Blocks JS

      */
    public function wpshopify_wizard_assets()
    {
        if ($this->is_wizard_page()) {
            $runtime_url = WP_SHOPIFY_PLUGIN_URL . 'dist/runtime.73ad37.min.js';
            $vendors_admin_url =
                WP_SHOPIFY_PLUGIN_URL . 'dist/vendors-admin.73ad37.min.js';
            $main_url = WP_SHOPIFY_PLUGIN_URL . 'dist/wizard.73ad37.min.js';

            wp_enqueue_script('wpshopify-runtime', $runtime_url, []);
            wp_enqueue_script(
                'wpshopify-vendors-admin',
                $vendors_admin_url,
                []
            );
            wp_enqueue_script('wpshopify-wizard', $main_url, [
                'wp-blocks',
                'wp-element',
                'wp-editor',
                'wp-components',
                'wp-i18n',
                'wpshopify-runtime',
                'wpshopify-vendors-admin',
            ]);

            wp_set_script_translations(
                'wpshopify-wizard',
                'wpshopify',
                WP_SHOPIFY_PLUGIN_DIR . WP_SHOPIFY_LANGUAGES_FOLDER
            );

            $this->Data_Bridge->add_settings_script('wpshopify-wizard', true);
        }
    }

    public function wpshopify_block_categories($categories, $post)
    {
        return array_merge($categories, [
            [
                'slug' => 'wpshopify-products',
                'title' => __('WP Shopify Products', 'wpshopify'),
            ],
        ]);
    }

    public function wpshopify_wizard_redirect()
    {

         $is_plugin_specific_pages = $this->is_plugin_specific_pages();
         $is_wizard_page = $this->is_wizard_page();
         $is_wizard_completed = $this->is_wizard_completed();
        
         if (!$is_plugin_specific_pages || empty($this->plugin_settings)) {
            return;
         }

         $has_finished_wizard_param = isset($_GET['wpshopify-finished-wizard']);

        if ($has_finished_wizard_param) {
           
            $updated_col = $this->DB_Settings_General->update_column_single(
                ['wizard_completed' => 1],
                ['id' => 1]
            );

            wp_safe_redirect(
                esc_url(admin_url('/admin.php?page=wps-settings'))
            );
            exit();
        }

        if (
            $is_plugin_specific_pages &&
            !$is_wizard_page &&
            !$is_wizard_completed
        ) {
            wp_safe_redirect(
                esc_url(admin_url('/admin.php?page=wpshopify-wizard'))
            );
            exit();
        }

        if ($is_wizard_page && $is_wizard_completed) {
            wp_safe_redirect(
                esc_url(admin_url('/admin.php?page=wps-settings'))
            );
            exit();
        }
    }

    

    public function create_edit_link_href($domain, $id, $type) {
      return 'https://' . $domain . '/admin/' . $type . '/' . $id;
    }

    public function create_edit_link_href_general($domain, $type) {
      return 'https://' . $domain . '/admin/' . $type;
    }

    public function create_edit_link_html($href) {
      return '<a href="' . $href . '" aria-label="Edit in Shopify" target="_blank">Edit in Shopify</a>';
    }

    public function make_products_link($post_id) {

      $domain = $this->plugin_settings['connection']['domain'];
      $product_id_result = $this->DB_Products->get_product_ids_from_post_ids([$post_id]);

      if (empty($product_id_result)) {
         return $this->create_edit_link_href_general($domain, 'products');
      }

      $product_id = $product_id_result[0];
      return $this->create_edit_link_href($domain, $product_id, 'products');
    }

    public function make_collections_link($post_id) {
      
      $domain = $this->plugin_settings['connection']['domain'];
      $id_result = $this->DB_Collections->get_collection_by_post_id($post_id);

      if (empty($id_result)) {
         return $this->create_edit_link_href_general($domain, 'collections');
      }

      $product_id = $id_result[0]->collection_id;

      return $this->create_edit_link_href($domain, $product_id, 'collections');

    }

   public function custom_action_links($actions, $post) {

      $pt = $post->post_type;

      if ($pt !== "wps_products" && $pt !== "wps_collections") {
         return $actions;
      }

      if ($pt === "wps_products") {
         $link_href = $this->make_products_link($post->ID);
      }

      if ($pt === "wps_collections") {
         $link_href = $this->make_collections_link($post->ID);
      }

      $actions['edit_in_shopify'] = $this->create_edit_link_html($link_href);


      if (add_filter('wpshopify_remove_quick_edit', true)) {
         unset($actions['inline hide-if-no-js']);
      }

      return $actions;
   }

   public function add_edit_in_shopify_button() {
       $screen = get_current_screen();

       if ( $screen->post_type !== 'wps_products' && $screen->post_type !== 'wps_collections' ) {
         return;
       }

       global $post;

       if ($screen->base !== 'post') {
          return;
       }

      if ($post->post_type === "wps_products") {
         $link_href = $this->make_products_link($post->ID);
      }

      if ($post->post_type === "wps_collections") {
         $link_href = $this->make_collections_link($post->ID);
      }

       ?>
      <div class="wrap">
         <h1 class="wp-heading-inline show" style="display:inline-block;">Edit Product</h1>
         <a href="<?= $link_href; ?>" target="_blank" class="page-title-action show">Edit in Shopify</a>
      </div>

      <style scoped>
      .wp-heading-inline:not(.show),
      .page-title-action:not(.show) { display:none !important;}
      </style>
      <?php
 }
 

    public function init()
    {

      if (is_admin()) {

         add_action('admin_menu', [$this, 'add_dashboard_menus']);
         add_action('admin_enqueue_scripts', [$this, 'admin_styles']);
         add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);
         add_filter('plugin_action_links_' . WP_SHOPIFY_BASENAME, [
               $this,
               'add_action_links',
         ]);
         add_action('admin_init', [$this, 'on_options_update']);

         add_filter('admin_body_class', [$this, 'wps_admin_body_class']);
         add_action('in_admin_header', [$this, 'wps_posts_notice']);

         add_action('admin_head', [
               $this,
               'wpshopify_usage_tracking_analytics_head',
         ]);
         add_action('admin_footer', [
               $this,
               'wpshopify_usage_tracking_analytics_footer',
         ]);

         add_action('admin_enqueue_scripts', [$this, 'wpshopify_wizard_assets']);


         // add_action('current_screen', [$this, 'wpshopify_wizard_redirect']);

         add_filter('post_row_actions', [$this, 'custom_action_links'], 10, 2);
         add_action('admin_notices',[$this, 'add_edit_in_shopify_button']);

      }
   }
}
