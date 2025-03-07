<?php

namespace App\Service\Actions\Game\AtomProcessors\Require;

use App\Enum\ActionHandler\PointType;
use App\Service\CitizenHandler;
use App\Structures\ActionHandler\Evaluation;
use App\Translation\T;
use MyHordes\Fixtures\DTO\Actions\Atoms\Requirement\PointRequirement;
use MyHordes\Fixtures\DTO\Actions\RequirementsAtom;

class ProcessPointRequirement extends AtomRequirementProcessor
{
    public function __invoke(Evaluation $cache, RequirementsAtom|PointRequirement $data): bool
    {
        if ($data->min === null && $data->max === null) return true;
        /** @var CitizenHandler $citizenHandler */
        $citizenHandler = $data->fromLimit ? $this->container->get(CitizenHandler::class) : null;

        $value = $cache->citizen->getPoints($data->require);
        $min = $data->min === null ? null : (($citizenHandler?->getMaxPoints( $cache->citizen, $data->require) ?? 0) + $data->min);
        $max = $data->max === null ? null : (($citizenHandler?->getMaxPoints( $cache->citizen, $data->require) ?? 0) + $data->max);

        [ $key, $name ] = match ($data->require) {
            PointType::AP => [
                'ap',
                T::__('AP', 'game')
            ],
            PointType::CP => [
                'cp',
                T::__('CP', 'game')
            ],
            PointType::MP => [
                'mp',
                T::__('MP', 'game')
            ],
            PointType::SP => [
                'sp',
                T::__('SP', 'game')
            ]
        };

        $cache->addTranslationKey("{$key}_current", $value);
        $cache->addTranslationKey('pt_current', $value);
        $cache->addMetaTranslationKey('pt_name', $name, 'game');

        if ($min !== null) {
            $cache->addTranslationKey("{$key}_min", $min);
            $cache->addTranslationKey('pt_min', $min);
        }
        if ($max !== null) {
            $cache->addTranslationKey("{$key}_max", $max);
            $cache->addTranslationKey('pt_max', $max);
        }

        if ($min !== null && $value < $min) return false;
        if ($max !== null && $value > $max) return false;
        return true;
    }
}