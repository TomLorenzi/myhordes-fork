import {getPushServiceRegistration, registerForPushNotifications} from "./push";

let worker: ServiceWorkerRegistration = null;
let transferTable = [];

export function serviceWorkerCall(request: string): Promise<any> {
    return new Promise<any>((resolve,reject) => {
        if (!worker) reject(null);
        transferTable.push(resolve);
        worker.active.postMessage({request, to: `${transferTable.length - 1}`});
    })
}

async function initLive() {
    require('string.prototype.matchall').shim();
    await initServiceWorker();
}

async function initOnceLoaded() {

    document.querySelector('footer').addEventListener('click', () => {
        registerForPushNotifications();
        getPushServiceRegistration()
            .then(s => console.log(s))
            .catch(e => console.error('no reg', e));
    })

}

async function initServiceWorker(): Promise<boolean> {
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

    worker = await navigator.serviceWorker.ready;
    navigator.serviceWorker.addEventListener('message', e => {
        console.log(e);
        switch (e.data.request) {
            case 'response':
                const id = parseInt( e.data.to as string );
                const callback = transferTable[id] ?? null;
                if (callback) {
                    callback(JSON.parse(e.data.payload));
                    transferTable[id] = null;
                }
                break;
        }
    })

    worker.active.postMessage({request: 'ping'});
}

export function init () {
    initLive();
    window.addEventListener('DOMContentLoaded', () => initOnceLoaded());
}


