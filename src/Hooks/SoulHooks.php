<?php

namespace App\Hooks;

use App\Entity\CitizenProfession;
use App\Entity\PictoPrototype;
use App\Entity\User;
use App\Service\User\UserUnlockableService;

class SoulHooks extends HooksCore {


    private function counter(User $user, string $template = null, string $subject = null): array {
        /** @var UserUnlockableService $unlockService */
        $unlockService = $this->container->get(UserUnlockableService::class);

        $num = $unlockService->hasRecordedHeroicExperienceFor( $user, template: $template, subject: $subject, season: true, total: $count );
        return [
            'achieved' => $num > 0,
            'total' => $count,
        ];
    }

	public function earnXHP(): string {
        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        $day_data = [
            [
                'value' => 1,
                'valueNote'     => $this->translator->trans('pro überlebtem Tag', [], 'soul'),
                'description'   => $this->translator->trans('Überlebe in einer Stadt, solange du kannst!', [], 'soul'),
                'repeat' => true,
                ...$this->counter($user, template: 'hxp_survived_days_base'),
            ],
            [
                'value' => 2,
                'valueNote'     => null,
                'description'   => $this->translator->trans('Überlebe mindestens {days} Tage in einer Pandämonium-Stadt', ['days' => 5], 'soul'),
                'repeat' => true,
                ...$this->counter($user, template: 'hxp_panda_day5'),
            ],
            ...array_map(function(CitizenProfession $p) use ($user) {
                return [
                    'value' => 2,
                    'valueNote'     => null,
                    'description'   => $this->translator->trans('Überlebe mindestens {days} Tage als {profession} in einer Stadt.', ['days' => 10, 'profession' => $this->translator->trans($p->getLabel(), [], 'game')], 'soul'),
                    'repeat' => false,
                    ...$this->counter($user, subject: "profession_day10_{$p->getName()}"),
                ];
            }, array_filter( $this->em->getRepository(CitizenProfession::class)->findSelectable(), fn(CitizenProfession $p) => $p->getName() !== 'shaman' )),
            [
                'value' => 5,
                'valueNote'     => null,
                'description'   => $this->translator->trans('Überlebe insgesamt mindestens {days} Tage in einer Stadt.', ['days' => 15], 'soul'),
                'repeat' => true,
                ...$this->counter($user, template: 'hxp_common_day15'),
            ],
            [
                'value' => 10,
                'valueNote'     => null,
                'description'   => $this->translator->trans('Überlebe insgesamt mindestens {days} Tage in einer Stadt.', ['days' => 30], 'soul'),
                'repeat' => false,
                ...$this->counter($user, subject: 'common_day30'),
            ],
        ];

        $multi_picto_db = [
            'r_surgrp_#00' => [0 => [null,2]],
            'r_surlst_#00' => [5 => [8, 7], 9 => [13,14], 14 => [null,21]],
            'r_suhard_#00' => [5 => [null, 7]],
        ];

        $pt_2 = [ 1 => 2, 3 => 1, 5 => 1, 8 => 1, 10 => 1 ];
        $pt_5 = [ 1 => 5, 3 => 2, 5 => 2, 8 => 2, 10 => 2 ];
        $pt_7 = [ 1 => 7, 3 => 2, 5 => 2, 8 => 2, 10 => 2 ];
        $pt_2_5  = [ 5 => 2, 10 => 1, 15 => 1, 20 => 1 ];
        $pt_2_10 = [ 10 => 2, 20 => 1, 30 => 1, 50 => 1 ];

        $picto_db = [
            'r_thermal_#00' => $pt_2,
            'r_ebcstl_#00' =>  $pt_2,
            'r_ebpmv_#00' =>   $pt_2,
            'r_ebgros_#00' =>  $pt_2,
            'r_ebcrow_#00' =>  $pt_2,
            'r_wondrs_#00' =>  $pt_2,
            'r_maso_#00'   =>  $pt_2,

            'r_batgun_#00' =>  $pt_5,
            'r_door_#00'   =>  $pt_5,
            'r_explo2_#00' =>  $pt_5,
            'r_ebuild_#00' =>  $pt_5,

            'r_chstxl_#00' =>  $pt_7,
            'r_dnucl_#00'  =>  $pt_7,
            'r_watgun_#00' =>  $pt_7,
            'r_cmplst_#00' =>  $pt_7,

            'r_tronco_#00' =>  [ 1 => 10, 2 => 2, 3 => 2, 5 => 2 ],
            'r_cobaye_#00' =>  $pt_2_5,
            'r_solban_#00' =>  $pt_2_5,
            'r_explor_#00' =>  $pt_2_5,
            'r_mystic_#00' =>  $pt_2_5,

            'r_repair_#00' =>  $pt_2_10,
            'r_guard_#00'  =>  $pt_2_10,
            'r_theft_#00'  =>  $pt_2_10,
            'r_plundr_#00' =>  $pt_2_10,
            'r_camp_#00'   =>  $pt_2_10,
        ];

        $picto_data = [];

        foreach ($multi_picto_db as $id => $points) {

            $picto_proto = $this->em->getRepository(PictoPrototype::class)->findOneByName($id);
            if (!$picto_proto) continue;

            foreach ($points as $day => [$tillDay, $point])
                $picto_data[] = [
                    'value' => $point,
                    'valueNote' => $day > 0
                        ? ( $tillDay !== null
                            ? $this->translator->trans('Wenn du zwischen {day1} und {day2} Tage in der Stadt überlebt hast!', ['day1' => $day, 'day2' => $tillDay], 'soul')
                            : $this->translator->trans('Wenn du zwischen mindestens {day} Tage in der Stadt überlebt hast!', ['day' => $day], 'soul')
                        )
                        : null,
                    'valuePost' => null,
                    'icon'  => $picto_proto->getIcon(),
                    'name'  => $this->translator->trans($picto_proto->getLabel(), [], 'game'),
                    'repeat' => true,
                    'achieved' => false,
                    'total' => 0,
                ];
        }

        foreach ($picto_db as $id => $points) {
            $picto_proto = $this->em->getRepository(PictoPrototype::class)->findOneByName($id);
            if (!$picto_proto) continue;

            foreach ($points as $count => $value)
                $picto_data[] = [
                    'value' => $value,
                    'valueNote' => null,
                    'valuePost' => "× $count",
                    'icon'  => $picto_proto->getIcon(),
                    'name'  => $this->translator->trans($picto_proto->getLabel(), [], 'game'),
                    'repeat' => false,
                    ...$this->counter($user, subject: "picto_{$id}" . ( $count > 1 ? "__$count" : "" ) ),
                ];
        }

        $other_data = [
            [
                'value' => 10,
                'valueNote'     => $this->translator->trans('pro geworbenem Spieler', [], 'soul'),
                'description'   => $this->translator->trans('Lade neue Spieler zu MyHordes ein und kümmere dich rührend um sie, bis sie das erste Mal Erfahrung für eine Fähigkeit ausgeben.', [], 'soul'),
                'repeat'        => true,
                ...$this->counter($user, template: 'hxp_ref_first'),
            ],
            [
                'value' => 2,
                'valueNote'     => $this->translator->trans('pro gefressenem Bürger', [], 'soul'),
                'description'   => $this->translator->trans('Dezimiere als Ghul die Bewohner deiner Stadt', [], 'soul'),
                'repeat' => true,
                ...$this->counter($user, template: 'hxp_ghoul_aggression'),
            ],
        ];

        return $this->twig->render('partials/hooks/soul/hxp_earnings.html.twig', [
            'days' => $day_data,
            'pictos' => $picto_data,
            'others' => $other_data,
        ]);

	}
}