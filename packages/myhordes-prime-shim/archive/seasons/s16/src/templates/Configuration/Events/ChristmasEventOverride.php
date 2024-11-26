<?php

namespace MyHordes\Prime\Configuration\Events;

use App\Enum\DropMod;
use App\Event\Game\EventHooks\Christmas\NightlyEvent;
use App\Translation\T;
use MyHordes\Plugins\Interfaces\ConfigurationProviderInterface;
use MyHordes\Prime\Event\Game\EventHooks\Christmas\NightlyGift1Event;
use MyHordes\Prime\Event\Game\EventHooks\Christmas\NightlyGift2Event;
use MyHordes\Prime\Event\Game\EventHooks\Christmas\NightlyGift3Event;

class ChristmasEventOverride implements ConfigurationProviderInterface
{
    private const EventRangeBegin = '12-06 00:00';
    private const EventPhaseEnd1 = '12-18 00:00';
    private const EventPhaseEnd2 = '01-01 00:00';
    private const EventRangeEnd = '01-02 00:00';
    private const EventGiftDay1 = '12-24 00:00';
    private const EventGiftDay2 = '12-25 00:00';
    private const EventGiftDay3 = '01-01 00:00';

    public function data(): array
    {
        return [
            'christmas' => [
                'trigger' => [
                    'begin' => self::EventRangeBegin,
                    'end' => self::EventRangeEnd,
                ],
                'conf' => [
                    'mods' => null,
                    'dispatch' => null
                ]
            ],

            'christmas_sub_1' => [
                'trigger' => [
                    'type' => 'datetime',
                    'begin' => self::EventRangeBegin,
                    'end' => self::EventPhaseEnd1,
                ],
                'conf' => [
                    'name' => 'Weihnachten (Subevent 1)',
                    'mods' => [
                        'enable' => [ DropMod::EventChristmasAlt1->value ]
                    ]
                ]
            ],
            'christmas_sub_2' => [
                'trigger' => [
                    'type' => 'datetime',
                    'begin' => self::EventPhaseEnd1,
                    'end' => self::EventPhaseEnd2,
                ],
                'conf' => [
                    'name' => 'Weihnachten (Subevent 1)',
                    'mods' => [
                        'enable' => [ DropMod::EventChristmasAlt2->value ]
                    ]
                ]
            ],

            'christmas_gift_1' => [
                'trigger' => [
                    'type' => 'datetime',
                    'begin' => self::EventGiftDay1,
                ],
                'conf' => [
                    'name' => 'Weihnachten (Gift 1)',
                ],
                'dispatch' => [
                    'night_after' => NightlyGift1Event::class,
                    'night_none'  => NightlyGift1Event::class,
                ]
            ],
            'christmas_gift_2' => [
                'trigger' => [
                    'type' => 'datetime',
                    'begin' => self::EventGiftDay2,
                ],
                'conf' => [
                    'name' => 'Weihnachten (Gift 2)',
                ],
                'dispatch' => [
                    'night_after' => NightlyGift2Event::class,
                    'night_none'  => NightlyGift2Event::class,
                ]
            ],
            'christmas_gift_3' => [
                'trigger' => [
                    'type' => 'datetime',
                    'begin' => self::EventGiftDay3,
                ],
                'conf' => [
                    'name' => 'Weihnachten (Gift 3)',
                ],
                'dispatch' => [
                    'night_after' => NightlyGift3Event::class,
                    'night_none'  => NightlyGift3Event::class,
                ]
            ],
        ];
    }
}