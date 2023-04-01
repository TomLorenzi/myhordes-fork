import {useContext} from "react";
import {Globals} from "./Wrapper";
import * as React from "react";

export const Flag = ( {lang, className, width}: {
    lang: string,
    className?: string|null,
    width?: number|null
} ) => {
    const globals = useContext(Globals)

    let props = {};
    if (className) props['className'] = className;
    if (width) props['width'] = width;

    return <img alt={lang} src={globals.strings.common.flags[lang] ?? globals.strings.common.flags['multi']} {...props}/>
};