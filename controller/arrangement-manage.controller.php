<?php

use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Arrangement\Write;
use UKMNorge\Geografi\Fylker;
use UKMNorge\Geografi\Kommune;
use UKMNorge\Log\Logger;
use UKMNorge\Nettverk\Omrade;
use UKMNorge\Wordpress\Blog;
use UKMNorge\Arrangement\Kontaktperson\Write as WriteKontakt;
use UKMNorge\Innslag\Typer\Typer;

require_once('UKM/Autoloader.php');

// STEG 1 Vi mangler basis-verdier
if (empty($_POST['type']) || empty($_POST['pamelding'])) {
    /** MANGLENDE VERDIER  **/
    if (empty($_POST['type'])) {
        UKMnettverket::getFlashbag()->add(
            'danger',
            'Du må velge type arrangement for å komme videre'
        );
    }

    if (empty($_POST['pamelding'])) {
        UKMnettverket::getFlashbag()->add(
            'danger',
            'Du må velge type påmelding for å komme videre'
        );
    }
}
// VI HAR ALLE VERIDER
elseif (isset($_POST['path'])) {
    /** IKKE STØTTEDE VERDIER **/
    if ($_POST['pamelding'] == 'lukket') {
        throw new Exception(
            'BEKLAGER, vi støtter ikke påmelding med krav for øyeblikket. Det kommer snart'
        );
    }

    
    if ($_GET['type'] == 'fylke') {
        $geografi = [Fylker::getById($_GET['omrade'])];
    } elseif ($_GET['type'] == 'kommune') {
        $geografi = [new Kommune($_GET['omrade'])];
    } else {
        throw new Exception('Mangler info for å definere geografi');
    }

    // Setup logger 
    Logger::initWP(0);
    
    // Opprett arrangement
    $arrangement = Write::create(
        $_GET['type'],                              // Type mønstring
        $_GET['omrade'],                             // Eier
        (int) get_site_option('season'),            // Sesong
        $_POST['navn'],                             // Navn
        $geografi,             // Geografisk tilhørighet (hm..)
        $_POST['path']
    );

    // Legg til alle kommuner i fellesmønstringen
    if (isset($_POST['kommuner']) && is_array($_POST['kommuner']) && sizeof($_POST['kommuner']) > 0) {
        $fellesmonstring = true;
        foreach ($_POST['kommuner'] as $kommune_id) {
            $kommune = new Kommune($kommune_id);
            $arrangement->getKommuner()->leggTil($kommune);
        }
    } else {
        $fellesmonstring = false;
    }

    // Sett start-dato (hvis vi har den)
    if (isset($_POST['start'])) {
        $arrangement->setStart(
            Write::inputToDateTime(
                $_POST['start'],
                '18:00'
            )
        );
    }

    // Angi påmeldingsinnstillinger
    if (isset($_POST['pamelding'])) {
        if ($_POST['pamelding'] == 'betinget') {
            $arrangement->setPamelding('betinget');
            $arrangement->setHarVideresending(false);
        } else if ($_POST['pamelding'] == 'apen') {
            $arrangement->setPamelding('apen');
            $arrangement->setHarVideresending(false);
        } else {
            $arrangement->setPamelding('ingen');
        }
    }

    // Sett synlighet
    $arrangement->setSynlig($_POST['synlig'] == 'true');

    // Hvis dette er arrangement-arrangement 
    // (i motsetning til mønstring-arrangement), angi dette.
    if( $_POST['type'] == 'arrangement' ) {
        $arrangement->setSubtype('arrangement');

        // Fjern alle typer som legges til som standard når ingen er lagt til.
        $arrangement->getInnslagTyper()->getAll(); // gjennomfør innlasting
        foreach (Typer::getAllTyper() as $tilbud) {
            try {
                $arrangement->getInnslagtyper()->fjern(Typer::getByName($tilbud->getKey()));
            } catch (Exception $e) {
                if ($e->getCode() != 110001) {
                    throw $e;
                }
            }
        }
        // Legg til typen som skal være der
        $arrangement->getInnslagTyper()->leggTil(Typer::getByKey('enkeltperson'));
    }
    
    // Lagre arrangementet
    Write::save($arrangement);

    // Hvilket område jobber vi med?
    $omrade = new Omrade($_GET['type'], $_GET['omrade']);

    /////////////////////
    /// OPPRETT BLOGG ///
    /////////////////////

    // Hvis område
    // - er land
    // Drit i det. For now.
    if( $omrade->getType() == 'land' ) {
        throw new Exception(
            'Beklager, kan ikke opprette blogg for nasjonale arrangementer enda.'
        );
    }
    // Hvis arrangementet
    // - tilhører et fylke
    // - eller er en fellesmønstring
    // Opprett en ny blogg
    elseif( $omrade->getType() == 'fylke' || $fellesmonstring ) {
        // Oppretter man en fellesmønstring med en path som finnes, 
        // hvor bloggen også er deaktivert, så kan vi overta den.
        // Skulle bloggen finnes - så er det en bug. Da burde arrangementet
        // muligens hatt annet navn. Det er uansett en edge-case som bør håndteres.
        if( Blog::eksisterer( $_POST['path'] ) ) {
            $blog_id = Blog::getIdByPath( $_POST['path'] );
            // Nettstedadressen er tatt, men inneholder info om gammelt arrangement
            // overta.
            if( Blog::erDeaktivert( $blog_id ) ) {
                Blog::oppdaterFraArrangement($blog_id, $arrangement);
                Blog::aktiver( $blog_id );
            }
            // Nettstedadressen er tatt, og fortsatt aktiv. Da er det skummelt å overta,
            // ettersom dette kan være et arrangement i årets sesong (mulig å sjekke det da)
            else {
                // Nettstedet er et arrangement.
                if( Blog::getOption( $blog_id, 'pl_id' ) ) {
                    $arrangement = new Arrangement( Blog::getOption( $blog_id, 'pl_id') );
                    // Skulle arrangementet være fra i fjor eller forifjor, er det bare å overta det.
                    if( $arrangement->getSesong() < get_site_option('season') ) {
                        Blog::oppdaterFraArrangement($blog_id, $arrangement);
                    } else {
                        throw new Exception(
                            'Kunne ikke opprette arrangementets nettsted, da nettstedadressen er opptatt. '.
                            'Fordi arrangementet er fra i år, er det ikke mulig å overta nettstedet. '.
                            'Kontakt <a href="mailto:support@ukm.no?subject=Opprett nettsted">support@ukm.no</a>'
                        );
                    }
                } else {
                    throw new Exception(
                        'Kunne ikke opprette arrangementets nettsted. Kontakt '.
                        '<a href="mailto:support@ukm.no?subject=Opprett nettsted - fant ikke pl_id">support@ukm.no</a>.'
                    );
                }
            }
        } else {
            $blog_id = Blog::opprettForArrangement(
                $arrangement,
                $_POST['path']
            );
        }
    }
    // Hvis arrangementet
    // - tilhører en kommune (implisitt av if)
    // - ikke har et arrangement fra før
    // Oppdater den eksisterende siden (eller opprett om den ikke finnes)
    // OBS: 1 == 0, da arrangementet ble opprettet litt lenger opp i scriptet
    elseif( $omrade->getArrangementer( get_site_option('season') )->getAntall() == 1 ) {
        try {
            $blog_id = Blog::getIdByPath($_POST['path']);
        } catch (Exception $e) {
            // 172007 = fant ingen blogg 
            // (som kan skje hvis vi ikke har opprettet blogger for alle kommuner og fylker)
            // Fordi vi vet dette er første arrangement, og kommune-siden da skal vise 
            // arrangementsinfo, opprettes den like godt "forArrangement"
            if ($e->getCode() == 172007) {
                $blog_id = Blog::opprettForArrangement(
                    $arrangement,
                    $_POST['path']
                );
            } else {
                throw $e;
            }
        }
        Blog::oppdaterFraArrangement($blog_id, $arrangement);
    }
    // Hvis arrangementet
    // - tilhører en kommune (implisitt av if)
    // - har ett arrangement fra før
    // Opprett ny blogg for dette arrangementet
    // Flytt kommune/land-siden til ny blogg
    // Opprett ny kommune/land-side
    // OBS: 2 == 1, da arrangementet ble opprettet litt lenger opp i scriptet
    elseif ($omrade->getArrangementer(get_site_option('season'))->getAntall() == 2) {
        // Opprett blogg for nytt arrangement
        $blog_id = Blog::opprettForArrangement(
            $arrangement,
            $_POST['path']
        );
        Blog::oppdaterFraArrangement($blog_id, $arrangement);
        
        // Identifiser kommuneside, og gjør klar for flytting
        $kommune = $omrade->getKommune();
        $flytt_blog_id = Blog::getIdByPath( $kommune->getPath() );
        
        // Beregn ny path
        $kommune_path = rtrim($kommune->getNavn(), '/');
        $flytt_arrangement = new Arrangement( get_blog_option( $flytt_blog_id, 'pl_id') );
        $ny_path = Blog::sanitizePath( $kommune_path .'-'. $flytt_arrangement->getNavn() );
        
        $arrangement_blog_eksisterer = false;
        try { 
            $existing_blog = Blog::getIdByPath($ny_path);
            
            if( Blog::getOption( $existing_blog, 'pl_id' ) == $flytt_arrangement->getId() ) {
                $arrangement_blog_eksisterer = true;
                Blog::aktiver( $existing_blog ); // Den skal være deaktivert. Aktiver for sikkerhets skyld
            } else {
                // bloggen finnes fra før - lag manuell path (denne SKAL ikke finnes fra før).
                $ny_path = Blog::sanitizePath($kommune_path .'-arrangement-1/');
            }
        } catch( Exception $e ) {
            // Hvis feilkoden ikke er "bloggen finnes ikke", kast exception
            if( $e->getCode() != 172007) {
                throw $e;
            }
        }
        
        // Flytt kommunesiden ut til egen blogg hvis 
        // arrangementet ikke tidligere har hatt en egen blogg
        if( !$arrangement_blog_eksisterer ) {
            Blog::flytt( $flytt_blog_id, $ny_path );
        }
        Blog::oppdaterFraArrangement($blog_id, $flytt_arrangement);
        $flytt_arrangement->setPath( $ny_path );
        Write::save( $flytt_arrangement );

        // Opprett ny kommuneside og legg til områdets admins
        try {
            $ny_kommune_blog_id = Blog::opprettForKommune( $kommune );
        } catch( Exception $e ) {
            // 172008 = 'Kunne ikke opprette blogg da siden allerede eksisterer'
            if( $e->getCode() == 172008 ) {
                $ny_kommune_blog_id = Blog::getIdByPath( $kommune->getPath() );
            } else {
                throw $e;
            }
        }
        // Siden vi nå har flere arrangement, skal kommunesiden være en ren kommuneside
        Blog::fjernArrangementData( $ny_kommune_blog_id );

        foreach ($omrade->getAdministratorer()->getAll() as $admin) {
            Blog::leggTilBruker($ny_kommune_blog_id, $admin->getId(), 'editor');
        }
        if( $omrade->getType() == 'kommune' ) {
            $fylke_omrade = Omrade::getByFylke( $omrade->getFylke()->getId() );
            foreach( $fylke_omrade->getAdministratorer()->getAll() as $admin ) {
                Blog::leggTilBruker($ny_kommune_blog_id, $admin->getId(), 'editor');
            }
        }
    }
    // Hvis arrangementet
    // - tilhører en kommune (implisitt av if)
    // - har flere arrangementer fra før
    // Opprett ny blogg for dette arrangementet
    else {
        $blog_id = Blog::opprettForArrangement(
            $arrangement,
            $_POST['path']
        );
    }

    // Legg til admins som kontakter og administratorer for bloggen
    foreach ($omrade->getAdministratorer()->getAll() as $admin) {
        Blog::leggTilBruker($blog_id, $admin->getId(), 'editor');

        $kontakt = WriteKontakt::create(
            $admin->getUser()->getFirstName(),
            $admin->getUser()->getLastName(),
            $admin->getUser()->getPhone()
        );
        $kontakt->setEpost($admin->getUser()->getEmail());
        WriteKontakt::save($kontakt);

        $arrangement->getKontaktpersoner()->leggTil($kontakt);
    }

    if( $omrade->getType() == 'kommune' ) {
        $fylke_omrade = Omrade::getByFylke( $omrade->getFylke()->getId() );
        foreach( $fylke_omrade->getAdministratorer()->getAll() as $admin ) {
            Blog::leggTilBruker($blog_id, $admin->getId(), 'editor');
        }
    }
    
    Write::save($arrangement);

    UKMnettverket::addViewData('arrangement', $arrangement);
    UKMnettverket::addViewData('blog_path', $_POST['path']);
    UKMnettverket::addViewData('omrade', $omrade);
    UKMnettverket::setAction('arrangement-added');
}
