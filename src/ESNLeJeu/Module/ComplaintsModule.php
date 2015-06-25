<?php

namespace Jhiino\ESNLeJeu\Module;

use Exception;
use Jhiino\ESNLeJeu\Entity\Scheduler;
use Jhiino\ESNLeJeu\Helper\Node;
use Symfony\Component\DomCrawler\Crawler;

class ComplaintsModule extends Module
{
    /**
     * @var string
     */
    const URI = '/doleances.php';

    public function flannel()
    {
        $positif = 0;
        $negatif = 0;
        $page    = 1;

        do {
            $url      = vsprintf('%s?P=%s', [self::URI, $page]);
            $body     = $this->client->getConnection()->get($url)->send()->getBody(true);
            $crawler  = new Crawler($body);
            $children = $crawler->filter(self::CSS_FILTER);

            if (0 == $children->count()) {
                break;
            }

            $children->each(function (Crawler $crawler) use (&$positif, &$negatif) {
                $button = Node::buttonExists($crawler, 'td:nth-child(3) > a.btn', 'Traiter');

                // Si bouton info
                if ($button) {
                    // Récupération de l'id
                    $temp   = explode(',', $button->attr('onclick'));
                    $id     = preg_replace('/\D/', '', $temp[0]);
                    $numrow = rand(1, 30);
                    // Faire apparaitre les bouton
                    $post = [
                        'a'      => 'D',
                        'id_r'   => $id,
                        'numrow' => $numrow
                    ];

                    $this->client->getConnection()->post(self::AJAX_ACTION_URI, [], $post)->send();

                    // Tempo random
                    Scheduler::getInstance()->waitBeforeNextComplaint();

                    // Baratiner
                    $post    = [
                        'a'      => 'DB',
                        'id_r'   => $id,
                        'numrow' => $numrow
                    ];
                    $body    = $this->client->getConnection()->post(self::AJAX_ACTION_URI, [], $post)->send()->getBody(true);
                    $crawler = new Crawler($body);

                    // Tempo random
                    Scheduler::getInstance()->waitBeforeNextComplaint();

                    // Traitement de la réponse pour les statistiques
                    if (null != $crawler->filter('span.positif')->getNode(0)) {
                        $positif++;
                    } elseif (null != $crawler->filter('span.negatif')->getNode(0)) {
                        $negatif++;
                    } else {
                        throw new Exception('Erreur de baratin!');
                    }
                }
            });

            $page++;
        } while (true);

        return [
            'positif' => $positif,
            'negatif' => $negatif
        ];
    }
}
