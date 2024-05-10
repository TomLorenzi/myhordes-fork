import {Global} from "./defaults";
import {broadcast} from "./v2/init";

interface callbackTemplate<T extends payload> { (T, bool):void }

class callbackStack<T extends payload> {
    private always: Array<callbackTemplate<T>> = [];
    private once: Array<callbackTemplate<T>> = [];

    public empty(): boolean {
        return this.always.length === 0 && this.once.length === 0;
    }

    public push(callback: callbackTemplate<T>, run_always: boolean = false): void {
        if (run_always) this.always.push(callback);
        else this.once.push(callback);
    }

    public trigger(payload: T, success: boolean): void {
        const once_temp = this.once;
        this.once = [];
        this.always.forEach( (callback: callbackTemplate<T>) => callback(payload, success) );
        once_temp.forEach( (callback: callbackTemplate<T>) => callback(payload, success) );
    }
}

class networker<T extends payload> {
    public stack: callbackStack<T>;
    public endpoint: string;

    private timeout: number|null = null;
    private pending:  boolean = false;
    private canceled: boolean = false;

    private muted: boolean = false;
    private muted_waiting: boolean = false;

    private last_key: string = null;

    constructor(api: string, private TType: new (o: object) => T) {
        this.stack = new callbackStack<T>();
        this.endpoint = api;
    }

    public adjust_endpoint(endpoint: string): void {
        const is_pending = this.pending;
        if (is_pending) this.cancel();
        this.endpoint = endpoint;
        if (is_pending) this.execute();
    }

    public set_last_key(key: string|null): void {
        this.last_key = key;
    }

    public execute(): boolean {
        if (this.muted) {
            this.muted_waiting = true;
            return true;
        }
        if (this.pending || this.stack.empty()) return !this.stack.empty() && !(this.canceled = false);
        else $.ajax.background().soft_fail().easySend(this.endpoint,this.last_key ? {rk: this.last_key} : {},(data: object) => {
            if (!this.canceled) {
                this.last_key = data['response_key'] ?? this.last_key ?? null;
                this.stack.trigger(new this.TType(data['payload'] ?? data), true);
            }
            this.pending = this.canceled = false;
        }, {}, () => {
            if (!this.canceled)
                this.stack.trigger(new this.TType({} ), false);
            this.pending = this.canceled = false;
        }, false);
        return true;
    }

    public cancel(): boolean {
        if (!this.pending) return false;
        return this.canceled = true;
    }

    public mute(): void {
        this.muted_waiting = this.pending;
        this.cancel();
        this.muted = true;
    }

    public unmute(): void {
        if (this.muted) {
            this.muted = false;
            if (this.muted_waiting) {
                this.muted_waiting = false;
                this.execute();
            }
        }
    }

    public hasTimeout(): boolean {
        return this.timeout !== null;
    }

    public cancelTimeout(): void {
        if (this.hasTimeout()) window.clearTimeout(this.timeout);
        this.timeout = null;
    }

    public setTimeout(delay: number): void {
        this.cancelTimeout();
        if (delay <= 0) this.execute();
        else this.timeout = window.setTimeout(() => {
            this.execute();
        }, delay);
    }
}

declare var $: Global;
declare global { interface Window { } }

class payload { constructor(args: object) {} }

class PayloadPing extends payload {
    public readonly newMessages: number;
    public readonly connected: boolean;
    public readonly delay: number;
    public readonly authoritative: boolean;

    public constructor(args: object) {
        super(args);
        this.newMessages = args['new'] ?? 0;
        this.connected = !!args['connected'] ?? false;
        this.delay = Math.max(5000, args['connected'] ? args['connected'] : 60000 );
        this.authoritative = args['authoritative'] ?? true;
    }
}

class PayloadFetchHTMLStack {
    public readonly data: Array<[string,number,Element]> = [];

    public constructor(dom: string|null) {
        if (dom === null) return;
        const node = document.createElement('div');
        node.innerHTML = dom as string;
        const list = node.querySelectorAll('*[x-identifier][x-domain]');
        for (let i = 0; i < list.length; i++) {
            this.data.push([list[i].getAttribute('x-domain'), parseInt(list[i].getAttribute('x-identifier')), list[i]]);
            list[i].remove();
        }
    }
}

class PayloadFetch extends payload {
    public readonly connected: boolean;
    public readonly delay: number;

    public readonly index_list: PayloadFetchHTMLStack;
    public readonly focus_list: PayloadFetchHTMLStack;

    public constructor(args: object) {
        super(args);
        this.connected = !!args['connected'] ?? false;
        this.delay = Math.max(5000, args['connected'] ? args['connected'] : 60000 );

        this.index_list = new PayloadFetchHTMLStack(args['index'] ?? null);
        this.focus_list = new PayloadFetchHTMLStack(args['focus'] ?? null);
    }
}

export default class MessageAPI {

    private fetch_default_endpoint: string = null;
    private started: boolean = false;

    private nw_ping:  networker<PayloadPing> = null;
    private nw_fetch: networker<PayloadFetch> = null;

    private last_ping: PayloadPing = null;

    public constructor() {
        const html = ((document.getRootNode() as Document).firstElementChild as HTMLElement);
        html
            .addEventListener('mercureMessage', e => {
                if (e.detail?.message === 'domains.pm.new') {
                    if (!this.initialized()) return;

                    if (e.detail?.language && e.detail?.language !== html.dataset.language) return;

                    this.nw_ping.stack.trigger( {
                        ...this.last_ping,
                        newMessages: this.last_ping.newMessages + (e.detail?.number ?? 1),
                        authoritative: false
                    }, true )

                    this.nw_fetch.setTimeout(0);
                }
            });
        html.addEventListener('broadcastMessage', e => {
            if (e.detail?.message === 'domains.pm.ping')
                this.nw_ping.stack.trigger( {
                    ...e.detail.ping as PayloadPing,
                    authoritative: false
                }, true )
        })
    }

    public initialized(): boolean {
        return this.nw_ping !== null && this.nw_fetch !== null;
    }

    public registerPingEndpoint(endpoint: string): void {
        this.nw_ping = new networker<PayloadPing>(endpoint, PayloadPing);
        this.registerPingCallback((a: PayloadPing) => {
            this.last_ping = a;
            if (a.authoritative) broadcast('domains.pm.ping', {ping: {...a, authoritative: false}});
        }, true);
        //this.registerPingCallback((a: PayloadPing) => this.nw_ping.setTimeout(a.delay), true);
    }

    public registerPingCallback(callback: callbackTemplate<PayloadPing>, persistent: boolean = false): void {
        this.nw_ping.stack.push(callback, persistent);
    }

    public togglePing(enabled: boolean): void {
        if (enabled) this.nw_ping.unmute();
        else this.nw_ping.mute();
    }

    public registerFetchEndpoint(endpoint: string): void {
        this.nw_fetch = new networker<PayloadFetch>(this.fetch_default_endpoint = endpoint, PayloadFetch);
        //this.registerFetchCallback((a: PayloadFetch) => this.nw_fetch.setTimeout(a.delay), true);
    }

    public overrideFetchEndpoint(endpoint: string): void {
        this.nw_fetch.adjust_endpoint(endpoint);
    }

    public restoreFetchEndpoint(): void {
        this.nw_fetch.adjust_endpoint(this.fetch_default_endpoint);
    }

    public registerFetchCallback(callback: callbackTemplate<PayloadFetch>, persistent: boolean = false): void {
        this.nw_fetch.stack.push(callback, persistent);
    }

    public setFetchRK(key: string|null): void {
        this.nw_fetch.set_last_key(key);
    }

    public toggleFetch(enabled: boolean): void {
        if (enabled) this.nw_fetch.unmute();
        else this.nw_fetch.mute();
    }

    public rescheduleFetch(timeout: number) {
        this.nw_fetch.cancel();
        this.nw_fetch.setTimeout(timeout);
    }

    public execute(): boolean {
        if (!this.initialized()) return false;

        this.nw_ping.setTimeout(0);
        this.nw_fetch.setTimeout(0);
        return true;
    }

    public jumpstart(): boolean {
        return this.started ? true : (this.started = this.execute());
    }
}