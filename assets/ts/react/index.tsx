import * as React from "react";
import * as ReactDOM from "react-dom";

import {Global} from "../defaults";
import {MapCoreProps} from "./map/typedef";
import {MapWrapper} from "./map/Wrapper";

declare var $: Global;

export interface ReactData<Type=object> {
    data: Type,
    eventGateway: (event: string, data: object)=>void,
    eventRegistrar: (event: string, callback: ReactIOEventListener, remove:boolean)=>void
}

type ReactIOIncomingEvent = { event: string, data: object }
type ReactIOEventListener = (data:object)=>void;
interface ReactIOEventListenerList {
    [key:string]: ReactIOEventListener[];
}

export class ReactIO {
    private dom: HTMLElement;
    private listeners: ReactIOEventListenerList;
    private dom_listeners: ReactIOEventListenerList;
    //private react_listener: ((CustomEvent)=>void) | undefined;

    constructor(parent: HTMLElement) {
        this.clear();
        (this.dom = parent).addEventListener('_react', (e:CustomEvent) => {
            const detail = e.detail as ReactIOIncomingEvent;
            if (typeof this.listeners[detail.event] === "undefined") return;
            this.listeners[detail.event].forEach( e=>e(detail.data) );
        })
    }

    public clear() {
        Object.entries(this.dom_listeners ?? {}).forEach(([key,list]) =>
            list.forEach( e => this.dom.removeEventListener(`_react_${key}`, e) )
        );
        this.listeners = {};
        this.dom_listeners = {};
    }

    public getReactTrigger() {
        return (event: string, data: object) => {
            this.dom.dispatchEvent( new CustomEvent(`_react_${event}`, {detail: data}) );
        }
    }

    public getReactListenerGateway() {
        return (event: string, callback: ReactIOEventListener, remove: boolean) => {
            if (typeof this.listeners[event] === "undefined") this.listeners[event] = [];
            if (remove) this.listeners[event] = this.listeners[event].filter(f=>f!==callback)
            else this.listeners[event].push(callback);
            if (this.listeners[event].length === 0) delete this.listeners[event];
        }
    }

    public addClientEvent(event: string, callback: ReactIOEventListener) {
        const wrap_call = (e:CustomEvent) => callback(e.detail);
        if (typeof this.dom_listeners[event] === "undefined") this.dom_listeners[event] = [];
        this.dom_listeners[event].push(wrap_call);
        this.dom.addEventListener(`_react_${event}`, wrap_call)
        console.log(this);
    }
}

interface ReactIORegistry {
    [key:string]: ReactIO;
}

export default class Components {

    private idcount: number = 0;
    private io_registry: ReactIORegistry = {};

    private static vitalize(parent: HTMLElement) {
        let tooltips = parent.querySelectorAll('div.tooltip');
        for (let t = 0; t < tooltips.length; t++)
            $.html.handleTooltip( tooltips[t] as HTMLElement );
    }

    prune() {
        Object.entries(this.io_registry).forEach( ([key,]) => {
            if (!document.querySelector(`[data-react="${key}"]`))
                delete this.io_registry[key];
        } );
    }

    generate(parent: HTMLElement, reactClass: string, data: object = {}) {

        let eventIO;
        if ( typeof parent.dataset.react === "undefined" ) {
            eventIO = new ReactIO(parent);
            parent.dataset.react = ""+(++this.idcount);
            this.io_registry[parent.dataset.react] = eventIO;
        } else {
            eventIO = this.io_registry[parent.dataset.react];
            eventIO.clear();
        }

        switch (reactClass) {
            case 'map':
                ReactDOM.render(<MapWrapper data={data as MapCoreProps} eventGateway={eventIO.getReactTrigger()} eventRegistrar={eventIO.getReactListenerGateway()} />, parent, () => Components.vitalize( parent ));
                break;
            default:
                console.error('Invalid react class definition: "' + reactClass + "'.", data)
        }
    }

    degenerate( parent: HTMLElement ) {
        if (ReactDOM.unmountComponentAtNode( parent )) {
            if (parent.dataset.react) delete this.io_registry[parent.dataset.react];
            parent.removeAttribute('data-react');
        }
    }

    dispatchEvent(parent: HTMLElement | string, event: string, data: object) {
        if (typeof parent === "string") parent = document.getElementById(parent);
        if (!parent) return;

        if (!parent.hasAttribute('x-react-mount')) {
            console.error('Attempt to bind a React event to something that is not a valid React mount point:', parent);
            return;
        }

        parent.dispatchEvent(new CustomEvent('_react', { detail: {event, data} }));
    }

    attachEventListener( parent: HTMLElement | string, event: string, callback: (object)=>void ) {
        if (typeof parent === "string") parent = document.getElementById(parent);
        if (!parent) return;

        if (!parent.hasAttribute('x-react-mount')) {
            console.error('Attempt to listen to a React event on something that is not a valid React mount point:', parent);
            return;
        }

        if (!parent.hasAttribute('data-react')) {
            console.error('Attempt to listen to a React event on non-initialized react mount point:', parent);
            return;
        }

        this.io_registry[parent.dataset.react].addClientEvent(event,callback);
    }
}