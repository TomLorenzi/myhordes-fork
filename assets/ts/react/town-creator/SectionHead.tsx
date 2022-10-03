import * as React from "react";

import {Global} from "../../defaults";
import {ResponseTownList, TownRules} from "./api";
import {useContext, useEffect, useRef} from "react";
import {Globals} from "./Wrapper";
import {OptionFreeText, OptionSelect, OptionToggleMulti} from "./Input";
import {number} from "prop-types";

declare var $: Global;

export const TownCreatorSectionHead = ( {townTypes, setDefaultRules, setBlocked}: {townTypes: ResponseTownList, setDefaultRules: (rules: TownRules) => void, setBlocked: (block: boolean) => void} ) => {
    const globals = useContext(Globals)

    const head = globals.strings.head;

    const appliedDefaults: {dot: string, default: any|string}[] = [
        { dot: 'head.townLang', default: globals.config.default_lang },
        { dot: 'head.townType', default: -1},
        { dot: 'head.townBase', default: -1},
    ];

    useEffect(() =>
        appliedDefaults.forEach( d => {
            if (!globals.getOption( d.dot ) ) globals.setOption( d.dot, d.default );
        } )
    );

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

    return <div data-map-property="head">
        <h5>{ head.section }</h5>

        { /* Town Name */ }
        <OptionFreeText propTitle={head.town_name} propTip={head.town_name_help}
                        value={globals.getOption( 'head.townName' )} propName="townName"
        />

        { /* Town Language */ }
        <OptionSelect propTitle={head.lang}
                      value={globals.getOption( 'head.townLang' )} propName="townLang"
                      options={ head.langs.map( lang => ({ value: lang.code, title: lang.label }) ) }
        />

        { /* Town Name */ }
        <OptionFreeText propTitle={head.code} propTip={head.code_help}
                        value={globals.getOption( 'head.townCode' )} propName="townCode"
        />

        { /* Number of citizens */ }
        <OptionFreeText type="number" propTitle={head.citizens} propHelp={head.citizens_help}
                        inputArgs={{min: 10, max: 80}}
                        value={(globals.getOption( 'head.townPop' ) as string) ?? '40'} propName="townPop"
        />

        { /* Number of citizens */ }
        <OptionFreeText type="number" propTitle={head.seed} propHelp={head.seed_help}
                        value={(globals.getOption( 'head.townSeed' ) as string) ?? '-1'} propName="townSeed"
        />

        { /* Town Type */ }
        <OptionSelect propTitle={head['type']} type="number"
                      value={`${globals.getOption( 'head.townType' ) ?? -1}`} propName="townType"
                      options={ [
                          ...( globals.getOption( 'head.townType' ) == -1 ? [{value: '-1', title: globals.strings.common.need_selection}] : [] ),
                          ...townTypes.map( town => ({ value: `${town.id}`, title: town.name }) )
                      ] }
        />

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