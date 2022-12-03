import * as React from "react";

import {Global} from "../../defaults";
import {ResponseTownList, TownRules} from "./api";
import {useContext, useEffect, useRef} from "react";
import {Globals} from "./Wrapper";
import {OptionCoreTemplate, OptionFreeText, OptionSelect, OptionToggleMulti} from "./Input";
import {number} from "prop-types";
import {BuildingListEntry} from "./strings";

declare var $: Global;

const BuildingConfigSection = ( { buildings, disabled, unlocked, initial, parent_id, parent_disabled, level, titles }: {
    buildings: BuildingListEntry[]
    disabled, unlocked, initial: Set<string>
    parent_id: number|null
    parent_disabled: boolean
    level: number,
    titles: string[]
} ) => {
    const globals = useContext(Globals)

    return <>
        {buildings.filter(b => b.parent === parent_id).map( (building, index, all) => (
            <React.Fragment key={building.id}>
                <div className="row-flex v-center mod" data-disabled={parent_disabled ? 'disabled' : ''}>
                    <div className="cell grow-0">
                        <label>
                            <input title={titles[0]} type="radio" checked={initial.has( building.name )} onChange={e => {
                                const v = (e.target as HTMLInputElement).checked;
                                globals.setOption( `rules.disabled_buildings.<>.${building.name}`, false );
                                globals.setOption( `rules.unlocked_buildings.<>.${building.name}`, false );
                                globals.setOption( `rules.initial_buildings.<>.${building.name}`, v );
                            }}/>
                        </label>
                    </div>
                    <div className="cell grow-0">
                        <label>
                            <input title={titles[1]} type="radio" checked={(unlocked.has( building.name ) || !building.unlockable) && !initial.has( building.name ) && !disabled.has( building.name )} onChange={e => {
                                const v = (e.target as HTMLInputElement).checked;
                                globals.setOption( `rules.disabled_buildings.<>.${building.name}`, false );
                                globals.setOption( `rules.unlocked_buildings.<>.${building.name}`, v );
                                globals.setOption( `rules.initial_buildings.<>.${building.name}`, false );
                            }}/>
                        </label>
                    </div>
                    <div className="cell grow-0">
                        <label data-disabled={!building.unlockable ? 'disabled' : ''}>
                            <input title={titles[2]} type="radio" checked={building.unlockable && !unlocked.has( building.name ) && !initial.has( building.name ) && !disabled.has( building.name )} onChange={e => {
                                const v = (e.target as HTMLInputElement).checked;
                                globals.setOption( `rules.disabled_buildings.<>.${building.name}`, false );
                                globals.setOption( `rules.unlocked_buildings.<>.${building.name}`, false );
                                globals.setOption( `rules.initial_buildings.<>.${building.name}`, false );
                            }}/>
                        </label>
                    </div>
                    <div className="cell grow-0">
                        <label>
                            <input title={titles[3]} type="radio" checked={disabled.has( building.name )} onChange={e => {
                                const v = (e.target as HTMLInputElement).checked;
                                globals.setOption( `rules.disabled_buildings.<>.${building.name}`, v );
                                globals.setOption( `rules.unlocked_buildings.<>.${building.name}`, false );
                                globals.setOption( `rules.initial_buildings.<>.${building.name}`, false );
                            }}/>
                        </label>
                    </div>
                    { [...(new Array(Math.max(0, level - 1))).keys()].map( i => (
                        <div key={`${building.name}-pre-${i}`} className="cell grow-0">
                            <code style={{fontSize: '2rem'}}>│</code>
                        </div>
                    ) ) }
                    { level > 0 && (
                        <div className="cell grow-0">
                            <code style={{fontSize: '2rem'}}>
                                { index < (all.length-1) && '├' }
                                { index === (all.length-1) && '└' }
                            </code>
                        </div>
                    ) }
                    <div className="cell grow-0 padded-small">
                        <label>
                            <img alt={building.label} src={building.icon}/>
                        </label>
                    </div>
                    <div className="cell grow-1">
                        <label>
                            {building.label}
                        </label>
                    </div>
                </div>
                <BuildingConfigSection buildings={buildings} disabled={disabled} unlocked={unlocked} initial={initial}
                                       parent_id={building.id} parent_disabled={parent_disabled || disabled.has( building.name )}
                                       level={level+1} titles={titles} />
            </React.Fragment>

        ) )}
    </>;
}

export const TownCreatorSectionAdvanced = () => {
    const globals = useContext(Globals)

    const advanced = globals.strings.advanced;

    const shaman_setting = globals.getOption( 'rules.features.shaman' );

    const job_set = new Set<string>(globals.getOption( 'rules.disabled_jobs' ) ?? (['role','none'].includes(globals.getOption( 'rules.features.shaman' )) ? ['shaman'] : []));
    const jobs_left = advanced.job_list.reduce( (i, {name}) => i + (job_set.has(name) ? 0 : 1), 0 )

    let init = useRef(false);
    if (!init.current) {
        init.current = true;
        job_set.forEach( s => globals.setOption(`rules.disabled_jobs.<>.${s}`, true) )
    }

    return <div>
        <h5>{ advanced.section }</h5>

        { /* Event setting */ }
        <OptionSelect propTitle={advanced.event_management}
                      value={globals.getOption( 'head.event' ) ?? 'auto'} propName="head.event"
                      options={ [
                          { value: 'auto', title: advanced.event_auto, help: advanced.event_auto_help },
                          { value: 'none', title: advanced.event_none, help: advanced.event_none_help },
                          ...advanced.event_list.map( preset => ({ value: preset.id, title: preset.label, help: advanced.event_any_help + ' ' + preset.desc }) )
                      ] }
        />

        { /* Job setting */ }
        <OptionCoreTemplate propName="head.customJobs" propTitle={advanced.jobs} wide={true} propTip={ advanced.jobs_help }>
            <label>
                <input type="checkbox" name="head.customJobs" checked={globals.getOption( 'head.customJobs' ) as boolean ?? false} onChange={globals.setOption}/>
                { advanced.show_section }
            </label>
            { globals.getOption( 'head.customJobs' ) && (
                <div className="row-table note">
                    { advanced.job_list.map( job => (
                        <div key={job.name} className="row-flex v-center mod" data-disabled={job.name === 'shaman' ? 'disabled' : ''}>
                            <div className="cell grow-0">
                                <input type="checkbox" name={`disabled_job_${job.name}`} checked={!job_set.has( job.name )}
                                       readOnly={ !job_set.has( job.name ) && jobs_left < 2 } data-disabled={!job_set.has( job.name ) && jobs_left < 2 ? 'disabled' : ''}
                                       data-invert-value={true} data-map-property={`<<.rules.disabled_jobs.<>.${job.name}`}
                                       onChange={!job_set.has( job.name ) && jobs_left < 2 ? ()=>{} : globals.setOption}
                                />
                            </div>
                            <div className="cell grow-0 padded-small">
                                <label htmlFor={`disabled_job_${job.name}`}>
                                    <img alt={job.label} src={job.icon}/>
                                </label>
                            </div>
                            <div className="cell grow-1">
                                <label htmlFor={`disabled_job_${job.name}`}>
                                    {job.label}
                                </label>
                            </div>
                        </div>
                    ) ) }
                </div>
            ) }

        </OptionCoreTemplate>

        { /* Building setting */ }
        <OptionCoreTemplate propName="head.customConstructions" propTitle={advanced.buildings} wide={true} propTip={ advanced.buildings_help }>
            <label>
                <input type="checkbox" name="head.customConstructions" checked={globals.getOption( 'head.customConstructions' ) as boolean ?? false} onChange={globals.setOption}/>
                { advanced.show_section }
            </label>
            { globals.getOption( 'head.customConstructions' ) && (
                <div className="row-table note">
                    <div className="row mod">
                        <div className="cell rw-12 padded">
                            <b>{advanced.building_props[0]}</b> / <b>{advanced.building_props[1]}</b> / <b>{advanced.building_props[2]}</b> / <b>{advanced.building_props[3]}</b>
                        </div>
                    </div>
                    <BuildingConfigSection buildings={advanced.buildings_list} titles={advanced.building_props}
                                           disabled={new Set<string>(globals.getOption( 'rules.disabled_buildings' ) ?? [])}
                                           unlocked={new Set<string>(globals.getOption( 'rules.unlocked_buildings' ) ?? [])}
                                           initial={new Set<string>(globals.getOption( 'rules.initial_buildings' ) ?? [])}
                                           parent_id={null} parent_disabled={false} level={0} />
                </div>
            ) }

        </OptionCoreTemplate>
    </div>;
};