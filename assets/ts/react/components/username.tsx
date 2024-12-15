import {HTMLAttributes, useLayoutEffect, useRef} from "react";
import {Tag} from "../index";
import * as React from "react";
import {Global} from "../../defaults";

declare var $: Global;

export default function Username(props: HTMLAttributes<HTMLElement>&{userId: number, tagName?: string, userName?: string, children?: React.ReactNode|React.ReactNode[]}) {
    const me = useRef<HTMLElement>(null);

    useLayoutEffect(() => {
        if (!me.current) return;
        const payload = $.html.handleUserPopup( me.current, props.userId );
        return () => $.html.discardUserPopup(payload);
    }, [props.id])

    const htmlProps = {...props};
    delete htmlProps.userId;
    delete htmlProps.tagName;
    delete htmlProps.userName;
    delete htmlProps.children;

    return <Tag elementRef={me} tagName={props.tagName ?? 'div'} classNames="username" {...htmlProps}>
        {props.userName ?? props.children}
    </Tag>
}