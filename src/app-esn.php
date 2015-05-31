<?php

use Jhiino\ESNLeJeu\Client;
use Jhiino\ESNLeJeu\Entity\CareerProfiles;
use Jhiino\ESNLeJeu\Entity\Scheduler;
use Jhiino\ESNLeJeu\Entity\Tender;
use Jhiino\ESNLeJeu\Entity\User;
use Jhiino\ESNLeJeu\Wrapper;

require(dirname(__FILE__) . '/../vendor/autoload.php');
set_time_limit(3500);

// Démarrage décalé
Scheduler::waitForStart();

print(PHP_EOL . '---------------------------------');
print(PHP_EOL . 'Actions du ' . date('Y-m-d H:i:s'));

// Connection
$esnClient = new Client(User::ESN_NAME, User::LOGIN, User::PASSWORD);
$esnClient->getConnection();

// Actions possibles
$modules = new Wrapper($esnClient);

// Popularité
$popularity = $modules->stats()->popularity();
print(PHP_EOL . 'Popularite : ' . $popularity . '%');

// Propales gagnées
$stats      = $modules->stats()->tenders();
$all        = $stats['won'] + $stats['lost'];
$percentage = ($all > 0) ? round($stats['won'] / $all * 100, 2) : 0;
print(PHP_EOL . 'Propales gagnees ce jour : ' . $stats['won'] . ' (' . $percentage . '%)');

// Baratiner
if (Scheduler::isFlannelTime()) {
    // Tempo random
    Scheduler::waitBeforeNextStep();

    $flannels    = $modules->complaints()->flannel();
    $total       = $flannels['negatif'] + $flannels['positif'];
    $pourcentage = (empty($total)) ? 'rien a baratiner' : round($flannels['positif'] / $total * 100) . '%';
    print(PHP_EOL . 'Baratins du jour : ' . $pourcentage);
}

// Appels d'offres
if (Scheduler::isBusinessTime()) {
    // Tempo random
    Scheduler::waitBeforeNextStep();

    // Récupérer tous les appels d'offres
    $tenders = $modules->tenders()->tenders();
    print(PHP_EOL . 'Appels d\'offres selon critères (>= ' . Tender::MIN_WEEKS . ' semaines) : ' . count($tenders));

    // Tempo random
    Scheduler::waitBeforeNextStep();

    // Placer les inter-contrats ou les promesses d'embauches
    $response = $modules->tenders()->bidOnTenders($tenders);
    print(PHP_EOL . 'Idles a placer : ' . count($response['idles']));
    print(PHP_EOL . 'Reponses aux appels d\'offres : ' . count($response['bids']));
    print(PHP_EOL . 'Recrutements : ' . count($response['newApplicants']));
}

print(PHP_EOL . '--------Fin de traitement--------');



