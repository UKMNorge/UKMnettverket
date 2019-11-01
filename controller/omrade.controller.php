<?php

use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Arrangement\Write;
use UKMNorge\Nettverk\Omrade;
use UKMNorge\Wordpress\Blog;

$omrade = UKMnettverket::getViewData()['omrade'];

if($omrade->getType() == 'fylke' ) {
    $fylke = $omrade->getFylke();
    $path = $fylke->getPath();

    UKMnettverket::addViewData([
        'blog_eksisterer' => Blog::eksisterer($path),
    ]);
    
}
elseif ($omrade->getType() == 'kommune') {
    $FIX = isset($_GET['arr']) ? 'arrangement' : 'kommune';
    $kommune = $omrade->getKommune();
    $path = $kommune->getPath();


    try {
        $slettet = Blog::getDetails(
            Blog::getIdByPath($path),
            'deleted'
        );
    } catch (Exception $e) {
        $slettet = true;
    }

    UKMnettverket::addViewData([
        'blog_eksisterer' => Blog::eksisterer($path),
        'blog_slettet' => $slettet
    ]);


    // Hvis vi jobber med arrangement, sett path fra arrangementet
    if ($FIX == 'arrangement') {
        $arrangement = new Arrangement((int) $_GET['arr']);
        $path = $arrangement->getPath();
    }

    if (isset($_GET['fix'])) {
        try {
            switch ($_GET['fix']) {
                    // OPPRETT BLOGG
                case 'opprett_nettsted':
                    if (Blog::eksisterer($path)) {
                        throw new Exception('Nettstedet eksisterer allerede. Hvis det ikke fungerer som det skal, må du prøve noe annet.');
                    }
                    // Opprett kommune-blogg
                    if ($FIX == 'kommune') {
                        Blog::opprettForKommune($kommune);
                        UKMnettverket::getFlash()->success('Nettstedet er nå opprettet, og alle kommune-innstillinger er satt.');
                        UKMnettverket::addViewData('blog_eksisterer',true);
                    }
                    // Opprett arrangement-blogg
                    elseif (isset($_POST['path'])) {
                        if (Blog::eksisterer($path)) {
                            Blog::flytt(
                                Blog::getIdByPath($path),
                                $_POST['path']
                            );
                            UKMnettverket::getFlash()->success('Nettstedet er nå flyttet, og arrangement-innstillinger er satt.');
                            UKMnettverket::addViewData('blog_eksisterer',true);
                        } else {
                            Blog::opprettForArrangement($arrangement, $_POST['path']);
                            UKMnettverket::getFlash()->success('Nettstedet er nå opprettet, og arrangement-innstillinger er satt.');
                            UKMnettverket::addViewData('blog_eksisterer',true);
                        }
                        $arrangement->setPath($_POST['path']);
                        Write::save($arrangement);
                    }
                    // Velg arrangement-path
                    else {
                        UKMnettverket::addViewData('arrangement', $arrangement);
                        UKMnettverket::addViewData('path', Blog::sanitizePath($arrangement->getPath()));
                        UKMnettverket::setAction('arrangement/path_velg');
                        UKMnettverket::includeActionController();
                    }
                    break;

                    // OPPDATER PATH FOR ARRANGEMENTET
                case 'oppdater_path':
                    if (isset($_POST['path'])) {
                        if (Blog::eksisterer($path)) {
                            Blog::flytt(
                                Blog::getIdByPath($path),
                                $_POST['path']
                            );
                            UKMnettverket::getFlash()->success('Nettstedet er flyttet, og arrangement-innstillinger er satt.');
                        } else {
                            Blog::opprettForArrangement($arrangement, $_POST['path']);
                            UKMnettverket::getFlash()->success('Nettstedet er nå opprettet, og arrangement-innstillinger er satt.');
                        }
                        $arrangement->setPath($_POST['path']);
                        Write::save($arrangement);
                    }
                    // Velg arrangement-path
                    else {
                        UKMnettverket::addViewData('arrangement', $arrangement);
                        UKMnettverket::addViewData('path', Blog::sanitizePath($arrangement->getPath()));
                        UKMnettverket::setAction('arrangement/path_velg');
                        UKMnettverket::includeActionController();
                    }
                    break;

                    // OPPDATER KOMMUNE-INNSTILLINGER
                case 'oppdater_fra_kommune':
                    if (!Blog::eksisterer($path)) {
                        throw new Exception('Nettstedet eksisterer ikke, og må opprettes først.');
                    }
                    Blog::oppdaterFraKommune(
                        Blog::getIdByPath($path),
                        $kommune
                    );
                    UKMnettverket::getFlash()->success('Nettstedet er nå oppdatert med alle kommune-innstillinger.');
                    break;

                    // FJERN ARRANGEMENT-INNSTILLINGER
                case 'fjern_arrangement':
                    if (!Blog::eksisterer($path)) {
                        throw Exception('Kunne ikke fjerne arrangement, da nettstedet må opprettes først.');
                    }
                    $blog_id = Blog::getIdByPath($path);
                    Blog::fjernArrangementData($blog_id);
                    UKMnettverket::getFlash()->success('All arrangement-info er nå fjernet fra nettstedet, og det fungerer som en ren kommuneside');
                    break;

                    // OPPDATER FRA ARRANGEMENT-INNSTILLINGER
                case 'oppdater_arrangement':
                    if (!Blog::eksisterer($path)) {
                        throw Exception('Kunne ikke fjerne arrangement, da nettstedet må opprettes først.');
                    }

                    if ($FIX == 'kommune') {
                        $blog_id = Blog::getIdByPath($path);
                        $arrangement = new Arrangement(
                            $omrade->getArrangementer(get_site_option('season'))->getFirst()->getId()
                        );
                    } else {
                        throw new Exception('BEKLAGER, ikke implementert enda');
                    }
                    Blog::oppdaterFraArrangement($blog_id, $arrangement);
                    UKMnettverket::getFlash()->success('Nettstedet er nå oppdatert med arrangement-info, og viser arrangementet ' . $arrangement->getNavn());
                    break;

                    // AKTIVER NETTSTED
                case 'aktiver_nettsted':
                    if (!Blog::eksisterer($path)) {
                        throw Exception('Kunne ikke aktivere nettsted, da det ikke finnes. Prøv opprett.');
                    }
                    Blog::setDetails(
                        Blog::getIdByPath($path),
                        [
                            'deleted' => false
                        ]
                    );
                    // Hvis fix==arrangement, så settes variablen i bunn av scriptet
                    if( $FIX == 'kommune') {
                        UKMnettverket::addViewData('blog_slettet', false);
                    }
                    UKMnettverket::getFlash()->success('Nettstedet er nå aktivert');
                    break;

                case 'leggtil_administratorer':
                    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                        $give = [];
                        $take = [];
                        foreach( $_POST as $key => $setting ) {
                            if( strpos($key, 'admin_' ) !== 0 ) {
                                continue;
                            }
                            $key_parts = explode('_', $key );
                            $real_key = $key_parts[1];
                            
                            // Hvis vi har fått et ja
                            if( $setting == 'yes' ) {
                                $give[] = $real_key;
                                // Har tidligere fått nei - avbryt den. Ett ja er nok
                                if( in_array($real_key, $take)){
                                    unset( $take[ array_search($real_key, $take) ]);
                                }
                            }
                            // Hvis vi har fått et nei, og ikke fått et ja tidligere
                            elseif( !in_array($real_key, $give) ) {
                                $take[] = $real_key;
                            }
                        }

                        $blog_id = Blog::getIdByPath( $arrangement->getPath() );
                        $add = 0;
                        $remove = 0;
                        $add_list = '';
                        $remove_list = '';
                        foreach( $give as $user_id ) {
                            $add++;
                            $add_list .= $_POST['name_'. $user_id].', ';
                            Blog::leggTilBruker( $blog_id, (Int) $user_id, 'editor' );
                        }
                        foreach( $take as $user_id ) {
                            $remove++;
                            $remove_list .= $_POST['name_'. $user_id].', ';
                            Blog::fjernBruker( $blog_id, $user_id );
                        }
                        UKMnettverket::getFlash()->success('Ga '. $add .' administratorer tilgang ('. rtrim($add_list,', ') .') til '. $arrangement->getNavn());
                        UKMnettverket::getFlash()->success('Fjernet '. $remove .' administratorer ('. rtrim($remove_list,', ') .') fra '. $arrangement->getNavn() );
                    } else {
                        UKMnettverket::addViewData('arrangement', $arrangement);
                        UKMnettverket::addViewData('fylke_omrade', Omrade::getByFylke($omrade->getFylke()->getId()));
                        UKMnettverket::setAction('arrangement/administratorer_velg');
                        UKMnettverket::includeActionController();
                    }
                    break;
            }
        } catch (Exception $e) {
            UKMnettverket::getFlash()->error($e->getMessage());
        }
    }
}
foreach ($omrade->getArrangementer(get_site_option('season'))->getAll() as $arrangement) {
    $arrangement->setAttr(
        'blog_eksisterer',
        Blog::eksisterer($arrangement->getPath())
    );

    try {
        $slettet = Blog::getDetails(
            Blog::getIdByPath($arrangement->getPath()),
            'deleted'
        );
    } catch (Exception $e) {
        $slettet = true;
    }
    $arrangement->setAttr('blog_slettet', $slettet);
}