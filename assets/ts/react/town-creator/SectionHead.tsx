import * as React from "react";

import {Global} from "../../defaults";
import {ResponseTownList, TownRules} from "./api";
import {useContext, useEffect, useRef} from "react";
import {Globals} from "./Wrapper";
import {OptionFreeText, OptionSelect, OptionToggleMulti} from "./Input";

declare var $: Global;

export const TownCreatorSectionHead = ( {townTypes, setDefaultRules, setBlocked}: {townTypes: ResponseTownList, setDefaultRules: (rules: TownRules) => void, setBlocked: (block: boolean) => void} ) => {
    const globals = useContext(Globals)

    const head = globals.strings.head;

    const appliedDefaults: {prop: string|any, dot: string, default: string}[] = [
        { prop: globals.options?.head?.townLang, dot: 'head.townLang', default: globals.config.default_lang },
        { prop: globals.options?.head?.townType, dot: 'head.townType', default: '-1'},
        { prop: globals.options?.head?.townBase, dot: 'head.townBase', default: '-1'},
    ];

    useEffect(() =>
        appliedDefaults.forEach( d => {
            if (!d.prop) globals.setOption( d.dot, d.default );
        } )
    );

    const fun_typeHasPreset = (id: string|number, defaultValue: boolean = false) =>
        townTypes.reduce( (value, object) => object.id == id ? object.preset : value, defaultValue );

    useEffect( () => {

        const type_id = globals.options?.head?.townType ?? -1;
        const base_id = globals.options?.head?.townBase ?? -1;
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

    }, [globals.options?.head?.townType, globals.options?.head?.townBase])

    return <div data-map-property="head">
        <h5>{ head.section }</h5>

        { /* Town Name */ }
        <OptionFreeText propTitle={head.town_name}
                        value={globals.options?.head?.townName} propName="townName"
        />

        { /* Town Language */ }
        <OptionSelect propTitle={head.lang}
                      value={globals.options?.head?.townLang} propName="townLang"
                      options={ head.langs.map( lang => ({ value: lang.code, title: lang.label }) ) }
        />

        { /* Number of citizens */ }
        <OptionFreeText type="number" propTitle={head.citizens} propHelp={head.citizens_help}
                        inputArgs={{min: 10, max: 80}}
                        value={(globals.options?.head?.townPop as string) ?? '40'} propName="townPop"
        />

        { /* Number of citizens */ }
        <OptionFreeText type="number" propTitle={head.seed} propHelp={head.seed_help}
                        value={(globals.options?.head?.townSeed as string) ?? '-1'} propName="townSeed"
        />

        { /* Town Type */ }
        <OptionSelect propTitle={head['type']}
                      value={`${globals.options?.head?.townType ?? -1}`} propName="townType"
                      options={ [
                          ...( globals.options?.head?.townType === '-1' ? [{value: '-1', title: globals.strings.common.need_selection}] : [] ),
                          ...townTypes.map( town => ({ value: `${town.id}`, title: town.name }) )
                      ] }
        />

        { /* Town Preset */ }
        { !fun_typeHasPreset( globals.options?.head?.townType, true ) && (
            <OptionSelect propTitle={head.base}
                          value={`${globals.options?.head?.townBase ?? -1}`} propName="townBase"
                          options={ [
                              ...( globals.options?.head?.townBase === '-1' ? [{value: '-1', title: globals.strings.common.need_selection}] : [] ),
                              ...townTypes.filter(town => town.preset).map( town => ({ value: `${town.id}`, title: town.name }) )
                          ] }
            />
        ) }

        { /* Additional settings */ }
        <OptionToggleMulti propName="townOpts" propTitle={head.settings.section}
                           options={[
                               {name: 'noApi', value: !!globals.options?.head?.townOpts?.noApi, title: head.settings.disable_api, help: head.settings.disable_api_help },
                               {name: 'alias', value: !!globals.options?.head?.townOpts?.alias, title: head.settings.alias,       help: head.settings.alias_help },
                               {name: 'ffa',   value: !!globals.options?.head?.townOpts?.ffa,   title: head.settings.ffa,         help: head.settings.ffa_help },
                           ]}
        />

    </div>;
};