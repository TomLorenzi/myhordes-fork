<?php

namespace MyHordes\Fixtures\Service;

use App\Entity\CouncilEntryTemplate;
use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class CouncilDataService implements FixtureProcessorInterface {

    public function process(array &$data, ?string $tag = null): void
    {
        $data = array_replace_recursive($data, [

            'shaman_root_first' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeRootShamanFirst, 'mode' => CouncilEntryTemplate::CouncilBranchModeStructured,
                'branches' => [
                    CouncilEntryTemplate::CouncilRootNodeGenericMCIntro,
                    CouncilEntryTemplate::CouncilRootNodeShamanIntroFirst,
                    CouncilEntryTemplate::CouncilRootNodeShamanFollowUpFirst,
                    CouncilEntryTemplate::CouncilRootNodeShamanBeginVoteAny,
                    CouncilEntryTemplate::CouncilRootNodeShamanVoteAny,
                    CouncilEntryTemplate::CouncilRootNodeShamanEndVoteAny,
                    CouncilEntryTemplate::CouncilRootNodeShamanStrawAny,
                    CouncilEntryTemplate::CouncilRootNodeShamanStrawResponseAny,
                    CouncilEntryTemplate::CouncilRootNodeShamanStrawFinalAny,
                    CouncilEntryTemplate::CouncilRootNodeShamanStrawResultAny,
                    CouncilEntryTemplate::CouncilRootNodeShamanStrawResultResponseAny,
                    CouncilEntryTemplate::CouncilRootNodeShamanFinalAny,
                ],
                'variables' => [ 'config' => [ '_mc_constraint' => ['from' => '_mc'], '_winner_constraint' => ['from' => '_winner'], '_council' => ['from' => '_council?'] ] ]
            ],

            'shaman_root_next' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeRootShamanNext, 'mode' => CouncilEntryTemplate::CouncilBranchModeStructured,
                'branches' => [
                    CouncilEntryTemplate::CouncilRootNodeGenericMCIntro,
                    CouncilEntryTemplate::CouncilRootNodeShamanIntroNext,
                    CouncilEntryTemplate::CouncilRootNodeShamanFollowUpNext,
                    CouncilEntryTemplate::CouncilRootNodeShamanBeginVoteAny,
                    CouncilEntryTemplate::CouncilRootNodeShamanVoteAny,
                    CouncilEntryTemplate::CouncilRootNodeShamanEndVoteAny,
                    CouncilEntryTemplate::CouncilRootNodeShamanStrawAny,
                    CouncilEntryTemplate::CouncilRootNodeShamanStrawResponseAny,
                    CouncilEntryTemplate::CouncilRootNodeShamanStrawFinalAny,
                    CouncilEntryTemplate::CouncilRootNodeShamanStrawResultAny,
                    CouncilEntryTemplate::CouncilRootNodeShamanStrawResultResponseAny,
                    CouncilEntryTemplate::CouncilRootNodeShamanFinalAny,
                ],
                'variables' => [ 'config' => [ '_mc_constraint' => ['from' => '_mc'], '_winner_constraint' => ['from' => '_winner'], '_council' => ['from' => '_council?'] ] ]
            ],

            'shaman_root_single' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeRootShamanSingle, 'mode' => CouncilEntryTemplate::CouncilBranchModeStructured,
                'branches' => [
                    CouncilEntryTemplate::CouncilRootNodeShamanIntroSingle,
                    CouncilEntryTemplate::CouncilRootNodeShamanFinalAny,
                ],
                'variables' => [ 'config' => [ '_winner_constraint' => ['from' => '_winner'] ] ]
            ],

            'shaman_root_none' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeRootShamanNone, 'mode' => CouncilEntryTemplate::CouncilBranchModeStructured,
                'branches' => [
                    CouncilEntryTemplate::CouncilRootNodeShamanIntroNone,
                ]
            ],

            'shaman_root_few' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeRootShamanFew, 'mode' => CouncilEntryTemplate::CouncilBranchModeStructured,
                'branches' => [
                    CouncilEntryTemplate::CouncilRootNodeShamanIntroFew,
                    CouncilEntryTemplate::CouncilRootNodeShamanIntroFew2,
                    CouncilEntryTemplate::CouncilRootNodeShamanStrawFew,
                    CouncilEntryTemplate::CouncilRootNodeShamanStrawResultAny,
                    CouncilEntryTemplate::CouncilRootNodeShamanStrawResultResponseAny,
                    CouncilEntryTemplate::CouncilRootNodeShamanFinalAny,
                ],
                'variables' => [ 'config' => [ '_mc_constraint' => ['from' => '_mc'], '_winner_constraint' => ['from' => '_winner'], '_council' => ['from' => '_council?'] ] ]
            ],

            'guide_root_first' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeRootGuideFirst, 'mode' => CouncilEntryTemplate::CouncilBranchModeStructured,
                'branches' => [
                    CouncilEntryTemplate::CouncilRootNodeGenericMCIntro,
                    CouncilEntryTemplate::CouncilRootNodeGuideIntroFirst,
                    CouncilEntryTemplate::CouncilRootNodeGuideFollowUpFirst,
                    CouncilEntryTemplate::CouncilRootNodeGuideBeginVoteAny,
                    CouncilEntryTemplate::CouncilRootNodeGuideVoteAny,
                    CouncilEntryTemplate::CouncilRootNodeGuideEndVoteAny,
                    CouncilEntryTemplate::CouncilRootNodeGuideStrawAny,
                    CouncilEntryTemplate::CouncilRootNodeGuideStrawResponseAny,
                    CouncilEntryTemplate::CouncilRootNodeGuideStrawFinalAny,
                    CouncilEntryTemplate::CouncilRootNodeGuideStrawResultAny,
                    CouncilEntryTemplate::CouncilRootNodeGuideStrawResultResponseAny,
                    CouncilEntryTemplate::CouncilRootNodeGuideFinalAny,
                ],
                'variables' => [ 'config' => [ '_mc_constraint' => ['from' => '_mc'], '_winner_constraint' => ['from' => '_winner'], '_council' => ['from' => '_council?'] ] ]
            ],

            'guide_root_next' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeRootGuideNext, 'mode' => CouncilEntryTemplate::CouncilBranchModeStructured,
                'branches' => [
                    CouncilEntryTemplate::CouncilRootNodeGenericMCIntro,
                    CouncilEntryTemplate::CouncilRootNodeGuideIntroNext,
                    CouncilEntryTemplate::CouncilRootNodeGuideFollowUpNext,
                    CouncilEntryTemplate::CouncilRootNodeGuideBeginVoteAny,
                    CouncilEntryTemplate::CouncilRootNodeGuideVoteAny,
                    CouncilEntryTemplate::CouncilRootNodeGuideEndVoteAny,
                    CouncilEntryTemplate::CouncilRootNodeGuideStrawAny,
                    CouncilEntryTemplate::CouncilRootNodeGuideStrawResponseAny,
                    CouncilEntryTemplate::CouncilRootNodeGuideStrawFinalAny,
                    CouncilEntryTemplate::CouncilRootNodeGuideStrawResultAny,
                    CouncilEntryTemplate::CouncilRootNodeGuideStrawResultResponseAny,
                    CouncilEntryTemplate::CouncilRootNodeGuideFinalAny,
                ],
                'variables' => [ 'config' => [ '_mc_constraint' => ['from' => '_mc'], '_winner_constraint' => ['from' => '_winner'], '_council' => ['from' => '_council?'] ] ]
            ],

            'guide_root_single' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeRootGuideSingle, 'mode' => CouncilEntryTemplate::CouncilBranchModeStructured,
                'branches' => [
                    CouncilEntryTemplate::CouncilRootNodeGuideIntroSingle,
                    CouncilEntryTemplate::CouncilRootNodeShamanFinalAny,
                ],
                'variables' => [ 'config' => [ '_winner_constraint' => ['from' => '_winner'] ] ]
            ],

            'guide_root_none' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeRootGuideNone, 'mode' => CouncilEntryTemplate::CouncilBranchModeStructured,
                'branches' => [
                    CouncilEntryTemplate::CouncilRootNodeGuideIntroNone,
                ]
            ],

            'guide_root_few' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeRootGuideFew, 'mode' => CouncilEntryTemplate::CouncilBranchModeStructured,
                'branches' => [
                    CouncilEntryTemplate::CouncilRootNodeGuideIntroFew,
                    CouncilEntryTemplate::CouncilRootNodeGuideIntroFew2,
                    CouncilEntryTemplate::CouncilRootNodeGuideStrawFew,
                    CouncilEntryTemplate::CouncilRootNodeGuideStrawResultAny,
                    CouncilEntryTemplate::CouncilRootNodeGuideStrawResultResponseAny,
                    CouncilEntryTemplate::CouncilRootNodeGuideFinalAny,
                ],
                'variables' => [ 'config' => [ '_mc_constraint' => ['from' => '_mc'], '_winner_constraint' => ['from' => '_winner'], '_council' => ['from' => '_council?'] ] ]
            ],

            'generic_root_mc_intro' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeGenericMCIntro, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branches' => [CouncilEntryTemplate::CouncilNodeGenericMCIntro]
            ],

            'shaman_root_intro_few2' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanIntroFew2, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branches' => [CouncilEntryTemplate::CouncilNodeGenericIntroFew]
            ],

            'shaman_root_intro_first' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanIntroFirst, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branches' => [CouncilEntryTemplate::CouncilNodeShamanIntroFirst]
            ],

            'shaman_root_intro_next' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanIntroNext, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branches' => [CouncilEntryTemplate::CouncilNodeShamanIntroNext]
            ],

            'shaman_root_intro_single' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanIntroSingle, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branches' => [CouncilEntryTemplate::CouncilNodeShamanIntroSingle]
            ],

            'shaman_root_intro_none' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanIntroNone, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branches' => [CouncilEntryTemplate::CouncilNodeShamanIntroNone]
            ],

            'shaman_root_intro_few' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanIntroFew, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branches' => [CouncilEntryTemplate::CouncilNodeShamanIntroFew]
            ],

            'shaman_root_follow_up_first' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanIntroFirst, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branch_count' => 2, 'branches' => [CouncilEntryTemplate::CouncilNodeShamanFollowUpFirst,CouncilEntryTemplate::CouncilNodeShamanFollowUpAny,CouncilEntryTemplate::CouncilNodeGenericFollowUpAny]
            ],

            'shaman_root_follow_up_next' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanIntroNext, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branch_count' => 2, 'branches' => [CouncilEntryTemplate::CouncilNodeShamanFollowUpNext,CouncilEntryTemplate::CouncilNodeShamanFollowUpAny,CouncilEntryTemplate::CouncilNodeGenericFollowUpAny]
            ],

            'shaman_root_begin_vote' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanBeginVoteAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branches' => [CouncilEntryTemplate::CouncilNodeShamanBeginVoteAny,CouncilEntryTemplate::CouncilNodeGenericBeginVoteAny]
            ],

            'shaman_root_vote' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanVoteAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branch_count' => [2,10], 'branches' => [CouncilEntryTemplate::CouncilNodeShamanVoteAny,CouncilEntryTemplate::CouncilNodeGenericVoteAny]
            ],

            'shaman_root_end_vote' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanEndVoteAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branches' => [CouncilEntryTemplate::CouncilNodeShamanEndVoteAny,CouncilEntryTemplate::CouncilNodeGenericEndVoteAny]
            ],

            'shaman_root_straw' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanStrawAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branches' => [CouncilEntryTemplate::CouncilNodeShamanStrawAny,CouncilEntryTemplate::CouncilNodeGenericStrawAny]
            ],

            'shaman_root_straw_few' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanStrawFew, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branches' => [CouncilEntryTemplate::CouncilNodeShamanStrawFew,CouncilEntryTemplate::CouncilNodeGenericStrawFew]
            ],

            'shaman_root_straw_response' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanStrawResponseAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branch_count' => [1,2], 'branches' => [CouncilEntryTemplate::CouncilNodeShamanStrawResponseAny,CouncilEntryTemplate::CouncilNodeGenericStrawResponseAny]
            ],

            'shaman_root_straw_final' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanStrawFinalAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branches' => [CouncilEntryTemplate::CouncilNodeShamanStrawFinalAny,CouncilEntryTemplate::CouncilNodeGenericStrawFinalAny]
            ],

            'shaman_root_straw_result' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanStrawResultAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branches' => [CouncilEntryTemplate::CouncilNodeShamanStrawResultAny]
            ],

            'shaman_root_straw_result_response' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanStrawResultResponseAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branch_count' => [1,3], 'branches' => [CouncilEntryTemplate::CouncilNodeShamanStrawResultResponseAny,CouncilEntryTemplate::CouncilNodeGenericStrawResultResponseAny]
            ],

            'shaman_root_final' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanFinalAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branches' => [CouncilEntryTemplate::CouncilNodeShamanFinalAny]
            ],

            'guide_root_intro_few2' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideIntroFew2, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branches' => [CouncilEntryTemplate::CouncilNodeGenericIntroFew]
            ],

            'guide_root_intro_first' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideIntroFirst, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branches' => [CouncilEntryTemplate::CouncilNodeGuideIntroFirst]
            ],

            'guide_root_intro_next' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideIntroNext, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branches' => [CouncilEntryTemplate::CouncilNodeGuideIntroNext]
            ],

            'guide_root_intro_single' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideIntroSingle, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branches' => [CouncilEntryTemplate::CouncilNodeGuideIntroSingle]
            ],

            'guide_root_intro_none' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideIntroNone, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branches' => [CouncilEntryTemplate::CouncilNodeGuideIntroNone]
            ],

            'guide_root_intro_few' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideIntroFew, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branches' => [CouncilEntryTemplate::CouncilNodeGuideIntroFew]
            ],

            'guide_root_follow_up_first' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideIntroFirst, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branch_count' => 2, 'branches' => [CouncilEntryTemplate::CouncilNodeGuideFollowUpFirst,CouncilEntryTemplate::CouncilNodeGuideFollowUpAny,CouncilEntryTemplate::CouncilNodeGenericFollowUpAny]
            ],

            'guide_root_follow_up_next' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideIntroNext, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branch_count' => 2, 'branches' => [CouncilEntryTemplate::CouncilNodeGuideFollowUpNext,CouncilEntryTemplate::CouncilNodeGuideFollowUpAny,CouncilEntryTemplate::CouncilNodeGenericFollowUpAny]
            ],

            'guide_root_begin_vote' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideBeginVoteAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branches' => [CouncilEntryTemplate::CouncilNodeGuideBeginVoteAny,CouncilEntryTemplate::CouncilNodeGenericBeginVoteAny]
            ],

            'guide_root_vote' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideVoteAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branch_count' => [2,10], 'branches' => [CouncilEntryTemplate::CouncilNodeGuideVoteAny,CouncilEntryTemplate::CouncilNodeGenericVoteAny]
            ],

            'guide_root_end_vote' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideEndVoteAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branches' => [CouncilEntryTemplate::CouncilNodeGuideEndVoteAny,CouncilEntryTemplate::CouncilNodeGenericEndVoteAny]
            ],

            'guide_root_straw' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideStrawAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branches' => [CouncilEntryTemplate::CouncilNodeGuideStrawAny,CouncilEntryTemplate::CouncilNodeGenericStrawAny]
            ],

            'guide_root_straw_few' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideStrawFew, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branches' => [CouncilEntryTemplate::CouncilNodeGuideStrawFew,CouncilEntryTemplate::CouncilNodeGenericStrawFew]
            ],

            'guide_root_straw_response' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideStrawResponseAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branch_count' => [1,2], 'branches' => [CouncilEntryTemplate::CouncilNodeGuideStrawResponseAny,CouncilEntryTemplate::CouncilNodeGenericStrawResponseAny]
            ],

            'guide_root_straw_final' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideStrawFinalAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branches' => [CouncilEntryTemplate::CouncilNodeGuideStrawFinalAny,CouncilEntryTemplate::CouncilNodeGenericStrawFinalAny]
            ],

            'guide_root_straw_result' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideStrawResultAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branches' => [CouncilEntryTemplate::CouncilNodeGuideStrawResultAny]
            ],

            'guide_root_straw_result_response' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideStrawResultResponseAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branch_count' => [1,3], 'branches' => [CouncilEntryTemplate::CouncilNodeGuideStrawResultResponseAny,CouncilEntryTemplate::CouncilNodeGenericStrawResultResponseAny]
            ],

            'guide_root_final' => [
                'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideFinalAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branches' => [CouncilEntryTemplate::CouncilNodeGuideFinalAny]
            ],

            'generic_follow_up_any_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericFollowUpAny,
                'text' => 'Wieso darf hier überhaupt {mc} die Leitung übernehmen? Kann mir das mal jemand erklären?', //  Why's [MC] get to run the show anyway? That's what I wanna know!
                'variables' => [ 'types' => [['type'=>"citizen", 'name'=>'mc']], 'config' => [ 'main' => ['from' => '_council?'], 'mc' => ['from' => '_mc'] ] ]
            ],
            'generic_follow_up_any_002' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericFollowUpAny,
                'text' => 'Warum hat dieser nichtsnutzige {mc} überhaupt das Sagen!?', // Why's that no good [MC] running the show anyway!?
                'variables' => [ 'types' => [['type'=>"citizen", 'name'=>'mc']], 'config' => [ 'main' => ['from' => '_council?'], 'mc' => ['from' => '_mc'] ] ]
            ],

            'generic_follow_up_any_q_response_001' => [
                'text' => 'Noob!', // Noob!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],

            'generic_begin_vote_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericBeginVoteAny,
                'text' => 'Keine Freiwilligen?', // No volunteers?
                'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
            ],
            'generic_begin_vote_002' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericBeginVoteAny,
                'text' => 'Ein Versuch kann ja nicht schaden, vielleicht ist hier jemand wirklich verrückt genug dafür?', // Well it can't hurt to try, there might be someone crazy enough out there?
                'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
            ],
            'generic_begin_vote_003' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericBeginVoteAny,
                'text' => 'Okay... Freiwillige vor?', // Soooo... Any volunteers?
                'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
            ],
            'generic_begin_vote_004' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericBeginVoteAny,
                'text' => 'Echt jetzt?!?', // Seriously?!?
                'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
            ],
            'generic_begin_vote_005' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericBeginVoteAny,
                'text' => 'Irgendwer?', // Anyone?
                'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
            ],

            'generic_mc_intro_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericMCIntro,
                'text' => 'Hallo zusammen, wir müssen das hier über die Bühne bringen, also ähem, melde ich mich mehr oder weniger freiwillig als Zeremonienmeister...', // Hey there everyone, we need to get this show on the road so errrr, I relucantly volunteer as MC...
                'variables' => [ 'config' => [ 'main' => ['from' => '_mc', 'flags' => [ 'same_mc' => false ]] ] ]
            ],
            'generic_mc_intro_002' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericMCIntro,
                'text' => 'Da Respekt für eine effektive Kommunikation unerlässlich ist, werde ich hier der Zeremonienmeister sein.', // Respect being essential to effective communication, I'll be the master of ceremonies around here.
                'variables' => [ 'config' => [ 'main' => ['from' => '_mc', 'flags' => [ 'same_mc' => false ]] ] ]
            ],
            'generic_mc_intro_003' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericMCIntro,
                'text' => 'Hört an, hört an!', // Hear ye hear ye!
                'variables' => [ 'config' => [ 'main' => ['from' => '_mc', 'flags' => [ 'same_mc' => false ]] ] ]
            ],
            'generic_mc_intro_004' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericMCIntro,
                'text' => 'Hm hm ... Ich will ja kein Spielverderber sein, aber wir sind noch nicht wirklich fertig...', // Hum hum... Je veux pas faire le rabat joie, mais on en a pas vraiment terminé encore...
                'variables' => [ 'config' => [ 'main' => ['from' => '_mc', 'flags' => [ 'same_mc' => true ]] ] ]
            ],

            'generic_intro_few_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericIntroFew, 'vocal' => false,
                'text' => 'Allen steht die Erschöpfung ins Gesicht geschrieben.', // Fatigue on the other hand is front and center...
            ],
            // THIS IS AN INTENTIONAL DUPLICATE! DO NOT REMOVE IT!
            'generic_intro_few_002' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericIntroFew, 'vocal' => false,
                'text' => 'Allen steht die Erschöpfung ins Gesicht geschrieben.', // Fatigue on the other hand is front and center...
            ],

            'generic_vote_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branch_count' => [0,2], 'branches' => [CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny],
                'text' => 'Ich denke, {voted} sollte das übernehmen, dann hätte er etwas zu tun! Was meint ihr?', //  I reckon it should be Jensine, that'd give him something to do! What' you guys think?
                'variables' => [ 'types' => [['type'=>"citizen", 'name'=>'voted']], 'config' => [ 'main' => ['from' => '_council?'], 'voted' => ['from' => 'voted', 'consume' => true] ] ]
            ],
            'generic_vote_002' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branch_count' => [0,2], 'branches' => [CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny],
                'text' => 'Ich stimme für {voted}.', // I vote for Rammas
                'variables' => [ 'types' => [['type'=>"citizen", 'name'=>'voted']], 'config' => [ 'main' => ['from' => '_council?'], 'voted' => ['from' => 'voted', 'consume' => true] ] ]
            ],
            'generic_vote_003' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteAny,
                'text' => 'Lasst uns dieses langweilige Ritual abschaffen und einfach mich auswählen! Ihr wisst alle, dass ich der perfekte Kandidat bin!', // Let's do away with this borning ritual and just pick me! You all know I'm the perfect candidate!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_004' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteAny,
                'text' => 'Ich ich ich ich ich ich!', //  Me me me me me me!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_005' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteAny,
                'text' => 'Wie spät ist es eigentlich?', // Has anyone got the time?
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_006' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteAny,
                'text' => 'Können wir jetzt endlich jemanden auswählen? Ich will mich hinhauen!', // Can we just choose already? I wanna hit the hay!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_007' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteAny,
                'text' => 'Wenn wir diesen Raum neu dekorieren würden...', // You know if we re-did the decorations in this room...
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_008' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteAny,
                'text' => 'Ich hab Hunger!', // I'm hungry!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_009' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteAny,
                'text' => 'Und was wäre, wenn wir wie üblich Strohhalme ziehen würden?', // And what about if we just drew straws like usual?
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_010' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteAny,
                'text' => 'Hört auf, mich anzugucken!', //  Stop looking!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_011' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteAny,
                'text' => 'Hat jemand gerade einen Jugendlichen vorbeigehen sehen?', // Did someone just see a a youngster go past?
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],

            'generic_vote_response_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
                'text' => '+1 !', //  +1 !
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_response_002' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
                'text' => 'Er stinkt nach Alkohol.', // He stinks of alcohol
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_response_003' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
                'text' => 'Er stinkt wie die Rückseite einer...', // He stinks like the backside of a...
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_response_004' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
                'text' => 'Ich vertraue ihm nicht.', // I don't trust him.
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_response_005' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
                'text' => 'Na das überrascht mich ja mal so gar nicht!', // Well now that doesn't surprise me one bit!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_response_006' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
                'text' => 'Nun, ich stimme zu, dieser Job würde sehr gut zu ihm passen.', // Well for me I agree, that job would suit him down to the ground.
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_response_007' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
                'text' => 'Ach komm schon!', // Oh man, come on!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_response_008' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
                'text' => 'Wir könnten ja auch warten, bis sich jemand anderes freiwillig meldet...', // We could always wait for someone else to volunteer...
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_response_009' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
                'text' => 'Es gehört sich nicht für andere zu sprechen, {parent}.', // It ain't nice to speak for others Acedia.
                'variables' => [ 'types' => [['type'=>"citizen", 'name'=>'parent']], 'config' => [ 'main' => ['from' => '_council?'], 'parent' => ['from' => '_parent'] ] ]
            ],
            'generic_vote_response_010' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
                'text' => 'Typisch für dich {parent}, immer jemand anderen vorschicken!', // Now that's just typical you Sagittaeri, always calling out someone else!
                'variables' => [ 'types' => [['type'=>"citizen", 'name'=>'parent']], 'config' => [ 'main' => ['from' => '_council?'], 'parent' => ['from' => '_parent'] ] ]
            ],
            'generic_vote_response_011' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
                'text' => 'Ja, ich habe genug von seinen Spielchen! Hängen wir ihn auf!', // Yeah I'm sick of his horseplay! Let's string him up!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_response_012' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
                'text' => 'Muss ich darauf antworten?', // Do I have to respond to that?
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_response_013' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
                'text' => 'Du warst schon immer feige! Hinter dem Rücken der anderen reden und so!', // Do I have to respond to that?
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_response_014' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
                'text' => 'Ich habe nicht wirklich eine Meinung, aber ich werde trotzdem meinen Senf dazu geben!', // I don't really have an opinion, but I'm gonna share my 2 cents anyway!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_response_015' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
                'text' => 'Solange nicht ich ausgewählt werde, bin ich zufrieden.', //  As long as y'all don't pick me I'm happy!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_response_016' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
                'text' => 'Ich möchte euch sagen, wie wenig mich das alles interessiert, aber ich habe keine Lust dazu.', //  I kind of feel like telling you all just how little I care about this, but I can't be bothered.
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_response_017' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
                'text' => 'Es gibt nichts Schlimmeres, als einen Traum zu verfolgen, der nie in Erfüllung geht.', //  There's nothing worse than pursuing a dream that never comes to fruition.
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_response_018' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
                'text' => 'Solange ich das nicht bin geht mir das sowas von am Arsch vorbei!', //  As long as it ain't me, I don't give a damn!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_response_019' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
                'text' => 'Solange ich das nicht bin, ist mir das völlig egal!', // As long as it's not me I don't give two hoots!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_response_020' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
                'text' => 'Also, ich fange an zu glauben, dass es mir eigentlich egal ist...', // Yeah I'm starting to think that I don't really give a damn...
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_response_021' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
                'text' => 'Ja, aber es ist entweder er oder jemand anderes.', // Yeah, but it's him or someone else.
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_response_022' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
                'text' => 'Yeah!', // Yeah!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_response_023' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
                'text' => 'Guter Witz!', // That's a good one!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_response_024' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
                'text' => 'looooooooool', // looooooooool
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_response_025' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
                'text' => 'Ach, jetzt bist du einfach nur gemein. Meinst du nicht, dass er es schon schwer genug hat, wenn er so behindert ist und so?!', // Oh now you're just being mean. Don't you think he's got it bad enough handicapped like that and all?!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_response_026' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
                'text' => 'Also meiner Meinung nach passt das gut zu ihm.', //  Yeah, that suits him, in my humble opinion...
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_response_027' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
                'text' => 'Lasst uns hiermit nicht herumalbern!', // No way, don't mess around with this!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],

            'generic_vote_end_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericEndVoteAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branch_count' => 1, 'branches' => [CouncilEntryTemplate::CouncilNodeGenericEndVoteResponseA],
                'text' => 'Ich habe einseitig beschlossen, dass wir hier keine Zeit mehr damit verschwenden werden, über etwas völlig Unwichtiges zu streiten.', // I have decided unilaterally, that we're not going to waste any more time arguing over something of singular unimportance.
                'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
            ],
            'generic_vote_end_002' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericEndVoteAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branch_count' => 1, 'branches' => [CouncilEntryTemplate::CouncilNodeGenericEndVoteResponseA],
                'text' => 'Es ist jedes Mal dasselbe! Können wir nicht einfach schnell und in Ruhe eine Entscheidung treffen, ohne ihne dass alles in einem Debakel endet?', // Every time it's the same! Can't we just make a decision quickly and quietly without it turning into a debacle?
                'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
            ],

            'generic_vote_end_response_a_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericEndVoteResponseA, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branch_count' => 1, 'branches' => [CouncilEntryTemplate::CouncilNodeGenericEndVoteResponseB],
                'text' => 'Die Hoffnung stirbt zuletzt...', // Hope is the last thing to die...
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_end_response_a_002' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericEndVoteResponseA, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branch_count' => 1, 'branches' => [CouncilEntryTemplate::CouncilNodeGenericEndVoteResponseB],
                'text' => 'Der Traum von einem organisierten Treffen ist der sprichwörtliche Topf voll Gold!', // The dream of an organised meeting is the proverbial pot of gold!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_end_response_a_003' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericEndVoteResponseA, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branch_count' => 1, 'branches' => [CouncilEntryTemplate::CouncilNodeGenericEndVoteResponseB],
                'text' => 'Diejenigen von euch, die nach uns kommen, verhärtet nicht eure Herzen gegen uns...', // Those of you who will come after, harden not your hearts against us...
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_end_response_a_004' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericEndVoteResponseA, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branch_count' => 1, 'branches' => [CouncilEntryTemplate::CouncilNodeGenericEndVoteResponseB],
                'text' => 'Wer keine Hoffnung mehr hat, kann auch nichts mehr bereuen.', //  He who has no more hope has no more regrets.
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],

            'generic_vote_end_response_b_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericEndVoteResponseB,
                'text' => 'Ja, halt die Klappe, Shakespeare.', // Yeah, pipe down Shakespeare
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_end_response_b_002' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericEndVoteResponseB,
                'text' => 'Trottel.', // Nincompoop
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_vote_end_response_b_003' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericEndVoteResponseB,
                'text' => 'Zum Galgen mit ihm!', // Quick! To the gallows!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],

            'generic_straw_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericStrawAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branch_count' => 1, 'branches' => [CouncilEntryTemplate::CouncilNodeGenericStrawInitAny],
                'text' => 'Nimm einfach einen Strohhalm und bring es hinter dich!', // Just pick a straw and get it over with already!
                'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
            ],
            'generic_straw_002' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericStrawAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branch_count' => 1, 'branches' => [CouncilEntryTemplate::CouncilNodeGenericStrawInitAny],
                'text' => 'Ja, ja, jetzt gib mir schon einen verfluchten Strohhalm!', //  Yeah yeah, gimme a stinking straw already!
                'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
            ],
            'generic_straw_003' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericStrawAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branch_count' => 1, 'branches' => [CouncilEntryTemplate::CouncilNodeGenericStrawInitAny],
                'text' => 'Ok, lasst uns wie immer Strohhalme ziehen und die Sache hinter uns bringen.', //  Ok let's draw straws as usual and get this done.
                'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
            ],

            'generic_straw_few_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericStrawFew,
                'text' => 'Wir ziehen Strohhalme, wie wir es immer machen. Der Kürzeste gewinnt. Kommt schon, kommt und zieht einen Stohhalm.', // We'll do the straw like we usually do. Shorts straw gets it. Come on now, come pick a straw.
                'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
            ],

            'generic_straw_init_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericStrawInitAny,
                'text' => 'Ok, jeder nimmt sich einen Strohhalm.', // Ok everyone come take a straw.
                'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
            ],
            // THIS IS AN INTENTIONAL DUPLICATE! DO NOT REMOVE IT!
            'generic_straw_init_002' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericStrawInitAny,
                'text' => 'Ok, jeder nimmt sich einen Strohhalm.', // Ok everyone come take a straw.
                'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
            ],

            'generic_straw_response_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResponseAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branch_count' => 1, 'branches' => ['generic_straw_response_001_r001','generic_straw_response_001_r002'],
                'text' => 'Warum werfen wir zur Abwechslung nicht mal eine Münze?', //  Why don't we toss a coin for a change?
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_straw_response_001_r001' => [
                'text' => 'Klar Einstein, Kopf oder Zahl zm eine Persion aus {_voted} auszuwählen.', // Yeah genius, heads or tales to pick 1 person out of 34.
                'variables' => [ 'types' => [['type'=>"num", 'name'=>'_voted']], 'config' => [ 'main' => ['from' => '_council?'], '_constraint_vote3_1' => ['from' => '_voted'], '_constraint_vote3_2' => ['from' => '_voted'], '_constraint_vote3_3' => ['from' => '_voted'] ] ]
            ],
            'generic_straw_response_001_r002' => [
                'text' => 'Hast du denn eine Münze?', // Have you got a coin?
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],

            'generic_straw_response_002' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResponseAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branch_count' => 2, 'branches' => ['generic_straw_response_002_r001','generic_straw_response_002_r002'],
                'text' => 'Wir wählen aus wer gefressen wird, richtig?', // So we're choosing who's gonna be eaten right?
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_straw_response_002_r001' => [
                'text' => 'Facepalm!', //  Facepalm!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_straw_response_002_r002' => [
                'text' => 'Echt jetzt?', // Seriously?
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],

            'generic_straw_response_003' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResponseAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branch_count' => 1, 'branches' => ['generic_straw_response_003_r001','generic_straw_response_003_r002','generic_vote_end_response_b_002'],
                'text' => 'Und was machen wir bei einem Gleichstand?', // And if it's a draw then what?
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_straw_response_003_r001' => [
                'text' => 'Wir hängen beide und fangen nochmal von vorne an!?', // We'll hang 'em both and start again!?
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_straw_response_003_r002' => [
                'text' => 'Komm, lass gut sein.', // Get outta here!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],

            'generic_straw_response_004' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResponseAny,
                'text' => 'Oh Mann! Der Einstein hier drüben hat seinen Strohhalm gefressen!', //  Oh man! Einstein over there has already eaten his straw!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],

            'generic_straw_response_005' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResponseAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeStructured,
                'branch_count' => 2, 'branches' => ['generic_straw_response_005_r001','generic_straw_response_005_r002'],
                'text' => 'Kann ich meinen Strohhalm essen wenn wir fertig sind?', // Can I eat my straw once we're done?
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_straw_response_005_r001' => [
                'text' => 'Wo hat er hier überhaupt Stroh her?', // Where'd he get some straw from anyway?
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_straw_response_005_r002' => [
                'text' => '...Wer sagt, dass das Stroh ist?...', // ...Who says it's straw?...
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],

            'generic_straw_response_006' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResponseAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branch_count' => 1, 'branches' => ['generic_straw_response_006_r001','generic_straw_response_006_r002'],
                'text' => 'Ihr wisst, dass ... nun, .... wie ich schon sagte ... ahhhh, auf mich hört sowieso niemand.', // You know that... well .... as I was saying ... ahhhh, nobody listens to me anyway.
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_straw_response_006_r001' => [
                'text' => 'Hat jemand etwas gesagt?', // Did someone say something?
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_straw_response_006_r002' => [
                'text' => 'Was?', // What's that?
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],

            'generic_straw_final_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericStrawFinalAny, 'vocal' => false,
                'text' => '...das Ziehen der Strohhalme findet in jugendlicher Unordnung statt, wobei jeder seinen Strohhalm mit dem des Nachbarn vergleicht...', // ...the drawing of the straws takes place in juvenile disorder, each person comparing their straw with the person beside them...
            ],
            'generic_straw_final_002' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericStrawFinalAny, 'vocal' => false,
                'text' => '...', // ...
            ],
            'generic_straw_final_003' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericStrawFinalAny, 'vocal' => false,
                'text' => '...Und so beginnt das Strohhalmziehen...', // ...And so the drawing of the straw ensues...
            ],

            'generic_straw_result_response_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericStrawResultResponseAny,
                'text' => 'Er war sowieso schon ziemlich seltsam...', // He was plenty weird to start with..
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_straw_result_response_002' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericStrawResultResponseAny,
                'text' => 'Pfffffff', // Pfffffff
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_straw_result_response_003' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericStrawResultResponseAny,
                'text' => 'Solange er zumindest ein paar Tage durchhält... Ich möchte dieses Treffen nicht jeden Morgen wiederholen müssen!', //  As long as he lasts a couple of days... I don't want to have to redo this meeting every morning!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'generic_straw_result_response_004' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGenericStrawResultResponseAny,
                'text' => 'Ich wusste es!', // I knew it!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],

            'shaman_intro_first_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanIntroFirst,
                'text' => 'Der erste Punkt auf der Tagesordnung ist die Wahl eines neuen Scharlatans, ich meine, ähm, Schamanen! Ja, einen Schamanen... Ich meine, jede verzweifelte Stadt braucht einen Schamanen, nicht wahr?', // First order of business is electing a new charlatan, I mean, errr, Shaman! Yeah, a Shaman... I mean, every desperate town needs a Shaman don't it?
                'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
            ],

            'shaman_intro_next_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanIntroNext,
                'text' => 'Da unser Schamane heute Nacht von uns gegangen ist, müssen wir jetzt einen Ersatz wählen.', // Notre chaman nous ayant quitté cette nuit, il nous faut en choisir un autre dès à présent.
                'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
            ],
            'shaman_intro_next_002' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanIntroNext,
                'text' => 'Unser Schamane, den wir alle sehr, sehr, sehr vermissen (lacht), ist kürzlich verstorben. Ich beantrage, dass wir über einen Nachfolger abstimmen.', // Our dearly, dearly, dearly missed shaman (snorts of laughter) passed recently. I move that we vote on a replacement.
                'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
            ],
            'shaman_intro_next_003' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanIntroNext,
                'text' => 'Wir alle wissen ja, wie das enden wird, zumindest die meisten von uns... Wir sollten also einen neuen Schamanen wählen, damit er sich um unsere Seelen kümmern und den letzten ersetzen kann, der, wie ich sagen muss, ein verdammt guter Voodooman war!', // Now we all know how this gonna end, least most of us do... So T say we elect a new shaman so that he can take care of our souls and replace the last one, who was, I have to say, one hell of a voodooman!
                'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
            ],

            'shaman_intro_single_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanIntroSingle,
                'text' => 'Gut, alles was ich jetzt noch zu tun habe ist mich selbst zum Schamanen zu wählen. Endlich gibt es mal ein einstimmiges Ergebnis.', // Bon, il ne me reste plus qu'à m'élire Chaman, pour une fois qu'il y a unanimité !
                'variables' => [ 'config' => [ 'main' => ['from' => '_winner'] ] ]
            ],

            'shaman_intro_none_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanIntroNone, 'vocal' => false,
                'text' => 'Niemand ist mehr in der Stadt, keiner kann diese Versammlung halten, daher überspringen wir die Wahl zum Schamanen.', // There is noone left in town, noone to hold this assembly, so today we're just going to skip the election of the Shaman
            ],

            'shaman_intro_few_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanIntroFew,
                'text' => 'OK, es sind nicht mehr viele von uns übrig, also lasst uns das schnell hinter uns bringen.', // OK, there's not many of us left, so let's get this over and done with.
                'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
            ],

            'shaman_follow_up_any_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanFollowUpAny,
                'text' => 'Ich hau den Zombies ganz einfach die Schädel ein, egal ob mit oder ohne Schamanen!', // I'm gonna split some zombie skull with or without a shaman!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'shaman_follow_up_any_002' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanFollowUpAny,
                'text' => 'Ja, das sehe ich auch so. Ich meine, selbst wenn er als Hexendoktor nichts taugt, sehe ich ihn gerne in diesem lächerlichen Kostüm!', // Yeah I second that. I mean even if he's no good as a witchdoctor, I sure love seeing him in that stupid costume!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'shaman_follow_up_any_003' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanFollowUpAny,
                'text' => 'Aber wir brauchen einen Schamanen, das ist wichtig!', // But we need a shaman, it's important!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'shaman_follow_up_any_004' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanFollowUpAny,
                'text' => 'Er jagt mir eine Heidenangst ein!', // He scares the heebie-jeebies out of me!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'shaman_follow_up_any_005' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanFollowUpAny,
                'text' => 'Kommt schon, so verzweifelt sind wir noch nicht!', // Come on now, we're not so desperate yet!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'shaman_follow_up_any_006' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanFollowUpAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branch_count' => [1,2], 'branches' => [
                    'shaman_follow_up_any_q_response_001','shaman_follow_up_any_q_response_002','shaman_follow_up_any_q_response_003','shaman_follow_up_any_q_response_004',
                    'generic_follow_up_any_q_response_001'
                ],
                'text' => 'Was genau ist überhaupt ein Schamane?', // What the hell's a Shaman anyway?
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'shaman_follow_up_any_q_response_001' => [
                'text' => 'Ah, jetzt ist er durchgedreht. Die letzte Nacht auf der Wacht war wohl zu viel...', // Ahh he's fried! One too many nights on the watch...
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'shaman_follow_up_any_q_response_002' => [
                'text' => 'Er ist unser Vertreter im Jenseits und Herr über unsere verdammten Seelen!', // He's our representative with the beyond, the master of our damned souls!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'shaman_follow_up_any_q_response_003' => [
                'text' => 'Er ist eigentlich genau wie du, nur interessanter.', // He's just like you, but more interesting
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'shaman_follow_up_any_q_response_004' => [
                'text' => 'Er ist eigentlich genau wie du, nur nützlicher.', // He's just like you but more useful
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],

            'shaman_follow_up_next_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanFollowUpNext,
                'text' => 'Krasse Sache! Ein neuer Schamane!', // Hot damn! A new Shaman!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'shaman_follow_up_next_002' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanFollowUpNext,
                'text' => 'Super, eine neue Wahl eines Schamanen!', // Great, a new election of the Shaman job!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],

            'shaman_vote_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanVoteAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branch_count' => [0,2], 'branches' => [CouncilEntryTemplate::CouncilNodeShamanVoteResponseAny,CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny],
                'text' => 'Also ich schlage unseren Freund {voted} vor, der einfach soooo gerne redet.', // Well I propose that it be our dear [randomVotedPerson] because he just looooves talkin'.
                'variables' => [ 'types' => [['type'=>"citizen", 'name'=>'voted']], 'config' => [ 'main' => ['from' => '_council?'], 'voted' => ['from' => 'voted', 'consume' => true] ] ]
            ],
            'shaman_vote_002' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanVoteAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branch_count' => [0,2], 'branches' => [CouncilEntryTemplate::CouncilNodeShamanVoteResponseAny,CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny],
                'text' => 'Ich wusste schon immer, dass {voted} sich gerne verkleidet...', //  I always knew that [randomVotedPerson] liked playing dress up..
                'variables' => [ 'types' => [['type'=>"citizen", 'name'=>'voted']], 'config' => [ 'main' => ['from' => '_council?'], 'voted' => ['from' => 'voted', 'consume' => true] ] ]
            ],
            'shaman_vote_003' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanVoteAny,
                'text' => 'Kann man gleichzeitig Ghul und Schamane sein?', // Can we be a ghoul and the shaman at the same time?
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'shaman_vote_004' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanVoteAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branch_count' => [0,2], 'branches' => [CouncilEntryTemplate::CouncilNodeShamanVoteResponseAny,CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny],
                'text' => 'Wie wäre es mit {voted}? Er hat immerhin vorhergesehen, dass {previous} etwas dämliches sagen wird...', // Why not -Sieg ried-? He did predict that DefenestrateMe was gonna say something stupid...
                'variables' => [ 'types' => [['type'=>"citizen", 'name'=>'voted'],['type'=>"citizen", 'name'=>'previous']], 'config' => [ 'main' => ['from' => '_council?'], 'voted' => ['from' => 'voted', 'consume' => true], 'previous' => ['from' => '_siblings'] ] ]
            ],

            'shaman_vote_response_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanVoteResponseAny,
                'text' => 'Mit so einem Kopf wird er den bösen Blick auf sich ziehen!', // He's gonna attract the evil eye with a head like that!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],

            'shaman_vote_end_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanEndVoteAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branch_count' => 1, 'branches' => [CouncilEntryTemplate::CouncilNodeGenericEndVoteResponseA],
                'text' => 'Ruhe! Wir müssen dieses Treffen zum Ende bringen! Immerhin haben wir eine Stadt zu verteidigen! Der Schamane ist sowieso nur hier, um uns Hoffnung zu machen', // Silence!  We've got to finish this meeting! We've got a town to defend! The shaman's only here to keep our hopes up anyway.
                'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
            ],

            'shaman_straw_result_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResultAny,
                'text' => 'Nun, da das erledigt ist, haben wir einen neuen Schamanen bekommen: {winner}!', //  Well now it's done and dusted, we've got ourselves a new Shaman: [Shaman]!
                'variables' => [ 'types' => [['type'=>"citizen", 'name'=>'winner']], 'config' => [ 'main' => ['from' => '_mc'], 'winner' => ['from' => '_winner'] ] ]
            ],

            'shaman_straw_result_response_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResultResponseAny,
                'text' => 'Alles in allem wäre {voted} im Nachhinein betrachtet vielleicht eine bessere Wahl gewesen.', // All things seen in hindsight maybe [randomVotedPerson] was a good choice.
                'variables' => [ 'types' => [['type'=>"citizen", 'name'=>'voted']], 'config' => [ 'main' => ['from' => '_council?'], 'voted' => ['from' => 'voted', 'consume' => true] ] ]
            ],
            'shaman_straw_result_response_002' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResultResponseAny,
                'text' => 'Eigentlich wollte ich Schamane werden!', // Actually, I wanted to be the Shaman!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'shaman_straw_result_response_003' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResultResponseAny,
                'text' => 'Oh Mann, wir sind komplett im Arsch!', // Oh man we're so screwed!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'shaman_straw_result_response_004' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResultResponseAny,
                'text' => 'Pff, der überlebt die Nacht doch eh nicht....', // Bah, he won't make it through the night....
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'shaman_straw_result_response_005' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResultResponseAny,
                'text' => 'Lasst uns ihn aufhängen! Wer macht mit!?', // Let's hang him! Who's with me!?
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'shaman_straw_result_response_006' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResultResponseAny,
                'text' => 'Wie buchstabiert man das eigentlich: Schamane oder Schamahne?', // How do you spell that anyway : chaman ou shaman?
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'shaman_straw_result_response_007' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResultResponseAny,
                'text' => 'Schaut doch mal, wie er schon jetzt angezogen ist... Wir werden den Unterschied gar nicht merken!', // Look at how he dresses already... We're not gonna notice the difference!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'shaman_straw_result_response_008' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResultResponseAny,
                'text' => 'Wenn wir so wählen, bist du der nächste Kandidat...', // Well if that's how we're choosing, you're shaping up as the next candidate...
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'shaman_straw_result_response_009' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResultResponseAny,
                'text' => 'Mann, ich wollte die Vorstellung übernehmen!', // Man I was doing the introduction!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'shaman_straw_result_response_010' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResultResponseAny,
                'text' => 'Leck mich doch!', // Get out of here!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'shaman_straw_result_response_011' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResultResponseAny,
                'text' => 'Gepriesen sei der Schamane!', // Blessed be the Shaman!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'shaman_straw_result_response_012' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResultResponseAny,
                'text' => 'Was ist ein Schaaaar Maaane?', // What's a shaaar maaan?
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],

            'shaman_final_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeShamanFinalAny, 'vocal' => false,
                'text' => '{winner} ist zum Schamanen gewählt worden, seine zweifelhaften schamanischen Kräfte treten sofort in Kraft. Hoffentlich können sie den Bewohnern der Stadt helfen, ihr erbärmliches Schicksal zu meistern.', // Peter has been elected as the Shaman, their dubious shamanic powers take effect immeadiately. Let's hope they can help the townsfolk improve their wretched lot in life.
                'variables' => [ 'types' => [['type'=>"citizen", 'name'=>'winner']], 'config' => [ 'winner' => ['from' => '_winner'] ] ]
            ],

            'guide_intro_first_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGuideIntroFirst,
                'text' => 'Wir brauchen einen neuen Reiseleiter für die Außenwelt im Eiltempo!', // We need a new Guide to the World Beyond on the double!
                'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
            ],

            'guide_intro_next_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGuideIntroNext,
                'text' => 'Da unser geliebter Reiseleiter in der Außenwelt uns heute Nacht verlassen hat, müssen wir ab sofort einen neuen wählen.', // Notre Guide de l'Outre-Monde aimé nous ayant quitté cette nuit, il nous faut en choisir un autre dès à présent.
                'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
            ],
            'guide_intro_next_002' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGuideIntroNext,
                'text' => 'Mit Traurigkeit und einem gewissen Sinn für Ironie beklagen wir den Verlust unseres Reiseleiters...', // It's with sadness, and a certain sense of irony that we lament the loss of our Guide...
                'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
            ],
            'guide_intro_next_003' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGuideIntroNext,
                'text' => 'Unser Reiseleiter hat sich gestern Abend irgendwie verlaufen und kommt nicht mehr zurück! Wer möchte ihn also ersetzen?', // Our Guide somehow managed to get lost last night, and he ain't coming back! So who wants to replace him?
                'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
            ],

            'guide_intro_single_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGuideIntroSingle,
                'text' => 'Gut, wenn niemand etwas dagegen einzuwenden hat, dann ernenne ich mich hiermit selbst zum Reiseleiter in der Außenwelt!', // Bon, puisque tout le monde est d'accord, je me prononce Guide de l'Outre-Monde
                'variables' => [ 'config' => [ 'main' => ['from' => '_winner'] ] ]
            ],

            'guide_intro_none_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGuideIntroNone, 'vocal' => false,
                'text' => 'Niemand ist mehr in der Stadt, keiner kann diese Versammlung halten, daher überspringen wir die Wahl zum Reiseleiter in der Außenwelt.', // There is noone left in town, noone to hold this assembly, so today we're just going to skip the election of the Guide to the World Beyond
            ],

            'guide_intro_few_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGuideIntroFew,
                'text' => 'OK, es sind nicht mehr viele von uns übrig, also lasst uns das schnell hinter uns bringen.', // OK, there's not many of us left, so let's get this over and done with.
                'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
            ],

            'guide_follow_up_any_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGuideFollowUpAny,
                'text' => 'Wir brauchen einen guten Reiseleiter. Eine gute Reiseleitung ist wichtig.', // You've got to have a good guide. Good guidance is important.
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'guide_follow_up_any_002' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGuideFollowUpAny,
                'text' => 'Es ist ziemlich offensichtlich, dass die Navigation nach den Sternen Schwachsinn ist!', // Pretty obvious that navigating by the stars is a crock!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'guide_follow_up_any_003' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGuideFollowUpAny,
                'text' => 'Ich wäre gerne der Reiseleiter!', // I'd love to be the Guide
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'guide_follow_up_any_004' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGuideFollowUpAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branch_count' => [1,2], 'branches' => [
                    'guide_follow_up_any_q_response_001','guide_follow_up_any_q_response_002','guide_follow_up_any_q_response_003',
                    'generic_follow_up_any_q_response_001'
                ],
                'text' => 'Was ist denn ein Speiseschreiter?', // What's a guyed anyway?
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'guide_follow_up_any_q_response_001' => [
                'text' => 'Es sind die Individuen, die uns mit Sicherheit in den sicheren Tod führen...', // They're the individual responsible for leading us surely to our certian death...
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'guide_follow_up_any_q_response_002' => [
                'text' => 'Die sind wie du? Nur hübscher?', // They're like you? But better looking?
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'guide_follow_up_any_q_response_003' => [
                'text' => 'Sie sind wie dieser Hexendoktor, nur nützlicher!', // They're like the witchdoctor guy, but useful!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],

            'guide_follow_up_next_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGuideFollowUpNext,
                'text' => 'Ich werde dich rächen, Kumpel! Du musst mir nur den Zombie bringen, der dich erwischt hat, dann wirst du schon sehen!', // I'll revenge you buddy! Just you bring me the zombie that got you, you'll see!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'guide_follow_up_next_002' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGuideFollowUpNext,
                'text' => 'Ich bin ein bisschen traurig... Ich werde den Kerl und seinen klapprigen alten Kompass vermissen.', // I'm a bit sad... I'll miss that guy and his dodgey old compass.
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'guide_follow_up_next_003' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGuideFollowUpNext,
                'text' => 'Oh, Mann! Ich habe ihm gesagt, dass er nach Osten gehen muss!', // Jeez! I told him he had to go east!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'guide_follow_up_next_004' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGuideFollowUpNext,
                'text' => 'Eine neue Wahl zum Reiseleiter durch die Außenwelt!', // Chouette une nouvelle élection de Guide de l'Outre-Monde !
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],

            'guide_vote_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGuideVoteAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branch_count' => [0,2], 'branches' => [CouncilEntryTemplate::CouncilNodeGuideVoteResponseAny,CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny],
                'text' => 'Nun, ich schlage vor, dass es unser lieber {voted} sein soll, er hat die malerischste kleine Hütte...', // Well I propose that it be our dear [randomVotedPerson] they've got the quaintest little hovel...
                'variables' => [ 'types' => [['type'=>"citizen", 'name'=>'voted']], 'config' => [ 'main' => ['from' => '_council?'], 'voted' => ['from' => 'voted', 'consume' => true] ] ]
            ],
            'guide_vote_002' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGuideVoteAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branch_count' => [0,2], 'branches' => [CouncilEntryTemplate::CouncilNodeGuideVoteResponseAny,CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny],
                'text' => 'Ich schlage vor, dass es nicht {voted} sein sollte, da es eine zu wichtige Rolle ist, um ein solches Risiko einzugehen.', // Je propose que ce ne soit pas Anarchik, c'est un rôle trop important pour prendre un tel risque.
                'variables' => [ 'types' => [['type'=>"citizen", 'name'=>'voted']], 'config' => [ 'main' => ['from' => '_council?'], 'voted' => ['from' => 'voted', 'consume' => true] ] ]
            ],

            'guide_vote_end_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGuideEndVoteAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
                'branch_count' => 1, 'branches' => [CouncilEntryTemplate::CouncilNodeGenericEndVoteResponseA],
                'text' => 'Ruhe! Wir müssen dieses Treffen zu Ende bringen! Wir haben eine große Wüste zu erforschen und keinen Reiseleiter, der uns hilft!', // Silence! We've got to finish this meeting! We've got a big ol' desert to explore and no Guide to help us!
                'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
            ],

            'guide_straw_result_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGuideStrawResultAny,
                'text' => 'So, jetzt haben wir ganz offiziell einen neuen Reiseleiter bestimmt: {winner}!', // So now we officially have our new Guide to the World Beyond: [Guide]!
                'variables' => [ 'types' => [['type'=>"citizen", 'name'=>'winner']], 'config' => [ 'main' => ['from' => '_mc'], 'winner' => ['from' => '_winner'] ] ]
            ],

            'guide_straw_result_response_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGuideStrawResultResponseAny,
                'text' => 'Hmmmm alles in allem wäre {voted} vielleicht die bessere Wahl gewesen...', // Hmmmm all things considered maybe [randomVotedPerson] would have been a better choice...
                'variables' => [ 'types' => [['type'=>"citizen", 'name'=>'voted']], 'config' => [ 'main' => ['from' => '_council?'], 'voted' => ['from' => 'voted', 'consume' => true] ] ]
            ],
            'guide_straw_result_response_002' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGuideStrawResultResponseAny,
                'text' => 'Ich hoffe, er hält wenigstens ein paar Tage durch... Ich will nicht jeden verdammten Tag abstimmen müssen!', // Here's hoping they last a few days... Don't want to be voting every bloody day!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'guide_straw_result_response_003' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGuideStrawResultResponseAny,
                'text' => 'Weis der überhaupt, wie man aus der Stadt kommt?', // Does he even know how to get out of town?
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'guide_straw_result_response_004' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGuideStrawResultResponseAny,
                'text' => 'Und er dachte, er wäre vorher gut gewesen...', // And he thought he was good before...
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'guide_straw_result_response_005' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGuideStrawResultResponseAny,
                'text' => 'Jetzt sind wir zwar immer noch auf dem sprichwörtlichen Holzweg, aber wir haben zumindest einen Reiseleiter!', // Now we're of the proverbial creek, but we have a paddle!
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'guide_straw_result_response_006' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGuideStrawResultResponseAny,
                'text' => 'Hurra für den Reiseleiter!', // Hooray for the Guide
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'guide_straw_result_response_007' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGuideStrawResultResponseAny,
                'text' => 'Er hat schon vorher geprahlt.', // Déjà qu'il se la racontait avant..
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],
            'guide_straw_result_response_008' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGuideStrawResultResponseAny,
                'text' => 'Wir sind sowas von im Ar...', // Nous voilà pas dans la m****
                'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
            ],

            'guide_final_001' => [
                'semantic' => CouncilEntryTemplate::CouncilNodeGuideFinalAny, 'vocal' => false,
                'text' => '{winner} wurde zum Führer gewählt. Hoffen wir, dass er uns aus diesem Schlamassel heraushelfen kann...', // [Guide] has been elected as the Guide, let's hope they can help get us out of this mess...
                'variables' => [ 'types' => [['type'=>"citizen", 'name'=>'winner']], 'config' => [ 'winner' => ['from' => '_winner'] ] ]
            ],
        ]);
    }
}