import Console from "./debug";

declare global {
    interface Window { transferTable: Array<any>; }
}

window.transferTable = window.transferTable || [];

export function serviceWorkerCall(request: string, args: object = {}): Promise<any> {
    return new Promise<any>((resolve,reject) => {
        navigator.serviceWorker.ready.then(worker => {
            if (!worker) reject(null);
            window.transferTable.push(resolve);
            worker.active.postMessage({...args, request, to: `${window.transferTable.length - 1}`});
        })
    })
}

function html(): HTMLElement {
    return ((document.getRootNode() as Document).firstElementChild as HTMLElement);
}

async function initLive() {
    require('string.prototype.matchall').shim();
    await initServiceWorker();
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

            case 'mercure.incoming':
                html().dispatchEvent(new CustomEvent('mercureMessage', {bubbles: true, cancelable: false, detail: JSON.parse(e.data.payload)}));
                break;

            case 'mercure.connection_state':
                const state = JSON.parse(e.data.payload);

                html().dispatchEvent(new CustomEvent('mercureState', {bubbles: true, cancelable: false, detail: state}));
                const mercure = html()?.dataset?.mercureAuth as string;
                if (!state.auth && mercure) serviceWorkerCall('mercure.authorize', {token: JSON.parse(mercure)});
                break;
        }
    })

    worker.active.postMessage({request: 'ping'});

    const mercure = html()?.dataset?.mercureAuth as string;
    if (mercure) {
        serviceWorkerCall('mercure.authorize', {token: JSON.parse(mercure)});
    }

}

export function init () {
    initLive();
    window.addEventListener('DOMContentLoaded', () => initOnceLoaded());
}


