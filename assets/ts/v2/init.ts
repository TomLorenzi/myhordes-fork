import Console from "./debug";

declare global {
    interface Window {
        transferTable: Array<any>;

        mhWorkerQueue: Array<any>,
        mhWorkerSetup: boolean|null,
        mhWorker: SharedWorker,
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
        if (window.mhWorkerSetup && !window.mhWorker) reject(null);
        window.transferTable.push(resolve);
        const payload = {...args, request, for: window.mhWorkerIdList, to: `${window.transferTable.length - 1}`};
        if (window.mhWorkerSetup)
            window.mhWorker.port.postMessage( payload )
        else {
            if (!window.mhWorkerQueue) window.mhWorkerQueue = [];
            window.mhWorkerQueue.push(payload);
        }
    })
}

export function broadcast(message: string, args: object = {}): void {
    window.mhWorker.port.postMessage( {payload: {...args, message}, request: 'broadcast', except: window.mhWorkerIdList} )
}

export function html(): HTMLElement {
    return ((document.getRootNode() as Document).firstElementChild as HTMLElement);
}

async function initLive() {
    require('string.prototype.matchall').shim();
    await initServiceWorker();
    await initSharedWorker();
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

    const mhWorker = new SharedWorker(sharedLoaderFile);
    mhWorker.port.start();
    mhWorker.port.addEventListener('message', e => {

        Console.debug('From shared worker', e.data);
        switch (e.data.request) {
            case 'worker.id':
                window.mhWorkerIdList.push( e.data.id );
                window.mhWorkerQueue?.forEach( p => window.mhWorker.port.postMessage({...p, for: window.mhWorkerIdList} ) );
                window.mhWorkerQueue = [];
                break;

            case 'response':
                const id = parseInt( e.data.to as string );
                const callback = window.transferTable[id] ?? null;
                if (callback) {
                    callback(e.data.payload);
                    window.transferTable[id] = null;
                } else Console.warn(`Did not find callback "${id}" in callback table:`, window.transferTable)
                break;

            case 'broadcast.incoming':
                html().dispatchEvent(new CustomEvent('broadcastMessage', {bubbles: true, cancelable: false, detail: e.data.payload}));
                break;

            case 'mercure.incoming':
                html().dispatchEvent(new CustomEvent('mercureMessage', {bubbles: true, cancelable: false, detail: e.data.payload}));
                break;

            case 'mercure.connection_state':
                const state = e.data.payload;

                html().dispatchEvent(new CustomEvent('mercureState', {bubbles: true, cancelable: false, detail: state}));
                const mercure = JSON.parse(html()?.dataset?.mercureAuth as string ?? 'null');
                if (!state.auth && mercure?.t) sharedWorkerCall('mercure.authorize', {token: mercure});
                break;
        }

    })
    window.mhWorker = mhWorker;
    window.addEventListener('beforeunload', () => {
        window.mhWorkerIdList.forEach(id => mhWorker.port.postMessage({request: 'worker.disconnect', id}));
        window.mhWorkerIdList = [];
    });
    mhWorker.port.postMessage({request: 'ping'});

    const mercure = html()?.dataset?.mercureAuth as string;
    if (mercure)
        sharedWorkerCall('mercure.authorize', {token: JSON.parse(mercure)});
}

export function init () {
    initLive();
    window.addEventListener('DOMContentLoaded', () => initOnceLoaded());
}


