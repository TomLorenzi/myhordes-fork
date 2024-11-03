import ServiceModule from "./common";
import Console from "../debug";
import {Fetch} from "../fetch";
import {VaultEntry, VaultRequest} from "../typedef/vault_td";
import {clear, createStore, delMany, getMany, keys, setMany} from "idb-keyval";

type DataRequest = {
    storage: string,
    ids: number[],
    origin: MessagePort,
}

type DataResponse = {
    v: string,
    l: string,
    data: VaultEntry[],
}

export default class VaultServiceModule extends ServiceModule {

    protected fetch: Fetch = null;

    protected ready = false;
    protected cleared = false;
    protected migrations = 0;

    protected schedule: number = null;
    protected fetching = false;

    protected requests: DataRequest[] = [];

    private readonly store = null;
    protected qv:string = null;
    protected ql:string = null;


    constructor(p, private readonly scope:Window|SharedWorkerGlobalScope, baseUrl: string|boolean) {
        super(p);

        this.fetch = baseUrl === false ? new Fetch() : new Fetch(`${baseUrl}/rest/v1/game/data`, false);

        this.store = createStore('myhordes', 'vault')
        this.ready = true
        this.queueProcess();
    }

    queueProcess = (n: number = 15) => {
        if (this.fetching || !this.ready) return;
        this.scope.clearTimeout(this.schedule);
        this.schedule = this.scope.setTimeout( () => this.processRequests(), n );
    }

    processRequests = () => {
        const raw_requests = [...this.requests];

        if (raw_requests.length === 0) return;
        this.fetching = true;
        this.requests = [];

        Promise
            .all( raw_requests.map( r => this.fetchFromStorage(r) ) )
            .then( proc => {
                const requests = proc.filter(s => !!s);

                if (requests.length === 0) {
                    this.fetching = false;
                    this.queueProcess();
                    return;
                }

                const requestTypes = new Set<string>( requests.map( r => r.storage ).reduce( (c, storage) => [...c,storage], [] ) );

                Promise.all([...requestTypes].map( t => {
                    const typeRequests = requests.filter(r => r.storage === t);
                    const ids = new Set<number>( typeRequests
                        .map( r => r.ids )
                        .reduce( (c, list) => [...c,...list], [] )
                    );

                    return this.fetch.from(t, {ids: [...ids].join(',')}).request().get()
                        .then((r: DataResponse) => {
                            const ports = new Set<MessagePort>( typeRequests.map( r => r.origin ) );
                            [...ports].forEach(p => p.postMessage({
                                request: 'vault.updated', payload: {
                                    storage: t,
                                    data: r.data
                                }
                            }));

                            this.qv = r.v;
                            this.ql = r.l;

                            return this.writeToStorage( t, r )
                        }).catch(e => {
                            Console.error('Vault fetch failure', e);
                            this.requests = [...this.requests, ...typeRequests]
                        });
                } ))
                    .then( () => {
                        this.fetching = false;
                        this.queueProcess()
                    } )
                    .catch( () => {
                        this.fetching = false;
                    })
            } )
    }

    fetchFromStorage(request: DataRequest): Promise<DataRequest|null> {
        return new Promise((resolve) => {
            getMany(request.ids.map(i => `v::${request.storage}::${this.qv}::${this.ql}::${i}`), this.store).then(entries => {
                const found = entries.filter(v => typeof v === "object" && v?.id);
                if (found.length === 0) resolve(request);
                else {
                    const found_ids = found.map(f => f.id);
                    request.ids = request.ids.filter( i => !found_ids.includes(i) );

                    request.origin.postMessage({
                        request: 'vault.updated', payload: {
                            storage: request.storage,
                            data: found
                        }
                    });

                    resolve(request.ids.length > 0 ? request : null);
                }
            })
        })
    }

    clearStorage(zealous: boolean): Promise<void> {
        if (zealous) return clear(this.store);
        else return keys(this.store).then( keys => {
            return keys.map(key => key.toString().split('::')).filter(([v = null, storage = null, version = null, language = null, id = null]) => {
                if (v === null || storage === null || version === null || language === null || id === null) return true;
                if (version !== this.qv)
                    return true;
                return false
            });
        } ).then( d => {
            if (d.length === 0) return;
            Console.log('Clearing deprecated vault keys', d);
            return delMany(d, this.store)
        } );
    }

    writeToStorage(storage: string, response: DataResponse): Promise<void> {
        return setMany(response.data.map(s => [
            `v::${storage}::${response.v}::${response.l}::${s.id}`,
            s
        ]), this.store)
    }

    handle(event: MessageEvent): void {
        Console.warn('VaultServiceModule: Call without storage.', event);
        this.broadcast('broadcast.incoming', event.data.payload, event.data.except ?? []);
    }

    handleMessage(event: MessageEvent, message: string): void {
        if (message === 'version') {
            this.qv = event.data.payload.version;
            this.ql = event.data.payload.language;
            if (!this.cleared) {
                this.cleared = true;
                this.clearStorage(false).then(() => {});
            }
            return;
        }

        if (!['items'].includes(message)) {
            Console.warn('VaultServiceModule: Invalid scoped call.', message, event);
            this.respond(event, {status: 'no'});
        }
        else {

            const payload = event.data.payload as VaultRequest;
            if (payload.ids.length === 0) return;

            const ports = (!event.data.for) ? event.ports : this.ports([], event.data.for);

            const data = {
                storage: message,
                origin: ports[0],
                ids: payload.ids
            };

            this.fetchFromStorage( data ).then( v => {
                if (v) {
                    this.requests.push( {
                        storage: message,
                        origin: ports[0],
                        ids: payload.ids
                    } );

                    this.queueProcess();
                }
            } )

            this.respond(event, {status: 'ok'});
        }
    }

    event(message: string, data: any = null): void {
        Console.info('VaultServiceModule', message, data);
    }

}