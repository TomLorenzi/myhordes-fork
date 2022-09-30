import * as React from "react";

import {Global} from "../../defaults";
import {ResponseTownList, TownRules} from "./api";
import {useContext, useEffect, useRef} from "react";
import {Globals} from "./Wrapper";
import {OptionCoreTemplate, OptionFreeText, OptionSelect, OptionToggleMulti} from "./Input";
import {number} from "prop-types";

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
                        <input  type="number" min={0} max={300} value={globals.options?.rules?.well?.min ?? 120} onChange={e => {
                            const v =  parseInt((e.target as HTMLInputElement).value);
                            globals.setOption('rules.well.min', v);
                            globals.setOption('rules.well.max', v);
                        }}/>
                    </div>
                </div>
            ) }
            { globals.options?.rules?.wellPreset === '_range' && (
                <div className="row-flex" data-map-property="well">
                    <div className="padded cell grow-1"><input type="number" data-prop-name="min" min={0} max={Math.min((globals.options?.rules?.well?.max as number) ?? 300, 300)} value={globals.options?.rules?.well?.min ?? 90} onChange={globals.setOption}/></div>
                    <div className="padded cell shrink-1">-</div>
                    <div className="padded cell grow-1"><input className="padded cell grow-1" type="number" data-prop-name="max" min={Math.max((globals.options?.rules?.well?.min as number) ?? 0, 0)} max={300} value={globals.options?.rules?.well?.max ?? 180} onChange={globals.setOption}/></div>
                </div>
            ) }
        </OptionCoreTemplate>

        { /* Map Settings */ }
        <OptionSelect value={ globals.options?.rules?.mapPreset } propName="mapPreset" propTitle={ difficulty.map }
                      options={ difficulty.map_presets.map( m => ({ value: m.value, title: m.label }) ) }
        />
        { globals.options?.rules?.mapPreset === '_custom' && (
            <>
                <OptionFreeText type="number" value={ globals.options?.rules?.map?.min as string ?? '26' } propName="map"
                                inputArgs={{min: 10, max: 35}} propTitle={ difficulty.map_exact }
                                onChange={e => {
                                    const v =  parseInt((e.target as HTMLInputElement).value);
                                    globals.setOption('rules.map.min', v);
                                    globals.setOption('rules.map.max', v);
                                }}

                />
                <OptionFreeText type="number" value={ globals.options?.rules?.ruins as string ?? '20' } propName="ruins"
                                inputArgs={{min: 0, max: 30}} propTitle={ difficulty.map_ruins }
                />
                <OptionFreeText type="number" value={ globals.options?.rules?.explorable_ruins as string ?? '1' } propName="explorable_ruins"
                                inputArgs={{min: 0, max: 3}} propTitle={ difficulty.map_e_ruins }
                />
            </>
        ) }

        { /* Attack Settings */ }
        <OptionSelect value={ globals.options?.rules?.features.attacks } propName="features.attacks" propTitle={ difficulty.attacks }
                      options={ difficulty.attacks_presets.map( m => ({ value: m.value, title: m.label }) ) }
        />
    </div>;
};