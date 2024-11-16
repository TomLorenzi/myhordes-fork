import * as React from "react";

import Components, {BaseMounter} from "../index";
import {Global} from "../../defaults";
import {ResponseIndex, ResponseTownList, SysConfig, TownCreatorAPI, TownOptions, TownRules} from "./api";
import {ChangeEvent, useEffect, useLayoutEffect, useRef, useState} from "react";
import {TownCreatorSectionHead} from "./SectionHead";
import {TranslationStrings} from "./strings";
import {TownCreatorSectionDifficulty} from "./SectionDifficulty";
import {TownCreatorSectionMods} from "./SectionMods";
import {TownCreatorSectionAnimator} from "./SectionAnimator";
import {TownCreatorSectionAdvanced} from "./SectionAdvanced";
import {AtLeast} from "./Permissions";
import {TownCreatorSectionTemplate} from "./SectionTemplate";

declare var $: Global;

interface mountProps {
    elevation: number,
    eventMode: boolean,
    presetHead: any|null,
    presetRules: any|null
}

export class HordesTownCreator extends BaseMounter<mountProps> {
    protected render(props: mountProps): React.ReactNode {
        return <TownCreatorWrapper {...props} />;
    }
}

type TownCreatorGlobals = {
    api: TownCreatorAPI,
    elevation: number,
    eventMode: boolean,

    strings: TranslationStrings,
    config: SysConfig,

    default_rules: TownRules,
    setOption: (dot: string|ChangeEvent, value?: any|null) => void
    getOption: (dot: string) => any
    removeOption: (dot: string) => any

    addFieldCheck: (check: () => boolean) => void
    removeFieldCheck: (check: () => boolean) => void
}

export const Globals = React.createContext<TownCreatorGlobals>(null);

const TownCreatorWrapper = ( {elevation, eventMode, presetHead, presetRules}: {elevation: number, eventMode: boolean, presetHead: any|null, presetRules: any|null} ) => {

    const apiRef = useRef<TownCreatorAPI>();

    const wrapper = useRef<HTMLDivElement>();

    const [index, setIndex] = useState<ResponseIndex>(null)
    const [townTownTypeList, setTownTypeList] = useState<ResponseTownList>()
    const [options, setOptions] = useState<TownOptions|object>({rules: {}, head: {}})
    const [defaultRules, setDefaultRules] = useState<TownRules|null>(null)

    const [blocked, setBlocked] = useState<boolean>(false);

    const [fieldChecks, setFieldChecks] = useState<(() => boolean)[]>([]);

    useEffect( () => {
        apiRef.current = new TownCreatorAPI();
        apiRef.current.index().then( index => setIndex(index) );
        apiRef.current.townList().then( list => {
            setTownTypeList(list);
            if (presetHead || presetRules) {
                setOptions({head: presetHead, rules: presetRules});
                if (presetHead) {
                    setBlocked(true);

                    const preset = list.find( v=>v.id === (parseInt(presetHead.townType) ?? -1) )?.preset ?? true;
                    apiRef.current.townRulesPreset(preset ? parseInt(presetHead.townType) : parseInt(presetHead.townBase), !preset).then(v => {
                        setDefaultRules(v);
                        setBlocked(false);
                    });
                }
            }
        } );
        return () => {
            setIndex(null);
            setTownTypeList(null);
            setOptions(null);
            setDefaultRules(null);
            setFieldChecks(null);
        }
    }, [] )

    const addFieldCheck = (check: () => boolean) => fieldChecks.push(check);
    const removeFieldCheck = (check: () => boolean) => setFieldChecks(fieldChecks.filter(existingCheck => existingCheck !== check));

    const fun_announce = options => {
        wrapper.current.dispatchEvent( new CustomEvent(
            'rules-changed',
            { bubbles: true, cancelable: false, detail: {options, ready: !!defaultRules } }
        ) )
    }

    const processDot = ( dot: string|string[] ) => {
        if (typeof dot === "string") return processDot( dot.split('.') );

        let search_index = dot.findIndex(v => v === '<<');
        while (search_index >= 0) {
            dot = dot.slice(search_index+1) as string[];
            search_index = dot.findIndex(v => v === '<<');
        }

        search_index = dot.findIndex(v => v === '<');
        while (search_index >= 0) {
            dot = (search_index === 0 ? dot.slice(1) : [
                ...dot.slice(0,search_index-1),...dot.slice(search_index+1)
            ]) as string[];
            search_index = dot.findIndex(v => v === '<');
        }

        return dot;
    }

    const getOptionFrom = (obj: object, dot: string[]) => {
        const fun = (obj: object, dot: string[]) => {
            if (dot.length === 0) return null;
            else if ( typeof obj[dot[0]] === "undefined") return undefined;
            else if (dot.length === 1)
                return obj[dot[0]];
            else {
                // Set access
                if (dot.length === 3 && dot[1] === '<>') {
                    if (typeof obj[dot[0]] === "undefined") return false;
                    else if (typeof obj[dot[0]] === "object") return (new Set<string>(obj[dot[0]])).has( dot[2] );
                    else return false;

                // Array access
                } else if (dot.length === 3 && dot[1] === '[]') {
                    if (typeof obj[dot[0]] === "undefined") return false;
                    else if (typeof obj[dot[0]] === "object") return (Array.from( obj[dot[0]] )).findIndex(v=>v===dot[2]) >= 0;
                    else return false;
                }

                return fun(obj[dot[0]], dot.slice(1));
            }
        }

        const data = fun( obj, dot );
        return typeof data === 'object' ? JSON.parse( JSON.stringify( data ) ) : data;
    }

    const getOption = (dot: string) => {
        const dot_p = processDot(dot);

        if (dot_p[0] === 'head') return getOptionFrom( options, dot_p );
        if (dot_p[0] === 'rules') {

            if (dot_p.findIndex(v => v === '<>' || v === '[]') >= 0)
                return getOptionFrom( options, dot_p )
            else return getOptionFrom(options, dot_p) ?? getOptionFrom(defaultRules, dot_p.slice(1));
        }
    }

    const setOption = (dot: string|ChangeEvent, value: any|null = null) => {

        if (typeof dot !== "string") {

            let target = ((dot as ChangeEvent).target as HTMLElement);

            const fun_extract_value = ( element: HTMLInputElement ): any => {
                switch ( element.dataset.valueType || element.type ) {
                    case 'checkbox': case 'radio':
                        return element.dataset.invertValue ? !element.checked : element.checked;
                    case 'number':
                        return parseFloat( element.value )
                    default:
                        return element.value;
                }
            }

            const value = fun_extract_value((dot as ChangeEvent).target as HTMLInputElement);
            let dot_constructor = [];

            while ( target ) {
                const accessor = (target.dataset?.mapProperty ?? '*').replace( '*', target.dataset.propName ?? target.getAttribute('name') ?? target.getAttribute('id') ?? 'this' ).split('.');
                if (accessor) dot_constructor = [ ...accessor, ...dot_constructor ];
                target = target.parentElement?.closest<HTMLElement>( 'hordes-town-creator,[data-map-property]' );
                if (target.tagName === 'HORDES-TOWN-CREATOR') target = null;        // Do not leave the base tag!
            }

            return setOption( dot_constructor.join('.'), value );
        }

        const fun = (obj: object, dot: string[], value: any, default_value: any) => {
            if (dot.length === 0) return value;
            else if (dot.length === 1) {
                const v = typeof obj[dot[0]] === "object" ? JSON.parse( JSON.stringify( obj[dot[0]] ) ) : obj[dot[0]];
                obj[dot[0]] = value;
                return v;
            } else {
                // Set access
                if (dot.length === 3 && dot[1] === '<>') {
                    if (typeof obj[dot[0]] === "undefined") obj[dot[0]] = new Set<string>( default_value ?? [] );
                    else if (typeof obj[dot[0]] === "object") obj[dot[0]] = new Set<string>(obj[dot[0]]);

                    const v = (obj[dot[0]] as Set<string>).has( dot[2] );
                    if (value) (obj[dot[0]] as Set<string>).add( dot[2] );
                    else (obj[dot[0]] as Set<string>).delete( dot[2] );

                    // Save set as array to allow serialization
                    obj[dot[0]] = Array.from( obj[dot[0]] );
                    return v;

                // Array access
                } else if (dot.length === 3 && dot[1] === '[]') {
                    if (typeof obj[dot[0]] === "undefined") obj[dot[0]] = Array.from( default_value ?? [] );
                    else if (typeof obj[dot[0]] === "object") obj[dot[0]] = Array.from( obj[dot[0]] );

                    if (value) {
                        (obj[dot[0]] as string[]).push(dot[2]);
                        return false;
                    }
                    else {
                        const index = (obj[dot[0]] as string[]).findIndex(v => v === dot[2]);
                        if (index < 0) return false;

                        (obj[dot[0]] as string[]).splice( index, 1 );
                        return true;
                    }
                }

                else if (typeof obj[dot[0]] === "undefined") obj[dot[0]] = {};
                return fun(obj[dot[0]], dot.slice(1), value, default_value);
            }
        }

        const dot_p = processDot(dot);

        // Check if the value we set equals the default rule; if it does, remove the setting entirely
        if (defaultRules && dot_p[0] === 'rules' && dot_p.length > 1 && dot_p.findIndex(v => v === '<>' || v === '[]') < 0 && value === getOptionFrom( defaultRules, dot_p.slice(1) )) {
            removeOption( dot_p.join('.') );
        } else {
            const defaultValue = dot_p[0] === 'rules' && dot_p.findIndex(v => v === '<>' || v === '[]') > 1 ?
                getOptionFrom( defaultRules, dot_p.slice(1, dot_p.findIndex(v => v === '<>' || v === '[]')) ) : undefined;

            const obj = { ...options };
            if ( fun(obj, dot_p, value, defaultValue) !== value) {
                setOptions(obj);
                if (eventMode) fun_announce(obj);
            };
        }
    }

    const removeOption = (dot: string) => {
        const fun = (obj: object, dot: string[]) => {
            if (dot.length === 0) return false;
            else if ( typeof obj[dot[0]] === "undefined") return false;
            else if (dot.length === 1) {
                delete obj[dot[0]];
                return true;
            } else return fun(obj[dot[0]], dot.slice(1));
        }

        const obj = { ...options };
        if ( fun(obj, processDot(dot)) ) {
            setOptions(obj);
            if (eventMode) fun_announce(obj);
        }
    }

    useLayoutEffect( () => Components.vitalize( wrapper.current ) )

    const checkFields = (): boolean => {
        return fieldChecks.reduce((state, fieldCheck) => state && fieldCheck(), true);
    }

    return (
        <div ref={wrapper}>
            <Globals.Provider value={{ api: apiRef.current, strings: index?.strings, config: index?.config,
                default_rules: defaultRules as TownRules, elevation, eventMode,
                setOption, getOption, removeOption, addFieldCheck, removeFieldCheck }}>
                { townTownTypeList && index && (
                    <form data-disabled={blocked ? 'disabled' : ''}>
                        <TownCreatorSectionHead townTypes={townTownTypeList} setBlocked={setBlocked}
                                                setDefaultRules={v => setDefaultRules(v)} applyDefaults={!presetHead}/>

                        { defaultRules as TownRules && <>
                            <TownCreatorSectionTemplate getOptions={ () => (options as TownOptions).rules } />
                            <AtLeast elevation="crow">
                                <TownCreatorSectionAnimator/>
                            </AtLeast>
                            <TownCreatorSectionMods/>
                            <TownCreatorSectionDifficulty/>
                            <AtLeast elevation="crow">
                                <TownCreatorSectionAdvanced/>
                            </AtLeast>
                            { !eventMode && (
                                <div className="row">
                                    <div className="cell padded rw-12">
                                        { getOption('rules.open_town_limit') === 2 && (
                                            <>
                                                <div className="warning">
                                                    <strong>{ index.strings.common.notice } </strong>
                                                    { index.strings.common.negate }
                                                </div>
                                                <br/>
                                            </>
                                        ) }
                                        <button type="button" onClick={() => {
                                            if (!confirm( index.strings.common.confirm )) return;

                                            if (!checkFields()) {
                                                alert( index.strings.common.incorrect_fields );
                                                return;
                                            }

                                            apiRef.current.createTown(options as TownOptions)
                                                .then( r => $.ajax.load(null, r.url, true) )

                                        }}>{ index.strings.common.create }</button>
                                    </div>
                                </div>
                            ) }
                        </> }
                    </form>
                ) }
                { !(townTownTypeList && index) && <div className="loading"></div> }
            </Globals.Provider>
        </div>
    )
};