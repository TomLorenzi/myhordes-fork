import * as React from "react";

import {Global} from "../../defaults";
import {ResponseTownList, TownRules} from "./api";
import {useContext, useEffect, useRef} from "react";
import {Globals} from "./Wrapper";
import {OptionCoreTemplate, OptionFreeText, OptionSelect, OptionToggleMulti} from "./Input";
import {number} from "prop-types";

declare var $: Global;

export const TownCreatorSectionMods = () => {
    const globals = useContext(Globals)

    const mods = globals.strings.mods;

    const disabled_buildings = new Set<string>(globals.getOption( 'rules.disabled_buildings' ) ?? []);
    const chest_items = new Set<string>(globals.getOption( 'rules.initial_chest' ) ?? []);

    const streetlight_disabled = mods.nightmode_buildings.reduce( (v,b) => v && disabled_buildings.has(b), true ) ;
    const improved_dump_disabled = mods.modules.improveddump_buildings.reduce( (v,b) => v && disabled_buildings.has(b), true ) ;
    const night_mode_setting = globals.getOption( 'rules.features.nightmode' ) ?? true;

    const has_all_beta_items = mods.special.beta_items.reduce( (v,b) => v && chest_items.has(b), true ) ;

    let night_mode_value = null;
    if ( streetlight_disabled && !night_mode_setting )
        night_mode_value = 'none';
    else if ( streetlight_disabled && night_mode_setting )
        night_mode_value = 'hordes';
    else if ( !streetlight_disabled && night_mode_setting )
        night_mode_value = 'myhordes';

    const nw_enabled = globals.getOption( 'rules.features.nightwatch.enabled' ) ?? true;
    const nw_instant = globals.getOption( 'rules.features.nightwatch.instant' ) ?? false;

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
        <OptionSelect value={ globals.getOption( 'rules.features.ghoul_mode' ) } propName="features.ghoul_mode" propTitle={ mods.ghouls }
                      options={ mods.ghouls_presets.map( m => ({ value: m.value, title: m.label, help: m.help }) ) }
        />

        { /* Shaman setting */ }
        <OptionSelect value={ globals.getOption( 'rules.features.shaman' ) } propName="features.shaman" propTitle={ mods.shamans }
                      options={ mods.shamans_presets.map( m => ({ value: m.value, title: m.label, help: m.help }) ) }
                      onChange={e => {
                          const v = (e.target as HTMLInputElement).value;
                          globals.setOption('rules.features.shaman', v);
                          globals.setOption('rules.disabled_jobs.<>.shaman', v === "normal" || v === "none");
                          globals.setOption('rules.disabled_roles.<>.shaman', v === "job" || v === "none");
                          if (v === "job" || v === "both") {
                              globals.setOption('head.customJobs', true);
                              globals.setOption('head.customConstructions', true);
                          }
                          mods.shaman_buildings.job.forEach( b => {
                              globals.setOption(`rules.disabled_buildings.<>.${b}`, v === "normal" || v === "none");
                              globals.setOption(`rules.unlocked_buildings.<>.${b}`, false );
                              globals.setOption(`rules.initial_buildings.<>.${b}`, false );
                          } )
                          mods.shaman_buildings.normal.forEach( b => {
                              globals.setOption(`rules.disabled_buildings.<>.${b}`, v === "job" || v === "none");
                              globals.setOption(`rules.unlocked_buildings.<>.${b}`, false );
                              globals.setOption(`rules.initial_buildings.<>.${b}`, false );
                          } )
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
                              mods.watch_buildings.forEach( b => {
                                  globals.setOption(`rules.disabled_buildings.<>.${b}`, v !== "normal");
                                  globals.setOption(`rules.unlocked_buildings.<>.${b}`, false );
                                  globals.setOption(`rules.initial_buildings.<>.${b}`, false );
                              } )
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
                              mods.nightmode_buildings.forEach( b => {
                                  globals.setOption(`rules.disabled_buildings.<>.${b}`, v !== "myhordes");
                                  globals.setOption(`rules.unlocked_buildings.<>.${b}`, false );
                                  globals.setOption(`rules.initial_buildings.<>.${b}`, false );
                              } )
                          }}
            />
        ) }

        { /* Night phase setting */ }
        <OptionCoreTemplate propName="modifiers.daytime" propTitle={mods.timezone} propHelp={
            mods.timezone_presets?.filter(v => v.value === (globals.getOption( 'rules.modifiers.daytime.invert' ) ? 'night' : 'day'))?.pop()?.help
        }>
            <select name="timeZonePreset" value={globals.getOption( 'rules.modifiers.daytime.invert' ) ? 'night' : 'day'}
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
                    <select name="timeZoneBegin" value={ globals.getOption( 'rules.modifiers.daytime.range' )[0] ?? '5'} data-map-property="0"
                            onChange={e => {
                                const range = globals.getOption( 'rules.modifiers.daytime.range' ) ?? [7,18];
                                range[0] = parseInt( (e.target as HTMLSelectElement).value );
                                globals.setOption('rules.modifiers.daytime.range', range);
                            }}
                    >
                        { [...Array(24).keys()].filter(v => v <= globals.getOption( 'rules.modifiers.daytime.range' )[1] ?? 18).map( v => <React.Fragment key={v}>
                            <option value={v}>{ `${v}:00` }</option>
                        </React.Fragment> ) }
                    </select>
                </div>
                <div className="padded cell shrink-1">-</div>
                <div className="padded cell grow-1">
                    <select name="timeZoneEnd" value={globals.getOption( 'rules.modifiers.daytime.range' )[1] ?? '18'} data-map-property="1"
                            onChange={e => {
                                const range = globals.getOption( 'rules.modifiers.daytime.range' ) ?? [7,18];
                                range[1] = parseInt( (e.target as HTMLSelectElement).value );
                                globals.setOption('rules.modifiers.daytime.range', range);
                            }}
                    >
                        { [...Array(24).keys()].map(v=>v+1).filter(v => v >= globals.getOption( 'rules.modifiers.daytime.range' )[0] ?? 7).map( v => <React.Fragment key={v}>
                            <option value={v}>{ `${v}:00` }</option>
                        </React.Fragment> ) }
                    </select>
                </div>
            </div>
        </OptionCoreTemplate>

        { /* Game features */ }
        <OptionToggleMulti propName="features" options={[
            { value: globals.getOption( 'rules.features.citizen_alias' ) as boolean, name: 'citizen_alias',  title: mods.modules.alias, help: mods.modules.alias_help },
            { value: (globals.getOption( 'rules.explorable_ruins' ) ?? 1) > 0, name: 'eruins_disabled', title: mods.modules.e_ruins, help: mods.modules.e_ruins_help, onChange: e => {
                globals.setOption('rules.explorable_ruins', (e.target as HTMLInputElement).checked ? Math.max(1,globals.default_rules.explorable_ruins as number ?? 0) : 0);
            } },
            { value: globals.getOption( 'rules.features.escort.enabled' ) as boolean, name: 'escort.enabled', title: mods.modules.escorts, help: mods.modules.escorts_help },
            { value: globals.getOption( 'rules.features.shun' ) as boolean, name: 'shun', title: mods.modules.shun, help: mods.modules.shun_help },
            { value: globals.getOption( 'rules.features.camping' ) as boolean, name: 'camping', title: mods.modules.camp, help: mods.modules.camp_help },
            { value: globals.getOption( 'rules.modifiers.building_attack_damage' ) as boolean, name: '<.modifiers.building_attack_damage', title: mods.modules.buildingdamages, help: mods.modules.buildingdamages_help },
            { value: !improved_dump_disabled, name: 'improved_dump', title: mods.modules.improveddump, help: mods.modules.improveddump_help, onChange: e => {
                mods.modules.improveddump_buildings.forEach( b => {
                    globals.setOption(`rules.disabled_buildings.<>.${b}`, !(e.target as HTMLInputElement).checked);
                    globals.setOption(`rules.unlocked_buildings.<>.${b}`, false );
                    globals.setOption(`rules.initial_buildings.<>.${b}`, false );
                } );
            } },
            {value: globals.getOption( 'rules.features.xml_feed' ) as boolean, name: "xml_feed", title: mods.modules.api, help: mods.modules.api_help },
            {value: globals.getOption( 'rules.features.free_for_all' ) as boolean, name: "free_for_all", title: mods.modules.ffa, help: mods.modules.ffa_help },
            {value: globals.getOption( 'rules.features.free_from_teams' ) as boolean, name: "free_from_teams", title: mods.modules.fft, help: mods.modules.fft_help }
        ].filter( globals.elevation < 3 ? option=>!['citizen_alias','free_for_all','free_from_teams'].includes(option.name) : ()=>true )} propTitle={mods.modules.section}/>

        { /* Special rules */ }
        <OptionToggleMulti propName="features" options={[
            ...new Set<string>(globals.default_rules.unlocked_buildings ?? []).size ? [{ value: (new Set<string>(globals.getOption( 'rules.unlocked_buildings' ) as string[] ?? [])).size === 0, name: 'nobuilding', title: mods.special.nobuilding, help: mods.special.nobuilding_help, onChange: e => {
                globals.setOption('rules.unlocked_buildings', (e.target as HTMLInputElement).checked ? new Set<string>() : [...globals.default_rules.unlocked_buildings]);
            } }] : [],
            { value: globals.getOption( 'rules.features.all_poison' ) as boolean, name: 'all_poison', title: mods.special.poison, help: mods.special.poison_help },
            { value: has_all_beta_items, name: 'beta', title: mods.special.beta, help: mods.special.beta_help, onChange: e => {
                mods.special.beta_items.forEach( b => globals.setOption(`rules.initial_chest.<>.${b}`, (e.target as HTMLInputElement).checked ) );
            } },
            { value: (new Set<string>( globals.getOption( 'rules.overrides.named_drops' ) as string[] ?? [])).has('with-toxin'), name: '<.overrides.named_drops.<>.with-toxin', title: mods.special["with-toxin"], help: mods.special["with-toxin_help"] },
            { value: globals.getOption( 'rules.features.hungry_ghouls' ) as boolean, name: 'hungry_ghouls', title: mods.special["hungry-ghouls"], help: mods.special["hungry-ghouls_help"] },
            { value: globals.getOption( 'rules.modifiers.poison.stack_poisoned_items' ) as boolean && globals.getOption( 'rules.modifiers.poison.transgress' ) as boolean, name: 'super_poison', title: mods.special.super_poison, help: mods.special.super_poison_help, onChange: e => {
                const v = (e.target as HTMLInputElement).checked;
                globals.setOption( 'rules.modifiers.poison.stack_poisoned_items', v );
                globals.setOption( 'rules.modifiers.poison.transgress', v );
            } },
            { value: globals.getOption( 'rules.modifiers.allow_redig' ) as boolean, name: '<<.rules.modifiers.allow_redig', title: mods.special.redig, help: mods.special.redig_help },
            { value: globals.getOption( 'rules.modifiers.carry_extra_bag' ) as boolean, name: '<<.rules.modifiers.carry_extra_bag', title: mods.special.carry_bag, help: mods.special.carry_bag_help },
            { value: globals.getOption( 'rules.modifiers.strange_soil' ) as boolean && globals.getOption( 'rules.modifiers.poison.transgress' ) as boolean, name: 'strange_soil', title: mods.special.strange_soil, help: mods.special.strange_soil_help, onChange: e => {
                    const v = (e.target as HTMLInputElement).checked;
                    globals.setOption( 'rules.modifiers.strange_soil', v );
                    globals.setOption( 'rules.modifiers.poison.transgress', v );
                } },
        ]} propTitle={mods.special.section}/>
    </div>;
};