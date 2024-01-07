import * as React from "react";

import {Global} from "../../defaults";
import {ResponseTownList, TownRules} from "./api";
import {useContext, useEffect, useRef} from "react";
import {Globals} from "./Wrapper";
import {OptionCoreTemplate, OptionFreeText, OptionSelect, OptionToggleMulti} from "./Input";
import {number} from "prop-types";
import {AtLeast} from "./Permissions";

declare var $: Global;

export const TownCreatorSectionDifficulty = () => {
    const globals = useContext(Globals)

    const difficulty = globals.strings.difficulty;

    /* Inputs */
    const WELL = "well"
    const MAP_PRESET = "mapPreset"
    const EXPLORABLE_PRESET = "explorablePreset"
    const EXPLORABLE_TIMING_PRESET = "explorableTimingPreset"
    const MAP = "map"
    const RUINS = "ruins"
    const EXPLORABLE_RUINS = "explorable_ruins"
    const MAP_MARGIN_PRESET = "mapMarginPreset"
    const MARGIN_CUSTOM_PREFIX = "margin_custom_"
    const FEATURES_ATTACKS = "features.attacks"

    enum Direction { north, south, west, east };
    const getOppositeDir = (direction: Direction) => {
        return direction + ((direction % 2) === 1 ? -1 : 1);
    };
    const handleCustomMarginChange = (input: HTMLInputElement, direction: Direction) => {
        const direction_opposite = getOppositeDir(direction);

        const margin = parseInt(input.value);
        const margin_opposite = parseInt(globals.getOption( 'rules.margin_custom.'+Direction[direction_opposite]) ?? 25);

        let input_opposite = document.getElementsByName('margin_custom_'+Direction[direction_opposite])[0] as HTMLInputElement;

        const max_value = 100 - margin_opposite;
        input.max = max_value.toString();
        input_opposite.max = (100 - margin).toString();

        globals.setOption('rules.margin_custom.'+Direction[direction], margin);
    }

    const checkCustomMargins = () => {        
        for(const [dir, dir_str] of Object.entries(Direction)) {
            const dir_str_opposite = Direction[getOppositeDir(parseInt(dir))];

            const margin = parseInt(globals.getOption(`rules.margin_custom.${dir_str}`) ?? 25);
            const margin_opposite = parseInt(globals.getOption(`rules.margin_custom.${dir_str_opposite}`) ?? 25);

            if((100 - margin_opposite) < margin) return false;
        }
        return true;
    }

    const getInputByName = (name: string) => document.getElementsByName(name)[0];

    useEffect(() => {
        const fieldCheck = () => {
            try {
                const marginPreset = (getInputByName(MAP_MARGIN_PRESET) as HTMLInputElement).value;
                
                if(marginPreset === '_custom') {
                    if(!checkCustomMargins()) return false;
                }
            } catch(e) {
                return false;
            }
            return true;
        }

        globals.addFieldCheck(fieldCheck);
        return () => globals.removeFieldCheck(fieldCheck);
    }, [])

    return <div data-map-property="rules">
        <h5>{ difficulty.section }</h5>

        { /* Well Level */ }
        <OptionCoreTemplate propName={WELL} propHelp={difficulty.well_help} propTitle={difficulty.well}>
            <select name="wellPreset" value={globals.getOption( 'rules.wellPreset' ) ?? ''}
                onChange={e => {
                    const v =  (e.target as HTMLSelectElement).value;
                    if (v === '_fixed') {
                        if (!globals.getOption('rules.well.min')) globals.setOption('rules.map.min', 120);
                        globals.setOption('rules.map.max', globals.getOption('rules.map.min'));
                    } else if (v === '_range') {
                        if (!globals.getOption('rules.well.min')) globals.setOption('rules.map.min', 90);
                        if (!globals.getOption('rules.well.max')) globals.setOption('rules.map.max', 180);
                    } else {
                        globals.removeOption('rules.well.min');
                        globals.removeOption('rules.well.max');
                    }
                    globals.setOption('rules.wellPreset', v);
                }}>
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
        <OptionSelect value={ globals.getOption( 'rules.mapPreset' ) } propName={MAP_PRESET} propTitle={ difficulty.map }
                      options={ difficulty.map_presets.filter(globals.elevation < 3 ? v=> ['small','normal'].includes(v.value) : ()=>true).map( m => ({ value: m.value, title: m.label }) ) }
                      onChange={e => {
                          const v =  (e.target as HTMLSelectElement).value;
                          if (v === '_custom') {
                              if (!globals.getOption('rules.map.min')) globals.setOption('rules.map.min', 26);
                              if (!globals.getOption('rules.map.max')) globals.setOption('rules.map.max', 26);
                              if (!globals.getOption('rules.ruins')) globals.setOption('rules.ruins', 20);
                              if (!globals.getOption('rules.explorable_ruins')) globals.setOption('rules.explorable_ruins', 1);
                          } else {
                              globals.removeOption('rules.map.min');
                              globals.removeOption('rules.map.max');
                              globals.removeOption('rules.ruins');
                              globals.removeOption('rules.explorable_ruins');
                          }

                          globals.setOption('rules.mapPreset', v);
                      }}
        />
        { globals.getOption( 'rules.mapPreset' ) === '_custom' && (
            <AtLeast elevation="crow">
                <OptionFreeText type="number" value={ globals.getOption( 'rules.map.min' ) as string ?? '26' } propName={MAP}
                                inputArgs={{min: 10, max: 35}} propTitle={ difficulty.map_exact }
                                onChange={e => {
                                    const v =  parseInt((e.target as HTMLInputElement).value);
                                    globals.setOption('rules.map.min', v);
                                    globals.setOption('rules.map.max', v);
                                }}

                />
                <OptionFreeText type="number" value={ globals.getOption( 'rules.ruins' ) as string ?? '20' } propName={RUINS}
                                inputArgs={{min: 0, max: 30}} propTitle={ difficulty.map_ruins }
                />
                <OptionFreeText type="number" value={ globals.getOption( 'rules.explorable_ruins' ) as string ?? '1' } propName={EXPLORABLE_RUINS}
                                inputArgs={{min: 0, max: 3}} propTitle={ difficulty.map_e_ruins }
                />
            </AtLeast>
        ) }

        { /* Explorable Ruin Settings */ }
        { globals.getOption('rules.explorable_ruins') > 0 && <>
            <OptionSelect value={ globals.getOption( 'rules.explorablePreset' ) ?? 'normal' } propName={EXPLORABLE_PRESET} propTitle={ difficulty.explorable }
                          options={ difficulty.explorable_presets.filter(globals.elevation < 3 ? v=> ['classic','normal','large'].includes(v.value) : ()=>true).map( m => ({ value: m.value, title: m.label }) ) }
                          onChange={e => {
                              const v =  (e.target as HTMLSelectElement).value;
                              if (v === '_custom') {
                                  if (!globals.getOption('rules.explorable_ruin_params.space.floors')) globals.setOption('rules.explorable_ruin_params.space.floors', 2);
                                  if (!globals.getOption('rules.explorable_ruin_params.room_config.total')) globals.setOption('rules.explorable_ruin_params.room_config.total', 15);
                                  if (!globals.getOption('rules.explorable_ruin_params.room_config.min')) globals.setOption('rules.explorable_ruin_params.room_config.min', 5);
                              } else {
                                  globals.removeOption('rules.explorable_ruin_params.space.floors');
                                  globals.removeOption('rules.explorable_ruin_params.room_config.total');
                                  globals.removeOption('rules.explorable_ruin_params.room_config.min');
                              }

                              globals.setOption('rules.explorablePreset', v);
                          }}
            />
            { globals.getOption( 'rules.explorablePreset' ) === '_custom' && (
                <AtLeast elevation="crow">
                    <OptionFreeText type="number" value={ globals.getOption( 'rules.explorable_ruin_params.space.floors' ) as string ?? '2' } propName="explorable_ruin_params.space.floors"
                                    inputArgs={{min: 1, max: 5}} propTitle={ difficulty.explorable_floors }

                    />
                    <OptionFreeText type="number" value={ globals.getOption( 'rules.explorable_ruin_params.space.x' ) as string ?? '13' } propName={'explorable_ruin_params.space.x'}
                                    inputArgs={{min: 8, max: 25}} propTitle={ difficulty.explorable_space_x }
                    />
                    <OptionFreeText type="number" value={ globals.getOption( 'rules.explorable_ruin_params.space.y' ) as string ?? '13' } propName={'explorable_ruin_params.space.y'}
                                    inputArgs={{min: 8, max: 25}} propTitle={ difficulty.explorable_space_y }
                    />
                    <OptionFreeText type="number" value={ globals.getOption( 'rules.explorable_ruin_params.room_config.total' ) as string ?? '15' } propName={'explorable_ruin_params.room_config.total'}
                                    inputArgs={{min: 1, max: 50}} propTitle={ difficulty.explorable_rooms }
                    />
                    <OptionFreeText type="number" value={ globals.getOption( 'rules.explorable_ruin_params.room_config.min' ) as string ?? '1' } propName={'explorable_ruin_params.room_config.min'}
                                    inputArgs={{min: 1, max: 15}} propTitle={ difficulty.explorable_min_rooms }
                    />
                </AtLeast>
            ) }

            <AtLeast elevation="crow">
                <OptionSelect value={ globals.getOption( 'rules.explorableTimingPreset' ) ?? 'normal' } propName={EXPLORABLE_TIMING_PRESET} propTitle={ difficulty.explorable_timing }
                              options={ difficulty.explorable_timing_presets.map( m => ({ value: m.value, title: m.label }) ) }
                />
            </AtLeast>
        </> }

        { /* Position Settings */ }
        <AtLeast elevation="crow">
            <OptionSelect value={ globals.getOption( 'rules.mapMarginPreset' ) ?? 'normal' } propName={MAP_MARGIN_PRESET} propTitle={ difficulty.position }
                          options={ difficulty.position_presets.map( m => ({ value: m.value, title: m.label }) ) }
            />
        </AtLeast>
        { globals.getOption( 'rules.mapMarginPreset' ) === '_custom' && (
            <AtLeast elevation="crow">
                {
                    Object.keys(Direction).map((dir_str) => {
                        const dir = Direction[dir_str];
                        if(typeof dir !== 'number') return;

                        return (
                            <OptionFreeText
                                type="number"
                                value={ globals.getOption( `rules.margin_custom.${dir_str}` ) as string ?? '25' }
                                key={`${MARGIN_CUSTOM_PREFIX}${dir_str}`}
                                propName={`${MARGIN_CUSTOM_PREFIX}${dir_str}`}
                                inputArgs={{min: 0, max: 100}}
                                propTitle={ `${difficulty[`position_${dir_str}`]} (%)` }
                                onChange={e => handleCustomMarginChange(e.target as HTMLInputElement, dir as Direction)}
                            />
                        );
                    })
                }
            </AtLeast>
        ) }

        { /* Attack Settings */ }
        <OptionSelect value={ globals.getOption( 'rules.features.attacks' ) } propName={FEATURES_ATTACKS} propTitle={ difficulty.attacks }
                      options={ difficulty.attacks_presets.map( m => ({ value: m.value, title: m.label }) ) }
        />

    </div>;
};