<?php

use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Arrangement\Arrangementer;
use UKMNorge\Geografi\Fylker;
use UKMNorge\Nettverk\Administratorer;
use UKMNorge\Nettverk\Omrade;
use UKMNorge\Nettverk\OmradeKontaktperson;
use UKMNorge\Nettverk\WriteOmradeKontaktperson;

die('Migrering av kontaktpersoner deaktivert');

$fylker = new Fylker();

$fylkerCount = 0;
$kommunerCount = 0;
$countKontaktpersoner = 0;

$arrangementer = [];


foreach( $fylker->getAll() as $fylke ) {
    $omradeFylke = new Omrade('fylke', $fylke->getId());
    echo $fylke->getId() . ': ' . $fylke->getNavn() . '<br>';
    
    $fylkeArrangementer = new Arrangementer('fylke', $fylke->getId());
    foreach( $fylkeArrangementer->getAll() as $fArrangement ) {
        migrateKontaktpersoner($fArrangement, $omradeFylke, $countKontaktpersoner);
    }
    
    $fylkerCount++;
    foreach( $fylke->getKommuner()->getAll() as $kommune ) {
        $omradeKommune = new Omrade('kommune', $kommune->getId());
        echo $kommune->getId() . ': ' . $kommune->getNavn() . '<br>';

        $kommuneArrangementer = new Arrangementer('kommune', $kommune->getId());
        foreach( $kommuneArrangementer->getAll() as $kArrangement ) {
            migrateKontaktpersoner($kArrangement, $omradeKommune, $countKontaktpersoner);
        }
        $kommunerCount++;
    }
}

echo '<h4>Antall kontaktpersoner: ' . $countKontaktpersoner . '</h4>';



function migrateKontaktpersoner(Arrangement $arrangement, Omrade $fylkeKommuneOmrade, &$countKontaktpersoner) {
    foreach($arrangement->getKontaktpersoner()->getAll() as $kontaktperson) {
        if($kontaktperson->getTelefon() == null || $kontaktperson->getTelefon() == '' || $kontaktperson->getTelefon() == 0 || !preg_match('/^\d{8}$/', $kontaktperson->getTelefon())
            && $kontaktperson->getEpost() == null || $kontaktperson->getEpost() == '') {
            echo 'Mangler mobilnummer og epost for kontaktperson: ' . $kontaktperson->getFornavn() . ' ' . $kontaktperson->getEtternavn() . ' i ' . $arrangement->getNavn() . '<br>';
        } else {
            echo '<pre>';
            var_dump($kontaktperson);
            var_dump($kontaktperson->getFornavn());
            echo '</pre><hr><br>';
            $okp = new OmradeKontaktperson([
                'id' => -1, 
                'fornavn' => $kontaktperson->getFornavn() == '' ? 'Ukjent' : $kontaktperson->getFornavn(), 
                'etternavn' => $kontaktperson->getEtternavn() == '' ? 'Ukjent' : $kontaktperson->getEtternavn(), 
                'mobil' => $kontaktperson->getTelefon() == null ? null : $kontaktperson->getTelefon(), 
                'epost' => $kontaktperson->getEpost() == null ? null : $kontaktperson->getEpost(), 
                'beskrivelse' => '', 
                'profile_image_url' => $kontaktperson->bilde != null && strlen($kontaktperson->bilde) > 5 ? $kontaktperson->bilde : null,
                'eier_omrade_id' => $fylkeKommuneOmrade->getForeignId(), 
                'eier_omrade_type' => $fylkeKommuneOmrade->getType()
            ]);

            $arrangementOmrade = new Omrade('monstring', $arrangement->getId());
            WriteOmradeKontaktperson::leggTilOmradeKontaktperson($arrangementOmrade, $okp);
        }
        

        $countKontaktpersoner++;
    }
}