import * as React from "react";

import {Global} from "../../defaults";
import {ResponseTownList, TownRules} from "./api";
import {useContext, useEffect, useRef, useState} from "react";
import {Globals} from "./Wrapper";
import {OptionCoreTemplate, OptionFreeText, OptionSelect, OptionToggleMulti} from "./Input";
import {number} from "prop-types";
import {AtLeast} from "./Permissions";
import {UserResponse, UserResponses, UserSearchBar} from "../user-search/Wrapper";

declare var $: Global;

export const TownCreatorSectionHead = ( {townTypes, setDefaultRules, setBlocked}: {townTypes: ResponseTownList, setDefaultRules: (rules: TownRules) => void, setBlocked: (block: boolean) => void} ) => {
    const globals = useContext(Globals)

    const head = globals.strings.head;

    const type_default = (globals.elevation < 3 ? townTypes : [])
        .reduce( (value, object) => !object.preset ? object.id : value, -1 )

    const appliedDefaults: {dot: string, default: any|string}[] = [
        { dot: 'head.townLang', default: globals.config.default_lang },
        { dot: 'head.townNameLang', default: globals.config.default_lang },
        { dot: 'head.townType', default: type_default},
        { dot: 'head.townBase', default: -1},
    ];

    const [enableReservedPlaces, setEnableReservedPlaces] = useState<boolean>(false);
    const [reservedPlaces, setReservedPlaces] = useState<UserResponses>([]);

    useEffect(() =>
        appliedDefaults.forEach( d => {
            globals.setOption( d.dot, d.default );
        } )
    , [townTypes]);

    const fun_typeHasPreset = (id: string|number, defaultValue: boolean = false) =>
        townTypes.reduce( (value, object) => object.id == id ? object.preset : value, defaultValue );

    useEffect( () => {

        const type_id = globals.getOption( 'head.townType' ) ?? -1;
        const base_id = globals.getOption( 'head.townBase' ) ?? -1;
        const id = fun_typeHasPreset( type_id )
            ? parseInt( `${type_id}`)
            : (fun_typeHasPreset( base_id )
                ? parseInt( `${base_id}`)
                : -1)

        if (id > 0) {
            setBlocked(true);
            globals.api.townRulesPreset(id, !fun_typeHasPreset( type_id )).then(v => {
                setDefaultRules(v);
                setBlocked(false)
            });
        }

    }, [globals.getOption( 'head.townType' ), globals.getOption( 'head.townBase' )])

    const addReserved = (u: UserResponses) => {
        const to_add = u.filter( u => reservedPlaces.findIndex( uu => uu.id === u.id ) === -1 );
        if (to_add.length > 0) {
            const newList = [...reservedPlaces, ...to_add];
            setReservedPlaces(newList);
            globals.setOption( 'head.reserve', newList.map(u=>u.id) );
        }
    }

    const removeReserved = (u: UserResponse) => {
        const index = reservedPlaces.findIndex( uu => uu.id === u.id );
        if (index !== -1) {
            const newList = [...reservedPlaces.slice(0, index), ...reservedPlaces.slice(index + 1)];
            setReservedPlaces(newList);
            globals.setOption( 'head.reserve', newList.map(u=>u.id) );
        }
    }

    return <div data-map-property="head">
        <h5>{ head.section }</h5>

        { /* Town Name */ }
        <AtLeast elevation="crow">
            <OptionFreeText propTitle={head.town_name} propTip={head.town_name_help}
                            value={globals.getOption( 'head.townName' )} propName="townName"
            />
        </AtLeast>

        { /* Town Language */ }
        <OptionSelect propTitle={head.lang}
                      value={globals.getOption( 'head.townLang' )} propName="townLang"
                      options={ head.langs.map( lang => ({ value: lang.code, title: lang.label }) ) }
                      onChange={e => {
                          const v =  (e.target as HTMLSelectElement).value;
                          globals.setOption('head.townLang', v)
                          globals.setOption('head.townNameLang', v);
                      }}
        />

        { /* Town Name Language */ }
        { globals.getOption('head.townLang') === 'multi' && (
            <OptionSelect propTitle={head.name_lang}
                          value={globals.getOption( 'head.townNameLang' )} propName="townNameLang"
                          options={ head.langs.map( lang => ({ value: lang.code, title: lang.label }) ) }
            />
        )}

        { /* Town Code */ }
        <OptionFreeText propTitle={head.code} propTip={head.code_help}
                        value={globals.getOption( 'head.townCode' )} propName="townCode"
        />

        { /* Reserved spaces */ }
        <OptionCoreTemplate propName="head.reserve" propTitle="" wide={ reservedPlaces.length > 0 || enableReservedPlaces }>
            { reservedPlaces.length === 0 && !enableReservedPlaces && <button onClick={()=>setEnableReservedPlaces(true)}>{ head.reserve }</button> }
            { (reservedPlaces.length > 0 || enableReservedPlaces) && (
                <>
                    <div className="save-spots-container">
                        { reservedPlaces.length === 0 && <div className="placeholder">{ head.reserve_none }</div> }
                        { reservedPlaces.length > 0 && <>
                            <div className="placeholder">{ head.reserve_num } { reservedPlaces.length }</div>
                            { reservedPlaces.map( u => <div key={u.id} className="town-reserved-spot">{ u.name }<span onClick={()=>removeReserved(u)}><i className="fa fa-times-circle pointer"></i></span></div> ) }
                        </> }
                    </div>
                    <h5>{ head.reserve_add }</h5>
                    <UserSearchBar callback={u=>addReserved(u)} exclude={reservedPlaces.map(u=>u.id)} clearOnCallback={true} acceptCSVListSearch={true}/>
                    <div className="help" dangerouslySetInnerHTML={{__html: head.reserve_help}}/>
                </>
            ) }
        </OptionCoreTemplate>

        { /* Number of citizens */ }
        <AtLeast elevation="crow">
            <OptionFreeText type="number" propTitle={head.citizens} propHelp={head.citizens_help}
                            inputArgs={{min: 10, max: 80}}
                            value={(globals.getOption( 'head.townPop' ) as string) ?? '40'} propName="townPop"
            />

            { /* Number of citizens */ }
            <OptionFreeText type="number" propTitle={head.seed} propHelp={head.seed_help}
                            value={(globals.getOption( 'head.townSeed' ) as string) ?? '-1'} propName="townSeed"
            />

            { /* Management Settings */ }
            <OptionToggleMulti propName="features.<" options={[
                { value: globals.getOption( 'head.townEventTag' ) as boolean, name: '<.head.townEventTag', title: head.management.event_tag, help: head.management.event_tag_help },
            ]} propTitle={head.management.section}/>

            { /* Participation Settings */ }
            <OptionSelect propTitle={head.participation}
                          value={globals.getOption( 'head.townIncarnation' ) ?? 'none'} propName="<.head.townIncarnation"
                          options={ head.participation_presets.map( preset => ({ value: preset.value, title: preset.label, help: preset.help }) ) }
                          onChange={e => {
                              const v = (e.target as HTMLSelectElement).value;
                              globals.setOption('head.townIncarnation', v);
                              if (v === 'incarnate') globals.removeOption( 'head.townSchedule' );
                          }}
            />

            { /* Scheduler Settings */ }
            <OptionFreeText propTitle={head.schedule} type={ "datetime-local" } propHelp={head.schedule_help}
                            value={ globals.getOption( 'head.townSchedule' ) } propName="head.townSchedule"
                            onChange={e => {
                                globals.setOption('head.townSchedule', (e.target as HTMLInputElement).value);
                                if ((globals.getOption( 'head.townIncarnation' ) ?? 'none') === 'incarnate')
                                    globals.setOption( 'head.townIncarnation', 'none' );
                            }}
            />

            { /* Town Type */ }
            <OptionSelect propTitle={head['type']} type="number"
                          value={`${globals.getOption( 'head.townType' ) ?? -1}`} propName="townType"
                          options={ [
                              ...( globals.getOption( 'head.townType' ) == -1 ? [{value: '-1', title: globals.strings.common.need_selection}] : [] ),
                              ...townTypes.map( town => ({ value: `${town.id}`, title: town.name }) )
                          ] }
            />
        </AtLeast>

        { /* Town Preset */ }
        { !fun_typeHasPreset( globals.getOption( 'head.townType' ), true ) && (
            <OptionSelect propTitle={head.base} type="number"
                          value={`${globals.getOption( 'head.townBase' ) ?? -1}`} propName="townBase"
                          options={ [
                              ...( globals.getOption( 'head.townBase' ) == -1 ? [{value: '-1', title: globals.strings.common.need_selection}] : [] ),
                              ...townTypes.filter(town => town.preset).map( town => ({ value: `${town.id}`, title: town.name }) )
                          ] }
            />
        ) }
    </div>;
};