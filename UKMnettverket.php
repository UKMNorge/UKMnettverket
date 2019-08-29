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
            2000
        );

        add_action(
            'user_admin_menu',
            ['UKMnettverket', 'meny'],
            20000
        );
    }

    public static function meny()
    {
        require_once('UKM/Nettverk/Administrator.class.php');
        $current_admin = new Administrator(get_current_user_id());

        $scripts = [];

        // Hvis vedkommende er admin for ett eller flere fylker
        if ($current_admin->erAdmin('fylke')) {
            $meny = ($current_admin->getAntallOmrader('fylke') == 1) ?
                $current_admin->getOmrade('fylke')->getNavn() : 'Dine fylker';
            $scripts[] = add_menu_page(
                'GEO',
                $meny,
                'subscriber',
                'UKMnettverket_fylke',
                ['UKMnettverket', 'renderFylke'],
                'dashicons-location-alt', #//ico.ukm.no/system-16.png',
                22
            );
        }

        // Hvis vedkommende er admin for en eller flere kommuner
        if ($current_admin->erAdmin('fylke') || $current_admin->erAdmin('kommune')) {
            if (isset($_GET['omrade']) && isset($_GET['type']) && $_GET['type'] == 'kommune' && $current_admin->getAntallOmrader('kommune') == 1) {
                if( $current_admin->getOmrade('kommune')->getId() == 'kommune_'. $_GET['omrade'] ) {
                    $meny = $current_admin->getOmrade('kommune')->getNavn();
                } else {
                    $meny = 'Dine kommuner';
                }
             } elseif( $current_admin->getAntallOmrader('kommune') == 1 ) {
                $meny = $current_admin->getOmrade('kommune')->getNavn();
             } else {
                 $meny = 'Dine kommuner';
             }
            
            $scripts[] = add_menu_page(
                'GEO',
                $meny,
                'subscriber',
                'UKMnettverket_kommune',
                ['UKMnettverket', 'renderKommune'],
                'dashicons-location', #//ico.ukm.no/system-16.png',
                23
            );
        }

        foreach ($scripts as $page) {
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
        $scripts[] = add_submenu_page(
            'index.php',
            'Fylker',
            'Fylker',
            'superadmin',
            'UKMnettverket_fylker',
            ['UKMnettverket', 'renderAdmin']
        );

        $scripts[] = add_submenu_page(
            'index.php',
            'Kommuner',
            'Kommuner',
            'superadmin',
            'UKMnettverket_kommune',
            ['UKMnettverket', 'renderKommune']
        );

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
        echo 'helloo there?';
        wp_enqueue_style('UKMwp_dash_css');
        wp_enqueue_script('WPbootstrap3_js');
        wp_enqueue_style('WPbootstrap3_css');

        wp_enqueue_script(
            'UKMnettverket',
            plugin_dir_url(__FILE__) . 'js/UKMnettverket.js'
        );
        wp_enqueue_script(
            'UKMnettverket_arrangement',
            plugin_dir_url(__FILE__) . 'js/arrangement.js'
        );
        wp_enqueue_script(
            'UKMnettverket_administratorer',
            plugin_dir_url(__FILE__) . 'js/administratorer.js'
        );
    }

    public static function renderFylke()
    {
        self::renderAdmin('fylke');
    }


    public static function renderKommune()
    {
        self::renderAdmin('kommune');
    }
}

UKMnettverket::init(__DIR__);
UKMnettverket::hook();
