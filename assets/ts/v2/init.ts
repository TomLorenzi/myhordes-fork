import Console from "./debug";

declare global {
    interface Window {
        transferTable: Array<any>;

        mhWorkerQueue: Array<any>,
        mhWorkerSetup: boolean|null,
        mhWorkerSetupShim: boolean|null,
        mhWorker: SharedWorker,
        mhWorkerShim: boolean|null,
        mhWorkerIdList: Array<string>
    }
}

window.transferTable = window.transferTable || [];
window.mhWorkerIdList = window.mhWorkerIdList || [];

export function serviceWorkerCall(request: string, args: object = {}): Promise<any> {
    return new Promise<any>((resolve,reject) => {
        navigator.serviceWorker.ready.then(worker => {
            if (!worker) reject(null);
            window.transferTable.push(resolve);
            worker.active.postMessage({...args, request, to: `${window.transferTable.length - 1}`});
        })
    })
}

export function sharedWorkerCall(request: string, args: object = {}): Promise<any> {
    return new Promise<any>((resolve,reject) => {
        if (window.mhWorkerSetup && !window.mhWorker && window.mhWorkerSetupShim && !window.mhWorkerShim) reject(null);
        window.transferTable.push(resolve);
        const payload = {...args, request, for: window.mhWorkerIdList, to: `${window.transferTable.length - 1}`};
        if (window.mhWorkerSetup && window.mhWorker)
            window.mhWorker.port.postMessage( payload )
        else if (window.mhWorkerSetupShim && window.mhWorkerShim)
            document.getElementById('_shared_shim_incoming')?.dispatchEvent(new CustomEvent('__message', {detail: payload}));
        else {
            if (!window.mhWorkerQueue) window.mhWorkerQueue = [];
            window.mhWorkerQueue.push(payload);
        }
    })
}

export function sharedWorkerMessageHandler(connection: string = null, message: string = null, callback: (any)=>void ) {
    return (e) => {
        if (
            e.detail?.data &&
            (connection === null || e.detail.connection === connection) &&
            (message === null || e.detail.data.message === message)
        ) callback( e.detail.data );
    }
}

export function broadcast(message: string, args: object = {}): void {
    window.mhWorker?.port.postMessage( {payload: {...args, message}, request: 'broadcast', except: window.mhWorkerIdList} )
}

export function html(): HTMLElement {
    return ((document.getRootNode() as Document).firstElementChild as HTMLElement);
}

async function initLive() {
    require('string.prototype.matchall').shim();
    await initServiceWorker();
    const shared = await initSharedWorker();

    if (!shared) {
        console.warn('Could not enable shared worker. Attempting fallback to shim');
        window.mhWorkerShim = await initSharedWorkerShim();
    }
}

async function initOnceLoaded() {

}

async function initServiceWorker(): Promise<boolean> {
    window.transferTable = [];

    if (!('serviceWorker' in navigator)) {
        Console.error('No service worker support detected.')
        return false;
    }

    const serviceLoaderFile = html()?.dataset?.serviceWorker as string;
    if (!serviceLoaderFile) {
        Console.warn('Service worker file not defined.')
        return false;
    }

    (await navigator.serviceWorker.getRegistrations()).forEach(registration => {
        if (registration.active.scriptURL !== serviceLoaderFile)
            Console.info(`Found outdated service worker '${registration.active.scriptURL}'.`);
    });

    await navigator.serviceWorker.register( serviceLoaderFile, {
        scope: "/"
    });

    const worker = await navigator.serviceWorker.ready;
    navigator.serviceWorker.addEventListener('message', e => {
        Console.debug('From service worker', e.data);
        switch (e.data.request) {
            case 'response':
                const id = parseInt( e.data.to as string );
                const callback = window.transferTable[id] ?? null;
                if (callback) {
                    callback(JSON.parse(e.data.payload));
                    window.transferTable[id] = null;
                } else Console.warn(`Did not find callback "${id}" in callback table:`, window.transferTable)
                break;
        }
    })

    worker.active.postMessage({request: 'ping'});
}

async function initSharedWorker(): Promise<boolean> {
    window.mhWorkerSetup = true;

    const sharedLoaderFile = html()?.dataset?.sharedWorker as string;
    if (!sharedLoaderFile) {
        Console.warn('Shared worker file not defined.')
        return false;
    }

    if (typeof SharedWorker !== 'function') {
        Console.warn('Shared worker not supported.')
        return false;
    }
    const mhWorker = new SharedWorker(sharedLoaderFile);
    mhWorker.port.start();
    mhWorker.port.addEventListener('message', e => {
        handleSharedWorkerResponse(e.data);
    })
    window.mhWorker = mhWorker;
    window.addEventListener('beforeunload', () => {
        window.mhWorkerIdList.forEach(id => mhWorker.port.postMessage({request: 'worker.disconnect', id}));
        window.mhWorkerIdList = [];
    });
    mhWorker.port.postMessage({request: 'ping'});

    setupSharedWorker();

    return true;
}

async function initSharedWorkerShim(): Promise<boolean> {
    window.mhWorkerSetupShim = true;
    window.mhWorkerShim = true;

    const sharedLoaderFile = html()?.dataset?.sharedShim as string;
    if (!sharedLoaderFile) {
        Console.warn('Shared worker shim file not defined.')
        return false;
    }

    const response = await fetch( sharedLoaderFile );
    if (!response.ok) {
        Console.warn('Shared worker shim failed to load.')
        return false;
    }

    eval(await response.text());
    const port_send = document.getElementById('_shared_shim_incoming');
    const port = document.getElementById('_shared_shim_outgoing');
    if (!port_send || !port) {
        Console.warn('Shared worker shim failed to initialize.')
        return false;
    }

    port.addEventListener('__message', (e: CustomEvent) => {
        handleSharedWorkerResponse( e.detail, true, port_send );
    })
    port_send.dispatchEvent(new CustomEvent('__connect'));

    port_send.dispatchEvent(new CustomEvent('__message', {detail: {request: 'ping'}} ) );

    setupSharedWorker();

    return true;
}

function setupSharedWorker(): void {
    const mercure = html()?.dataset?.mercureAuth as string;
    if (mercure)
        sharedWorkerCall('mercure.authorize', {connection: 'live', token: JSON.parse(mercure)});

    const version = html()?.dataset?.version as string;
    const language = html().getAttribute('lang') ?? 'de';
    if (version)
        sharedWorkerCall('vault.version', {payload: {version, language}});
}

function handleSharedWorkerResponse(data: any, shim: boolean = false, port_send: HTMLElement = null): void {
    Console.debug(shim ? 'From shared worker shim' : 'From shared worker', data);
    switch (data.request) {
            case 'worker.id':
                window.mhWorkerIdList.push( data.id );
                window.mhWorkerQueue?.forEach( p => shim
                    ? port_send.dispatchEvent(new CustomEvent('__message', {detail: {...p, for: window.mhWorkerIdList}} ) )
                    : window.mhWorker.port.postMessage({...p, for: window.mhWorkerIdList} ) );
                window.mhWorkerQueue = [];
                break;

            case 'response':
                const id = parseInt( data.to as string );
                const callback = window.transferTable[id] ?? null;
                if (callback) {
                    callback(data.payload);
                    window.transferTable[id] = null;
                } else Console.warn(`Did not find callback "${id}" in callback table:`, window.transferTable)
                break;

            case 'broadcast.incoming':
                html().dispatchEvent(new CustomEvent('broadcastMessage', {bubbles: true, cancelable: false, detail: data.payload}));
                break;

            case 'mercure.incoming':
                html().dispatchEvent(new CustomEvent('mercureMessage', {bubbles: true, cancelable: false, detail: data.payload}));
                break;

            case 'vault.updated':
                html().dispatchEvent(new CustomEvent('vaultUpdate', {bubbles: true, cancelable: false, detail: data.payload}));
                break;

            case 'mercure.connection_state':
                const state = data.payload.state;

                html().dispatchEvent(new CustomEvent('mercureState', {bubbles: true, cancelable: false, detail: data.payload}));
                if (state.connection === 'live') {
                    const mercure = JSON.parse(html()?.dataset?.mercureAuth as string ?? 'null');
                    if (!state.auth && mercure?.t) sharedWorkerCall('mercure.authorize', {connection: 'live', token: mercure}).then(()=>null);
                }

                break;

    }
}

export function init () {
    initLive();
    window.addEventListener('DOMContentLoaded', () => initOnceLoaded());
}


