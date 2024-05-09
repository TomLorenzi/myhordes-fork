import Console from "./debug";
import PingServiceModule from "./shared-worker-modules/ping";
import MercureServiceModule from "./shared-worker-modules/mercure";
import ServiceModule from "./shared-worker-modules/common";

const scope = (self as unknown as SharedWorkerGlobalScope);

let id = 0;
let ports: Array<{ id: string, port: MessagePort }> = [];

type ModuleRepository = {
    ping?: PingServiceModule|null
    mercure?: MercureServiceModule|null
} | {
    [modname: string]: ServiceModule
}

const modules: ModuleRepository = {};

const event = (e: string, d: any = null) => Object.values(modules).forEach( m => m.event(e, d))

const sys_handler = ( message: string, event: MessageEvent ):void => {
    switch (message) {
        case 'disconnect':
            Console.log('Disconnecting client.', event.data.id);
            ports = ports.filter((current=> current.id !== event.data.id ));
            Console.log('# of clients', ports.length);
            break;
        default:
            Console.error('Unknown syscall', message);
    }
}

scope.addEventListener('connect', client => {
    Console.log('Connecting client.');
    client.ports.forEach( p => {
        ports.push({id: `${++id}`, port: p} );

        p.start();
        p.postMessage({request: 'worker.id', id: `${id}`});
        p.addEventListener('message', e => {
            Console.log('From client', e.data, e.source);

            const request = (e.data?.request ?? '_none').split('.');
            if (request[0] === 'worker') {
                sys_handler( request.slice(1).join('.'), e );
            } else {
                const handler = modules[ request[0] ] ?? null;

                if (!handler) Console.warn(`No handler for ${request[0]} (from ${e.data?.request})`, e);
                else if (request.length === 1) handler.handle( e );
                else handler.handleMessage( e, request.slice(1).join('.') );
            }


        })
    });
    Console.log('# of clients', ports.length);
});

(() => {
    const f = () => ports.map(p => p.port);

    // Installer
    modules.ping = new PingServiceModule(f);
    modules.mercure = new MercureServiceModule(f);
    event('install');
})();

