import {useContext, useEffect, useLayoutEffect, useRef, useState} from "react";
import {Globals} from "./Wrapper";
import * as React from "react";
import {TranslationStrings} from "./strings";
import {EventCore, EventCreationAPI} from "./api";

export const HordesEventCreatorViewer = ( {creator,editor}: {
    creator: (()=>void)|null,
    editor: ((e:EventCore)=>void)|null
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
            { events?.map(event => <React.Fragment key={event.uuid}>
                    <HordesEventCreatorEventListing event={event}
                                                    editEvent={(editor && event.own) ? ()=>editor(event) : null}
                                                    deleteEvent={(editor && event.own) ? ()=>{
                                                        setEvents(events.filter(e => e.uuid !== event.uuid));
                                                        globals.api.delete(event.uuid).catch( () => refresh(true) );
                                                    } : null}/>
                </React.Fragment>
            ) }
            { creator && <div className="row">
                <div className="cell rw-4 ro-8 rw-md-6 ro-md-6 rw-sm-12 ro-sm-0">
                    <button onClick={()=>creator()}>{ globals.strings.common.create }</button>
                </div>
            </div> }
        </>
    )
};

const HordesEventCreatorEventListing = ( {event,editEvent,deleteEvent}: {
    event: EventCore
    editEvent: (()=>void)|null
    deleteEvent: (()=>void)|null
}) => {
    const globals = useContext(Globals)

    const description = useRef<HTMLDivElement>();
    const [showDetails, setShowDetails] = useState<boolean>(false);

    useLayoutEffect(() => {
        if (!description.current) return;

        const h = description.current.clientHeight;
        description.current.animate([
            { maxHeight: 0, overflow: 'hidden' },
            { maxHeight: `${h}px`, overflow: 'hidden' }
        ], {duration: 250, easing: "ease-in-out"})
    }, [showDetails]);

    return (
        <div className="note note-event-custom">
            <div className="row row-flex v-center">
                <b className="cell grow-1">{ event.name ?? globals.strings.list.default_event }</b>
                { editEvent && <>
                    <span className="cell padded-small shrink-0" title={globals.strings.common.edit}>
                        <img className="pointer" alt={globals.strings.common.edit} src={globals.strings.common.edit_icon} onClick={() => editEvent()} />
                    </span>
                </> }
                { deleteEvent && <>
                    <span className="cell padded-small shrink-0" title={globals.strings.common.delete}>
                        <img className="pointer" alt={globals.strings.common.delete} src={globals.strings.common.delete_icon} onClick={() => {
                            if (confirm( globals.strings.list.delete_confirm )) deleteEvent();
                        }} />
                    </span>
                </> }
            </div>

            { event.short && <div className="small">
                { event.short }{' '}
                { event.description && !showDetails && <a href="#" onClick={e=> {
                    setShowDetails(true);
                    e.preventDefault();
                }}>{ globals.strings.list.more_info }</a> }
            </div> }
            { event.description && <div className={`small ${showDetails ? '' : 'hidden'}`} ref={description}>{ event.description }</div> }
        </div>
    )
};