import ServiceModule from "./common";
import Console from "../debug";

type MercureAuthorization = {
    m: string,
    u: number|null,
    p: number,
    t: string|null
}

type MercureConnection = {
    auth: MercureAuthorization|null;
    eventSource: EventSource|null;
    pendingEventSource: EventSource|null;

    connectionFails: number;
    reconnectTimeout: null|any;

    reserves: number,

    config: {
        reconnect: boolean
    };
}

export default class MercureServiceModule extends ServiceModule{

    private readonly connections: {[key: string]: MercureConnection}

    //private auth: MercureAuthorization|null;
    //private eventSource: EventSource|null;
    //private pendingEventSource: EventSource|null;

    //private connectionFails = 0;
    //private reconnectTimeout = null;

    constructor(p) {
        super(p);
        this.connections = {};
    }

    private initializeConnection(connection: string) {
        if (!this.connections.hasOwnProperty(connection))
            this.connections[connection] = {
                auth: null,
                eventSource: null,
                pendingEventSource: null,
                connectionFails: 0,
                reconnectTimeout: null,
                reserves: 0,
                config: {
                    reconnect: true
                }
            }
    }

    private static eventSourceState(e: EventSource|null): string {
        if (!e || e?.readyState === EventSource.CLOSED ) return 'closed';
        else if (e.readyState === EventSource.CONNECTING) return 'connecting';
        else if (e.readyState === EventSource.OPEN) return 'open';
    }

    private incoming(connection: string, data: object) {
        this.broadcast('mercure.incoming', {connection, data});
    }

    private swap(connection: string) {
        this.initializeConnection(connection);

        if (MercureServiceModule.eventSourceState(this.connections[connection].pendingEventSource) !== 'open')
            return;

        const old = this.connections[connection].eventSource;
        const update = this.connections[connection].pendingEventSource;

        this.connections[connection].pendingEventSource.addEventListener('message', e => this.incoming( connection, JSON.parse(e.data) ));
        this.connections[connection].pendingEventSource.addEventListener('error', () => {
            update.close();
            this.connections[connection].eventSource = null;
            this.broadcastState(connection);
            if (this.connections[connection].config.reconnect) this.connect(connection);
        }, {once: true});
        if (MercureServiceModule.eventSourceState( old ) !== 'closed') old.close();

        this.connections[connection].eventSource = update;
        this.connections[connection].pendingEventSource = null;
    }

    private renderState(connection: string): object {
        this.initializeConnection(connection);
        const e = MercureServiceModule.eventSourceState( this.connections[connection].eventSource );
        const p = MercureServiceModule.eventSourceState( this.connections[connection].pendingEventSource );

        let state = 'indeterminate';

        if (e === 'closed') {
            if (p === 'closed') state = 'closed';
            else state = 'connecting';
        }
        else if (e === 'open') {
            if (p === 'connecting') state = 'upgrading';
            else state = 'open';
        }

        return {
            connected: e === 'open',
            state,
            auth: !!this.connections[connection].auth?.t
        };
    }

    private broadcastState(connection: string) {
        this.broadcast('mercure.connection_state', {connection, state: this.renderState(connection)});
    }

    handle(event: MessageEvent): void {
        Console.warn('MercureServiceModule: Invalid unscoped call.');
    }

    handleMessage(event: MessageEvent, message: string): void {
        const connection = event.data.connection ?? 'default';
        switch (message) {
            case 'configure':
                this.initializeConnection(connection);
                this.connections[connection].config = {
                    ...this.connections[connection].config,
                    ...event.data.config
                }
                break;

            case 'alloc':
                this.initializeConnection(connection);
                this.connections[connection].reserves++;
                break;

            case 'dealloc':
                this.initializeConnection(connection);
                this.connections[connection].reserves--;
                if (this.connections[connection].reserves <= 0) {
                    this.connections[connection].reserves = 0;
                    this.connections[connection].eventSource?.close();
                    this.connections[connection].pendingEventSource?.close();
                    this.connections[connection].eventSource = null;
                    this.connections[connection].pendingEventSource = null;
                    this.broadcastState(connection);
                }
                break;

            case 'authorize':
                const auth = event.data.token as MercureAuthorization;

                this.initializeConnection(connection);

                if (auth.m !== this.connections[connection].auth?.m || auth.u !== this.connections[connection].auth?.u) {
                    this.connections[connection].eventSource?.close();
                    this.connections[connection].pendingEventSource?.close();
                    clearTimeout( this.connections[connection].reconnectTimeout );
                    this.connections[connection].connectionFails = 0;
                    this.broadcastState(connection);
                    this.connections[connection].auth = auth;
                    this.connect(connection);
                }
                else if ( auth.p > (this.connections[connection].auth?.p ?? -1) ) {
                    this.connections[connection].pendingEventSource?.close();
                    clearTimeout( this.connections[connection].reconnectTimeout );
                    this.connections[connection].connectionFails = 0;
                    this.connections[connection].auth = auth;
                    this.connect(connection);
                } else if (MercureServiceModule.eventSourceState(this.connections[connection].eventSource) === 'closed' && !this.connections[connection].reconnectTimeout)
                    this.connect(connection);
                else Console.debug('Keeping connection');
                break;
            case 'state':
                this.respond(event, this.renderState(connection));
                break;

        }
    }

    private factory(connection: string): EventSource|null {
        this.initializeConnection(connection);
        if (!this.connections[connection].auth?.m || !this.connections[connection].auth?.t || this.connections[connection].auth.m === '#') return null;

        const url = new URL(this.connections[connection].auth?.m);
        url.searchParams.append('authorization', this.connections[connection].auth.t);
        url.searchParams.append('topic', '*');
        return new EventSource(url, { withCredentials: false });
    }

    private connect(connection: string) {
        this.initializeConnection(connection);
        if (MercureServiceModule.eventSourceState(this.connections[connection].pendingEventSource) !== 'closed')
            return;

        const errorHandler = (e) => {
            Console.error(e);
            this.connections[connection].connectionFails++;
            let timeout = 60000;
            if (this.connections[connection].connectionFails <= 10)
                timeout = 1000;
            else if (this.connections[connection].connectionFails <= 20)
                timeout = 5000;
            this.connections[connection].reconnectTimeout = setTimeout(() => {
                this.connections[connection].reconnectTimeout = null;
                this.connect(connection)
            }, timeout);
            this.broadcastState(connection);
        }

        this.connections[connection].pendingEventSource = this.factory(connection);
        this.connections[connection].pendingEventSource?.addEventListener('open', () => {
            this.connections[connection].pendingEventSource.removeEventListener('error', errorHandler);
            this.swap(connection);
            this.broadcastState(connection);
        }, {once: true});
        this.connections[connection].pendingEventSource?.addEventListener('error', errorHandler, {once: true});
        this.broadcastState(connection);
    }

    event(message: string, data: any = null): void {
        Console.info('MercureServiceModule', message, data);

        switch (message) {
            case 'activate': case 'install':
                Object.keys(this.connections).forEach(connection => {
                    if (MercureServiceModule.eventSourceState(this.connections[connection].eventSource) === 'closed' && !this.connections[connection].reconnectTimeout)
                        this.connect(connection);
                })

            break;
        }
    }

}