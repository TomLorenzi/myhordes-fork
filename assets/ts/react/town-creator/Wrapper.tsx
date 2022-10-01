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

    options: TownOptions,
    default_rules: TownRules,
    setOption: (dot: string|ChangeEvent, value?: any|null) => void
}

export const Globals = React.createContext<TownCreatorGlobals>(null);

const TownCreatorWrapper = ( {api}: {api: string} ) => {

    const apiRef = useRef<TownCreatorAPI>();

    const [index, setIndex] = useState<ResponseIndex>(null)
    const [townTownTypeList, setTownTypeList] = useState<ResponseTownList>()
    const [options, setOptions] = useState<TownOptions|{}>({})
    const [defaultRules, setDefaultRules] = useState<TownRules|{}>({})

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

    const setOption = (dot: string|ChangeEvent, value: any|null = null) => {

        if (typeof dot !== "string") {

            let target = ((dot as ChangeEvent).target as HTMLElement);

            const fun_extract_value = ( element: HTMLInputElement ): any => {
                switch (element.type) {
                    case 'checkbox': case 'radio':
                        return element.checked;
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
                target = target.parentElement?.closest<HTMLElement>( '[data-map-property]' );
            }

            let search_index = dot_constructor.findIndex(v => v === '<');
            while (search_index >= 0) {
                dot_constructor = search_index === 0 ? dot_constructor.slice(1) : [
                    ...dot_constructor.slice(0,search_index-1),...dot_constructor.slice(search_index+1)
                ];
                search_index = dot_constructor.findIndex(v => v === '<');
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

        const obj = { ...options };
        if ( fun(obj, dot.split('.'), value) !== value )
            setOptions( obj );
    }

    return (
        <Globals.Provider value={{ api: apiRef.current, options: options as TownOptions, default_rules: defaultRules as TownRules, strings: index?.strings, config: index?.config, setOption }}>
            { townTownTypeList && index && (
                <form data-disabled={blocked ? 'disabled' : ''}>
                    <TownCreatorSectionHead townTypes={townTownTypeList} setBlocked={setBlocked}
                                            setDefaultRules={v => { setDefaultRules(v); setOption('rules', JSON.parse( JSON.stringify(v) )) }}/>

                    { (options as TownOptions).rules && <>
                        <TownCreatorSectionMods rules={(options as TownOptions).rules}/>
                        <TownCreatorSectionDifficulty rules={(options as TownOptions).rules}/>
                    </> }
                </form>
            ) }
            { !(townTownTypeList && index) && <div>...</div> }
        </Globals.Provider>
    )
};