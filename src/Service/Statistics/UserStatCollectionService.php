<?php


namespace App\Service\Statistics;

use App\Entity\User;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class UserStatCollectionService
{
    public function __construct(
        private readonly EntityManagerInterface $entity_manager
    ) {}

    public function collectData(DateTime|DateTimeImmutable $cutoff, array $languages): array {
        $data = ['total' => 0, 'by_lang' => ['others' => 0], 'by_pronoun' => ['male' => 0, 'female' => 0, 'unset' => 0]];
        foreach ($languages as $code) $data['by_lang'][$code] = 0;
        foreach ($this->entity_manager->getRepository(User::class)->createQueryBuilder('u')
            ->select("count(u.id) as count, u.language, JSON_EXTRACT(u.settings, '$.preferred-pronoun') as pronoun")
            ->andWhere('u.validated = 1')
            ->andWhere('u.email NOT LIKE :d')->setParameter('d', '$%')
            ->andWhere('u.lastActionTimestamp > :cutoff')->setParameter( 'cutoff', $cutoff )
            ->groupBy("u.language", 'pronoun')
            ->getQuery()->getResult() as $lang
        ) {
            if (isset($data['by_lang'][$lang['language']])) $data['by_lang'][$lang['language']] += $lang['count'];
            else $data['by_lang']['others'] += $lang['count'];

            switch ((int)$lang['pronoun']) {
                case User::PRONOUN_MALE: $data['by_pronoun']['male'] += $lang['count']; break;
                case User::PRONOUN_FEMALE: $data['by_pronoun']['female'] += $lang['count']; break;
                default: $data['by_pronoun']['unset'] += $lang['count']; break;
            };

            $data['total'] += $lang['count'];
        }

        return $data;
    }
}