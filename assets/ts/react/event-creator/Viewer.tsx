import {useContext, useEffect, useState} from "react";
import {Globals} from "./Wrapper";
import * as React from "react";
import {TranslationStrings} from "./strings";
import {EventCore, EventCreationAPI} from "./api";

export const HordesEventCreatorViewer = ( {creator,editor}: {
    creator: (()=>void)|null,
    editor: ((e:string)=>void)|null
}) => {
    const globals = useContext(Globals)

    const [events, setEvents] = useState<EventCore[]>(null)

    const refresh = (unset: boolean = true) => {
        if (unset) setEvents(null);
        globals.api.list().then(list => setEvents(list.events))
    };

    useEffect( () => {
        refresh(false);
        return () => {setEvents(null)}
    }, [] )



    return (
        <>
            { events === null && <div className="loading"></div> }
            { events !== null && !events?.length && <span className="small">{ globals.strings.list.no_events }</span> }
            { events?.map(event => <div key={event.uuid} className="note note-event-custom">
                <div className="row row-flex v-center">
                    <b className="cell grow-1">{ event.name ?? globals.strings.list.default_event }</b>
                    { editor && event.own && <>
                        <span className="cell padded-small shrink-0" title={globals.strings.common.edit}>
                            <img className="pointer" alt={globals.strings.common.edit} src={globals.strings.common.edit_icon} onClick={() => editor(event.uuid)} />
                        </span>
                        <span className="cell padded-small shrink-0" title={globals.strings.common.delete}>
                            <img className="pointer" alt={globals.strings.common.delete} src={globals.strings.common.delete_icon} onClick={() => {
                                if (confirm( globals.strings.list.delete_confirm )) {
                                    setEvents(events.filter(e => e.uuid !== event.uuid));
                                    globals.api.delete(event.uuid).catch( () => refresh(true) );
                                }
                            }} />
                        </span>
                    </> }
                </div>

                { event.description && <span className="small">{ event.description }</span> }
            </div>) }
            { creator && <div className="row">
                <div className="cell rw-4 ro-8 rw-md-6 ro-md-6 rw-sm-12 ro-sm-0">
                    <button onClick={()=>creator()}>{ globals.strings.common.create }</button>
                </div>
            </div> }
        </>
    )
};