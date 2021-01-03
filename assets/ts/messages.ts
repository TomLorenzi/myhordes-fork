import {Global} from "./defaults";

interface callbackTemplate<T extends payload> { (T, bool):void }

class callbackStack<T extends payload> {
    private always: Array<callbackTemplate<T>> = [];
    private once: Array<callbackTemplate<T>> = [];

    public push(callback: callbackTemplate<T>, run_always: boolean = false): void {
        if (run_always) this.always.push(callback);
        else this.once.push(callback);
    }

    public trigger(payload: T, success: boolean): void {
        this.always.forEach( (callback: callbackTemplate<T>) => callback(payload, success) );
        this.once.forEach( (callback: callbackTemplate<T>) => callback(payload, success) );
        this.once = [];
    }
}

class networker<T extends payload> {
    public stack: callbackStack<T>;
    public endpoint: string;

    private pending:  boolean = false;
    private canceled: boolean = false;

    constructor(api: string, private TType: new (o: object) => T) {
        this.stack = new callbackStack<T>();
        this.endpoint = api;
    }

    public execute(): boolean {
        if (this.pending) return !(this.canceled = false);
        else $.ajax.background().easySend(this.endpoint,{},(data: object) => {
            if (!this.canceled)
                this.stack.trigger(new this.TType( data ), true);
            this.pending = false;
        }, {}, () => {
            if (!this.canceled)
                this.stack.trigger(new this.TType({} ), false);
            this.pending = false;
        }, false);
        return true;
    }

    public cancel(): boolean {
        if (!this.pending) return false;
        return this.canceled = true;
    }
}

declare var $: Global;
declare global { interface Window { } }

class AjaxMessageEndpoints {

    public ping: string = null;

    public configured(): boolean {
        return this.ping !== null;
    }
}

class payload { constructor(args: object) {} }

class PayloadPing extends payload {
    public readonly newMessages: number;
    public readonly connected: boolean;
    public readonly delay: number;

    public constructor(args: object) {
        super(args);
        this.newMessages = args['new'] ?? 0;
        this.connected = !!args['connected'] ?? false;
        this.delay = Math.max(5000, args['connected'] ? args['connected'] : 60000 );
    }
}

export default class MessageAPI {

    private nw_ping: networker<PayloadPing> = null;
    private ping_timeout = null;

    public initialized(): boolean {
        return this.nw_ping !== null;
    }

    public registerPingEndpoint(endpoint: string) {
        this.nw_ping = new networker<PayloadPing>(endpoint, PayloadPing);
        this.registerPingCallback((a: PayloadPing) => this.ping_timeout = window.setTimeout(() => {
            this.nw_ping.execute();
        }, a.delay), true);
    }

    public registerPingCallback(callback: callbackTemplate<PayloadPing>, persistent: boolean = false): void {
        this.nw_ping.stack.push(callback, persistent);
    }

    public execute(): boolean {
        if (!this.initialized()) return false;

        if (this.ping_timeout) {
            window.clearTimeout(this.ping_timeout);
            this.ping_timeout = null;
        }

        this.nw_ping.execute();
        return true;
    }
}