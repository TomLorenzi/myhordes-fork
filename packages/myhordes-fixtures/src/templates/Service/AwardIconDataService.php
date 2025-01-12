<?php

namespace MyHordes\Fixtures\Service;

use MyHordes\Fixtures\DTO\Awards\AwardIconPrototypeDataContainer;
use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class AwardIconDataService implements FixtureProcessorInterface {

    public function process(array &$data, ?string $tag = null): void
    {
        $container = new AwardIconPrototypeDataContainer($data);
        $container->add()->icon('r_heroac')->unlockquantity(15)->associatedpicto('r_heroac_#00')->commit();
        $container->add()->icon('r_cwater')->unlockquantity(50)->associatedpicto('r_cwater_#00')->commit();
        $container->add()->icon('r_solban')->unlockquantity(20)->associatedpicto('r_solban_#00')->commit();
        $container->add()->icon('r_cookr')->unlockquantity(10)->associatedpicto('r_cookr_#00')->commit();
        $container->add()->icon('r_animal')->unlockquantity(30)->associatedpicto('r_animal_#00')->commit();
        $container->add()->icon('r_cmplst')->unlockquantity(10)->associatedpicto('r_cmplst_#00')->commit();
        $container->add()->icon('r_camp')->unlockquantity(10)->associatedpicto('r_camp_#00')->commit();
        $container->add()->icon('r_cannib')->unlockquantity(10)->associatedpicto('r_cannib_#00')->commit();
        $container->add()->icon('r_watgun')->unlockquantity(10)->associatedpicto('r_watgun_#00')->commit();
        $container->add()->icon('r_chstxl')->unlockquantity(5)->associatedpicto('r_chstxl_#00')->commit();
        $container->add()->icon('r_buildr')->unlockquantity(100)->associatedpicto('r_buildr_#00')->commit();
        $container->add()->icon('r_nodrug')->unlockquantity(20)->associatedpicto('r_nodrug_#00')->commit();
        $container->add()->icon('r_collec')->unlockquantity(2)->associatedpicto('r_collec_#00')->commit();
        $container->add()->icon('r_wrestl')->unlockquantity(20)->associatedpicto('r_wrestl_#00')->commit();
        $container->add()->icon('r_ebuild')->unlockquantity(1)->associatedpicto('r_ebuild_#00')->commit();
        $container->add()->icon('r_digger')->unlockquantity(50)->associatedpicto('r_digger_#00')->commit();
        $container->add()->icon('r_deco')->unlockquantity(100)->associatedpicto('r_deco_#00')->commit();
        $container->add()->icon('r_cobaye')->unlockquantity(50)->associatedpicto('r_cobaye_#00')->commit();
        $container->add()->icon('r_ruine')->unlockquantity(5)->associatedpicto('r_ruine_#00')->commit();
        $container->add()->icon('r_explor')->unlockquantity(15)->associatedpicto('r_explor_#00')->commit();
        $container->add()->icon('r_explo2')->unlockquantity(5)->associatedpicto('r_explo2_#00')->commit();
        $container->add()->icon('r_share')->unlockquantity(10)->associatedpicto('r_share_#00')->commit();
        $container->add()->icon('r_guide')->unlockquantity(300)->associatedpicto('r_guide_#00')->commit();
        $container->add()->icon('r_drgmkr')->unlockquantity(10)->associatedpicto('r_drgmkr_#00')->commit();
        $container->add()->icon('r_theft')->unlockquantity(10)->associatedpicto('r_theft_#00')->commit();
        $container->add()->icon('r_maso')->unlockquantity(20)->associatedpicto('r_maso_#00')->commit();
        $container->add()->icon('r_bgum')->unlockquantity(1)->associatedpicto('r_bgum_#00')->commit();
        $container->add()->icon('r_ebcstl')->unlockquantity(5)->associatedpicto('r_ebcstl_#00')->commit();
        $container->add()->icon('r_ebpmv')->unlockquantity(5)->associatedpicto('r_ebpmv_#00')->commit();
        $container->add()->icon('r_ebgros')->unlockquantity(5)->associatedpicto('r_ebgros_#00')->commit();
        $container->add()->icon('r_ebcrow')->unlockquantity(5)->associatedpicto('r_ebcrow_#00')->commit();
        $container->add()->icon('r_jtamer')->unlockquantity(10)->associatedpicto('r_jtamer_#00')->commit();
        $container->add()->icon('r_jrangr')->unlockquantity(10)->associatedpicto('r_jrangr_#00')->commit();
        $container->add()->icon('r_jermit')->unlockquantity(10)->associatedpicto('r_jermit_#00')->commit();
        $container->add()->icon('r_jcolle')->unlockquantity(10)->associatedpicto('r_jcolle_#00')->commit();
        $container->add()->icon('r_jguard')->unlockquantity(10)->associatedpicto('r_jguard_#00')->commit();
        $container->add()->icon('r_jtech')->unlockquantity(10)->associatedpicto('r_jtech_#00')->commit();
        $container->add()->icon('r_dinfec')->unlockquantity(20)->associatedpicto('r_dinfec_#00')->commit();
        $container->add()->icon('r_dnucl')->unlockquantity(10)->associatedpicto('r_dnucl_#00')->commit();
        $container->add()->icon('r_surlst')->unlockquantity(10)->associatedpicto('r_surlst_#00')->commit();
        $container->add()->icon('r_suhard')->unlockquantity(5)->associatedpicto('r_suhard_#00')->commit();
        $container->add()->icon('r_mystic')->unlockquantity(10)->associatedpicto('r_mystic_#00')->commit();
        $container->add()->icon('r_doutsd')->unlockquantity(20)->associatedpicto('r_doutsd_#00')->commit();
        $container->add()->icon('r_door')->unlockquantity(1)->associatedpicto('r_door_#00')->commit();
        $container->add()->icon('r_plundr')->unlockquantity(30)->associatedpicto('r_plundr_#00')->commit();
        $container->add()->icon('r_wondrs')->unlockquantity(20)->associatedpicto('r_wondrs_#00')->commit();
        $container->add()->icon('r_repair')->unlockquantity(15)->associatedpicto('r_repair_#00')->commit();
        $container->add()->icon('r_brep')->unlockquantity(100)->associatedpicto('r_brep_#00')->commit();
        $container->add()->icon('r_rp')->unlockquantity(5)->associatedpicto('r_rp_#00')->commit();
        $container->add()->icon('r_cgarb')->unlockquantity(60)->associatedpicto('r_cgarb_#00')->commit();
        $container->add()->icon('r_batgun')->unlockquantity(15)->associatedpicto('r_batgun_#00')->commit();
        $container->add()->icon('r_pande')->unlockquantity(50)->associatedpicto('r_pande_#00')->commit();
        $container->add()->icon('r_tronco')->unlockquantity(5)->associatedpicto('r_tronco_#00')->commit();
        $container->add()->icon('r_guard')->unlockquantity(20)->associatedpicto('r_guard_#00')->commit();
        $container->add()->icon('r_winbas')->unlockquantity(2)->associatedpicto('r_winbas_#00')->commit();
        $container->add()->icon('r_wintop')->unlockquantity(1)->associatedpicto('r_wintop_#00')->commit();
        $container->add()->icon('r_winthi')->unlockquantity(2)->associatedpicto('r_winthi_#00')->commit();
        $container->add()->icon('r_killz')->unlockquantity(100)->associatedpicto('r_killz_#00')->commit();
        $container->add()->icon('r_beta')->unlockquantity(1)->associatedpicto('r_beta_#00')->commit();
        $container->add()->icon('r_sandb')->unlockquantity(10)->associatedpicto('r_sandb_#00')->commit();
        $container->add()->icon('r_paques')->unlockquantity(1)->associatedpicto('r_paques_#00')->commit();
        $container->add()->icon('r_santac')->unlockquantity(10)->associatedpicto('r_santac_#00')->commit();
        $container->add()->icon('r_armag')->unlockquantity(1)->associatedpicto('r_armag_#00')->commit();
        $container->add()->icon('r_ginfec')->unlockquantity(1)->associatedpicto('r_ginfec_#00')->commit();
        $container->add()->icon('r_ptame')->unlockquantity(100)->associatedpicto('r_ptame_#00')->commit();
        $container->add()->icon('r_jsham')->unlockquantity(10)->associatedpicto('r_jsham_#00')->commit();
        $container->add()->icon('r_rrefer')->unlockquantity(1)->associatedpicto('r_rrefer_#00')->commit();
        $container->add()->icon('r_fjvani')->unlockquantity(1)->associatedpicto('r_fjvani_#00')->commit();
        $container->add()->icon('r_fjv2')->unlockquantity(1)->associatedpicto('r_fjv2_#00')->commit();
        $container->add()->icon('r_fjv')->unlockquantity(1)->associatedpicto('r_fjv_#00')->commit();
        $container->add()->icon('r_comu')->unlockquantity(1)->associatedpicto('r_comu_#00')->commit();
        $container->add()->icon('r_comu2')->unlockquantity(1)->associatedpicto('r_comu2_#00')->commit();
        $container->add()->icon('r_cott')->unlockquantity(1)->associatedpicto('r_cott_#00')->commit();
        $container->add()->icon('r_ermwin')->unlockquantity(1)->associatedpicto('r_ermwin_#00')->commit();
        $container->add()->icon('r_cdhwin')->unlockquantity(1)->associatedpicto('r_cdhwin_#00')->commit();
        $container->add()->icon('r_defwin')->unlockquantity(1)->associatedpicto('r_defwin_#00')->commit();
        $container->add()->icon('r_kohlmb')->unlockquantity(1)->associatedpicto('r_kohlmb_#00')->commit();
        $container->add()->icon('r_lepre')->unlockquantity(10)->associatedpicto('r_lepre_#00')->commit();
        $container->add()->icon('r_goodg')->unlockquantity(1)->associatedpicto('r_goodg_#00')->commit();
        $container->add()->icon('r_surgrp')->unlockquantity(5)->associatedpicto('r_surgrp_#00')->commit();
        $container->add()->icon('r_alcool')->unlockquantity(30)->associatedpicto('r_alcool_#00')->commit();
        $container->add()->icon('r_gsp')->unlockquantity(1)->associatedpicto('r_gsp_#00')->commit();
        $container->add()->icon('r_beta2')->unlockquantity(1)->associatedpicto('r_beta2_#00')->commit();
        $container->add()->icon('r_ripflash')->unlockquantity(1)->associatedpicto('r_ripflash_#00')->commit();
        $container->add()->icon('r_jbasic')->unlockquantity(10)->associatedpicto('r_jbasic_#00')->commit();
        $container->add()->icon('r_scaddh')->unlockquantity(1)->associatedpicto('r_scaddh_#00')->commit();
        $container->add()->icon('r_rangwin')->unlockquantity(1)->associatedpicto('r_rangwin_#00')->commit();
        $container->add()->icon('r_tamwin')->unlockquantity(1)->associatedpicto('r_tamwin_#00')->commit();
        $container->add()->icon('r_eventwin')->unlockquantity(1)->associatedpicto('r_eventwin_#00')->commit();
        $container->add()->icon('r_eventpart')->unlockquantity(1)->associatedpicto('r_eventpart_#00')->commit();
        $container->add()->icon('r_techwin')->unlockquantity(1)->associatedpicto('r_techwin_#00')->commit();

        $data = $container->toArray();
    }
}