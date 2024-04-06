import * as React from "react";
import { createRoot } from "react-dom/client";

import {useContext, useEffect, useLayoutEffect, useRef, useState} from "react";
import {TranslationStrings} from "./strings";
import {Const, Global} from "../../defaults";
import {v4 as uuidv4} from 'uuid';
import {TwinoEditorControls, TwinoEditorControlsTabList} from "./Controls";
import {EmoteResponse, TwinoEditorAPI} from "./api";
import {Fetch} from "../../v2/fetch";

declare var $: Global;
declare var c: Const;

type Feature = "tags"|"title"|"version"|"language"|"preview"|"alias"|"passive"
type Control = "core"|"extended"|"emote"|"image"|"admin"|"mod"|"oracle"|"glory"|"poll"|"game"|"rp"|"snippet"|"user"
type Skin = "forum"|"pm"|"line"|"textarea"

interface HeaderConfig {
    header?: string|null,
    username?: string|null,
    user?: number|null
    tags?: {[index:string]: string}|null,
}

interface EditorConfig {
    maxLength?: number|null,
    placeholder?: string|null,
    enterKeyHint?: 'enter'|'done'|'go'|'next'|'previous'|'search'|'send'|null
}

type HTMLConfig = HeaderConfig & {
    id?: string|null,
    context: string,
    features: Feature[],
    controls: Control[],
    skin: Skin,
    roles?: {[index:string]: string},
    target?: {
        url: string,
        method?: string,
        map?: {[index:string]: string}
        include?: {[index:string]: string}
    }|null
    defaultFields?: {[index:string]: string|number},
    redirectAfterSubmit?: string|boolean,
    previewSelector?: string,
    editorPrefs: EditorConfig,
}

type FieldChangeEventTrigger = ( field: string, value: string|number|null, old_value: string|number|null, is_default: boolean ) => void
type SubmitEventTrigger = ( fields: {[index:string]: string|number}, response?: any ) => void
type FieldMutator = ( field: string, value: string|number|null ) => void
type FieldReader = ( field: string ) => string|number|null
type FeatureCheck = ( feature: Feature ) => boolean
type ControlCheck = ( control: Control ) => boolean

type TwinoEditorGlobals = {
    api: TwinoEditorAPI,
    uid: number,
    context: string,
    strings: null|TranslationStrings,
    uuid: string,
    setField: FieldMutator,
    getField: FieldReader,
    isEnabled: FeatureCheck,
    allowControl: ControlCheck,
    skin: Skin,
    selection: {
        start: number,
        end: number,
        update: (s:number,e:number) => void
    }
}

type TwinoContentImport = {
    body?: string,
    html?: string,
    wrap?: [string,string],
    insert?: boolean,
    opmode?: number,
}

export const Globals = React.createContext<TwinoEditorGlobals>(null);


export class HordesTwinoEditor {

    #_root = null;
    #_parent = null;
    #_detail_cache = null;
    #_values: {[index:string]: string|number} = {};
    #_content_import: (TwinoContentImport) => void = v=>this.#_detail_cache = v;

    public getValue(field: string): string|number|null {
        if (field === 'html') return deproxify( (this.#_values['html'] as string) ?? '' );
        else if (field === 'preview') return this.#_values['html'] ?? null;
        else return this.#_values[field] ?? null;
    }

    private onFieldChanged( field: string, value: string|number|null, old_value: string|number|null, is_default: boolean ): void {
        this.#_values[field] = value;
        this.#_parent.dispatchEvent( new CustomEvent('change', {
            bubbles: false,
            detail: { field, value, old_value, is_default }
        }) );
    }

    private onSubmit( fields: {[index:string]: string|number}, response: any = null ): void {
        this.#_parent.dispatchEvent( new CustomEvent('submit', {
            bubbles: false,
            detail: { fields, response: response ?? null }
        }) )
    }

    public mount(parent: HTMLElement, props: HTMLConfig): void {
        if (!this.#_root) {
            this.#_root = createRoot(this.#_parent = parent);
            this.#_parent.addEventListener('import', e => {
                this.#_content_import(e.detail);
            })
        }
        this.#_values = props.defaultFields ?? {};
        this.#_root.render( <TwinoEditorWrapper
            {...props}
            onFieldChanged={(f:string,v:string|number|null,v0:string|number|null,d:boolean) => this.onFieldChanged(f,v,v0,d)}
            onSubmit={(fields, response) => this.onSubmit(fields, response)}
            connectImport={ callback => {
                this.#_content_import = callback;
                if (this.#_detail_cache) {
                    callback(this.#_detail_cache);
                    this.#_detail_cache = null;
                }
            } }
        /> );
    }

    public unmount(parent: HTMLElement): void {
        if (this.#_root) {
            this.#_root.unmount();
            this.#_root = null;
            this.#_detail_cache = null;
            this.#_content_import = v=>this.#_detail_cache = v;
        }
    }
}

const deproxify = (s: string): string => {
    const div = document.createElement('div');
    div.innerHTML = s;

    // remove proxies
    let proxies = div.querySelectorAll('[x-foxy-proxy]');
    for (let j = 0; j < proxies.length; j++) {
        if (!proxies[j].parentNode) continue;
        proxies[j].parentNode.insertBefore(document.createTextNode(proxies[j].getAttribute('x-foxy-proxy')), proxies[j]);
        proxies[j].parentNode.removeChild(proxies[j]);
    }

    return div.innerHTML;
}

export const TwinoEditorWrapper = ( props: HTMLConfig & { onFieldChanged: FieldChangeEventTrigger, onSubmit?: SubmitEventTrigger, connectImport?: (any)=>void } ) => {

    const cache = $.client.config.scopedEditorCache.get() ?? ['',''];
    const cache_value = (cache[0] ?? '_') === props.context ? cache[1] : null;

    const uuid = useRef(props.id ?? uuidv4());
    const [strings, setStrings] = useState<TranslationStrings>(null);
    const [fields, setFields] = useState<{[index:string]: string|number}>({
        ...props.defaultFields ?? {},
    });
    const fieldRef = useRef<{[index:string]: string|number}>(fields);

    const [expanded, setExpanded] = useState<boolean>(props.skin !== 'pm');

    const selection = useRef({
        start: 0,
        end: 0,
        update: (s,e) => {}
    });

    const apiRef = useRef<TwinoEditorAPI>(new TwinoEditorAPI());

    const me = useRef<HTMLDivElement>(null);

    const [emotes, setEmotes] = useState<EmoteResponse>(null);
    const emoteRef = useRef<EmoteResponse>(null);

    const isEnabled = (f:Feature): boolean => props.features.includes(f);
    const controlAllowed = (c:Control): boolean => props.controls.includes(c);

    const emoteResolver = (s:string): [string|null,string] => {
        if (!controlAllowed('emote')) return [null,s];
        const e = emotes ?? emoteRef.current ?? null;
        if (e === null) return [null,s];
        s = e.mock[s] ?? s;
        return [e.result[s]?.url ?? null, s];
    }

    const convertToHTML = (twino:string,update = (s:string) => {console.warn('no processor.')}) => $.html.twinoParser.parseToString(twino, s => emoteResolver(s), {autoLinks: $.client.config.autoParseLinks.get()}, update);
    const convertToTwino = (html:string,opmode:number = null) => $.html.twinoParser.parseFrom(html, opmode ?? $.html.twinoParser.OpModeRaw);

    const setField = (field: string, value: string|number) => {
        const current = fieldRef.current[field] ?? null;

        // Replace snippets
        if (field === 'body' && emotes?.snippets) value = `${value}`.replace( /%(\w*?)%(\w+)/, (match:string, lang:string, short:string) => emotes.snippets.list[lang === emotes.snippets.base ? `%%${short}` : match]?.value ?? match )

        if (current !== value) {
            let new_fields = {...fieldRef.current};
            props.onFieldChanged(field, new_fields[field] = value, current, value === ((props.defaultFields ?? {})[field] ?? null));

            // Changing the body also changes the HTML and invokes the cache
            if (field === 'body') {
                $.client.config.scopedEditorCache.set([props.context, value]);

                const update = (s:string) => {
                    props.onFieldChanged('html', s, current, s === ((props.defaultFields ?? {})['html'] ?? null));
                    if (props.previewSelector) (document.querySelector(props.previewSelector) ?? {innerHTML:''}).innerHTML = s;
                }
                update(new_fields['html'] = convertToHTML(`${value}`, s => {
                    update(s);
                    setField('html', s);
                }));

            } else if (field === 'html') (document.querySelector(props.previewSelector) ?? {innerHTML:''}).innerHTML = value as string;

            setFields({...fieldRef.current = new_fields});
        }
    }

    const getField = (f: string): number|string|null => fieldRef.current[f] ?? null;

    const submit = () => {
        const html = deproxify( `${fieldRef.current.html ?? ''}` );

        if (!props.target) {
            $.client.config.scopedEditorCache.set(['','']);
            if (props.onSubmit) props.onSubmit({...fieldRef.current, html });
        } else {
            let submissionData = {};

            const check = (field: string, value: string|number): boolean => {
                switch (field) {
                    case 'role': return props.roles && props.roles.hasOwnProperty(value);
                    default: return value !== '' && value !== null && value !== undefined;
                }
            }

            if (props.target?.map) Object.entries(props.target.map).forEach(([field,property]) => {
                if ( field === 'html') submissionData[ property ] = html;
                else if (fieldRef.current.hasOwnProperty( field ) && check( field, fieldRef.current[ field ] )) submissionData[ property ] = fieldRef.current[ field ];
            }); else submissionData = {...fieldRef.current, html };

            if (props.target.include) Object.entries(props.target.include).forEach(([field,selector]) => {
                const elem = document.querySelector(selector);
                const asArray = field.slice(-2) === '[]';
                if (elem) submissionData[ asArray ? field.slice(0,-2) : field ] = asArray ? (elem as HTMLInputElement).value.split(',').filter(s => s !== '') : (elem as HTMLInputElement).value;
            });

            (new Fetch( props.target.url, false )).fromEndpoint()
                .bodyDeterminesSuccess(true)
                .withLoader()
                .withXHRHeader()
                .request().method( props.target.method ?? 'post', submissionData )
                .then(r => {
                    $.client.config.scopedEditorCache.set(['','']);
                    props.onSubmit({...fieldRef.current}, r);
                    if (props.redirectAfterSubmit === true) {
                        const url = r.url ?? r.redirect ?? null;
                        if (url) $.ajax.load( null, url, true );
                    } else if (props.redirectAfterSubmit) $.ajax.load( null, props.redirectAfterSubmit as string, true );
                })
        }
    }

    useEffect(() => {
        if (props.connectImport)
            props.connectImport( (t:TwinoContentImport) => {
                let body = null;
                if ((t.html ?? null) !== null)
                    body = convertToTwino( t.html, t.opmode );
                else if ((t.body ?? null) !== null)
                    body = t.body;

                if (body !== null) {
                    body = ((t.wrap ?? ['',''])[0] ?? '') + body + ((t.wrap ?? ['',''])[1] ?? '');

                    if (t.insert) {
                        const prev = `${fieldRef.current.body ?? ''}`;
                        setField('body', prev.slice( 0, selection.current.start ) + body + prev.slice( selection.current.end ) )
                        selection.current.start = selection.current.end = (selection.current.end + body.length);
                    }
                    else {
                        setField('body', body);
                        selection.current.start = selection.current.end = body.length;
                    }
                }

            } );

        apiRef.current.index().then(data => setStrings(data.strings));
        if (controlAllowed('emote') || controlAllowed('snippet'))
            apiRef.current.emotes(props.user,props.context).then(data => {
                setEmotes({...emoteRef.current = data});
                const update = (s:string) => {
                    if (s !== (fieldRef.current['html'] ?? '')) setField('html', s);
                }
                update( convertToHTML( `${fieldRef.current['body'] ?? ''}` , update) );
            })
        else setEmotes( emoteRef.current = { mock: {}, snippets: null, result: {}} )
    }, []);

    useLayoutEffect(() => {
        if (getField('body') && !getField('html')) setField('html', convertToHTML( `${getField('body')}` ));
        else if (!getField('body') && getField('html')) setField('body', convertToTwino( `${getField('html')}` ));
        else if (!getField('body') && !getField('html') && cache_value) setField('body', cache_value );
    }, []);

    const padded = props.skin !== 'line' && props.skin !== 'textarea';

    return (
        <>
            { strings === null && <div className="loading"/> }
            { strings !== null && <Globals.Provider value={{
                api: apiRef.current,
                uid: props.user,
                context: props.context,
                uuid: uuid.current,
                setField: (f:string,v:string|number|null) => setField(f,v),
                getField: (f:string) => getField(f),
                isEnabled: (f:Feature) => isEnabled(f),
                allowControl: (c:Control) => controlAllowed(c),
                selection: selection.current,
                strings,
                skin: props.skin,
            }}>
                <div className={`${props.skin}-editor`}>
                    {props.header && <TwinoEditorHeader {...props} />}
                    <TwinoEditorFields tags={props.tags}/>
                    <div className="row classic-editor classic-editor-react" ref={me}>
                        {isEnabled("preview") && <div className={`${padded ? 'padded' : ''} cell rw-12`}>
                            <TwinoEditorPreview
                                html={`${getField("html") ?? convertToTwino(`${getField('body') ?? ''}`) ?? ''}`}/>
                        </div>}

                        <div className={padded ? "padded cell rw-12" : 'overlay-controls'}>
                            {padded && <div className="row">
                                <div className="cell rw-6 rw-md-12">
                                    <label className="small"
                                           htmlFor={`${uuid.current}-editor`}>{strings.sections.message}</label>
                                </div>
                                {!expanded && <div className="cell rw-6 rw-md-12 right">
                                    <span className="pointer small"
                                          onClick={() => setExpanded(true)}>{strings.common.expand}</span>
                                </div>}
                            </div>}
                            <div className={expanded ? '' : 'hidden'}><TwinoEditorControls emotes={emoteRef.current === null ? null : Object.values(emoteRef.current.result)}/></div>
                        </div>
                        <div className={`${padded ? 'padded' : 'overlay-central'} cell rw-12`}>
                            <TwinoEditorEditor
                                body={`${fieldRef.current['body'] ?? getField('body') ?? ''}`}
                                fixed={props.skin === "pm" || props.skin === "textarea"} prefs={props.editorPrefs}
                                controlTrigger={s => {
                                    if (s === 'enter') submit();
                                    else {
                                        const list = me.current?.querySelectorAll(`[data-receive-control-event="${s}"]`);
                                        list.forEach(e => e.dispatchEvent(new CustomEvent('controlActionTriggered', {bubbles: false})));
                                        return list.length > 0;
                                    }
                                }}
                            />
                        </div>
                        {padded && <div className={`padded cell rw-12 ${expanded ? '' : 'hidden'}`}>
                            <TwinoEditorControlsTabList
                                emotes={emoteRef.current === null ? null : Object.values(emoteRef.current.result)}
                                snippets={emoteRef.current === null ? null : Object.values(emoteRef.current.snippets?.list ?? {})}
                            />
                        </div> }

                    </div>
                    <div className="row-flex v-stretch right">
                        {Object.values(props.roles ?? []).length > 0 && <>
                            <div className="padded cell">
                                <label><select value={getField('role')} className="full-height"
                                               onChange={e => setField('role', e.target.value)}>
                                    {Object.entries(props.roles).map( ([role,name]) => <option key={ role } value={ role }>{ name }</option> ) }
                                </select></label>
                            </div>
                        </>}
                        { !isEnabled('passive') && <>
                            <div className="padded cell">
                                <div className="forum-button" tabIndex={20} onClick={() => submit()}>
                                <span className="forum-button-tooltip">
                                    <div className="center">{strings.common.send}</div>
                                    <div
                                        className="keyboard"><kbd>{strings.common.ctrl}</kbd> + <kbd>{strings.common.enter}</kbd></div>
                                </span>
                                    {strings.common.send}
                                </div>
                            </div>
                        </>}
                    </div>
                </div>
            </Globals.Provider>}
        </>
    )
};

const TwinoEditorHeader = ({header, username}: HeaderConfig) => {
    return <div className="forum-editor-header">
        <i>{header}</i>
        <b>{username}</b>
    </div>
};

const TwinoEditorFields = ({tags}: { tags: { [index: string]: string } }) => {
    const globals = useContext(Globals)

    const [showTagDropdown, setShowTagDropdown] = useState(!!globals.getField('tag'));

    return <div>
        {globals.isEnabled("title") &&
            <div className="row-flex v-center">
            <div className="cell rw-3 padded">
                    <label htmlFor={`${globals.uuid}-title`}>{globals.strings.header.title}</label>
                </div>
                <div
                    className={`cell ${globals.isEnabled("tags") && !globals.getField('tag') && !showTagDropdown ? 'rw-5 rw-sm-9' : 'rw-9'} padded`}>
                    <input type="text" id={`${globals.uuid}-title`} tabIndex={1}
                           defaultValue={globals.getField('title')}
                           onChange={v => globals.setField('title', v.target.value)}
                    />
                </div>
                {globals.isEnabled("tags") && !globals.getField('tag') && !showTagDropdown &&
                    <div className="cell rw-4 rw-sm-12 padded">
                    <span className="small pointer" onClick={() => setShowTagDropdown(true)}>
                        {globals.strings.header.add_tag}
                    </span>
                    </div>
                }
            </div>
        }
        { globals.isEnabled("tags") && showTagDropdown &&
            <div className="row-flex v-center">
                <div className="cell rw-3 padded">
                    <label htmlFor={`${globals.uuid}-tags`}>{ globals.strings.header.tag }</label>
                </div>
                <div className="cell rw-9 padded">
                    <select id={`${globals.uuid}-tags`} tabIndex={2}
                           defaultValue={ globals.getField('tag') }
                           onChange={v => globals.setField('tag', v.target.value)}
                    >
                        <option value="-none-">[ { globals.strings.header.no_tag } ]</option>
                        { Object.entries(tags).map( ([k,v]) => <option key={k} value={k}>{v}</option> ) }
                    </select>
                </div>
            </div>
        }
        { globals.isEnabled("version") &&
            <div className="row-flex v-center">
                <div className="cell rw-3 padded">
                    <label htmlFor={`${globals.uuid}-version`}>{ globals.strings.header.version }</label>
                </div>
                <div className="cell rw-9 padded">
                    <input type="text" id={`${globals.uuid}-version`}  tabIndex={3}
                           defaultValue={globals.getField('version')}
                           onChange={v => globals.setField('version', v.target.value)}
                    />
                </div>
            </div>
        }
        {globals.isEnabled("language") &&
            <div className="row-flex v-center">
                <div className="cell rw-3 padded">
                    <label htmlFor={`${globals.uuid}-language`}>{ globals.strings.header.language }</label>
                </div>
                <div className="cell rw-9 padded">
                    <select id={`${globals.uuid}-language`}  tabIndex={4}
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
    const globals = useContext(Globals)

    const [displayPreview, setDisplayPreview] = useState(true);

    const preview = useRef<HTMLDivElement>();

    useLayoutEffect( () => {
        preview.current.querySelectorAll('.username[x-user-id]').forEach( e => $.html.handleUserPopup(e as HTMLElement) );
        $.html.handleCollapseSection( preview.current );
    } );

    return <>
        <label className="small pointer" onClick={() => setDisplayPreview(!displayPreview)}>
            { globals.strings.sections.preview }
        </label>
        <div ref={preview} translate="no" className="twino-editor-preview" dangerouslySetInnerHTML={{__html: html}}/>
    </>
}

const TwinoEditorEditor = ({body, fixed, controlTrigger, prefs}: {body: string, fixed: boolean, controlTrigger?: null|((s:string) => boolean), prefs: EditorConfig}) => {
    const textArea = useRef<HTMLTextAreaElement|HTMLInputElement|any>(null);
    const globals = useContext(Globals);

    const shouldFocus = useRef(globals.skin !== 'line' && globals.skin !== 'textarea');

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
        globals.selection.update = (s: number, e: number) => {
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
            textArea.current.setSelectionRange(globals.selection.start, globals.selection.end);
            textArea.current.focus();
            shouldFocus.current = false;
        }
    })

    return globals.skin === "line"
        ? <input
            type="text" value={body} maxLength={prefs.maxLength} placeholder={prefs.placeholder}
            ref={textArea} enterKeyHint={prefs.enterKeyHint}
            tabIndex={0} id={`${globals.uuid}-editor`}
            onInput={e => globals.setField('body', e.currentTarget.value)}
            onKeyDown={e => {
                if (controlTrigger && (e.ctrlKey || e.metaKey) && (!e.shiftKey)) {
                    if (controlTrigger(e.key.toLowerCase())) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                }
            }}
        />
        : <textarea ref={textArea}
            value={body} maxLength={prefs.maxLength} placeholder={prefs.placeholder}
            tabIndex={10} id={`${globals.uuid}-editor`}
            style={fixed ? {height: '90px', minHeight: '90px'} : {}}
            onInput={e => globals.setField('body', e.currentTarget.value)}
            onKeyDown={e => {
                if (controlTrigger && (e.ctrlKey || e.metaKey) && (!e.shiftKey)) {
                    if (controlTrigger(e.key.toLowerCase())) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                }
            }}
        />
}