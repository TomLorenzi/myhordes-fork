import ServiceModule from "./common";
import Console from "../debug";

type MercureAuthorization = {
    m: string,
    u: number|null,
    p: number,
    t: string|null
}

export default class MercureServiceModule extends ServiceModule{

    private auth: MercureAuthorization|null;
    private eventSource: EventSource|null;
    private pendingEventSource: EventSource|null;

    private connectionFails = 0;
    private reconnectTimeout = null;

    constructor(p) { super(p); }

    private static eventSourceState(e: EventSource|null): string {
        if (!e || e?.readyState === EventSource.CLOSED ) return 'closed';
        else if (e.readyState === EventSource.CONNECTING) return 'connecting';
        else if (e.readyState === EventSource.OPEN) return 'open';
    }

    private incoming(data: object) {
        this.broadcast('mercure.incoming', data);
    }

    private swap() {
        if (MercureServiceModule.eventSourceState(this.pendingEventSource) !== 'open')
            return;

        const old = this.eventSource;
        const update = this.pendingEventSource;

        this.pendingEventSource.addEventListener('message', e => this.incoming( JSON.parse(e.data) ));
        this.pendingEventSource.addEventListener('error', () => {
            update.close();
            this.eventSource = null;
            this.broadcastState();
            this.connect();
        }, {once: true});
        if (MercureServiceModule.eventSourceState( old ) !== 'closed') old.close();

        this.eventSource = update;
        this.pendingEventSource = null;
    }

    private renderState(): object {
        const e = MercureServiceModule.eventSourceState( this.eventSource );
        const p = MercureServiceModule.eventSourceState( this.pendingEventSource );

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
            auth: !!this.auth?.t
        };
    }

    private broadcastState() {
        this.broadcast('mercure.connection_state', this.renderState());
    }

    handle(event: MessageEvent): void {
        Console.warn('MercureServiceModule: Invalid unscoped call.');
    }

    handleMessage(event: MessageEvent, message: string): void {
        switch (message) {
            case 'authorize':
                const auth = event.data.token as MercureAuthorization;

                if (auth.m !== this.auth?.m || auth.u !== this.auth?.u) {
                    this.eventSource?.close();
                    this.pendingEventSource?.close();
                    clearTimeout( this.reconnectTimeout );
                    this.connectionFails = 0;
                    this.broadcastState();
                    this.auth = auth;
                    this.connect();
                }
                else if ( auth.p > (this.auth?.p ?? -1) ) {
                    this.pendingEventSource?.close();
                    clearTimeout( this.reconnectTimeout );
                    this.connectionFails = 0;
                    this.auth = auth;
                    this.connect();
                } else if (MercureServiceModule.eventSourceState(this.eventSource) === 'closed' && !this.reconnectTimeout)
                    this.connect();
                else Console.debug('Keeping connection');
                break;
            case 'state':
                this.respond(event, this.renderState());
                break;

        }
    }

    private factory(): EventSource|null {
        if (!this.auth?.m || !this.auth?.t || this.auth.m === '#') return null;

        const url = new URL(this.auth?.m);
        url.searchParams.append('authorization', this.auth.t);
        url.searchParams.append('topic', '*');
        return new EventSource(url, { withCredentials: false });
    }

    private connect() {
        if (MercureServiceModule.eventSourceState(this.pendingEventSource) !== 'closed')
            return;

        const errorHandler = (e) => {
            Console.error(e);
            this.connectionFails++;
            let timeout = 60000;
            if (this.connectionFails <= 10)
                timeout = 1000;
            else if (this.connectionFails <= 20)
                timeout = 5000;
            this.reconnectTimeout = setTimeout(() => {
                this.reconnectTimeout = null;
                this.connect()
            }, timeout);
            this.broadcastState();
        }

        this.pendingEventSource = this.factory();
        this.pendingEventSource?.addEventListener('open', () => {
            this.pendingEventSource.removeEventListener('error', errorHandler);
            this.swap();
            this.broadcastState();
        }, {once: true});
        this.pendingEventSource?.addEventListener('error', errorHandler, {once: true});
        this.broadcastState();
    }

    event(message: string, data: any = null): void {
        Console.info('MercureServiceModule', message, data);

        switch (message) {
            case 'activate': case 'install':
                if (MercureServiceModule.eventSourceState(this.eventSource) === 'closed' && !this.reconnectTimeout)
                    this.connect();
            break;
        }
    }

}