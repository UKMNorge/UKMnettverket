<?php
/* 
Plugin Name: UKM-Nettverket
Plugin URI: http://www.ukm-norge.no
Description: Håndterer nettverket (geografi, administratorer, brukere og arrangementer)
Author: UKM Norge / M Mandal
Version: 1.0
Author URI: http://mariusmandal.no
*/

use UKMNorge\Nettverk\Administrator;
use UKMNorge\Wordpress\Modul;

require_once('UKM/wp_modul.class.php');

class UKMnettverket extends Modul
{
    public static $action = 'dashboard';
    public static $path_plugin = null;

    /**
     * Register hooks
     */
    public static function hook()
    {
        add_action(
            'wp_ajax_UKMnettverket_ajax',
            ['UKMnettverket', 'ajax']
        );

        add_action(
            'network_admin_menu',
            ['UKMnettverket', 'nettverkMeny'],
            200
        );
        
        add_action(
            'user_admin_menu',
            ['UKMnettverket', 'meny'],
            200
        );

    }

    public static function meny() {
        require_once('UKM/Nettverk/Administrator.class.php');
        $current_admin = new Administrator( get_current_user_id() );
        
        $scripts = [];
        
        // Hvis vedkommende er admin for ett eller flere fylker
        if( $current_admin->erAdmin('fylke') ) {
            $meny = ($current_admin->getAntallOmrader('fylke') == 1 ) ? 
                $current_admin->getOmrade('fylke')->getNavn() : 
                'Dine fylker';
            $scripts = [
                add_menu_page(
                    'GEO',
                    $meny,
                    'subscriber',
                    'UKMnettverket_fylke',
                    ['UKMnettverket', 'renderFylke'],
                    'dashicons-location-alt', #//ico.ukm.no/system-16.png',
                    22
                )
            ];
        }

        // Hvis vedkommende er admin for en eller flere kommuner
        if( $current_admin->erAdmin('kommune') ) {
            $meny = ($current_admin->getAntallOmrader('kommune') == 1 ) ? 
                $current_admin->getOmrade('kommune')->getNavn() : 
                'Dine kommuner';
            $scripts = [
                add_menu_page(
                    'GEO',
                    $meny,
                    'subscriber',
                    'UKMnettverket_kommune',
                    ['UKMnettverket', 'renderKommune'],
                    'dashicons-location', #//ico.ukm.no/system-16.png',
                    23
                )
            ];
        }

        foreach ($scripts as $page) {    
            add_action(
                'admin_print_styles-' . $page,
                ['UKMnettverket', 'administratorer_scripts_and_styles'],
                11000
            );
            add_action(
                'admin_print_styles-' . $page,
                ['UKMnettverket', 'arrangement_scripts_and_styles'],
                11000
            );
            add_action(
                'admin_print_styles-' . $page,
                ['UKMnettverket', 'scripts_and_styles']
            );
        }
    }

    /**
     * Add menu
     */
    public static function nettverkMeny()
    {
        /**
         * Menyvalget NETTVERKET
         */
        $meny = add_submenu_page(
            'index.php',
            'Administratorer',
            'Administratorer',
            'superadmin',
            'UKMnettverket_admins',
            ['UKMnettverket', 'renderAdmin']
        );
        add_action(
            'admin_print_styles-' . $meny,
            ['UKMnettverket', 'administratorer_scripts_and_styles'],
            10000
        );
        $scripts[] = $meny;

        foreach ($scripts as $page) {
            add_action(
                'admin_print_styles-' . $page,
                ['UKMnettverket', 'scripts_and_styles']
            );
        }
    }

    /**
     * Scripts og stylesheets som skal være med i alle
     * system tools-sider
     *
     * @return void
     */
    public static function scripts_and_styles()
    {
        wp_enqueue_style('UKMwp_dash_css');
        wp_enqueue_script('WPbootstrap3_js');
        wp_enqueue_style('WPbootstrap3_css');

        wp_enqueue_script(
            'UKMnettverket',
            plugin_dir_url(__FILE__) . 'js/UKMnettverket.js'
        );
    }

    /**
     * Scripts og stylesheets som skal være med for administrator-admin
     *
     * @return void
     */
    public static function administratorer_scripts_and_styles()
    {
        wp_enqueue_script(
            'UKMnettverket_administratorer',
            plugin_dir_url(__FILE__) . 'js/administratorer.js'
        );
    }

    /**
     * Scripts og stylesheets som skal være med for arrangement-admin
     *
     * @return void
     */
    public static function arrangement_scripts_and_styles()
    {
        wp_enqueue_script(
            'UKMnettverket_arrangement',
            plugin_dir_url(__FILE__) . 'js/arrangement.js'
        );
    }

    public static function renderFylke() {
        self::renderAdmin('fylke');
    }
}

UKMnettverket::init(__DIR__);
UKMnettverket::hook();