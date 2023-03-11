import {useContext, useEffect, useLayoutEffect, useRef, useState} from "react";
import {Globals} from "./Wrapper";
import * as React from "react";
import {EventMeta, TownPresetData} from "./api";
import Components from "../index";

declare global {
    namespace JSX {
        interface IntrinsicElements {
            ['hordes-town-creator']: any;
        }
    }
}

export const HordesEventCreatorWizard = ( {cancel, uuid}: {
    cancel: ()=>void,
    uuid: string,
} ) => {
    const globals = useContext(Globals)

    let [meta, setMeta] = useState<EventMeta[]>(null);
    let [towns, setTowns] = useState<EventMeta[]>(null);

    let [activeTownEditor, setActiveTownEditor] = useState<string|boolean>(false);
    let [townEditorPayload, setTownEditorPayload] = useState<{header:object,rules:object}|null>(null);

    useEffect(() => {
        globals.api.listMeta( uuid ).then( r => setMeta(r.meta) );
        return () => setMeta(null);
    }, [uuid]);

    return (
        <>
            <h4>{ globals.strings.editor.edit }</h4>

            <h5>META</h5>
            <div className="row">
                { meta === null && <div className="loading"></div> }
                { meta !== null && ['en','fr','de','es'].map( lang => <div key={lang} className="padded cell rw-6 rw-sm-12">
                    <HordesEventMetaEditor lang={lang} uuid={uuid} meta={ meta?.find( m => m.lang === lang ) ?? null } replace={(m:EventMeta|null) => {
                        let metaClone = [...meta];
                        if (m !== null) {
                            const i = meta.findIndex( mm => mm.lang === lang );
                            if (i<0) metaClone.push( m );
                            else metaClone[i] = m;
                        } else metaClone = metaClone.filter( mm => mm.lang !== lang )
                        setMeta(metaClone);
                    }} />
                </div> ) }
            </div>

            { activeTownEditor === true && <>
                <h5>MAKE TOWN</h5>
                <div className="row">
                    <div className="cell rw-12">
                        <HordesEventTownPresetEditor update={e => setTownEditorPayload(e)} uuid={null}/>
                    </div>
                    <div className="cell rw-6">
                        <button onClick={()=> {
                            setActiveTownEditor(false);
                            setTownEditorPayload(null)
                        }}>{ globals.strings.common.cancel }</button>
                    </div>
                    <div className="cell rw-6">
                        <button disabled={!townEditorPayload}>{ globals.strings.common.save }</button>
                    </div>
                </div>
            </> }

            { activeTownEditor && activeTownEditor !== true && <>
                <h5>EDIT TOWN</h5>
                <div className="row">
                    <div className="cell rw-12">
                        <HordesEventTownPresetEditor update={e => setTownEditorPayload(e)} uuid={activeTownEditor as string}/>
                    </div>
                    <div className="cell rw-6">
                        <button onClick={()=> {
                            setActiveTownEditor(false);
                            setTownEditorPayload(null)
                        }}>{ globals.strings.common.cancel }</button>
                    </div>
                    <div className="cell rw-6">
                        <button disabled={!townEditorPayload}>{ globals.strings.common.save }</button>
                    </div>
                </div>
            </> }

            { activeTownEditor === false && <>
                <h5>TOWN LIST</h5>
                <div className="row">
                    <button onClick={()=>setActiveTownEditor(true)}>ADD</button>
                </div>
            </> }

            <div className="row">
                <div className="cell rw-4 ro-8 rw-md-6 ro-md-6 rw-sm-12 ro-sm-0">
                    <button onClick={()=>cancel()}>{ globals.strings.common.cancel_create }</button>
                </div>
            </div>
        </>
    )
};

const HordesEventTownPresetEditor = ( {uuid, update}: {
    uuid: string|null,
    update: (TownPresetData) => void,
} ) => {

    let wrapper = useRef<HTMLDivElement>();
    useLayoutEffect( () => {
        Components.vitalize( wrapper.current )
        const listener = event => {
            console.log(event);
            update(event.detail.ready ? event.detail.options : null)
        }

        const w = wrapper.current;
        w.addEventListener('rules-changed', listener);
        return () => w.removeEventListener('rules-changed', listener)
    } )

    return (
            <div ref={wrapper}>
                <hordes-town-creator data-event-mode="1" data-elevation="3"></hordes-town-creator>
            </div>
        )
}

const HordesEventMetaEditor = ( {lang, uuid, meta, replace}: {
    lang: string,
    uuid: string,
    meta: EventMeta|null,
    replace: (EventMeta)=>void
} ) => {
    const globals = useContext(Globals)

    const editorTitle = useRef<HTMLInputElement>();
    const editorDescription = useRef<HTMLTextAreaElement>();

    let [editing, setEditing] = useState<boolean>(false);

    return (
        <div className={`note ${meta === null ? 'pointer' : ''}`} onClick={ (meta === null && !editing) ? () => setEditing(true) : ()=>{} }>
            { meta === null && !editing && <div className="center">
                <img alt={lang} src={globals.strings.common.flags[lang] ?? globals.strings.common.flags['multi']}/><br />
                <span className="small">{ globals.strings.editor.add_meta.replace('{lang}', globals.strings.common.langs[lang]) }</span>
            </div> }
            { (meta !== null || editing) && <div className="row row-flex">
                <div className="cell padded-small"><img width={16} className="cell shrink-0" alt={lang} src={globals.strings.common.flags[lang] ?? globals.strings.common.flags['multi']}/></div>
                <div className="cell grow-1">
                    { !editing && <>
                        <div className="small"><b>{ meta?.name }</b></div>
                        <div className="small"><i>{ meta?.description }</i></div>
                        <div className="row">
                            <div className="padded cell"><button className="small" onClick={() => setEditing(true)}>{ globals.strings.common.edit }</button></div>
                            <div className="padded cell"><button className="small" onClick={() => {
                                replace(null);
                                globals.api.deleteMeta( uuid, lang ).catch(() => replace(meta));
                            }}>{ globals.strings.common.delete }</button></div>
                        </div>
                    </> }
                    { editing && <>
                        <input type="text" placeholder={globals.strings.editor.field_title} defaultValue={ meta?.name ?? '' } ref={editorTitle} />
                        <textarea style={{resize: 'none'}} placeholder={globals.strings.editor.field_description} defaultValue={ meta?.description ?? '' } ref={editorDescription}></textarea>
                        <div className="row">
                            <div className="padded cell"><button className="small" onClick={() => setEditing(false)}>{ globals.strings.common.cancel }</button></div>
                            <div className="padded cell right"><button onClick={() => {
                                setEditing(false);
                                globals.api.setMeta( uuid, lang, editorTitle.current.value, editorDescription.current.value ).then(m => replace(m.meta))
                                replace({name: editorTitle.current.value, desc: editorDescription.current.value});
                            }} className="small">{ globals.strings.common.save }</button></div>
                        </div>
                    </> }
                </div>
            </div> }
        </div>
    )
};