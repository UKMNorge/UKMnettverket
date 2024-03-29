<?php

use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Nettverk\Omrade;
use UKMNorge\Wordpress\Blog;

$arrangement = new Arrangement( $_GET['arr'] );
$omrade = new Omrade($_GET['type'], (Int)$_GET['omrade']);


$arrangementerSammeType = [];
foreach($omrade->getKommendeArrangementer()->getAll() as $arrang) {
    if($arrang->getId() != $arrangement->getId() && $arrang->erKunstgalleri() == $arrangement->erKunstgalleri()) {
        $arrangementerSammeType[] = $arrang;
    }
}



UKMnettverket::addViewData('arrangementerSammeType', $arrangementerSammeType);
UKMnettverket::addViewData('arrangement', $arrangement);
UKMnettverket::addViewData('omrade', $omrade);
UKMnettverket::addViewData('sesong', get_site_option('season'));
UKMnettverket::addViewData(
    'num_posts',
    Blog::getAntallPosts(
        Blog::getIdByPath( $arrangement->getPAth() )
    )
);