import * as React from "react";
import * as ReactDOM from "react-dom";

import Components, {ReactData} from "../index";
import {Global} from "../../defaults";
import {ResponseIndex, ResponseTownList, SysConfig, TownCreatorAPI, TownOptions} from "./api";
import {ChangeEvent, useEffect, useRef, useState} from "react";
import {TownCreatorSectionHead} from "./SectionHead";
import {TranslationStrings} from "./strings";
import {element} from "prop-types";
import {TownCreatorSectionDifficulty} from "./SectionDifficulty";

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
    setOption: (dot: string|ChangeEvent, value?: any|null) => void
}

export const Globals = React.createContext<TownCreatorGlobals>(null);

const TownCreatorWrapper = ( {api}: {api: string} ) => {

    const apiRef = useRef<TownCreatorAPI>();

    const [index, setIndex] = useState<ResponseIndex>(null)
    const [townTownTypeList, setTownTypeList] = useState<ResponseTownList>()
    const [options, setOptions] = useState<TownOptions|{}>({})

    useEffect( () => {
        apiRef.current = new TownCreatorAPI(api);
        apiRef.current.index().then( index => setIndex(index) );
        apiRef.current.townList().then( list => setTownTypeList(list) );
        return () => {
            setIndex(null);
            setTownTypeList(null);
            setOptions(null);
        }
    }, [api] )

    console.log( options );

    const setOption = (dot: string|ChangeEvent, value: any|null = null) => {

        if (typeof dot !== "string") {
            let dot_constructor = [];
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

            while ( target ) {
                const accessor = (target.dataset?.mapProperty ?? '*').replace( '*', target.dataset.propName ?? target.getAttribute('name') ?? target.getAttribute('id') ?? 'this' );
                if (accessor) dot_constructor = [ accessor, ...dot_constructor ];
                target = target.parentElement?.closest<HTMLElement>( '[data-map-property]' );
            }

            return setOption( dot_constructor.join('.'), value );
        }

        const fun = (obj: object, dot: string[], value: any) => {
            if (dot.length === 0) return value;
            else if (dot.length === 1) {
                const v = obj[dot[0]];
                obj[dot[0]] = value;
                return v;
            } else {
                if (typeof obj[dot[0]] === "undefined") obj[dot[0]] = {};
                return fun(obj[dot[0]], dot.slice(1), value);
            }
        }

        const obj = { ...options };
        if ( fun(obj, dot.split('.'), value) !== value )
            setOptions( obj );
    }

    return (
        <Globals.Provider value={{ api: apiRef.current, options: options as TownOptions, strings: index?.strings, config: index?.config, setOption }}>
            { townTownTypeList && index && (
                <form>
                    <TownCreatorSectionHead townTypes={townTownTypeList}></TownCreatorSectionHead>

                    { (options as TownOptions).rules && <>
                        <TownCreatorSectionDifficulty rules={(options as TownOptions).rules}/>
                    </> }
                </form>
            ) }
            { !(townTownTypeList && index) && <div>...</div> }
        </Globals.Provider>
    )
};