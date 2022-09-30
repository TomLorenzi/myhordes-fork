import * as React from "react";

import {Global} from "../../defaults";
import {ResponseTownList, TownRules} from "./api";
import {useContext, useEffect, useRef} from "react";
import {Globals} from "./Wrapper";
import {OptionCoreTemplate, OptionFreeText, OptionSelect, OptionToggleMulti} from "./Input";

declare var $: Global;

export const TownCreatorSectionDifficulty = ( {rules}: {rules: TownRules} ) => {
    const globals = useContext(Globals)

    const difficulty = globals.strings.difficulty;

    return <div data-map-property="rules">
        <h5>{ difficulty.section }</h5>

        { /* Well Level */ }
        <OptionCoreTemplate propName="well" propHelp={difficulty.well_help} propTitle={difficulty.well}>
            <select name="wellPreset" value={globals.options?.rules?.wellPreset ?? ''} onChange={globals.setOption}>
                { difficulty.well_presets.map( option => <React.Fragment key={option.value}>
                    <option value={option.value}>{ option.label }</option>
                </React.Fragment> ) }
            </select>
            { globals.options?.rules?.wellPreset === '_fixed' && (
                <div className="row-flex">
                    <div className="padded cell">
                        <input  type="number" value={globals.options?.rules?.well?.min ?? 120} onChange={e => {
                            const v =  parseInt((e.target as HTMLInputElement).value);
                            globals.setOption('rules.well.min', v);
                            globals.setOption('rules.well.max', v);
                        }}/>
                    </div>
                </div>
            ) }
            { globals.options?.rules?.wellPreset === '_range' && (
                <div className="row-flex" data-map-property="well">
                    <div className="padded cell grow-1"><input type="number" data-prop-name="min" value={globals.options?.rules?.well?.min ?? 90} onChange={globals.setOption}/></div>
                    <div className="padded cell shrink-1">-</div>
                    <div className="padded cell grow-1"><input className="padded cell grow-1" type="number" data-prop-name="max" value={globals.options?.rules?.well?.max ?? 180} onChange={globals.setOption}/></div>
                </div>
            ) }
        </OptionCoreTemplate>

    </div>;
};