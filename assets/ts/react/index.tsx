import {Global} from "../defaults";
import {createRoot, Root} from "react-dom/client";
import * as React from "react";
import {HordesBuildingPageWrapper} from "./buildings/BuildingPage";
import HTML from "../html";
import {ErrorBoundary} from "react-error-boundary";
import {Simulate} from "react-dom/test-utils";
import error = Simulate.error;
import {HTMLAttributes, MutableRefObject, ReactHTML} from "react";

declare var $: Global;

type ReactMapBootstrapData = {
    displayType: string,
    className: string,
    etag: number,
    endpoint: string,
    fx: boolean,
}

export interface ReactData {
    data: ReactMapBootstrapData,
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

    public clearClientEvents(event: string) {
        (this.dom_listeners[event]??[]).forEach( e => this.dom.removeEventListener(`_react_${event}`, e) );
        delete this.listeners[event];
    }

    public addClientEvent(event: string, callback: ReactIOEventListener) {
        const wrap_call = (e:CustomEvent) => callback(e.detail);
        if (typeof this.dom_listeners[event] === "undefined") this.dom_listeners[event] = [];
        this.dom_listeners[event].push(wrap_call);
        this.dom.addEventListener(`_react_${event}`, wrap_call)
    }
}

interface ReactIORegistry {
    [key:string]: ReactIO;
}

export default class Components {

    private idcount: number = 0;
    private io_registry: ReactIORegistry = {};

    public static vitalize(parent: HTMLElement) {
        $.ajax.load_dynamic_modules( parent );
    }

    prune() {
        Object.entries(this.io_registry).forEach( ([key,]) => {
            if (!document.querySelector(`[data-react="${key}"]`))
                delete this.io_registry[key];
        } );
    }

    kickstart(parent: HTMLElement, data: object = {}) {
        let eventIO;
        if ( typeof parent.dataset.react === "undefined" ) {
            eventIO = new ReactIO(parent);
            parent.dataset.react = ""+(++this.idcount);
            this.io_registry[parent.dataset.react] = eventIO;
        } else {
            eventIO = this.io_registry[parent.dataset.react];
            eventIO.clear();
        }

        return {
            data,
            eventGateway: eventIO.getReactTrigger(),
            eventRegistrar: eventIO.getReactListenerGateway()
        }
    }

    degenerate( parent: HTMLElement ) {
        if (parent.dataset.react) delete this.io_registry[parent.dataset.react];
        parent.removeAttribute('data-react');
    }

    dispatchEvent(parent: HTMLElement | string, event: string, data: object) {
        if (typeof parent === "string") parent = document.getElementById(parent);
        if (!parent) return;

        const deferrable = () => {
            if (!(parent as HTMLElement).dataset.reactMount) {
                console.error('Attempt to dispatch a React event to something that is not a valid React mount point:', parent);
                return;
            }

            (parent as HTMLElement).dispatchEvent(new CustomEvent('_react', { detail: {event, data} }));
        }

        if ((parent as HTMLElement).matches(':not(:defined)'))
            customElements.whenDefined(parent.localName).then(deferrable);
        else deferrable();
    }

    clearEventListeners( parent: HTMLElement | string, event: string ) {
        if (typeof parent === "string") parent = document.getElementById(parent);
        if (!parent) return;

        const deferrable = () => {
            if (!(parent as HTMLElement).dataset.reactMount) {
                console.error('Attempt to clear a React event on something that is not a valid React mount point:', parent);
                return;
            }

            if (!(parent as HTMLElement).hasAttribute('data-react')) {
                console.error('Attempt to clear a React event on non-initialized react mount point:', parent);
                return;
            }

            this.io_registry[(parent as HTMLElement).dataset.react].clearClientEvents(event);
        }

        if ((parent as HTMLElement).matches(':not(:defined)'))
            customElements.whenDefined(parent.localName).then(deferrable);
        else deferrable();
    }

    attachEventListener( parent: HTMLElement | string, event: string, callback: (object)=>void ) {
        if (typeof parent === "string") parent = document.getElementById(parent);
        if (!parent) return;

        const deferrable = () => {
            if (!(parent as HTMLElement).dataset.reactMount) {
                console.error('Attempt to listen to a React event on something that is not a valid React mount point:', parent);
                return;
            }

            if (!(parent as HTMLElement).hasAttribute('data-react')) {
                console.error('Attempt to listen to a React event on non-initialized react mount point:', parent);
                return;
            }

            this.io_registry[(parent as HTMLElement).dataset.react].addClientEvent(event,callback);
        }

        if ((parent as HTMLElement).matches(':not(:defined)'))
            customElements.whenDefined(parent.localName).then(deferrable);
        else deferrable();
    }
}

export interface ShimLoader {
    mount(HTMLElement,object): void,
    unmount(HTMLElement): void,
}

export abstract class Shim<ReactType extends ShimLoader> extends HTMLElement {

    private initialized: ReactType|null = null;
    private data: object = {}
    private do_mount = false;
    private lazy_observer: IntersectionObserver = null;

    protected allow_migration: boolean = false;

    protected abstract generateProps(): object|null;
    protected abstract generateInstance(): ReactType;
    protected static observedAttributeNames(): string[] { return []; };

    protected mountsLazily(): boolean { return false; }

    protected nestedObject(): ReactType|null {
        return this.initialized;
    }

    protected selfMount(data: object = {}): void {
        this.initialized?.mount(this, { ...this.data, ...data } );
    }

    protected selfUnmount(): void {
        this.initialized?.unmount(this)
    }

    private extractData() {
        const extracted = this.generateProps();
        this.data = extracted ?? {}
        return extracted !== null;
    }


    private initialize() {
        if (!this.do_mount || this.initialized || !this.isConnected) return;
        if (this.extractData()) {
            this.initialized = this.generateInstance();
            this.selfMount();
        }
    }

    connectedCallback() {
        if (!this.do_mount) return;
        this.initialize();
        if (this.extractData()) this.selfMount();
    }

    disconnectedCallback() {
        if (!this.allow_migration) {
            this.selfUnmount();
            this.initialized = null;
            this.data = {};
        }
    }

    static get observedAttributes() { return this.observedAttributeNames(); }

    attributeChangedCallback(name, oldValue, newValue) {
        if (oldValue === newValue) return;
        if (this.extractData()) this.selfMount();
    }

    public constructor() {
        super();
        this.addEventListener('x-react-degenerate', () => {
            this.selfUnmount()
        });
        if (this.mountsLazily()) {
            const minHeightBefore = this.style.minHeight;
            this.style.minHeight = '1px';
            this.lazy_observer = new IntersectionObserver( (v) => {
                if (v[0].isIntersecting) {
                    this.do_mount = true;
                    this.lazy_observer.unobserve(this);
                    this.style.minHeight = minHeightBefore;
                    this.initialize();
                }
            }, {
                root: null,
                rootMargin: "0px",
            } );
            this.lazy_observer.observe(this);
        } else {
            this.do_mount = true;
            this.initialize();
        }
    }
}

export abstract class PersistentShim<ReactType extends ShimLoader> extends Shim<ReactType> {
    protected allow_migration: boolean = true;

    public constructor() {
        super();
        if (!this.dataset.reactMount && !this.id) {
            const id = `auto-${window.crypto.randomUUID()}`;
            this.dataset.reactMount = id;
            this.setAttribute('id',id);
        }
        else if (!this.id) this.setAttribute('id',this.dataset.reactMount);
        else if (!this.dataset.reactMount) this.dataset.reactMount = this.id;
    }
}

export abstract class BaseMounter<PropDef extends object> {

    protected parent: HTMLElement = null;
    protected root: Root = null;
    protected props: PropDef = null;

    protected renderError(error: string) {
        return <div style={{background: '#650000', padding: '3px', fontSize: '10px'}}>
            <b>⚠ Critical Failure:</b> A component on this page has crashed.
            Details have been sent to the browser console.
            <code style={{fontSize: '8px', display: "block", background: "rgba(0,0,0,0.1)", padding: '2px', margin: "2px 0"}}>{error}</code>
            <code style={{fontSize: '8px', display: "block", background: "rgba(0,0,0,0.1)", padding: '2px', margin: "2px 0"}}>{JSON.stringify(this.props)}</code>
            <code style={{fontSize: '8px', display: "block", background: "rgba(0,0,0,0.1)", padding: '2px', margin: "2px 0"}}>{window?.navigator?.userAgent ?? '[UA unavailable]'}</code>
            <em>Please report this error!</em>
        </div>
    }

    private errorRenderer(error: any ) {
        console.error('⚠ Critical Failure', this.props, error);

        if (typeof error === 'string') return this.renderError(error);
        else if (error === null || typeof error === 'undefined') return this.renderError(`[No further details.]`);
        else if (typeof error === 'object') return this.renderError(
            error?.message ?? error?.error ?? error?.text ?? `[error object: ${JSON.stringify(error)}]`,
        );
        else return this.renderError(`[error type: ${typeof error}]`);
    }

    protected renderWrapper(props: PropDef): React.ReactNode {
        return <ErrorBoundary fallbackRender={({error}) => this.errorRenderer(error)}>
            { this.render(this.props = props) }
        </ErrorBoundary>
    }

    protected abstract render(props: PropDef): React.ReactNode;

    protected createReactRoot(parent: HTMLElement) {
        return createRoot(this.parent = parent);
    }

    protected trashReactRoot() {
        if (this.root) {
            this.root.unmount();
            this.root = null;
            this.parent = null;
        }
    }

    public mount(parent: HTMLElement, props: PropDef): any {
        if (!this.root) this.root = this.createReactRoot(parent);
        this.root.render(this.renderWrapper(props));
    }

    public unmount() {
        this.trashReactRoot();
    }
}

export const classNames = (classes: string|{[p: string]: boolean}): string => {
    return typeof classes === "object"
        ? Object.entries(classes)
            .filter(([,v]) => !!v)
            .map(([k]) => k)
            .join(' ')
        : classes;
}

export const Tag = (props: React.DetailedHTMLProps<HTMLAttributes<HTMLElement>, HTMLElement> & {tagName?: string, classNames?: string|{[p: string]: boolean}, elementRef?: MutableRefObject<HTMLElement>}) => {
    const TagName = (props.tagName ?? 'div') as keyof JSX.IntrinsicElements;

    const htmlProps = {...props};
    delete htmlProps.classNames;
    delete htmlProps.tagName;
    delete htmlProps.elementRef;

    return <TagName ref={props.elementRef} {...htmlProps} {...(props.classNames ? {
        className: [classNames(props.classNames), props.className]
            .filter((s: string|null) => s?.length > 0)
            .join(' ')
        ,
    } : {})}>{props.children}</TagName>
}

export interface DialogExtraMountProps {
    callback: (a:any)=>void
}

export abstract class ReactDialogMounter<PropDef extends object> extends BaseMounter<PropDef & DialogExtraMountProps> {

    private auto_div = null;
    private activator = null;

    private callback = null;
    private click_handler = e => {
        if (this.callback) {
            (this.callback)();
            e.preventDefault();
        }
    }

    protected abstract findActivator(parent: HTMLElement, props: PropDef): HTMLElement|null;

    protected findActivatorAsync(parent: HTMLElement, props: PropDef): Promise<HTMLElement> {
        return new Promise<HTMLElement>((resolve,reject) => {
            const fetchActivator = () => {
                const elem = this.findActivator( parent, props );
                if (elem) resolve(elem);
                else reject();
            }

            if (document.readyState !== "complete")
                document.addEventListener('DOMContentLoaded', fetchActivator)
            else fetchActivator();
        })
    }

    public mount(parent: HTMLElement, props: PropDef): any {
        if (!this.auto_div) {

            this.findActivatorAsync( parent, props )
                .then( activator => {
                    parent.insertAdjacentElement('beforeend', this.auto_div = document.createElement('div'));
                    this.auto_div.style.position = 'absolute';
                    this.auto_div.style.cursor = 'default';
                    this.auto_div.style.textAlign = 'left';
                    (this.activator = activator).addEventListener('click', this.click_handler);

                    if (!this.root) this.root = this.createReactRoot(this.auto_div);

                    this.root.render( this.renderWrapper( {...props, callback: c => this.callback = c} ) );
                })
                .catch(() => console.warn( 'Unable to fetch the activator for a dialog mount.', parent, props ));
        } else {
            if (!this.root) this.root = this.createReactRoot(this.auto_div);

            this.root.render(this.renderWrapper({...props, callback: c => this.callback = c}));
        }

    }

    public unmount() {
        super.unmount();

        if (this.auto_div) {
            this.activator.removeEventListener('click', this.click_handler);
            this.auto_div.remove();
            this.auto_div = null;
        }
    }
}