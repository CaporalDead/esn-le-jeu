<?php namespace Jhiino\ESNLeJeu\Module;

use Exception;
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

        do {
            $html = $this->client->getConnection()->get(self::URI)->send()->getBody(true);

            $crawler  = new Crawler($html);
            $children = $crawler->filter(self::CSS_FILTER);

            if (0 == $children->count()) {
                break;
            }

            $children->each(
                function (Crawler $child) use (&$positif, &$negatif) {

                    // Faire apparaitre le bouton
                    $formInput = [
                        'a'      => 'D',
                        'id_r'   => filter_var(trim(substr($child->attr('id'), 2)), FILTER_SANITIZE_NUMBER_INT),
                        'numrow' => rand(1, 30)
                    ];

                    $this->client->getConnection()->post(self::AJAX_ACTION_URI, [], $formInput)->send();
                    usleep(rand(500, 999));

                    // Baratiner
                    $formInput = [
                        'a'      => 'DB',
                        'id_r'   => filter_var(trim(substr($child->attr('id'), 2)), FILTER_SANITIZE_NUMBER_INT),
                        'numrow' => rand(1, 30)
                    ];

                    $html = $this->client->getConnection()->post(self::AJAX_ACTION_URI, [], $formInput)->send()->getBody(true);
                    usleep(rand(500, 999));

                    // Traitement de la réponse pour les statistiques
                    $crawler = new Crawler($html);

                    if (null != $crawler->filter('span.positif')->getNode(0)) {
                        $positif += 1;
                    } elseif (null != $crawler->filter('span.negatif')->getNode(0)) {
                        $negatif += 1;
                    } else {
                        throw new Exception('Erreur de baratin!');
                    }
                }
            );
        } while (true);

        return [
            'positif' => $positif,
            'negatif' => $negatif
        ];
    }
}
