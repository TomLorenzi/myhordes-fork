import * as React from "react";

import {Global} from "../../defaults";
import {ResponseTownList, TownRules} from "./api";
import {useContext, useEffect, useRef} from "react";
import {Globals} from "./Wrapper";
import {OptionCoreTemplate, OptionFreeText, OptionSelect, OptionToggleMulti} from "./Input";
import {number} from "prop-types";

declare var $: Global;

export const TownCreatorSectionAnimator = ( {rules}: {rules: TownRules} ) => {
    const globals = useContext(Globals)

    const animation = globals.strings.animation;

    return <div data-map-property="rules">
        <h5>{ animation.section }</h5>

        { /* SP Settings */ }
        <OptionSelect propTitle={animation.sp}
                      value={rules.features.give_soulpoints ? 'all' : 'none'} propName="features.give_soulpoints"
                      options={ animation.sp_presets.map( preset => ({ value: preset.value, title: preset.label, help: preset.help }) ) }
                      onChange={e => globals.setOption('rules.features.give_soulpoints', (e.target as HTMLInputElement).value === 'all')}
        />

        { /* Picto Settings */ }
        <OptionSelect propTitle={animation.pictos}
                      value={rules.features.give_all_pictos ? 'all' : 'reduced'} propName="features.give_all_pictos"
                      options={ animation.pictos_presets.map( preset => ({ value: preset.value, title: preset.label, help: preset.help }) ) }
                      onChange={e => globals.setOption('rules.features.give_all_pictos', (e.target as HTMLInputElement).value === 'all')}
        />

        { /* Picto Rule Settings */ }
        <OptionSelect propTitle={animation.picto_rules}
                      value={rules.modifiers.strict_picto_distribution ? 'small' : 'normal'} propName="modifiers.strict_picto_distribution"
                      options={ animation.picto_rules_presets.map( preset => ({ value: preset.value, title: preset.label, help: preset.help }) ) }
                      onChange={e => globals.setOption('modifiers.strict_picto_distribution', (e.target as HTMLInputElement).value === 'small')}
        />

        { /* Participation Settings */ }
        <OptionSelect propTitle={animation.participation}
                      value={globals.options.head.townIncarnation ?? 'incarnate'} propName="<.head.townIncarnation"
                      options={ animation.participation_presets.map( preset => ({ value: preset.value, title: preset.label, help: preset.help }) ) }
        />

        { /* Management Settings */ }
        <OptionToggleMulti propName="features.<" options={[
            { value: rules.lock_door_until_full as boolean, name: 'lock_door_until_full', title: animation.management.lock_door, help: animation.management.lock_door_help },
            { value: rules.open_town_limit as number === 2, name: 'open_town_limit', title: animation.management.negate, help: animation.management.negate_help, onChange: e => {
                globals.setOption('rules.open_town_limit', (e.target as HTMLInputElement).checked ? 2 : 7)
            }},
            { value: globals.options.head.townEventTag as boolean, name: '<.head.townEventTag', title: animation.management.event_tag, help: animation.management.event_tag_help },
        ]} propTitle={animation.management.section}/>

    </div>;
};