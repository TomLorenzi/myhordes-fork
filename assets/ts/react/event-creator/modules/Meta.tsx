import {useContext, useEffect, useLayoutEffect, useRef, useState} from "react";
import {Globals} from "../Wrapper";
import * as React from "react";
import {EventConfig, EventMeta} from "../api";
import {Flag} from "../Common";
import {EditorGlobals} from "../Creator";

declare global {
    namespace JSX {
        interface IntrinsicElements {
            ['hordes-town-creator']: any;
        }
    }
}

export const HordesEventCreatorModuleMeta = ( {uuid}: {
    uuid: string
} ) => {
    const globals = useContext(Globals)
    const editorGlobals = useContext(EditorGlobals)

    let [meta, setMeta] = useState<EventMeta[]>(null);
    let [config, setConfig] = useState<EventConfig>(null);

    useEffect(() => {
        globals.api.listMeta( uuid ).then( r => setMeta(r.meta) );
        return () => setMeta(null);
    }, [uuid]);

    useEffect(() => {
        globals.api.getConfig( uuid ).then( r => setConfig(r) );
        return () => setConfig(null);
    }, [uuid]);

    return (
        <>
            <div className="row">
                <div className="cell rw-12">
                    <div className="help">
                        { globals.strings.editor.help }
                    </div>
                </div>
            </div>
            { (config === null || meta === null) && <div className="loading"></div> }
            { (config !== null && meta !== null) && <>
                <HordesEventConfigEditor config={config} setConfig={c=>{
                    globals.api.editConfig( uuid, c ).then( r => setConfig(r) );
                }}/>
                <div className="row">
                    { ['en','fr','de','es'].map( lang => <div key={lang} className="padded cell rw-12">
                        { (editorGlobals.writable || meta?.find( m => m.lang === lang )) && <>
                            <HordesEventMetaEditor lang={lang} uuid={uuid} meta={ meta?.find( m => m.lang === lang ) ?? null } replace={(m:EventMeta|null) => {
                                let metaClone = [...meta];
                                if (m !== null) {
                                    const i = meta.findIndex( mm => mm.lang === lang );
                                    if (i<0) metaClone.push( m );
                                    else metaClone[i] = m;
                                } else metaClone = metaClone.filter( mm => mm.lang !== lang )
                                setMeta(metaClone);
                            }} />
                        </> }
                    </div> ) }
                </div>
            </>  }
        </>
    )
};

const HordesEventConfigEditor = ( {config, setConfig}: {
    config: EventConfig,
    setConfig: (EventConfig)=>void
} ) => {
    const globals = useContext(Globals);
    const editorGlobals = useContext(EditorGlobals);

    const min = new Date();
    min.setDate(min.getDate() + 1)
    const max = new Date();
    max.setDate(max.getDate() + 194)

    return <>
        <div className="row">
            <div className="cell padded rw-3 rw-md-6 rw-sm-12 note note-lightest">
                <label htmlFor="te_startDate">{ globals.strings.editor.schedule }</label>
            </div>
            <div className="cell padded rw-3 rw-md-6 rw-sm-12">
                <input type="date" defaultValue={config.startDate} name="te_startDate" readOnly={!editorGlobals.writable}
                       min={`${min.getFullYear()}-${`${min.getMonth()+1}`.padStart(2,'0')}-${`${min.getDate()}`.padStart(2,'0')}`}
                       max={`${max.getFullYear()}-${`${max.getMonth()+1}`.padStart(2,'0')}-${`${max.getDate()}`.padStart(2,'0')}`}
                       onChange={e=>e.target.validity.valid ? setConfig({startDate: e.target.value}) : null}/>
            </div>
        </div>
    </>
}

const HordesEventMetaEditor = ( {lang, uuid, meta, replace}: {
    lang: string,
    uuid: string,
    meta: EventMeta|null,
    replace: (EventMeta)=>void
} ) => {
    const globals = useContext(Globals)
    const editorGlobals = useContext(EditorGlobals)

    const editorTitle = useRef<HTMLInputElement>();
    const editorDescription = useRef<HTMLTextAreaElement>();
    const editorShort = useRef<HTMLTextAreaElement>();

    let [editing, setEditing] = useState<boolean>(false);

    return (
        <div className={`note ${meta === null ? 'pointer' : ''}`} onClick={ (meta === null && !editing) ? () => setEditing(true) : ()=>{} }>
            <div className="row row-flex">
                <div className="cell padded-small"><Flag lang={lang} className="cell shrink-0" width={16}/></div>
                <div className="cell grow-1">
                    { meta === null && !editing && <span className="small">{ globals.strings.editor.add_meta.replace('{lang}', globals.strings.common.langs[lang]) }</span> }
                    { (meta !== null || editing) && <>
                            { !editing && <>
                                <div className="small"><b>{ meta?.name }</b></div>
                                <div className="small"><i>{ meta?.short }</i></div>
                                <div className="small"><i>{ meta?.description }</i></div>
                            </> }
                            { editing && <>
                                <input type="text" placeholder={globals.strings.editor.field_title} defaultValue={ meta?.name ?? '' } ref={editorTitle} />
                                <textarea style={{resize: 'none', minHeight: '45px'}} placeholder={globals.strings.editor.field_short} defaultValue={ meta?.short ?? '' } ref={editorShort}></textarea>
                                <textarea style={{resize: 'none'}} placeholder={globals.strings.editor.field_description} defaultValue={ meta?.description ?? '' } ref={editorDescription}></textarea>
                                <div className="row">
                                    <div className="padded cell"><button className="small" onClick={() => setEditing(false)}>{ globals.strings.common.cancel }</button></div>
                                    <div className="padded cell right"><button onClick={() => {
                                        setEditing(false);
                                        globals.api.setMeta( uuid, lang, editorTitle.current.value, editorDescription.current.value, editorShort.current.value ).then(m => replace(m.meta))
                                        replace({name: editorTitle.current.value, desc: editorDescription.current.value});
                                    }} className="small">{ globals.strings.common.save }</button></div>
                                </div>
                            </> }
                    </> }
                </div>
                { meta && !editing && editorGlobals.writable && <div className="cell shrink-0">
                    <span className="cell padded-small shrink-0" title={globals.strings.common.edit}>
                        <img className="pointer" alt={globals.strings.common.edit} src={globals.strings.common.edit_icon} onClick={() => setEditing(true)} />
                    </span>
                    <span className="cell padded-small shrink-0" title={globals.strings.common.delete}>
                        <img className="pointer" alt={globals.strings.common.delete} src={globals.strings.common.delete_icon} onClick={() => {
                            replace(null);
                            globals.api.deleteMeta( uuid, lang ).catch(() => replace(meta));
                        }} />
                    </span>
                </div> }
            </div>
        </div>
    )
};