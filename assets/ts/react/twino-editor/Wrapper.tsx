import * as React from "react";
import { createRoot } from "react-dom/client";

import {ChangeEvent, MouseEventHandler, useContext, useEffect, useLayoutEffect, useRef, useState} from "react";
import {TranslationStrings} from "./strings";
import {Const, Global} from "../../defaults";
import {v4 as uuidv4} from 'uuid';
import {TwinoEditorControls, TwinoEditorControlsTabList} from "./Controls";

declare var $: Global;
declare var c: Const;

type Feature = "tags"|"title"|"version"|"language"|"preview"|"compact"|"alias"
type Control = "core"|"extended"|"image"|"admin"|"mod"|"oracle"|"glory"|"poll"

interface HeaderConfig {
    header: string|null,
    username: string|null,
    tags: {[index:string]: string},
}

type HTMLConfig = HeaderConfig & {
    context: string,
    pm: boolean,
    features: Feature[],
    controls: Control[]
    defaultFields: object,
}

type FieldChangeEventTrigger = ( field: string, value: string|number|null, old_value: string|number|null, is_default: boolean ) => void
type FieldMutator = ( field: string, value: string|number|null ) => void
type FieldReader = ( field: string ) => string|number|null
type FeatureCheck = ( feature: Feature ) => boolean
type ControlCheck = ( control: Control ) => boolean

type TwinoEditorGlobals = {
    //api: NotificationManagerAPI,
    //strings: TranslationStrings,
    uuid: string,
    setField: FieldMutator,
    getField: FieldReader,
    isEnabled: FeatureCheck,
    allowControl: ControlCheck,
    selection: {
        start: number,
        end: number,
        update: (s:number,e:number) => void
    }
}



export const Globals = React.createContext<TwinoEditorGlobals>(null);


export class HordesTwinoEditor {

    #_root = null;
    #_parent = null;

    private onFieldChanged( field: string, value: string|number|null, old_value: string|number|null, is_default: boolean ): void {
        this.#_parent.dispatchEvent( new CustomEvent('change', {
            bubbles: false,
            detail: { field, value, old_value, is_default }
        }) )
    }

    public mount(parent: HTMLElement, props: HTMLConfig): void {
        if (!this.#_root) this.#_root = createRoot(this.#_parent = parent);
        this.#_root.render( <TwinoEditorWrapper onFieldChanged={(f:string,v:string|number|null,v0:string|number|null,d:boolean) => this.onFieldChanged(f,v,v0,d)} {...props} /> );
    }

    public unmount(parent: HTMLElement): void {
        if (this.#_root) {
            this.#_root.unmount();
            this.#_root = null;
        }
    }
}

const TwinoEditorWrapper = ( props: HTMLConfig & { onFieldChanged: FieldChangeEventTrigger } ) => {

    const cache = $.client.config.scopedEditorCache.get() ?? ['',''];
    const cache_value = (cache[0] ?? '_') === props.context ? cache[1] : null;

    const uuid = useRef(uuidv4());
    const [fields, setFields] = useState({
        ...props.defaultFields,
    });

    const selection = useRef({
        start: 0,
        end: 0,
        update: (s,e) => {}
    });

    const me = useRef<HTMLDivElement>(null);

    const convertToHTML = (twino:string) => $.html.twinoParser.parseToString(twino, s => [null, s], {autoLinks: $.client.config.autoParseLinks.get()});
    const convertToTwino = (html:string) => $.html.twinoParser.parseFrom(html, $.html.twinoParser.OpModeRaw);

    const setField = (field: string, value: string|number) => {
        const current = fields[field] ?? null;
        if (current !== value) {
            let new_fields = {...fields};
            props.onFieldChanged(field, new_fields[field] = value, current, value === (props.defaultFields[field] ?? null));

            // Changing the body also changes the HTML and invokes the cache
            if (field === 'body') {
                $.client.config.scopedEditorCache.set([props.context, value]);
                const current_html = fields['html'] ?? null;
                const html = convertToHTML(`${value}`);
                props.onFieldChanged('html', new_fields['html'] = html, current, html === (props.defaultFields['html'] ?? null));
            }

            setFields(new_fields);
        }
    }

    const getField = (f: string): number|string|null => fields[f] ?? null;

    const isEnabled = (f:Feature): boolean => props.features.includes(f);
    const controlAllowed = (c:Control): boolean => props.controls.includes(c);

    useLayoutEffect(() => {
        if (getField('body') && !getField('html')) setField('html', convertToHTML( `${getField('body')}` ));
        else if (!getField('body') && getField('html')) setField('body', convertToTwino( `${getField('html')}` ));
        else if (!getField('body') && !getField('html') && cache_value) setField('body', cache_value );
    }, []);

    return (
        <Globals.Provider value={{
            uuid: uuid.current,
            setField: (f:string,v:string|number|null) => setField(f,v),
            getField: (f:string) => getField(f),
            isEnabled: (f:Feature) => isEnabled(f),
            allowControl: (c:Control) => controlAllowed(c),
            selection: selection.current,
        }}>
            <div className={ props.pm ? 'pm-editor' : 'forum-editor' }>
                { props.header && <TwinoEditorHeader {...props} /> }
                <TwinoEditorFields tags={props.tags}/>
                <div className="row classic-editor classic-editor-react" ref={me}>
                    <div className="padded cell rw-12">
                        {isEnabled("preview") && <TwinoEditorPreview
                            html={`${getField("html") ?? convertToTwino(`${getField('body') ?? ''}`) ?? ''}`}/>}
                        <label htmlFor={`${uuid.current}-editor`}>Deine Nachricht</label>
                        <TwinoEditorControls/>
                    </div>
                    <div className="padded cell rw-12">
                        <TwinoEditorEditor
                            fixed={props.pm}
                            controlTrigger={ s => {
                                const list = me.current?.querySelectorAll(`[data-receive-control-event="${s}"]`);
                                list.forEach(e => e.dispatchEvent( new CustomEvent('controlActionTriggered', { bubbles: false }) ));
                                return list.length > 0;
                            } }
                        />
                    </div>
                    <div className="padded cell rw-12">
                        <TwinoEditorControlsTabList/>
                    </div>
                </div>
            </div>
        </Globals.Provider>
    )
};

const TwinoEditorHeader = ({header, username}: HeaderConfig ) => {
    return <div className="forum-editor-header">
        <i>{ header }</i>
        <b>{ username }</b>
    </div>
};

const TwinoEditorFields = ({tags}: {tags: { [index: string]: string }}) => {
    const globals = useContext(Globals)

    const [showTagDropdown, setShowTagDropdown] = useState( !!globals.getField('tag') );

    return <div>
        { globals.isEnabled("title") &&
            <div className="row-flex v-center">
                <div className="cell rw-3 padded">
                    <label htmlFor={`${globals.uuid}-title`}>Titel</label>
                </div>
                <div className={`cell ${ globals.isEnabled("tags") && !globals.getField('tag') && !showTagDropdown ? 'rw-5 rw-sm-9' : 'rw-9' } padded`}>
                    <input type="text" id={`${globals.uuid}-title`}
                           defaultValue={globals.getField('title')}
                           onChange={v => globals.setField('title', v.target.value)}
                    />
                </div>
                { globals.isEnabled("tags") && !globals.getField('tag') && !showTagDropdown &&
                    <div className="cell rw-4 rw-sm-12 padded">
                    <span className="small pointer" onClick={() => setShowTagDropdown(true)}>
                        Tag hinzufügen (optional)
                    </span>
                    </div>
                }
            </div>
        }
        { globals.isEnabled("tags") && showTagDropdown &&
            <div className="row-flex v-center">
                <div className="cell rw-3 padded">
                    <label htmlFor={`${globals.uuid}-tags`}>Tag</label>
                </div>
                <div className="cell rw-9 padded">
                    <select id={`${globals.uuid}-tags`}
                           defaultValue={ globals.getField('tag') }
                           onChange={v => globals.setField('tag', v.target.value)}
                    >
                        <option value="-none-">[ Kein Tag ]</option>
                        { Object.entries(tags).map( ([k,v]) => <option key={k} value={k}>{v}</option> ) }
                    </select>
                </div>
            </div>
        }
        { globals.isEnabled("version") &&
            <div className="row-flex v-center">
                <div className="cell rw-3 padded">
                    <label htmlFor={`${globals.uuid}-version`}>Version</label>
                </div>
                <div className="cell rw-9 padded">
                    <input type="text" id={`${globals.uuid}-version`}
                           defaultValue={globals.getField('version')}
                           onChange={v => globals.setField('version', v.target.value)}
                    />
                </div>
            </div>
        }
        {globals.isEnabled("language") &&
            <div className="row-flex v-center">
                <div className="cell rw-3 padded">
                    <label htmlFor={`${globals.uuid}-language`}>Sprache</label>
                </div>
                <div className="cell rw-9 padded">
                    <select id={`${globals.uuid}-language`}
                            defaultValue={globals.getField('language')}
                            onChange={v => globals.setField('language', v.target.value)}
                    >
                        {Object.entries(c.langs).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                    </select>
                </div>
            </div>
        }
    </div>
}

const TwinoEditorPreview = ({html}: {html:string}) => {

    const [displayPreview, setDisplayPreview] = useState(true);

    return <>
        <label className="small pointer" onClick={() => setDisplayPreview(!displayPreview)}>
            Vorschau
        </label>
        <div translate="no" className="twino-editor-preview" dangerouslySetInnerHTML={{__html: html}}/>
    </>
}

const TwinoEditorEditor = ({fixed, controlTrigger}: {fixed: boolean, controlTrigger?: null|((s:string) => boolean)}) => {
    const textArea = useRef<HTMLTextAreaElement>(null);
    const globals = useContext(Globals);

    const shouldFocus = useRef(false);

    useEffect(() => {
        const onSelectionChange = () => {
            if (textArea.current && document.activeElement === textArea.current) {
                globals.selection.start = textArea.current.selectionStart;
                globals.selection.end = textArea.current.selectionEnd;
            }
        }

        document.addEventListener('selectionchange', onSelectionChange);
        return () => document.removeEventListener('selectionchange', onSelectionChange);
    });

    useLayoutEffect(() => {
        globals.selection.update = (s:number, e:number) => {
            globals.selection.start = s;
            globals.selection.end = e;
            shouldFocus.current = true;
        };

        return () => {
            globals.selection.update = (s, e) => {}
        }
    })

    useLayoutEffect(() => {
        if (shouldFocus.current) {
            textArea.current.setSelectionRange( globals.selection.start, globals.selection.end );
            textArea.current.focus();
            shouldFocus.current = false;
        }
    })

    return <textarea ref={textArea}
        value={globals.getField('body') ?? ''}
        tabIndex={0} id={`${globals.uuid}-editor`} style={fixed ? {height: '90px', minHeight: '90px'} : {}}
        onInput={e => globals.setField('body', e.currentTarget.value)}
        onKeyDown={e => {
            if (controlTrigger && (e.ctrlKey || e.metaKey)) {
                if (controlTrigger(e.key.toLowerCase())) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            }
        }}
    />
}