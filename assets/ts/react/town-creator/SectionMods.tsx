import * as React from "react";

import {Global} from "../../defaults";
import {ResponseTownList, TownRules} from "./api";
import {useContext, useEffect, useRef} from "react";
import {Globals} from "./Wrapper";
import {OptionCoreTemplate, OptionFreeText, OptionSelect, OptionToggleMulti} from "./Input";
import {number} from "prop-types";

declare var $: Global;

export const TownCreatorSectionMods = ( {rules}: {rules: TownRules} ) => {
    const globals = useContext(Globals)

    const mods = globals.strings.mods;

    const disabled_buildings = new Set<string>(rules.disabled_buildings ?? []);

    const streetlight_disabled = mods.nightmode_buildings.reduce( (v,b) => v && disabled_buildings.has(b), true ) ;
    const night_mode_setting = rules?.features?.nightmode ?? true;

    let night_mode_value = null;
    if ( streetlight_disabled && !night_mode_setting )
        night_mode_value = 'none';
    else if ( streetlight_disabled && night_mode_setting )
        night_mode_value = 'hordes';
    else if ( !streetlight_disabled && night_mode_setting )
        night_mode_value = 'myhordes';

    const nw_enabled = rules?.features?.nightwatch?.enabled ?? true;
    const nw_instant = rules?.features?.nightwatch?.instant ?? false;

    let nw_mode_value = null;
    if ( nw_enabled && nw_instant )
        nw_mode_value = 'instant';
    else if ( nw_enabled && !nw_instant )
        nw_mode_value = 'normal';
    else if (!nw_enabled )
        nw_mode_value = 'none';

    return <div data-map-property="rules">
        <h5>{ mods.section }</h5>

        { /* Ghoul setting */ }
        <OptionSelect value={ globals.options?.rules?.features?.ghoul_mode } propName="features.ghoul_mode" propTitle={ mods.ghouls }
                      options={ mods.ghouls_presets.map( m => ({ value: m.value, title: m.label, help: m.help }) ) }
        />

        { /* Shaman setting */ }
        <OptionSelect value={ globals.options?.rules?.features?.shaman } propName="features.shaman" propTitle={ mods.shamans }
                      options={ mods.shamans_presets.map( m => ({ value: m.value, title: m.label, help: m.help }) ) }
                      onChange={e => {
                          const v = (e.target as HTMLInputElement).value;
                          globals.setOption('rules.features.shaman', v);
                          globals.setOption('rules.disabled_jobs.<>.shaman', v === "normal" || v === "none");
                          globals.setOption('rules.disabled_roles.<>.shaman', v === "job" || v === "none");
                          mods.shaman_buildings.job.forEach( b => globals.setOption(`rules.disabled_buildings.<>.${b}`, v === "normal" || v === "none") )
                          mods.shaman_buildings.normal.forEach( b => globals.setOption(`rules.disabled_buildings.<>.${b}`, v === "job" || v === "none") )
                      }}
        />

        { /* Watch setting */ }
        { nw_mode_value && (
            <OptionSelect value={ nw_mode_value } propName="features.nightwatch" propTitle={ mods.watch }
                          options={ mods.watch_presets.map( m => ({ value: m.value, title: m.label, help: m.help }) ) }
                          onChange={e => {
                              const v = (e.target as HTMLInputElement).value;
                              globals.setOption('rules.features.nightwatch.enabled', v !== "none");
                              globals.setOption('rules.features.nightwatch.instant', v === "instant");
                              mods.watch_buildings.forEach( b => globals.setOption(`rules.disabled_buildings.<>.${b}`, v !== "normal") )
                          }}
            />
        ) }


        { /* Night mode setting */ }
        { night_mode_value && (
            <OptionSelect value={ night_mode_value } propName="features.nightmode" propTitle={ mods.nightmode }
                          options={ mods.nightmode_presets.map( m => ({ value: m.value, title: m.label, help: m.help }) ) }
                          onChange={e => {
                              const v = (e.target as HTMLInputElement).value;
                              globals.setOption('rules.features.nightmode', v !== "none");
                              mods.nightmode_buildings.forEach( b => globals.setOption(`rules.disabled_buildings.<>.${b}`, v !== "myhordes") )
                          }}
            />
        ) }

        { /* Night phase setting */ }
        <OptionCoreTemplate propName="modifiers.daytime" propTitle={mods.timezone} propHelp={
            mods.timezone_presets?.filter(v => v.value === (globals.options?.rules?.modifiers?.daytime?.invert ? 'night' : 'day'))?.pop()?.help
        }>
            <select name="timeZonePreset" value={globals.options?.rules?.modifiers?.daytime?.invert ? 'night' : 'day'}
                    onChange={e => {
                        globals.setOption('rules.modifiers.daytime.invert', (e.target as HTMLSelectElement).value !== "day");
                    }}
            >
                { mods.timezone_presets.map( option => <React.Fragment key={option.value}>
                    <option value={option.value} title={option.help}>{ option.label }</option>
                </React.Fragment> ) }
            </select>
            <div className="row-flex" data-map-property="range">
                <div className="padded cell grow-1">
                    <select name="timeZoneBegin" value={globals.options?.rules?.modifiers?.daytime?.range[0] ?? '5'} data-map-property="0"
                            onChange={e => {
                                const range = globals.options?.rules?.modifiers?.daytime?.range ?? [7,18];
                                range[0] = parseInt( (e.target as HTMLSelectElement).value );
                                globals.setOption('rules.modifiers.daytime.range', range);
                            }}
                    >
                        { [...Array(24).keys()].filter(v => v <= globals.options?.rules?.modifiers?.daytime?.range[1]).map( v => <React.Fragment key={v}>
                            <option value={v}>{ `${v}:00` }</option>
                        </React.Fragment> ) }
                    </select>
                </div>
                <div className="padded cell shrink-1">-</div>
                <div className="padded cell grow-1">
                    <select name="timeZoneEnd" value={globals.options?.rules?.modifiers?.daytime?.range[1] ?? '18'} data-map-property="1"
                            onChange={e => {
                                const range = globals.options?.rules?.modifiers?.daytime?.range ?? [7,18];
                                range[1] = parseInt( (e.target as HTMLSelectElement).value );
                                globals.setOption('rules.modifiers.daytime.range', range);
                            }}
                    >
                        { [...Array(24).keys()].map(v=>v+1).filter(v => v >= globals.options?.rules?.modifiers?.daytime?.range[0]).map( v => <React.Fragment key={v}>
                            <option value={v}>{ `${v}:00` }</option>
                        </React.Fragment> ) }
                    </select>
                </div>
            </div>
        </OptionCoreTemplate>


    </div>;
};