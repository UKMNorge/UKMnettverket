<?php
/* 
Plugin Name: UKM-Nettverket
Plugin URI: http://www.ukm-norge.no
Description: Håndterer nettverket (geografi, administratorer, brukere og arrangementer)
Author: UKM Norge / M Mandal
Version: 1.0
Author URI: http://mariusmandal.no
*/

use UKMNorge\Geografi\Kommune;
use UKMNorge\Nettverk\Administrator;
use UKMNorge\Nettverk\Omrade;
use UKMNorge\Wordpress\Modul;

require_once('UKM/Autoloader.php');

class UKMnettverket extends Modul
{
    public static $action = 'dashboard';
    public static $path_plugin = null;
    public static $current_admin;

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

    /**
     * Er CurrentUser administrator for ett eller flere fylker?
     *
     * @return Bool
     */
    public static function erCurrentAdminFylkeAdmin()
    {
        return static::getCurrentAdmin()->erAdmin('fylke');
    }

    /**
     * Er CurrentUser administrator for en eller flere kommuner?
     *
     * @return Bool
     */
    public static function erCurrentAdminKommuneAdmin()
    {
        return static::getCurrentAdmin()->erAdmin('kommune');
    }

    /**
     * Hent admin-objekt for current user
     *
     * @return Administrator
     */
    public static function getCurrentAdmin()
    {
        if (is_null(static::$current_admin)) {
            static::$current_admin = new Administrator(get_current_user_id());
        }
        return static::$current_admin;
    }

    /**
     * Registrer menyen
     *
     * @return void
     */
    public static function meny()
    {
        $scripts = [];

        // Hvis vedkommende er admin for ett eller flere fylker
        if (static::erCurrentAdminFylkeAdmin()) {
            $meny = (static::getCurrentAdmin()->getAntallOmrader('fylke') == 1) ?
                static::getCurrentAdmin()->getOmrade('fylke')->getNavn() : 'Mine fylker';
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
        if (static::erCurrentAdminFylkeAdmin() || static::erCurrentAdminKommuneAdmin()) {
            if (isset($_GET['omrade']) && isset($_GET['type']) && $_GET['type'] == 'kommune' && static::getCurrentAdmin()->getAntallOmrader('kommune') == 1) {
                if (static::getCurrentAdmin()->getOmrade('kommune')->getId() == 'kommune_' . $_GET['omrade']) {
                    $meny = static::getCurrentAdmin()->getOmrade('kommune')->getNavn();
                } else {
                    $meny = 'Mine kommuner';
                }
            } elseif (static::getCurrentAdmin()->getAntallOmrader('kommune') == 1) {
                $meny = static::getCurrentAdmin()->getOmrade('kommune')->getNavn();
            } else {
                $meny = 'Mine kommuner';
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

        $scripts[] = add_menu_page(
            'Min side',
            'Min side',
            'subscriber',
            'UKMnettverket_tilbake',
            ['UKMnettverket', 'tilbakeFunc'],
            'dashicons-arrow-left-alt', #//ico.ukm.no/system-16.png',
            25
        );

        $scripts[] = add_menu_page(
            'nettside',
            'Nettside',
            'subscriber',
            'UKMnettverket_nettside',
            ['UKMnettverket', 'renderNettside'],
            'dashicons-desktop', #//ico.ukm.no/system-16.png',
            25
        );
        
        // Fjerner alle meny items bortsett fra ... for kommmune og fylke
        if(isset($_GET['page']) && ($_GET['page'] == 'UKMnettverket_kommune' || $_GET['page'] == 'UKMnettverket_fylke')) {
            global $menu;
            global $kommuneEllerFylke;
            $kommuneEllerFylke = null;

            // Endre tittel på admin bar
            add_action('wp_before_admin_bar_render', ['UKMnettverket', '_changeAdminBarInfo'], 10005);
            
            // URL har ukmNettverket_[fylke eller kommune] og har single fylke eller kommune
            if($_GET['page'] == 'UKMnettverket_fylke' && static::getCurrentAdmin()->getAntallOmrader('fylke') == 1) {
                $kommuneEllerFylke = static::getCurrentAdmin()->getOmrade('fylke')->getFylke();
            }
            elseif($_GET['page'] == 'UKMnettverket_kommune' && static::getCurrentAdmin()->getAntallOmrader('kommune') == 1) {
                $kommuneEllerFylke = static::getCurrentAdmin()->getOmrade('kommune')->getKommune();
            }
            else if(isset($_GET['omrade']) && isset($_GET['type'])) {
                $kommuneEllerFylke = $_GET['type'] == 'kommune' ? Omrade::getByKommune($_GET['omrade'])->getKommune() : Omrade::getByFylke($_GET['omrade'])->getFylke();
            }

            if($kommuneEllerFylke) {
                foreach ($menu as $key => $item) {
                    if($item[0] != 'Min side' && $item[0] != 'Nettside' ) {
                        unset($menu[$key]);
                    }
                    elseif($item[0] == 'Nettside') {
                        $menu[$key][0] = $kommuneEllerFylke instanceof Kommune ? 'Kommune nettside' : 'Fylke nettside';
                        $menu[$key][2] = $kommuneEllerFylke->getPath() . 'wp-admin/edit.php?page=UKMnettside';
                    }
                }
            }
        }
        else {
            global $menu;
            foreach ($menu as $key => $item) {
                if($item[0] == 'Min side' || $item[0] == 'Nettside' ) {
                    unset($menu[$key]);
                }
            }
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
        wp_enqueue_style('UKMwp_dash_css');
        wp_enqueue_script('WPbootstrap3_js');
        wp_enqueue_style('WPbootstrap3_css');
    	wp_enqueue_style('UKMArrSysStyle', 9999);


        wp_enqueue_style(
            'UKMnettverket_arrangement_css',
            static::getPluginUrl() . 'UKMnettverket.css'
        );
        wp_enqueue_script(
            'UKMnettverket_arrangement',
            static::getPluginUrl() . 'js/arrangement.js?v=2021-01-13'
        );
        wp_enqueue_script(
            'UKMnettverket_administratorer',
            static::getPluginUrl() . 'js/administratorer.js'
        );
        wp_enqueue_script(
            'UKMnettverket_bildeadmin',
            static::getPluginUrl() . 'js/bilde-admin.js'
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

    public static function tilbakeFunc() {
        echo '<script>';
        echo 'window.location.href = "/wp-admin/user/";';
        echo '</script>';
        exit;
    }

    public static function _changeAdminBarInfo() {
        global $kommuneEllerFylke; // Make the $kommuneEllerFylke object accessible within this function.
        $navn = $kommuneEllerFylke ? $kommuneEllerFylke->getNavn() . ' - admin side' : null;
        changeAdminBarInfo($navn);
    }
}

UKMnettverket::init(__DIR__);
UKMnettverket::hook();
