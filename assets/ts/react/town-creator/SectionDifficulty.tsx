import * as React from "react";

import {Global} from "../../defaults";
import {ResponseTownList, TownRules} from "./api";
import {useContext, useEffect, useRef} from "react";
import {Globals} from "./Wrapper";
import {OptionCoreTemplate, OptionFreeText, OptionSelect, OptionToggleMulti} from "./Input";
import {number} from "prop-types";

declare var $: Global;

export const TownCreatorSectionDifficulty = () => {
    const globals = useContext(Globals)

    const difficulty = globals.strings.difficulty;

    return <div data-map-property="rules">
        <h5>{ difficulty.section }</h5>

        { /* Well Level */ }
        <OptionCoreTemplate propName="well" propHelp={difficulty.well_help} propTitle={difficulty.well}>
            <select name="wellPreset" value={globals.getOption( 'rules.wellPreset' ) ?? ''} onChange={globals.setOption}>
                { difficulty.well_presets.map( option => <React.Fragment key={option.value}>
                    <option value={option.value}>{ option.label }</option>
                </React.Fragment> ) }
            </select>
            { globals.getOption( 'rules.wellPreset' ) === '_fixed' && (
                <div className="row-flex">
                    <div className="padded cell">
                        <input  type="number" min={0} max={300} value={globals.getOption( 'rules.well.min' ) ?? 120} onChange={e => {
                            const v =  parseInt((e.target as HTMLInputElement).value);
                            globals.setOption('rules.well.min', v);
                            globals.setOption('rules.well.max', v);
                        }}/>
                    </div>
                </div>
            ) }
            { globals.getOption( 'rules.wellPreset' ) === '_range' && (
                <div className="row-flex" data-map-property="well">
                    <div className="padded cell grow-1"><input type="number" data-prop-name="min" min={0} max={Math.min((globals.getOption( 'rules.well.max' ) as number) ?? 300, 300)} value={globals.getOption( 'rules.well.min' ) ?? 90} onChange={globals.setOption}/></div>
                    <div className="padded cell shrink-1">-</div>
                    <div className="padded cell grow-1"><input className="padded cell grow-1" type="number" data-prop-name="max" min={Math.max((globals.getOption( 'rules.well.min' ) as number) ?? 0, 0)} max={300} value={globals.getOption( 'rules.well.max' ) ?? 180} onChange={globals.setOption}/></div>
                </div>
            ) }
        </OptionCoreTemplate>

        { /* Map Settings */ }
        <OptionSelect value={ globals.getOption( 'rules.mapPreset' ) } propName="mapPreset" propTitle={ difficulty.map }
                      options={ difficulty.map_presets.map( m => ({ value: m.value, title: m.label }) ) }
        />
        { globals.getOption( 'rules.mapPreset' ) === '_custom' && (
            <>
                <OptionFreeText type="number" value={ globals.getOption( 'rules.map.min' ) as string ?? '26' } propName="map"
                                inputArgs={{min: 10, max: 35}} propTitle={ difficulty.map_exact }
                                onChange={e => {
                                    const v =  parseInt((e.target as HTMLInputElement).value);
                                    globals.setOption('rules.map.min', v);
                                    globals.setOption('rules.map.max', v);
                                }}

                />
                <OptionFreeText type="number" value={ globals.getOption( 'rules.ruins' ) as string ?? '20' } propName="ruins"
                                inputArgs={{min: 0, max: 30}} propTitle={ difficulty.map_ruins }
                />
                <OptionFreeText type="number" value={ globals.getOption( 'rules.explorable_ruins' ) as string ?? '1' } propName="explorable_ruins"
                                inputArgs={{min: 0, max: 3}} propTitle={ difficulty.map_e_ruins }
                />
            </>
        ) }

        { /* Position Settings */ }
        <OptionSelect value={ globals.getOption( 'rules.mapMarginPreset' ) ?? 'normal' } propName="rules.mapMarginPreset" propTitle={ difficulty.position }
                      options={ difficulty.position_presets.map( m => ({ value: m.value, title: m.label }) ) }
        />

        { /* Attack Settings */ }
        <OptionSelect value={ globals.getOption( 'rules.features.attacks' ) } propName="features.attacks" propTitle={ difficulty.attacks }
                      options={ difficulty.attacks_presets.map( m => ({ value: m.value, title: m.label }) ) }
        />

    </div>;
};