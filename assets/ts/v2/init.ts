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

async function initLive() {
    require('string.prototype.matchall').shim();
    await initServiceWorker();
}

async function initOnceLoaded() {

}

async function initServiceWorker(): Promise<boolean> {
    window.transferTable = [];

    if (!('serviceWorker' in navigator)) {
        console.error('No service worker support detected.')
        return false;
    }

    const serviceLoaderFile = ((document.getRootNode() as Document).firstElementChild as HTMLElement)?.dataset?.serviceWorker as string;
    if (!serviceLoaderFile) {
        console.warn('Service worker file not defined.')
        return false;
    }

    await navigator.serviceWorker.register( serviceLoaderFile, {
        scope: "/"
    });

    const worker = await navigator.serviceWorker.ready;
    navigator.serviceWorker.addEventListener('message', e => {
        console.log(e);
        switch (e.data.request) {
            case 'response':
                const id = parseInt( e.data.to as string );
                const callback = window.transferTable[id] ?? null;
                if (callback) {
                    callback(JSON.parse(e.data.payload));
                    window.transferTable[id] = null;
                } else console.warn(`Did not find callback "${id}" in callback table:`, window.transferTable)
                break;
        }
    })

    worker.active.postMessage({request: 'ping'});
}

export function init () {
    initLive();
    window.addEventListener('DOMContentLoaded', () => initOnceLoaded());
}


