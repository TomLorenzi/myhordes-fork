import ServiceModule from "./common";
import Console from "../debug";
import {Fetch} from "../fetch";

type MercureAuthorization = {
    m: string,
    u: number|null,
    p: number,
    t: string|null
    r?: [number,string]
}

type MercureConnection = {
    auth: MercureAuthorization|null;
    eventSource: EventSource|null;
    pendingEventSource: EventSource|null;

    connectionFails: number;
    reconnectTimeout: null|any;
    retokenizeTimeout: null|any;

    reserves: number,

    config: {
        reconnect: boolean,
        retokenize: boolean,
    };
}

export default class MercureServiceModule extends ServiceModule{

    private readonly connections: {[key: string]: MercureConnection}

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
                retokenizeTimeout: null,
                reserves: 0,
                config: {
                    reconnect: true,
                    retokenize: true
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

    private upgrade(connection: string, auth: MercureAuthorization) {
        this.connections[connection].pendingEventSource?.close();
        clearTimeout( this.connections[connection].reconnectTimeout );
        clearTimeout( this.connections[connection].retokenizeTimeout );
        this.connections[connection].connectionFails = 0;
        this.connections[connection].auth = auth;
        this.connect(connection);
    }

    private replace(connection: string, auth: MercureAuthorization) {
        this.connections[connection].eventSource?.close();
        this.connections[connection].pendingEventSource?.close();
        clearTimeout( this.connections[connection].reconnectTimeout );
        clearTimeout( this.connections[connection].retokenizeTimeout );
        this.connections[connection].connectionFails = 0;
        this.broadcastState(connection);
        this.connections[connection].auth = auth;
        this.connect(connection);
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

                if (auth.m !== this.connections[connection].auth?.m || auth.u !== this.connections[connection].auth?.u)
                    this.replace(connection, auth);
                else if ( auth.p > (this.connections[connection].auth?.p ?? -1) ) {
                    this.upgrade(connection, auth);
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

    private handleTokenRenewCallback(connection: string, try_in: number = null): void {
        const retokenize_in = try_in ?? Math.max(0, (this.connections[connection].auth.r[0] * 1000) - Date.now());
        const retokenize_url = this.connections[connection].auth.r[1];

        clearTimeout(this.connections[connection].retokenizeTimeout);

        this.connections[connection].retokenizeTimeout = setTimeout(() => {
            Console.debug(`Attempting to renew token for ${connection} from ${retokenize_url}.`);
            (new Fetch(retokenize_url, false)).fromEndpoint().throwResponseOnError().request().get()
                .then((r: {token: MercureAuthorization}) => {
                    Console.debug(`Renewed token for ${connection}.`, r);
                    this.upgrade(connection, r.token)
                }).catch(r => {
                    if (r.status !== 406) {
                        Console.error(`Renewed token for ${connection} failed.`, r.status);
                        this.handleTokenRenewCallback(connection, 60000);
                    } else Console.error(`Server indicated that it will not renew ${connection}. Ceasing to retry.`, r.status);
                })
        }, retokenize_in);
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
            if (this.connections[connection].config.retokenize && this.connections[connection].auth.r)
                this.handleTokenRenewCallback( connection );
        }, {once: true});
        this.connections[connection].pendingEventSource?.addEventListener('error', errorHandler, {once: true});
        this.broadcastState(connection);
    }

    event(message: string, data: any = null): void {
        Console.debug('MercureServiceModule', message, data);

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