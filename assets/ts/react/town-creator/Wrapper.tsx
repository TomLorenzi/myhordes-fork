import * as React from "react";
import * as ReactDOM from "react-dom";

import Components, {ReactData} from "../index";
import {Global} from "../../defaults";
import {ResponseIndex, ResponseTownList, SysConfig, TownCreatorAPI, TownOptions, TownRules} from "./api";
import {ChangeEvent, useEffect, useRef, useState} from "react";
import {TownCreatorSectionHead} from "./SectionHead";
import {TranslationStrings} from "./strings";
import {element, string} from "prop-types";
import {TownCreatorSectionDifficulty} from "./SectionDifficulty";
import {TownCreatorSectionMods} from "./SectionMods";
import {TownCreatorSectionAnimator} from "./SectionAnimator";
import {TownCreatorSectionAdvanced} from "./SectionAdvanced";

declare var $: Global;

export class HordesTownCreator {
    public static mount(parent: HTMLElement, {api}): void {
        ReactDOM.render(<TownCreatorWrapper api={api} />, parent, () => Components.vitalize( parent ));
    }

    public static unmount(parent: HTMLElement): void {
        if (ReactDOM.unmountComponentAtNode( parent )) $.components.degenerate(parent);
    }
}

type TownCreatorGlobals = {
    api: TownCreatorAPI,

    strings: TranslationStrings,
    config: SysConfig,

    default_rules: TownRules,
    setOption: (dot: string|ChangeEvent, value?: any|null) => void
    getOption: (dot: string) => any
    removeOption: (dot: string) => any
}

export const Globals = React.createContext<TownCreatorGlobals>(null);

const TownCreatorWrapper = ( {api}: {api: string} ) => {

    const apiRef = useRef<TownCreatorAPI>();

    const [index, setIndex] = useState<ResponseIndex>(null)
    const [townTownTypeList, setTownTypeList] = useState<ResponseTownList>()
    const [options, setOptions] = useState<TownOptions|{rules: {}}>({rules: {}})
    const [defaultRules, setDefaultRules] = useState<TownRules|null>(null)

    const [blocked, setBlocked] = useState<boolean>(false);

    useEffect( () => {
        apiRef.current = new TownCreatorAPI(api);
        apiRef.current.index().then( index => setIndex(index) );
        apiRef.current.townList().then( list => setTownTypeList(list) );
        return () => {
            setIndex(null);
            setTownTypeList(null);
            setOptions(null);
            setDefaultRules(null);
        }
    }, [api] )

    const processDot = ( dot: string|string[] ) => {
        if (typeof dot === "string") return processDot( dot.split('.') );

        let search_index = dot.findIndex(v => v === '<<');
        while (search_index >= 0) {
            dot = dot.slice(search_index+1);
            search_index = dot.findIndex(v => v === '<<');
        }

        search_index = dot.findIndex(v => v === '<');
        while (search_index >= 0) {
            dot = search_index === 0 ? dot.slice(1) : [
                ...dot.slice(0,search_index-1),...dot.slice(search_index+1)
            ];
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

        const fun = (obj: object, dot: string[], value: any) => {
            if (dot.length === 0) return value;
            else if (dot.length === 1) {
                const v = typeof obj[dot[0]] === "object" ? JSON.parse( JSON.stringify( obj[dot[0]] ) ) : obj[dot[0]];
                obj[dot[0]] = value;
                return v;
            } else {
                // Set access
                if (dot.length === 3 && dot[1] === '<>') {
                    if (typeof obj[dot[0]] === "undefined") obj[dot[0]] = new Set<string>();
                    else if (typeof obj[dot[0]] === "object") obj[dot[0]] = new Set<string>(obj[dot[0]]);

                    const v = (obj[dot[0]] as Set<string>).has( dot[2] );
                    if (value) (obj[dot[0]] as Set<string>).add( dot[2] );
                    else (obj[dot[0]] as Set<string>).delete( dot[2] );

                    // Save set as array to allow serialization
                    obj[dot[0]] = Array.from( obj[dot[0]] );
                    return v;

                // Array access
                } else if (dot.length === 3 && dot[1] === '[]') {
                    if (typeof obj[dot[0]] === "undefined") obj[dot[0]] = [];
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
                return fun(obj[dot[0]], dot.slice(1), value);
            }
        }

        const dot_p = processDot(dot);

        // Check if the value we set equals the default rule; if it does, remove the setting entirely
        if (defaultRules && dot_p[0] === 'rules' && dot_p.length > 1 && dot_p.findIndex(v => v === '<>' || v === '[]') < 0 && value === getOptionFrom( defaultRules, dot_p.slice(1) )) {
            removeOption( dot_p.join('.') );
        } else {
            const obj = { ...options };
            if ( fun(obj, dot_p, value) !== value) setOptions( obj );
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
        if ( fun(obj, processDot(dot)) ) setOptions( obj );
    }

    return (
        <Globals.Provider value={{ api: apiRef.current, strings: index?.strings, config: index?.config,
            default_rules: defaultRules as TownRules,
            setOption, getOption, removeOption }}>
            { townTownTypeList && index && (
                <form data-disabled={blocked ? 'disabled' : ''}>
                    <TownCreatorSectionHead townTypes={townTownTypeList} setBlocked={setBlocked}
                                            setDefaultRules={v => setDefaultRules(v)}/>

                    { defaultRules as TownRules && <>
                        <TownCreatorSectionAnimator/>
                        <TownCreatorSectionMods/>
                        <TownCreatorSectionDifficulty/>
                        <TownCreatorSectionAdvanced/>
                        <div className="row">
                            <div className="cell padded rw-12">
                                <button type="button" onClick={() => {
                                    if (!confirm( index.strings.common.confirm )) return;
                                    apiRef.current.createTown({...options} as TownOptions ).then(e => console.log(e));
                                }}>{ index.strings.common.create }</button>
                            </div>
                        </div>
                    </> }
                </form>
            ) }
            { !(townTownTypeList && index) && <div>...</div> }
        </Globals.Provider>
    )
};