import * as React from "react";
import {useContext, useEffect, useLayoutEffect, useRef} from "react";
import {Globals} from "./Wrapper";

export const ControlButtonNodeWrap = ({fa, label, node, control = null}: {fa:string, label: string, node: string, control?: string|null} ) => {

    const globals = useContext(Globals);

    const button = useRef<HTMLDivElement>();

    const handler = () => {
        const body = `${globals.getField('body') ?? ''}`;
        const selection = [globals.selection.start,globals.selection.end]

        const open = `[${node}]`;
        const close = `[/${node}]`;

        // Check if the current selection is already wrapped in the desired tag
        if (
            body.slice( Math.max(0,selection[0] - open.length), selection[0] ) === open &&
            body.slice( selection[1], selection[1] + close.length ) === close
        ) {
            globals.setField('body', `${body.slice(0,selection[0] - open.length)}${body.slice(selection[0],selection[1])}${body.slice(selection[1]+close.length)}`);
            globals.selection.update( selection[0] - open.length, selection[1] - open.length )
        // Check if the current selection starts and ends with the desired tag
        } else if (
            body.slice( selection[0], selection[0] + open.length ) === open &&
            body.slice( Math.max(0, selection[1] - close.length), selection[1] ) === close
        ) {
            globals.setField('body', `${body.slice(0,selection[0])}${body.slice(selection[0]+open.length,selection[1]-close.length)}${body.slice(selection[1])}`);
            globals.selection.update( selection[0], selection[1] - open.length - close.length )
        // Otherwise, add the tags
        } else {
            globals.setField('body', `${body.slice(0,selection[0])}${open}${body.slice(selection[0],selection[1])}${close}${body.slice(selection[1])}`);
            globals.selection.update( selection[0] + open.length, selection[1] + open.length )
        }
    }

    useLayoutEffect(() => {
        const callHandler = (e:CustomEvent) => handler();
        button.current.addEventListener('controlActionTriggered', callHandler);
        return () => button.current.removeEventListener('controlActionTriggered', callHandler);
    })

    return <div className="forum-button" ref={button} data-receive-control-event={control} onClick={e => {
        e.preventDefault();
        e.stopPropagation();
        handler()
    }}>
        <i className={`fa fa-${fa}`}/>
        <span className="forum-button-tooltip">
            <div className="center">
                <div>{label}</div>
                { control !== null && <div className="keyboard">
                    <kbd>{navigator.platform.indexOf("Mac") === 0 ? '⌘' : 'STRG'}</kbd>
                    <span>&nbsp;+&nbsp;</span>
                    <kbd>{control.toUpperCase()}</kbd>
                </div>}
            </div>


        </span>
    </div>
};