import {useContext, useEffect, useLayoutEffect, useRef, useState} from "react";
import {Globals} from "../Wrapper";
import * as React from "react";
import Components from "../../index";
import {TownPreset} from "../api";
import {Flag} from "../Common";

declare global {
    namespace JSX {
        interface IntrinsicElements {
            ['hordes-town-creator']: any;
        }
    }
}

export const HordesEventCreatorModuleTownPreset = ( {uuid}: {
    uuid: string,
} ) => {
    const globals = useContext(Globals)

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
        console.log(townEditorPayload);
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

    return (
        <>
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
                        <button onClick={()=>send_payload()} disabled={!townEditorPayload}>{ globals.strings.common.save }</button>
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
                        <button onClick={()=>send_payload()} disabled={!townEditorPayload}>{ globals.strings.common.save }</button>
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
                                { town.name && <i>{ town.name }</i> }
                                { !town.name && <span className="small">[ { globals.strings.towns.default_town } ]</span> }
                                <br /><span className="small">{ town['type'] }</span>

                            </div>
                            <div className="padded cell rw-2 right">
                                <span className="cell padded-small shrink-0" title={globals.strings.common.edit}>
                                    <img className="pointer" alt={globals.strings.common.edit} src={globals.strings.common.edit_icon} />
                                </span>
                                <span className="cell padded-small shrink-0" title={globals.strings.common.delete}>
                                    <img className="pointer" alt={globals.strings.common.delete} src={globals.strings.common.delete_icon} onClick={() => {
                                        if (confirm( globals.strings.towns.delete_confirm )) {
                                            setTownList( townList.filter(e => e.uuid !== town.uuid) )
                                            globals.api.deleteTown(uuid, town.uuid).catch( () => refresh() );
                                        }
                                    }} />
                                </span>
                            </div>
                        </div>
                    ) ) }

                </div> }
                <div className="row">
                    <div className="cell rw-4 ro-8 rw-md-6 ro-md-6 rw-sm-12 ro-sm-0">
                        <button onClick={()=>setActiveTownEditor(true)}>{ globals.strings.towns.add }</button>
                    </div>
                </div>
            </> }
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
            update(event.detail.ready ? {
                header: event.detail.options.head,
                rules: event.detail.options.rules
            } : null)
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