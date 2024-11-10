import * as React from "react";
import { createRoot } from "react-dom/client";

import {Global} from "../../defaults";
import {useEffect, useRef, useState} from "react";
import {EventCore, EventCreationAPI} from "./api";
import {TranslationStrings} from "./strings";
import {HordesEventCreatorViewer} from "./Viewer";
import {HordesEventCreatorWizard} from "./Creator";

declare var $: Global;

export class HordesEventCreator {

    #_root = null;

    public mount(parent: HTMLElement, props: {creator: boolean, reviewer: boolean, admin: boolean}): any {
        if (!this.#_root) this.#_root = createRoot(parent);
        this.#_root.render( <EventCreatorWrapper {...props} /> );
    }

    public unmount() {
        if (this.#_root) {
            this.#_root.unmount();
            this.#_root = null;
        }
    }
}

type EventCreatorGlobals = {
    api: EventCreationAPI,
    strings: TranslationStrings,
    is_reviewer: boolean
    is_event_admin: boolean
}

export const Globals = React.createContext<EventCreatorGlobals>(null);

const EventCreatorWrapper = ( {creator, reviewer, admin}: {creator: boolean, reviewer: boolean, admin: boolean} ) => {

    const apiRef = useRef<EventCreationAPI>();
    const [globalLoadingStack, setGlobalLoadingStack] = useState<number>(0);
    const startLoad = (n: number = 1) => setGlobalLoadingStack( globalLoadingStack + n );
    const doneLoad = (n: number = 1) => setGlobalLoadingStack( globalLoadingStack - n );

    const [strings, setStrings] = useState<TranslationStrings>(null)

    const [showCreator, setShowCreator] = useState<{ uuid: string, event?: EventCore }>(null)

    useEffect( () => {
        apiRef.current = new EventCreationAPI();
        startLoad();
        apiRef.current.index().then( index => {
            setStrings(index.strings);
            doneLoad();
        } );
        return () => {
            setStrings(null);
        }
    }, [] )

    const load_complete = globalLoadingStack <= 0 && strings !== null;

    return (
        <Globals.Provider value={{ api: apiRef.current, strings, is_reviewer: reviewer, is_event_admin: admin }}>
            <div className="row">
                <div className="padded cell rw-12">
                    { !load_complete && <>
                        <div className="loading"></div>
                    </> }
                    { load_complete && <>
                        { !showCreator && <HordesEventCreatorViewer creator={ creator ? ()=>{
                            startLoad();
                            apiRef.current.create()
                                .then( r => {
                                    setShowCreator(r);
                                    doneLoad();
                                } )
                                .catch( () => doneLoad() )
                        } : null } editor={ creator ? e => setShowCreator({uuid: e.uuid, event: e}) : null } /> }
                        { showCreator && <HordesEventCreatorWizard uuid={ showCreator.uuid }
                                                                   proposed={ showCreator.event?.proposed ?? false }
                                                                   published={ showCreator.event?.published ?? false }
                                                                   started={ showCreator.event?.started && !showCreator.event?.ended }
                                                                   owning={ showCreator.event?.own }
                                                                   expedited={ showCreator.event?.expedited }
                                                                   cancel={ creator ? ()=>setShowCreator(null) : null } /> }
                    </> }
                </div>
            </div>
        </Globals.Provider>
    )
};