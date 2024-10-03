import * as React from "react";
import {ReactElement, useContext, useEffect, useLayoutEffect, useRef, useState} from "react";
import {Globals} from "./Wrapper";
import {UserSearchBar} from "../user-search/Wrapper";
import {Tab, TabbedSection, TabGroup, TabProps} from "../tab-list/TabList";
import {Emote, Snippet} from "./api";
import {Tooltip} from "../tooltip/Wrapper";
import {v4 as uuidv4} from "uuid";
import {Global} from "../../defaults";

declare var $: Global;

type ControlButtonDefinition = {
    fa?:string,
    img?:string,
    label?: string,
    control?: string|null,
    children?: any|null,
}

type BaseNodeDefinition = {
    node: string,
    block?: boolean,
}

type ExtendedNodeDefinition = {
    valueCallback?: (string)=>string|null,
    contentCallback?: (string)=>string|null,
}

type StandaloneNodeDefinition = BaseNodeDefinition & {
    closes?: boolean,
    curley?: null|boolean,
    multiline?: boolean
}

type OverlayTypes = 'emotes'|'games'|'rp';


export const TwinoEditorControls = ({emotes}: {emotes: null|Array<Emote>}) => {

    const globals = useContext(Globals);

    const [showOverlay, setShowOverlay] = useState<boolean>(false)
    const [current, setCurrent] = useState<OverlayTypes|null>(null)
    const [mounted, setMounted] = useState<OverlayTypes[]>([])

    const select = (s:OverlayTypes) => {
        setShowOverlay( !showOverlay || s !== current )
        setCurrent(s);
        if (!mounted.includes(s)) setMounted([...mounted,s]);
    }

    const sectionButton = (s:OverlayTypes, src: string) => {
        return <div tabIndex={0} className="forum-button-component">
            <div className={`forum-button ${showOverlay && current === s ? 'active' : ''}`} onClick={() => select(s)}>
                <img alt="" src={src}/>
            </div>
        </div>
    }

    const overlay_mode = globals.skin === "line" || globals.skin === "textarea";

    return <>
        <div className="forum-button-bar">
            <div onClick={()=>setShowOverlay(false)} className="forum-button-bar-section">
                <ControlButtonNodeWrap node="b" label={globals.strings.controls.b} fa="bold" control="b"/>
                <ControlButtonNodeWrap node="i" label={globals.strings.controls.i} fa="italic" control="i" />
                <ControlButtonNodeWrap node="u" label={globals.strings.controls.u} fa="underline" control="u" />
                <ControlButtonNodeWrap node="s" label={globals.strings.controls.s} fa="strikethrough" control="s" />
                { globals.allowControl('extended') && <>
                    <ControlButtonNodeWrap node="big" label={globals.strings.controls.big} fa="expand-alt" control="+" />
                    <ControlButtonNodeWrap node="bad" label={globals.strings.controls.bad} fa="tint" />
                </> }
                <ControlButtonNodeWrap node="c" label={globals.strings.controls.c} fa="text-slash" />
            </div>
            <div onClick={()=>setShowOverlay(false)} className="forum-button-bar-section">
                { (globals.allowControl('user') || globals.allowControl('extended')) && <>
                    <ControlButtonInsertPlayer/>
                </> }
                { globals.allowControl('extended') && <>
                    <ControlButtonInsertLink />
                </> }
                { globals.allowControl('image') && <>
                    <ControlButtonInsertImage />
                </> }
            </div>
            <div onClick={()=>setShowOverlay(false)} className="forum-button-bar-section">
                { globals.allowControl('extended') && <>
                    <ControlButtonInsertQuote/>
                    <ControlButtonNodeWrap node="spoiler" label={globals.strings.controls.spoiler} fa="eye-slash" block={true} />
                    <ControlButtonNodeWrap node="aparte" label={globals.strings.controls.aparte} fa="compress-alt" block={true} />
                    <ControlButtonNodeWrap node="code" label={globals.strings.controls.code} fa="code" block={true} />
                    <ControlButtonInsertWithAttribute node="rp" label={globals.strings.controls.rp} fa="scroll" block={true} dialogTitle={globals.strings.controls['rp-dialog']} attribute={globals.strings.controls['rp-placeholder']} />
                    <ControlButtonInsertWithAttribute node="collapse" label={globals.strings.controls.collapse} fa="square-caret-down" block={true} dialogTitle={globals.strings.controls['collapse-dialog']} attribute={globals.strings.controls['collapse-placeholder']} />
                </> }
                { globals.allowControl('glory') && <>
                    <ControlButtonNodeWrap node="glory" label={globals.strings.controls.glory} fa="crown" block={true} />
                </> }
            </div>
            <div onClick={()=>setShowOverlay(false)} className="forum-button-bar-section">
                { globals.allowControl('extended') && <>
                    <ControlButtonNodeInsert node="*" label={globals.strings.controls["*"]} fa="star-of-life" block={true} control="." multiline={true} />
                    <ControlButtonNodeInsert node="0" label={globals.strings.controls["0"]} fa="list-ol" block={true} control="1" multiline={true} />
                    <ControlButtonNodeInsert node="hr" label={globals.strings.controls.hr} fa="grip-lines" block={true} closes={true} curley={true} control="-" />
                </> }
                { globals.allowControl('poll') && <>
                    <ControlButtonInsertPoll />
                </> }
            </div>
            <div onClick={()=>setShowOverlay(false)} className="forum-button-bar-section">
                { globals.allowControl('admin') && <ControlButtonNodeWrap node="admannounce" label={globals.strings.controls.admannounce} fa="bullhorn" block={true} /> }
                { globals.allowControl('mod') && <ControlButtonNodeWrap node="modannounce" label={globals.strings.controls.modannounce} fa="gavel" block={true} /> }
                { globals.allowControl('oracle') && <ControlButtonNodeWrap node="announce" label={globals.strings.controls.announce} fa="rss" block={true} /> }
            </div>
            {overlay_mode && <div className="forum-button-bar-section">
                { globals.allowControl('emote') && sectionButton('emotes', globals.strings.controls.emotes_img) }
                { globals.allowControl('game') && sectionButton('games', globals.strings.controls.games_img) }
                { globals.allowControl('rp') && sectionButton('rp', globals.strings.controls.rp_img) }
            </div>}
        </div>
        {overlay_mode &&
            <TwinoEditorControlsTabListOverlay show={showOverlay} current={current} mounted={mounted} emotes={emotes}/>}
    </>
}

export const TwinoEditorControlsTabList = ({emotes, snippets}: {
    emotes: null | Array<Emote>,
    snippets: null | Array<Snippet>
}) => {
    const globals = useContext(Globals);

    const langList = snippets?.map(v => v.lang) ?? [];

    return <TabbedSection mountOnlyActive={true} keepInactiveMounted={true} className="no-bottom-margin">
        <Tab icon={globals.strings.controls.emotes_img} id="emotes" if={ globals.allowControl('emote') }><EmoteTabSection emotes={emotes}/></Tab>
        <Tab icon={ globals.strings.controls.games_img } id="games" if={ globals.allowControl('game') }><GameTabSection/></Tab>
        <Tab icon={ globals.strings.controls.rp_img } id="rp" if={ globals.allowControl('rp') }><RPTabSection/></Tab>
        <TabGroup group="mod" id="mod" icon={ globals.strings.controls.mod_img } if={ globals.allowControl('snippet') && langList.length > 0 }>
            <Tab title="FR" id="mod_fr" if={ langList.includes('fr') }><ModTabSection snippets={snippets?.filter(v => v.lang === 'fr') ?? []}/></Tab>
            <Tab title="EN" id="mod_en" if={ langList.includes('en') }><ModTabSection snippets={snippets?.filter(v => v.lang === 'en') ?? []}/></Tab>
            <Tab title="DE" id="mod_de" if={ langList.includes('de') }><ModTabSection snippets={snippets?.filter(v => v.lang === 'de') ?? []}/></Tab>
            <Tab title="ES" id="mod_es" if={ langList.includes('es') }><ModTabSection snippets={snippets?.filter(v => v.lang === 'es') ?? []}/></Tab>
        </TabGroup>
    </TabbedSection>
}

const TwinoEditorControlsTabListOverlay = ({emotes,show,current,mounted}: {emotes: null|Array<Emote>, show: boolean, current: OverlayTypes|null, mounted: OverlayTypes[]}) => {
    const globals = useContext(Globals);

    return <div className={`overlay-controls layered ${show ? 'active' : 'inactive'}`}>
        { mounted.includes('emotes') && <div className={current === 'emotes' ? '' : 'hidden'}><EmoteTabSection emotes={emotes}/></div> }
        { mounted.includes('games') && <div className={current === 'games' ? '' : 'hidden'}><GameTabSection/></div> }
        { mounted.includes('rp') && <div className={current === 'rp' ? '' : 'hidden'}><RPTabSection/></div> }
    </div>
}

const ControlButton = ({fa = null, img = null, label = null, control = null, handler, dialogHandler = null, children = null, dialogTitle = null, manualConfirm = true, preConfirmHandler = null}: ControlButtonDefinition & {handler: ()=>void|boolean, dialogHandler?: (boolean)=>void|boolean, dialogTitle?: string|null, manualConfirm?: boolean, preConfirmHandler?: (HTMLFormElement)=>void}) => {
    const globals = useContext(Globals);

    const button = useRef<HTMLDivElement>();
    const dialog = useRef<HTMLDialogElement>()
    const form = useRef<HTMLFormElement>()

    const wrapped_handler = () => {
        if (!dialog.current) handler();
        else {
            if (!dialogHandler || dialogHandler(false) !== false)
                dialog.current.showModal();
        }
    }

    useLayoutEffect(() => {
        const callHandler = (e:CustomEvent) => wrapped_handler();
        button.current.addEventListener('controlActionTriggered', callHandler);
        return () => button.current.removeEventListener('controlActionTriggered', callHandler);
    }, [])

    const confirmDialog = () => {
        if (preConfirmHandler) preConfirmHandler(form.current);
        if (!form.current.checkValidity()) return;
        const l = (dialogHandler === null || dialogHandler(true) !== false) && handler() !== false;
        if (l) dialog.current.close();
    }

    return <div tabIndex={0} className="forum-button-component">
        <div className="forum-button" ref={button} data-receive-control-event={control} onClick={e => {
            e.preventDefault();
            wrapped_handler()
        }}>
            {fa && <i className={`fa fa-${fa}`}/>}
            {img && <img alt="" src={img}/>}
            {(label || control) && <span className="forum-button-tooltip">
                <div className="center">
                    {label && <div>{label}</div>}
                    {control !== null && <div className="keyboard">
                        <kbd>{navigator.platform.indexOf("Mac") === 0 ? '⌘' : globals.strings.common.ctrl}</kbd>
                        <span>&nbsp;+&nbsp;</span>
                        <kbd>{control.toUpperCase()}</kbd>
                    </div>}
                </div>
            </span>}
        </div>
        {children && <dialog ref={dialog}>
            <div className="modal-title">{dialogTitle ?? label}</div>
            <form method="dialog" ref={form} onKeyDown={e => {
                if (e.key.toLowerCase() === "enter") {
                    confirmDialog();
                    e.preventDefault();
                    e.stopPropagation();
                }
            }} onSubmit={() => confirmDialog()}>
                <div className="modal-content">{children}</div>
                <div className="modal-actions">
                    {manualConfirm && <>
                        <button type="button" className="modal-button small inline" onClick={() => confirmDialog()}>
                            {globals.strings.common.insert}
                        </button>
                    </>}
                    <div className="modal-button small inline" onClick={() => dialog.current.close()}>
                        {globals.strings.common.abort}
                    </div>
                </div>
            </form>
        </dialog>}
    </div>
}

const ControlButtonNodeWrap = ({
                                   fa,
                                   label,
                                   node,
                                   control = null,
                                   block = false,
                                   contentCallback = null,
                                   valueCallback = null,
                                   children = null,
                                   dialogHandler = null,
                                   dialogTitle = null,
                                   preConfirmHandler = null,
                               }: ControlButtonDefinition & BaseNodeDefinition & ExtendedNodeDefinition & {
    children?: any | null
    dialogHandler?: (boolean)=>void|boolean,
    dialogTitle?: string|null,
    preConfirmHandler?: (HTMLFormElement)=>void
}) => {

    const globals = useContext(Globals);

    return <ControlButton {...{fa, label, control, dialogHandler, dialogTitle, preConfirmHandler}} handler={() => {
        const fixValue = (link: string|null|undefined): string|null => {
            return link?.replaceAll('[', '［').replaceAll(']', '］') ?? null;
        }

        const body = `${globals.getField('body') ?? ''}`;
        const selection = [globals.selection.start, globals.selection.end]

        const rawSelection = body.slice(selection[0], selection[1]);
        const value = valueCallback ? fixValue(valueCallback(rawSelection)) : null;
        const content = contentCallback ? contentCallback(rawSelection) : null;

        const open = `[${node}${value ? `=${value}` : ''}]`;
        const close = `[/${node}]`;

        // Check if the current selection is already wrapped in the desired tag
        if (
            value === null && content === null &&
            body.slice(Math.max(0, selection[0] - open.length), selection[0]) === open &&
            body.slice(selection[1], selection[1] + close.length) === close
        ) {
            globals.setField('body', `${body.slice(0, selection[0] - open.length)}${body.slice(selection[0], selection[1])}${body.slice(selection[1] + close.length)}`);
            globals.selection.update( selection[0] - open.length, selection[1] - open.length )
            // Check if the current selection starts and ends with the desired tag
        } else if (
            value === null && content === null &&
            body.slice( selection[0], selection[0] + open.length ) === open &&
            body.slice( Math.max(0, selection[1] - close.length), selection[1] ) === close
        ) {
            globals.setField('body', `${body.slice(0,selection[0])}${body.slice(selection[0]+open.length,selection[1]-close.length)}${body.slice(selection[1])}`);
            globals.selection.update( selection[0], selection[1] - open.length - close.length )
            // Otherwise, add the tags
        } else {
            const opt_nl = block ? "\n" : '';
            const before = block ? body.slice(0,selection[0]).trimEnd() : body.slice(0,selection[0]);
            const text = content ?? rawSelection;
            const after = block ? body.slice(selection[1]).trimStart() : body.slice(selection[1]);
            globals.setField('body', `${before}${opt_nl}${open}${text}${close}${opt_nl}${after}`);
            globals.selection.update( before.length + open.length + opt_nl.length, before.length + text.length + open.length + opt_nl.length )
        }
    }}>{children}</ControlButton>
};

const ControlButtonNodeInsert = ({fa = null, img = null, label = null, node, control = null, block = false, closes = false, curley = false, multiline = false}: ControlButtonDefinition & StandaloneNodeDefinition ) => {

    const globals = useContext(Globals);

    return <ControlButton {...{fa,img,label,control}} handler={() => {
        const body = `${globals.getField('body') ?? ''}`;
        const selection = [globals.selection.start,globals.selection.end]

        const insert = curley === null ? node : (curley ? `{${node}} ` : `[${node}] `);

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

const ControlButtonInsertPlayer = () => {

    const globals = useContext(Globals);

    const parent = useRef<HTMLDivElement>()
    const selected = useRef<{id: number, name: string}>();

    return <ControlButton fa="user" label={globals.strings.controls["@"]} dialogTitle={globals.strings.controls["@-dialog"]} manualConfirm={false} handler={() => {
        if (!selected.current) return;

        const insert = `@${selected.current.name.replaceAll(/[^\w_]/gi, '')}:${selected.current.id}`;
        const body = `${globals.getField('body') ?? ''}`;

        let before = body.slice(0, globals.selection.start);
        if (before !== '' && !before.slice(-1).match(/\s/))
            before = `${before} `;

        let after = body.slice(globals.selection.start);
        if (after !== '' && !after.slice(0,1).match(/\s/))
            after = ` ${after}`;

        globals.setField('body', `${before}${insert}${after}`);
        globals.selection.update(before.length, before.length + insert.length)
    }}>
        <div ref={parent}>
            <UserSearchBar
                withAlias={globals.isEnabled('alias')}
                title={globals.strings.controls["@-placeholder"]}
                withSelf={true} withFriends={true}
                callback={e => {
                    selected.current = e[0] ?? null;
                    (parent.current.closest('form') as HTMLFormElement).requestSubmit();
                }}
                clearOnCallback={true}
            />
        </div>

    </ControlButton>

}

const ControlButtonInsertQuote = () => {
    const globals = useContext(Globals);

    const parent = useRef<HTMLDivElement>()
    const selected = useRef<{id: number, name: string}>();

    return <ControlButton fa="quote-left" label={globals.strings.controls.quote} dialogTitle={globals.strings.controls['quote-dialog']} manualConfirm={true} handler={() => {
        const body = `${globals.getField('body') ?? ''}`;

        let before = body.slice(0, globals.selection.start);
        if (before !== '' && !before.slice(-1).match(/\s/))
            before = `${before} `;

        let inner = body.slice(globals.selection.start, globals.selection.end);

        let after = body.slice(globals.selection.end);
        if (after !== '' && !after.slice(0,1).match(/\s/))
            after = ` ${after}`;

        let insert = '';
        if (selected.current)
            insert = selected.current.id < 0
                ? `[quote=${selected.current.name.replaceAll(/[\[\]=]/gi, '')}]${inner}[/quote]`
                : `[quote=@${selected.current.name.replaceAll(/[^\w_]/gi, '')}:${selected.current.id}]${inner}[/quote]`;
        else insert = `[quote]${inner}[/quote]`;

        globals.setField('body', `${before}${insert}${after}`);
        globals.selection.update(before.length, before.length + insert.length);
        selected.current = null;
    }}>
        <div ref={parent}>
            <div>{ globals.strings.controls['quote-placeholder'] }</div>
            <UserSearchBar
                withAlias={globals.isEnabled('alias')}
                title={globals.strings.controls['quote-placeholder']}
                withSelf={true} withFriends={true} withPlainString={true}
                valueCallback={s => {
                    selected.current = {name: s, id: -1}
                }}
                callback={e => {
                    selected.current = e[0] ?? null;
                    (parent.current.closest('form') as HTMLFormElement).requestSubmit();
                }}
                clearOnCallback={true}
            />
        </div>

    </ControlButton>

}

const ControlButtonInsertURL = ({
                                    node,
                                    block,
                                    fa,
                                    label,
                                    control,
                                    dialogTitle = null,
                                    urlField,
                                    textField,
                                    autoCompleteUrl,
                                }: BaseNodeDefinition & ControlButtonDefinition & {
    dialogTitle?: string | null,
    urlField: string | null,
    textField: string | null,
    autoCompleteUrl: boolean | null
}) => {

    const globals = useContext(Globals);

    const text = useRef<HTMLInputElement>()
    const link = useRef<HTMLInputElement>()

    const fixLink = (link: string): string => {
        return link.replaceAll('[', '%5B').replaceAll(']', '%5D')
    }

    const checkLink = (link: string, noProtocol: boolean = false): boolean => {
        return link.match(noProtocol
            ? /^(\w+:?\w*)?(\S+)(:\d+)?(?:\/|\/([\w#!:.?+=&%\-\/]))?$/
            : /^https?:\/\/(\w+:?\w*)?(\S+)(:\d+)?(?:\/|\/([\w#!:.?+=&%\-\/]))?$/
        ) !== null;
    }

    return <ControlButtonNodeWrap {...{node,label,control,fa,block,dialogTitle}}
        valueCallback={()=>(link.current ?? text.current).value} contentCallback={s=>(text.current && link.current) ? text.current.value : s}
        dialogHandler={(post) => {
            if (!post) {
                const s = `${globals.getField('body')}`.slice(globals.selection.start, globals.selection.end).trim();
                if (text.current && link.current) {
                    if (s && checkLink(s)) {
                        text.current.value = '';
                        link.current.value = fixLink(s);
                        window.requestAnimationFrame(() => text.current.focus());
                    } else {
                        text.current.value = s;
                        link.current.value = '';
                        window.requestAnimationFrame(() => (s ? link : text).current.focus());
                    }
                } else (text.current ?? link.current).value = '';

                return true;
            } else {
                if (text.current && link.current && checkLink(text.current.value) && !checkLink(link.current.value))
                    link.current.value = fixLink(text.current.value);

                if (link.current && text.current && !text.current.value) text.current.value = link.current.value;

                return !link.current || checkLink(link.current.value);
            }
        }}
        preConfirmHandler={() => {
            if (link.current && !checkLink( link.current.value ) && checkLink( link.current.value, true ))
                link.current.value = `https://${link.current.value}`

            if (link.current)
                link.current.value = fixLink(link.current.value);
        }}
    >
        <div className="flex">
            <div className="modal-form">
                {textField && <>
                    <label htmlFor={`${globals.uuid}-form-url-text`}>{textField}</label>
                    <input type="text" ref={text} id={`${globals.uuid}-form-url-text`}/>
                </>}
                {urlField && <>
                    <label htmlFor={`${globals.uuid}-form-url-link`}>{urlField}</label>
                    <input type="url" ref={link} id={`${globals.uuid}-form-url-link`}/>
                </>}
            </div>
        </div>
    </ControlButtonNodeWrap>
}

const ControlButtonInsertLink = () => {
    const globals = useContext(Globals)
    return <ControlButtonInsertURL node="link" label={globals.strings.controls.link} control="k" fa="link"
                                   urlField={globals.strings.controls["link-url"]}
                                   textField={globals.strings.controls["link-text"]}
                                   autoCompleteUrl={true}
    />
}
const ControlButtonInsertImage = () => {
    const globals = useContext(Globals);
    return <ControlButtonInsertURL node="image" label={globals.strings.controls.image} fa="image" block={false}
                                   urlField={globals.strings.controls["image-url"]}
                                   textField={globals.strings.controls["image-text"]}
                                   autoCompleteUrl={false}
    />
}

type InfoEntry = {
    t: "a"|"i",
    n: number,
    v: string,
    id: string,
}

const ControlButtonInsertPoll = () => {
    const globals = useContext(Globals);

    const [question, setQuestion] = useState<string>("");
    const [fields, setFields] = useState<InfoEntry[]>([]);

    const make_content = () => {
        let r = `\n`;
        if (question.trim().length > 0) r += `[q]${question.trim()}[/q]\n`
        r += fields.filter(f => f.v.trim().length > 0).map(f => f.t === 'a' ? `[*] ${f.v.trim()}` : `[desc]${f.v.trim()}[/desc]`).join(`\n`);
        return r + `\n`;
    }

    const build_nums = (v:InfoEntry[]): InfoEntry[] => {
        let a = 0, i = 0;
        v.forEach(f => f.n = f.t === "a" ? a++ : i++);
        return v;
    }

    return <ControlButtonNodeWrap node="poll" label={globals.strings.controls.poll} fa="poll" block={true}
                                  contentCallback={() => make_content()}
                                  dialogHandler={(post) => {
                                      if (!post) {
                                          setFields([]);
                                          setQuestion(`${globals.getField('body')}`.slice(globals.selection.start, globals.selection.end).trim())
                                      } else {
                                          if (fields.filter(f => f.t === "a" && f.v.trim().length > 0).length === 0) {
                                              $.html.error(globals.strings.controls["poll-need-answer"]);
                                              return false;
                                          }
                                          return true;
                                      }
                                  }}
    >
        <div className="flex">
            <div className="modal-form">
                <div className="row">
                    <div className="padded cell rw-12">
                        <div className="note note-critical">{globals.strings.controls["poll-help"]}</div>
                    </div>
                </div>

                <div className="row">
                    <div className="cell padded rw-12">
                        <div className="row-flex gap v-center">
                            <div className="cell factor-0">
                                <img alt="[Q]" src={globals.strings.controls.help_img}/>
                            </div>
                            <div className="cell factor-1">
                                <label
                                    htmlFor={`${globals.uuid}-poll-q`}>{globals.strings.controls["poll-question"]}</label>
                            </div>
                        </div>
                        <input type="text" id={`${globals.uuid}-poll-q`} value={question}
                               onChange={e => setQuestion(e.target.value)}/>
                    </div>
                </div>

                {fields.map((f, i) => <div className="row" key={f.id}>
                    <div className="padded cell rw-12">
                        <div className="row-flex gap v-center">
                            <div className="cell factor-0">
                                <img alt={f.t.toUpperCase()}
                                     src={f.t === "a" ? globals.strings.controls.answer_img : globals.strings.controls.info_img}/>
                            </div>
                            <div className="cell factor-1">
                                <div className="row-flex v-center gap">
                                    <div className="cell factor-1">
                                        <label htmlFor={`${globals.uuid}-poll-${f.id}`}>
                                            {f.t === "a" ? globals.strings.controls["poll-answer"] : globals.strings.controls["poll-info"]}
                                            &nbsp;
                                            {f.n + 1}
                                            &nbsp;
                                            {(f.t !== "a" || f.n > 0) ? globals.strings.controls["poll-optional"] : ''}
                                        </label>
                                    </div>
                                    {i > 0 &&
                                        <div className="cell factor-0"><span className="small pointer" onClick={() => {
                                            setFields(build_nums([...fields.slice(0, i - 1), f, fields[i - 1], ...fields.slice(i + 1)]));
                                        }}>🡱</span></div>
                                    }
                                    {i < (fields.length - 1) &&
                                        <div className="cell factor-0"><span className="small pointer" onClick={() => {
                                            setFields(build_nums([...fields.slice(0, i), fields[i + 1], f, ...fields.slice(i + 2)]));
                                        }}>🡳</span></div>
                                    }
                                    <div className="cell factor-0"><span className="small pointer" onClick={() => {
                                        setFields(build_nums([...fields.slice(0, i), ...fields.slice(i + 1)]));
                                    }}>×</span></div>
                                </div>

                            </div>
                        </div>
                        <input type="text" id={`${globals.uuid}-poll-${f.id}`} value={f.v}
                               onChange={e => {
                                   const v = [...fields];
                                   v[i].v = e.target.value;
                                   setFields(v);
                               }}/>
                    </div>
                </div>)}

                <div className="row-flex gap">
                    <div className="cell">
                        <button type="button" className="small inline" onClick={() => {
                            const v = [...fields];
                            v.push({
                                id: uuidv4(),
                                v: "",
                                t: "a",
                                n: fields.filter(f => f.t === "a").length
                            });
                            setFields(v);
                        }}>{globals.strings.controls["poll-answer-add"]}</button>
                    </div>
                    <div className="cell">
                        <button type="button" className="small inline" onClick={() => {
                            const v = [...fields];
                            v.push({
                                id: uuidv4(),
                                v: "",
                                t: "i",
                                n: fields.filter(f => f.t === "i").length
                            })
                            setFields(v);
                        }}>{globals.strings.controls["poll-info-add"]}</button>
                    </div>
                </div>
            </div>
        </div>
    </ControlButtonNodeWrap>
}
const ControlButtonInsertWithAttribute = ({
                                              node,
                                              fa,
                                              control = null,
                                              label,
                                              block = false,
                                              attribute,
                                              dialogTitle = null
                                          }: BaseNodeDefinition & ControlButtonDefinition & {
    attribute: string,
    dialogTitle?: string | null
}) => <ControlButtonInsertURL {...{node, label, fa, block, control, dialogTitle}} urlField={null}
                              textField={attribute} autoCompleteUrl={false}/>

const EmoteTabSection = ({emotes}: { emotes: null | Array<Emote> }) => {
    return <div className="lightbox">
        {emotes === null && <div className="loading"/>}
        {emotes !== null && <div className="forum-button-grid">
            {emotes.filter(a => a.orderIndex >= 0).sort((a, b) => a.orderIndex - b.orderIndex).map(emote => <React.Fragment key={emote.tag}>
                <ControlButtonNodeInsert node={emote.tag} img={emote.url} curley={null}/>
            </React.Fragment>)}
        </div>}
    </div>
}

const GameTabSection = () => {
    const [games, setGames] = useState<Array<Emote>>(null);

    const globals = useContext(Globals);

    useEffect(() => {
        globals.api.games(globals.uid,globals.context).then(r => setGames(Object.values(r.result)));
    }, []);

    return <div className="lightbox">
        {games === null && <div className="loading"/>}
        {games !== null && <div className="forum-button-grid">
            {games.sort((a, b) => a.orderIndex - b.orderIndex).map(emote => <React.Fragment key={emote.tag}>
                <ControlButtonNodeInsert node={emote.tag} img={emote.url} curley={null}/>
            </React.Fragment>)}
        </div>}
    </div>
}

const RPTabSection = () => {
    const [rp, setRP] = useState<Array<Emote>>(null);
    const [help, setHelp] = useState<string>(null);

    const globals = useContext(Globals);

    useEffect(() => {
        globals.api.rp(globals.uid,globals.context).then(r => {
            setRP(Object.values(r.result));
            setHelp(r.help ?? null);
        });
    }, []);

    return <div className="lightbox">
        {rp === null && <div className="loading"/>}
        {rp !== null && <div className="row-flex">
            <div className="cell factor-1">
                <div className="forum-button-grid">
                    {rp.sort((a, b) => a.orderIndex - b.orderIndex).map(emote => <React.Fragment key={emote.tag}>
                        <ControlButtonNodeInsert node={emote.tag} img={emote.url} curley={null}/>
                    </React.Fragment>)}
                </div>
            </div>
            {help && <div className="cell factor-0">
                <a className="help-button">
                    {globals.strings.common.help}
                    <Tooltip additionalClasses="help" html={help}/>
                </a>
            </div>}
        </div>}
    </div>
}

const ModTabSection = ({snippets}: { snippets: Array<Snippet> }) => {
    const globals = useContext(Globals);

    const roles: string[] = [];
    snippets.forEach(s => {
        if (!roles.includes(s.role)) roles.push(s.role)
    })

    return <div className="lightbox">
        {roles.sort((a, b) => a.localeCompare(b)).map(role => <React.Fragment key={role}>
            {roles.length > 1 && <div className="padded cell rw-12">
                <div className="row-flex gap v-center">
                    <div className="cell factor-0"><strong><span className="small">{role}</span></strong></div>
                    <div className="cell grow-1">
                        <hr/>
                    </div>
                </div>
            </div>}
            {snippets.filter(snippet => snippet.role === role).map(snippet => <div className="row" key={snippet.key}
                                                                                   style={{fontSize: '0.8em'}}>
                <div className="padded cell rw-3 rw-md-12"><strong className="pointer" onClick={() => {
                    const body = `${globals.getField('body')}`;
                    globals.setField('body', `${body.slice(0, globals.selection.start)}${snippet.value}${body.slice(globals.selection.start)}`);
                }}>{snippet.key}</strong></div>
                <div className="padded cell rw-9 rw-md-12"><span className="small"
                                                                 style={{fontSize: '0.8em'}}>{snippet.value}</span>
                </div>
            </div>)}
        </React.Fragment>)}

    </div>
}

const TabSection = ({section}: { section: string }) => {
    return <div className="lightbox">
        <div className="loading"></div>
        <div>{section}</div>
    </div>
}