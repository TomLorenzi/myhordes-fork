import * as React from "react";
import {useContext, useEffect, useLayoutEffect, useRef, useState} from "react";
import {Globals} from "./Wrapper";

export const TwinoEditorControls = () => {

    const globals = useContext(Globals);

    const [showControls, setShowControls] = useState( !globals.isEnabled('compact') )

    return <>
        {!showControls && <a className="float-right pointer" onClick={() => setShowControls(true)}>
            Zum erweiterten Editor wechseln</a>
        }
        {showControls && <>
            <div className="forum-button-bar">
                <div className="forum-button-bar-section">
                    <ControlButtonNodeWrap node="b" label="Fett" fa="bold" control="b" />
                    <ControlButtonNodeWrap node="i" label="Kursiv" fa="italic" control="i" />
                    <ControlButtonNodeWrap node="u" label="Unterstreichen" fa="underline" control="u" />
                    <ControlButtonNodeWrap node="s" label="Durchstreichen" fa="strikethrough" control="s" />
                    { globals.allowControl('extended') && <>
                        <ControlButtonNodeWrap node="big" label="Groß" fa="expand-alt" control="+" />
                        <ControlButtonNodeWrap node="bad" label="Verräter" fa="tint" />
                        <ControlButtonNodeWrap node="c" label="Keine Formatierung" fa="text-slash" />
                    </> }
                </div>
                <div className="forum-button-bar-section">
                    { globals.allowControl('extended') && <>
                        <ControlButtonNodeWrap node="quote" label="Zital" fa="quote-left" block={true} />
                        <ControlButtonNodeWrap node="spoiler" label="Spoiler" fa="eye-slash" block={true} />
                        <ControlButtonNodeWrap node="aparte" label="Vertraulich" fa="compress-alt" block={true} />
                        <ControlButtonNodeWrap node="code" label="Code" fa="code" block={true} />
                    </> }
                    { globals.allowControl('glory') && <>
                        <ControlButtonNodeWrap node="glory" label="Ruhm" fa="crown" block={true} />
                    </> }
                </div>
                <div className="forum-button-bar-section">
                    { globals.allowControl('extended') && <>
                        <ControlButtonNodeInsert node="*" label="Listenpunkt" fa="star-of-life" block={true} control="." multiline={true} />
                        <ControlButtonNodeInsert node="0" label="Num. Listenpunkt" fa="list-ol" block={true} control="1" multiline={true} />
                        <ControlButtonNodeInsert node="hr" label="Linie" fa="grip-lines" block={true} closes={true} curley={true} control="-" />
                    </> }
                </div>
                <div className="forum-button-bar-section">
                    { globals.allowControl('admin') && <ControlButtonNodeWrap node="admannounce" label="Admin Ankündigung" fa="bullhorn" block={true} /> }
                    { globals.allowControl('mod') && <ControlButtonNodeWrap node="modannounce" label="Mod Ankündigung" fa="gavel" block={true} /> }
                    { globals.allowControl('oracle') && <ControlButtonNodeWrap node="announce" label="Orakel Ankündigung" fa="rss" block={true} /> }
                </div>
            </div>
        </>}
    </>
}

type ControlButtonDefinition = {
    fa:string,
    label: string,
    control?: string|null,
    content?: any|null,
}

type BaseNodeDefinition = {
    node: string,
    block?: boolean
}

type StandaloneNodeDefinition = BaseNodeDefinition & {
    closes?: boolean,
    curley?: boolean,
    multiline?: boolean
}

const ControlButton = ({fa, label, control, handler, content = null}: ControlButtonDefinition & {handler: ()=>void}) => {
    const button = useRef<HTMLDivElement>();
    const dialog = useRef<HTMLDialogElement>()

    const wrapped_handler = () => {
        if (!dialog.current) handler();
    }

    useLayoutEffect(() => {
        const callHandler = (e:CustomEvent) => wrapped_handler();
        button.current.addEventListener('controlActionTriggered', callHandler);
        return () => button.current.removeEventListener('controlActionTriggered', callHandler);
    })

    return <div className="forum-button" ref={button} data-receive-control-event={control} onClick={e => {
        e.preventDefault();
        e.stopPropagation();
        wrapped_handler()
    }}>
        { content && <dialog ref={dialog}>

        </dialog> }
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
}

export const ControlButtonNodeWrap = ({fa, label, node, control = null, block = false}: ControlButtonDefinition & BaseNodeDefinition ) => {

    const globals = useContext(Globals);

    return <ControlButton {...{fa,label,control}} handler={() => {
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
            const opt_nl = block ? "\n" : '';
            const before = block ? body.slice(0,selection[0]).trimEnd() : body.slice(0,selection[0]);
            const text = body.slice(selection[0],selection[1]);
            const after = block ? body.slice(selection[1]).trimStart() : body.slice(selection[1]);
            globals.setField('body', `${before}${opt_nl}${open}${text}${close}${opt_nl}${after}`);
            globals.selection.update( before.length + open.length + opt_nl.length, before.length + text.length + open.length + opt_nl.length )
        }
    }}/>
};

export const ControlButtonNodeInsert = ({fa, label, node, control = null, block = false, closes = false, curley = false, multiline = false}: ControlButtonDefinition & StandaloneNodeDefinition ) => {

    const globals = useContext(Globals);

    return <ControlButton {...{fa,label,control}} handler={() => {
        const body = `${globals.getField('body') ?? ''}`;
        const selection = [globals.selection.start,globals.selection.end]

        const insert = curley ? `{${node}} ` : `[${node}] `;

        const opt_nl_before = (block && selection[0] > 0) ? "\n" : '';
        const opt_nl_inner = closes ? "\n" : '';
        const opt_nl_after = (block && !closes) ? "\n" : '';
        const before = block ? body.slice(0,selection[0]).trimEnd() : body.slice(0,selection[0]);

        let text = '';
        if (multiline) {
            text = body.slice(selection[0],selection[1]).trim().split('\n').map((s:string,index) => `${index > 0 ? insert : ''}${s.trim()}`).join("\n");
        } else {
            text = closes ? body.slice(selection[0],selection[1]).trim() : body.slice(selection[0],selection[1]);
        }

        const after = (block && !closes) ? body.slice(selection[1]).trimStart() : body.slice(selection[1]);
        globals.setField('body', `${before}${opt_nl_before}${insert}${opt_nl_inner}${text}${opt_nl_after}${after}`);
        globals.selection.update( before.length + (multiline ? 0 : insert.length) + opt_nl_before.length, before.length + text.length + insert.length + opt_nl_before.length )
    }}/>
};