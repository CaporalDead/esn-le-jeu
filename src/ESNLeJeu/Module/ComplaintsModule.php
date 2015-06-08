<?php namespace Jhiino\ESNLeJeu\Module;

use Exception;
use Jhiino\ESNLeJeu\Entity\Scheduler;
use Symfony\Component\DomCrawler\Crawler;

class ComplaintsModule extends Module
{
    /**
     * @var string
     */
    const URI = '/doleances.php';

    const TEXT_RESIGNATION  = 'Démission';
    const TEXT_DISAPPOINTED = 'Déçu';
    const TEXT_REFRESHED    = 'Remotivé';

    public function flannel()
    {
        $positif = 0;
        $negatif = 0;
        $page    = 1;

        do {
            $url  = vsprintf('%s?P=%s', [self::URI, $page]);
            $body = $this->client->getConnection()->get($url)->send()->getBody(true);

            $crawler = new Crawler($body);

            $children = $crawler->filter(self::CSS_FILTER);

            if (0 == $children->count()) {
                break;
            }

            $children->each(
                function (Crawler $child) use (&$positif, &$negatif) {

                    $mood = filter_var($child->filter('td:nth-child(3)')->html(), FILTER_SANITIZE_STRING);

                    // Si non traité
                    if (! in_array($mood, [self::TEXT_RESIGNATION, self::TEXT_DISAPPOINTED, self::TEXT_REFRESHED])) {
                        $numrow = rand(1, 30);

                        // Faire apparaitre les bouton
                        $formInput = [
                            'a'      => 'D',
                            'id_r'   => filter_var(trim(substr($child->attr('id'), 2)), FILTER_SANITIZE_NUMBER_INT),
                            'numrow' => $numrow
                        ];

                        $this->client->getConnection()->post(self::AJAX_ACTION_URI, [], $formInput)->send();

                        // Tempo random
                        Scheduler::waitBeforeNextComplaint();

                        // Baratiner
                        $formInput = [
                            'a'      => 'DB',
                            'id_r'   => filter_var(trim(substr($child->attr('id'), 2)), FILTER_SANITIZE_NUMBER_INT),
                            'numrow' => $numrow
                        ];

                        $body = $this->client->getConnection()->post(self::AJAX_ACTION_URI, [], $formInput)->send()->getBody(true);

                        // Tempo random
                        Scheduler::waitBeforeNextComplaint();

                        // Traitement de la réponse pour les statistiques
                        $crawler = new Crawler($body);

                        if (null != $crawler->filter('span.positif')->getNode(0)) {
                            $positif += 1;
                        } elseif (null != $crawler->filter('span.negatif')->getNode(0)) {
                            $negatif += 1;
                        } else {
                            throw new Exception('Erreur de baratin!');
                        }
                    }
                }
            );

            $page++;
        } while (true);

        return [
            'positif' => $positif,
            'negatif' => $negatif
        ];
    }
}
