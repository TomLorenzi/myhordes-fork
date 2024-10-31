import Console from "./debug";
import PingServiceModule from "./shared-worker-modules/ping";
import MercureServiceModule from "./shared-worker-modules/mercure";
import ServiceModule from "./shared-worker-modules/common";
import BroadcastServiceModule from "./shared-worker-modules/broadcast";
import VaultServiceModule from "./shared-worker-modules/vault";

const make_port = (name: string): HTMLDivElement => {
    let port = document.getElementById(name);
    if (!port) {
        port = document.createElement('div');
        port.setAttribute('id', name);
        document.body.appendChild(port);
    }
    return port as HTMLDivElement;
}

let port_incoming = make_port('_shared_shim_incoming');
let port_outgoing = make_port('_shared_shim_outgoing');

const fakePort = {
    postMessage: (detail: object) => port_outgoing.dispatchEvent( new CustomEvent('__message', {detail}))
}

type ModuleRepository = {
    ping?: PingServiceModule|null
    mercure?: MercureServiceModule|null
    broadcast?: BroadcastServiceModule|null
    vault?: VaultServiceModule|null
} | {
    [modname: string]: ServiceModule
}

const modules: ModuleRepository = {};

const event = (e: string, d: any = null) => Object.values(modules).forEach( m => m.event(e, d))

const sys_handler = ( message: string ):void => {
    switch (message) {
        case 'disconnect':
            Console.log('Disconnecting client.');
            break;
        default:
            Console.error('Unknown syscall', message);
    }
}

port_incoming.addEventListener('__connect', () => {
    Console.log('Connecting client.');

    port_outgoing.dispatchEvent( new CustomEvent('__message', {detail: {
        request: 'worker.id', id: 1
    }}) );

    port_incoming.addEventListener('__message', (e: CustomEvent) => {
        Console.log('From client', e.detail);

        const request = (e.detail?.request ?? '_none').split('.');
        if (request[0] === 'worker') {
            sys_handler( request.slice(1).join('.') );
        } else {
            const handler = modules[ request[0] ] ?? null;

            if (!handler) Console.warn(`No handler for ${request[0]} (from ${e.detail?.request})`, e);
            else if (request.length === 1) handler.handle( {ports: [fakePort], data: e.detail} );
            else handler.handleMessage( {ports: [fakePort], data: e.detail}, request.slice(1).join('.') );
        }
    })
});

(() => {
    const f = (except: Array<String> = [], only: Array<String> = null) => {
        const match =  only === null ? !except.includes("1") : only.includes("1");
        return match ? [fakePort] : []
    };

    // Installer
    modules.ping = new PingServiceModule(f);
    modules.mercure = new MercureServiceModule(f);
    modules.broadcast = new BroadcastServiceModule(f);
    modules.vault = new VaultServiceModule(f, window, false);
    event('install');
})();

