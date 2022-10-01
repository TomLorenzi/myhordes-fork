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

    </div>;
};