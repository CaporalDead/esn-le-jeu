<?php

use Jhiino\ESNLeJeu\Client;
use Jhiino\ESNLeJeu\Entity\Options;
use Jhiino\ESNLeJeu\Entity\Scheduler;
use Jhiino\ESNLeJeu\Wrapper;
use Symfony\Component\Yaml\Yaml;

set_time_limit(3500);

require(dirname(__FILE__) . '/../vendor/autoload.php');

if (! file_exists($configFile = dirname(__FILE__) . '/../config/parameters.yml')) {
    throw new Exception('Vous devez créer une fichier de configuration. Copiez-collez le fichier parameters.yml.dist en parameters.yml et modifiez le.');
}

$config = Yaml::parse($configFile);

//$logger = (new \Jhiino\ESNLeJeu\Logger\PhpOutpuLogger())->applyConfig($config);
$logger    = (new \Jhiino\ESNLeJeu\Logger\MailLogger((new \Jhiino\ESNLeJeu\Mailer())->applyConfig($config)))->applyConfig($config);
$client    = new Client();
$orchestra = new \Jhiino\ESNLeJeu\Orchestra($client, $config);

$client->setLogger($logger);
$orchestra->setLogger($logger);

$orchestra->run();

if ($logger instanceof \Jhiino\ESNLeJeu\Logger\NeedToBeFlushedInterface) {
    $logger->flush();
}

die;








// Actions possibles
$modules = new Wrapper(new Client(), $yaml);

// Popularité
$popularity = $modules->stats()->popularity();
print(PHP_EOL . 'Popularite : ' . $popularity . '%');

// Propales gagnées
$stats      = $modules->stats()->tenders();
$all        = $stats['won'] + $stats['lost'];
$percentage = ($all > 0) ? round($stats['won'] / $all * 100, 2) : 0;
print(PHP_EOL . 'Propales gagnees ce jour : ' . $stats['won'] . ' (' . $percentage . '%)');

// Audit
if (Scheduler::getInstance()->isAuditTime()) {
    // Tempo random
    Scheduler::getInstance()->waitBeforeNextStep();

    // Virer les salariés trop payés
//    if (Options::AUDIT_FIRE_EMPLOYEES) {
    $response = $modules->audit()->fireEmployees();
    print(PHP_EOL . 'Salaries trop payes vires : ' . $response);
//    }

    // Rénégocier les contrats
//    if (Options::AUDIT_RENEGOTIATE_CONTRACTS) {
    $response = $modules->audit()->renegotiateContracts();
    print(PHP_EOL . 'Contrats renegocies : ' . $response);
//    }
}

// Baratins
if (Scheduler::getInstance()->isFlannelTime()) {
    // Tempo random
    Scheduler::getInstance()->waitBeforeNextStep();

    $flannels    = $modules->complaints()->flannel();
    $total       = $flannels['negatif'] + $flannels['positif'];
    $pourcentage = (empty($total)) ? 'rien a baratiner' : round($flannels['positif'] / $total * 100) . '%';
    print(PHP_EOL . 'Baratins du jour : ' . $pourcentage);
}

// Appels d'offres
if (Scheduler::getInstance()->isBusinessTime()) {
    // Tempo random
    Scheduler::getInstance()->waitBeforeNextStep();

    // Récupérer tous les appels d'offres
    $tenders = $modules->tenders()->tenders();
    print(PHP_EOL . 'Appels d\'offres selon critères (>= ' . Options::BID_MIN_WEEKS . ' semaines) : ' . count($tenders));

    // Tempo random
    Scheduler::getInstance()->waitBeforeNextStep();

    // Placer les inter-contrats ou les promesses d'embauches
    $response = $modules->tenders()->bidOnTenders($tenders);
    print(PHP_EOL . 'Idles a placer : ' . count($response['idles']));
    print(PHP_EOL . 'Reponses aux appels d\'offres : ' . count($response['bids']));
    print(PHP_EOL . 'Recrutements : ' . count($response['newApplicants']));
}

print(PHP_EOL . '--------Fin de traitement--------' . PHP_EOL);



