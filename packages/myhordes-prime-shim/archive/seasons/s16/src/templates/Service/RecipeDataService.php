<?php

namespace MyHordes\Prime\Service;

use App\Entity\Recipe;
use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class RecipeDataService implements FixtureProcessorInterface {

    public function process(array &$data): void
    {
        $add = [];
        foreach ($data as $key => $recipe)
            if ($recipe['type'] === Recipe::WorkshopType && $recipe['in'] !== 'chest_xl_#00' && is_array($recipe['out']) && count($recipe['out']) > 0)
                foreach ($recipe['out'] as $index => $option)
                    $add[ "{$key}_ta_{$index}" ] = array_replace( $recipe, [
                        'type' => Recipe::WorkshopTypeTechSpecific,
                        'out' => is_array($option) ? $option[0] : $option
                    ] );

        $data = array_merge( $data, $add );
    }
}