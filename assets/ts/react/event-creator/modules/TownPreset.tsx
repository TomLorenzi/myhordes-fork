import {useContext, useEffect, useLayoutEffect, useRef, useState} from "react";
import {Globals} from "../Wrapper";
import * as React from "react";
import Components from "../../index";
import {TownPreset, TownPresetData} from "../api";
import {Flag} from "../Common";
import {EditorGlobals} from "../Creator";
import {Tooltip} from "../../tooltip/Wrapper";
import {Simulate} from "react-dom/test-utils";
import cancel = Simulate.cancel;

declare global {
    namespace JSX {
        interface IntrinsicElements {
            ['hordes-town-creator']: any;
        }
    }
}

export const HordesEventCreatorModuleTownPreset = ( {uuid,published,expedited,owning,cancel}: {
    uuid: string,
    published: boolean,
    expedited: boolean,
    owning: boolean,
    cancel: ()=>void,
} ) => {
    const globals = useContext(Globals)
    const editorGlobals = useContext(EditorGlobals)

    let [townList, setTownList] = useState<TownPreset[]|null>(null);
    let [activeTownEditor, setActiveTownEditor] = useState<string|boolean>(false);
    let [townEditorPayload, setTownEditorPayload] = useState<{header:object,rules:object}|null>(null);

    useEffect(() => {
        refresh();
        return () => setTownList(null);
    }, [uuid]);

    const refresh = () => {
        globals.api.listTowns(uuid).then(r => setTownList(r.towns))
    };

    const send_payload = () => {
        if (!townEditorPayload) return;
        const rq = (activeTownEditor === true)
            ? globals.api.createTown( uuid, townEditorPayload.header, townEditorPayload.rules )
            : globals.api.updateTown( uuid, activeTownEditor as string, townEditorPayload.header, townEditorPayload.rules )
        ;

        rq.then(() => {
            setTownEditorPayload(null);
            setActiveTownEditor(false);
            refresh();
        } )
    }

    const has_unopened_town = townList?.reduce( (c,town) => c || town.instance === null, false )

    return (
        <>
            <div className="row">
                <div className="cell rw-12">
                    <div className="help">
                        { globals.strings.towns.help1 }&nbsp;
                        <b>{ globals.strings.towns.help2 }</b>
                    </div>
                </div>
            </div>
            { activeTownEditor === true && <>
                <div className="row">
                    <div className="cell rw-12">
                        <HordesEventTownPresetEditor update={e => setTownEditorPayload(e)} uuid={uuid} town={null}/>
                    </div>
                    <div className="cell rw-6">
                        <button onClick={()=> {
                            setActiveTownEditor(false);
                            setTownEditorPayload(null)
                        }}>{ globals.strings.common.cancel }</button>
                    </div>
                    <div className="cell rw-6">
                        <button onClick={()=>send_payload()} disabled={!townEditorPayload}>{ globals.strings.common.save }</button>
                    </div>
                </div>
            </> }

            { activeTownEditor && activeTownEditor !== true && <>
                <h5>{ globals.strings.towns.town_edit }</h5>
                <div className="row">
                    <div className="cell rw-12" data-disabled={ editorGlobals.writable ? '' : 'blocked' }>
                        <HordesEventTownPresetEditor update={e => setTownEditorPayload(e)} uuid={uuid} town={activeTownEditor as string}/>
                    </div>
                    <div className="cell rw-6">
                        <button disabled={!editorGlobals.writable} onClick={()=> {
                            setActiveTownEditor(false);
                            setTownEditorPayload(null)
                        }}>{ globals.strings.common.cancel }</button>
                    </div>
                    <div className="cell rw-6">
                        <button onClick={()=>send_payload()} disabled={!townEditorPayload || !editorGlobals.writable}>{ globals.strings.common.save }</button>
                    </div>
                </div>
            </> }

            { activeTownEditor === false && <>
                <h5>{ globals.strings.towns.title }</h5>
                { townList === null && <div className="loading"/> }
                { townList !== null && townList.length === 0 && <span className="small">{ globals.strings.towns.no_towns }</span> }
                { townList !== null && townList.length > 0 && <div className="row-table">
                    <div className="row header hide-sm">
                        <div className="padded cell rw-1 rw-md-2"></div>
                        <div className="padded cell rw-9 rw-md-8">{ globals.strings.towns.table_town }</div>
                        <div className="padded cell rw-2 right">{ globals.strings.towns.table_act }</div>
                    </div>
                    { townList.map( town => (
                        <div className="row-flex wrap v-center" key={town.uuid}>
                            <div className="padded cell rw-1 rw-md-2">
                                <Flag lang={town.lang}/>
                            </div>
                            <div className="padded cell rw-9 rw-md-8">
                                <div>
                                    { town.instance !== null && town.instance.active && <img title={globals.strings.towns.town_instance_online} alt="" src={globals.strings.common.online_icon}/> }
                                    { town.instance !== null && !town.instance.active && <img title={globals.strings.towns.town_instance_offline} alt="" src={globals.strings.common.offline_icon}/> }
                                    { (town.name ?? town.instance?.name) && <i>{ town.name ?? town.instance.name }</i> }
                                    { !(town.name ?? town.instance?.name) && <span className="small">[ { globals.strings.towns.default_town } ]</span> }
                                </div>
                                <div className="small">
                                    { town['type'] }
                                    { town.instance !== null && <>
                                        {town.instance.filled !== null && town.instance.population !== null && `; ${globals.strings.towns.citizens}: ${town.instance.filled}/${town.instance.population}`}
                                        {town.instance.filled > 0 && `; ${globals.strings.towns.alive}: ${town.instance.living}`}
                                        {town.instance.day !== null && `; ${globals.strings.towns.day}: ${town.instance.day}`}
                                    </> }
                                </div>
                                { town.password && <>
                                    <div className="small townPassword">{ globals.strings.towns.password }: <pre>{ town.password }</pre></div>
                                </> }
                            </div>
                            <div className="padded cell rw-2 right">
                                { town.instance?.ranking_link && <div className="small"><a target="_blank" href={town.instance?.ranking_link}>{ globals.strings.towns.ranking_link }</a></div> }
                                { town.instance?.forum_link && <div className="small"><a target="_blank" href={town.instance?.forum_link}>{ globals.strings.towns.forum_link }</a></div> }
                                { editorGlobals.writable && <>
                                    <span className="cell padded-small shrink-0" title={globals.strings.common.edit}>
                                        <img className="pointer" alt={globals.strings.common.edit} src={globals.strings.common.edit_icon} onClick={()=>setActiveTownEditor(town.uuid)} />
                                    </span>
                                        <span className="cell padded-small shrink-0" title={globals.strings.common.delete}>
                                            <img className="pointer" alt={globals.strings.common.delete} src={globals.strings.common.delete_icon} onClick={() => {
                                                if (confirm( globals.strings.towns.delete_confirm )) {
                                                    setTownList( townList.filter(e => e.uuid !== town.uuid) )
                                                    globals.api.deleteTown(uuid, town.uuid).catch( () => refresh() );
                                                }
                                            }} />
                                        </span>
                                </> }
                                { !editorGlobals.writable && <>
                                    <span className="cell padded-small shrink-0" title={globals.strings.common.edit}>
                                        <img className="pointer" alt={globals.strings.common.edit} src={globals.strings.common.edit_icon} onClick={()=>setActiveTownEditor(town.uuid)} />
                                    </span>
                                </> }
                            </div>
                        </div>
                    ) ) }

                </div> }
                <div className="row-flex gap">
                    { editorGlobals.writable && <div className="cell">
                        <button onClick={()=>setActiveTownEditor(true)}>{ globals.strings.towns.add }</button>
                    </div> }
                    { published && owning && has_unopened_town && <div className="cell">
                        <div {...expedited ? {disabled: "disabled"} : {}}>
                            <button onClick={() => {
                                if (confirm( globals.strings.towns.expedite_confirm ))
                                    globals.api.editConfig( uuid, {expedited: true} ).then(() => {
                                        cancel();
                                    })
                            }}>{globals.strings.towns.expedite}</button>
                            <Tooltip>{ globals.strings.towns.expedite_help }</Tooltip>
                        </div>

                    </div>}
                </div>
            </> }
        </>
    )
};

const HordesEventTownPresetEditor = ( {uuid, town, update}: {
    uuid: string,
    town: string|null,
    update: (TownPresetData) => void,
} ) => {

    const globals = useContext(Globals)

    let wrapper = useRef<HTMLDivElement>();
    useLayoutEffect( () => {
        Components.vitalize( wrapper.current )
        const listener = event => {
            update(event.detail.ready ? {
                header: event.detail.options.head,
                rules: event.detail.options.rules
            } : null)
        }

        const w = wrapper.current;
        w.addEventListener('rules-changed', listener);
        return () => w.removeEventListener('rules-changed', listener)
    } );

    const [ preset, setPreset ] = useState<TownPresetData>(null);

    useEffect(() => {
        if (town !== null) {
            globals.api.getTown(uuid, town).then(v => setPreset(v));
            return () => setPreset(null);
        }
    }, [town])

    return (
            <div ref={wrapper}>
                { town === null && <hordes-town-creator data-event-mode="1" data-elevation="3"/> }
                { town !== null && preset === null && <div className="loading"/> }
                { town !== null && preset !== null && <hordes-town-creator data-event-mode="1" data-elevation="3" data-preset-head={JSON.stringify( preset.header )} data-preset-rules={JSON.stringify( preset.rules )}/> }
            </div>
        )
}