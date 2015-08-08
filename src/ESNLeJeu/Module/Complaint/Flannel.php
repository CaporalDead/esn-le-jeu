<?php

namespace Jhiino\ESNLeJeu\Module\Complaint;

use Jhiino\ESNLeJeu\Entity\ObjectDetails;
use Jhiino\ESNLeJeu\Helper\Node;
use Jhiino\ESNLeJeu\Module;
use Symfony\Component\DomCrawler\Crawler;

class Flannel extends Module
{
    /**
     * @var string
     */
    const URI_DOLEANCES = '/doleances.php';

    /**
     * Parse une page à la recherche d'employés à baratiner
     *
     * @param $page
     *
     * @return Crawler
     */
    protected function parsePage($page)
    {
        $body     = $this->client->get(self::URI_DOLEANCES, ['P' => $page]);
        $crawler  = new Crawler($body);
        $children = $crawler->filter(self::CSS_FILTER);

        return $children;
    }

    /**
     * @param Crawler $crawler
     *
     * @return bool|ObjectDetails
     */
    protected function tryToProcess(Crawler $crawler)
    {
        $this->logger->debug('On cherche le bouton "Traiter"');

        $button = Node::buttonExists($crawler, 'td:nth-child(3) > a.btn', 'Traiter');

        if (! $button) {
            $this->logger->debug('Pas de bouton "Traiter"');

            return false;
        }

        $temp    = explode(',', $button->attr('onclick'));
        $id      = preg_replace('/\D/', '', $temp[0]);
        $numRow  = rand(1, 30);
        $post    = [
            'a'      => 'D',
            'id_r'   => $id,
            'numrow' => $numRow
        ];
        $html    = $this->client->post(self::AJAX_ACTION_URI, $post);
        $crawler = new Crawler($html);

        $this->logger->debug(sprintf('Les détails de l\'employé [%s %s]', $id, $numRow));

        return new ObjectDetails($crawler, $id, $numRow);
    }

    protected function tryToFlannel(Crawler $crawler, $idToFlannel, $numRow)
    {
        $this->logger->debug('On cherche le bouton "Baratiner"');

        $button = Node::buttonExists($crawler, 'td:nth-child(3) > a.btn', 'Baratiner');

        if (! $button) {
            $this->logger->debug('Pas de bouton "Baratiner"');

            return false;
        }

        $post    = [
            'a'      => 'DB',
            'id_r'   => $idToFlannel,
            'numrow' => $numRow
        ];
        $html    = $this->client->post(self::AJAX_ACTION_URI, $post);
        $crawler = new Crawler($html);

        if (null != $crawler->filter('span.positif')->getNode(0)) {
            return 1;
        } elseif (null != $crawler->filter('span.negatif')->getNode(0)) {
            return 0;
        }

        return false;
    }

    /**
     * Action
     */
    public function fire()
    {
        $page = 1;

        $numberOfEmployeesToFlannel = 0;
        $numberOfEmployeesFlanneled = 0;

        do {
            $employees = $this->parsePage($page);
            $numberOfEmployeesToFlannel += $employees->count();

            if (0 == $employees->count()) {
                $this->logger->debug(sprintf('Aucun employé à baratiner sur la page %s', $page));

                break;
            }

            $employees->each(function (Crawler $crawler) use (& $numberOfEmployeesFlanneled) {
                if ($process = $this->tryToProcess($crawler)) {
                    if (false !== ($result = $this->tryToFlannel($process->crawler, $process->id, $process->numRow))) {
                        $numberOfEmployeesFlanneled += $result;
                    }
                }
            });

            $page++;
        } while (true);

        $this->logger->info(vsprintf('Nombre d\'employés baratinés : %s/%s (%s%%)', [
            $numberOfEmployeesFlanneled,
            $numberOfEmployeesToFlannel,
            ($numberOfEmployeesFlanneled * 100 / $numberOfEmployeesToFlannel),
        ]));
    }
}
