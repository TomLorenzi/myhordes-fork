import * as React from "react";

import {Global} from "../../defaults";
import {ResponseTownList, TownRules} from "./api";
import {useContext, useEffect, useRef} from "react";
import {Globals} from "./Wrapper";
import {OptionCoreTemplate, OptionFreeText, OptionSelect, OptionToggleMulti} from "./Input";
import {number} from "prop-types";

declare var $: Global;

export const TownCreatorSectionAnimator = () => {
    const globals = useContext(Globals)

    const animation = globals.strings.animation;

    return <div data-map-property="rules">
        <h5>{ animation.section }</h5>

        { /* SP Settings */ }
        <OptionSelect propTitle={animation.sp}
                      value={globals.getOption( 'rules.features.give_soulpoints' ) ? 'all' : 'none'} propName="features.give_soulpoints"
                      options={ animation.sp_presets.map( preset => ({ value: preset.value, title: preset.label, help: preset.help }) ) }
                      onChange={e => globals.setOption('rules.features.give_soulpoints', (e.target as HTMLInputElement).value === 'all')}
        />

        { /* Picto Settings */ }
        <OptionSelect propTitle={animation.pictos}
                      value={
                        globals.getOption( 'rules.features.enable_pictos' )
                            ? (globals.getOption( 'rules.features.give_all_pictos' ) ? 'all' : (globals.getOption( 'rules.features.picto_classic_cull_mode' ) ? 'reduced_classic' : 'reduced'))
                            : 'none'
                      } propName="features.pictos"
                      options={ animation.pictos_presets.map( preset => ({ value: preset.value, title: preset.label, help: preset.help }) ) }
                      onChange={e => {
                          const v = (e.target as HTMLInputElement).value;
                          globals.setOption('rules.features.enable_pictos', v !== 'none')
                          if (v !== 'none') {
                              globals.setOption('rules.features.give_all_pictos', v === 'all')
                              globals.setOption('rules.features.picto_classic_cull_mode', v === 'reduced_classic')
                          }
                      }}
        />

        { /* Picto Rule Settings */ }
        { globals.getOption( 'rules.features.enable_pictos' ) && (
            <OptionSelect propTitle={animation.picto_rules}
                          value={globals.getOption( 'rules.modifiers.strict_picto_distribution' ) ? 'small' : 'normal'} propName="modifiers.strict_picto_distribution"
                          options={ animation.picto_rules_presets.map( preset => ({ value: preset.value, title: preset.label, help: preset.help }) ) }
                          onChange={e => globals.setOption('rules.modifiers.strict_picto_distribution', (e.target as HTMLInputElement).value === 'small')}
            />
        ) }

        { /* Management Settings */ }
        <OptionToggleMulti propName="features.<" options={[
            { value: globals.getOption( 'rules.lock_door_until_full' ) as boolean, name: 'lock_door_until_full', title: animation.management.lock_door, help: animation.management.lock_door_help },
            { value: globals.getOption( 'rules.open_town_limit' ) as number === 2, name: 'open_town_limit', title: animation.management.negate, help: animation.management.negate_help, onChange: e => {
                globals.setOption('rules.open_town_limit', (e.target as HTMLInputElement).checked ? 2 : 7)
            }},
        ]} propTitle={animation.management.section}/>

    </div>;
};