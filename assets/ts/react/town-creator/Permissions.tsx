import * as React from "react";
import {
    ChangeEventHandler,
    HTMLInputTypeAttribute,
    InputHTMLAttributes,
    useContext,
    useLayoutEffect,
    useRef
} from "react";
import {Globals} from "./Wrapper";
import {Global} from "../../defaults";

declare var $: Global;

interface OptionTemplateArgs {
    propName: string
    propTitle?: string
    propHelp?: string
    propTip?: string
}

interface OptionCoreTemplateArgs extends OptionTemplateArgs {
    children: React.ReactNode,
    wide?: boolean
}

interface PermissionArgs  {
    elevation?: "crow"|"admin"|"super",

    notForEvents?: boolean,
    onlyForEvents?: boolean,

    children: React.ReactNode,
}

export const AtLeast = (props: PermissionArgs) => {
    const globals = useContext(Globals)

    let can = true;
    if (props.notForEvents && globals.eventMode) can = false;
    if (props.onlyForEvents && !globals.eventMode) can = false;
    else if (props.elevation) {
        if (globals.elevation < 3 && props.elevation === "crow") can = false;
        if (globals.elevation < 4 && props.elevation === "admin") can = false;
        if (globals.elevation < 5 && props.elevation === "super") can = false;
    }

    return <>{ can ? props.children : null }</>;
};