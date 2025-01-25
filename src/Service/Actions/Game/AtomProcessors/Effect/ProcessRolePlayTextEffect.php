<?php

namespace App\Service\Actions\Game\AtomProcessors\Effect;

use App\Entity\FoundRolePlayText;
use App\Entity\RolePlayText;
use App\Enum\ClientSignal;
use App\Service\Globals\ResponseGlobal;
use App\Service\PictoHandler;
use App\Service\RandomGenerator;
use App\Structures\ActionHandler\Execution;
use DateTime;
use MyHordes\Fixtures\DTO\Actions\Atoms\Effect\RolePlayTextEffect;
use MyHordes\Fixtures\DTO\Actions\EffectAtom;

class ProcessRolePlayTextEffect extends AtomEffectProcessor
{
    public function __invoke(Execution $cache, EffectAtom|RolePlayTextEffect $data): void
    {
        /** @var RandomGenerator $rg */
        $rg = $this->container->get(RandomGenerator::class);

        /** @var PictoHandler $picto_handler */
        $picto_handler = $this->container->get(PictoHandler::class);

        /** @var RolePlayText|null $text */
        $text = $rg->pickEntryFromRandomArray(
            ($cache->citizen->getTown()->getLanguage() === 'multi' || $cache->citizen->getTown()->getLanguage() === null)
                ? $cache->em->getRepository(RolePlayText::class)->findAll()
                : $cache->em->getRepository(RolePlayText::class)->findAllByLang($cache->citizen->getTown()->getLanguage() ));

        $already_found = !$text || $cache->em->getRepository(FoundRolePlayText::class)->findByUserAndText($cache->citizen->getUser(), $text);

        $cache->addTranslationKey('rp_text', $text->getTitle(), true);

        $this->container->get(ResponseGlobal::class)->withConditionalSignal(!$already_found, ClientSignal::InventoryHeadlessUpdate);

        if ($already_found)
            $cache->addTag('rp_fail');
        else {
            $cache->addTag('rp_ok');
            $found_rp = new FoundRolePlayText();
            $found_rp->setUser($cache->citizen->getUser())->setText($text)->setNew(true)->setDateFound(new DateTime());
            $cache->citizen->getUser()->getFoundTexts()->add($found_rp);

            $cache->em->persist($found_rp);
            $picto_handler->give_picto($cache->citizen, 'r_rp_#00');
        }
    }
}