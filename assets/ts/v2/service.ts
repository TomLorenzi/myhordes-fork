import Console from "./debug";
import MercureServiceModule from "./service-modules/mercure";
import ServiceModule from "./service-modules/common";
import PushServiceModule from "./service-modules/push";
import PingServiceModule from "./service-modules/ping";

const scope = (self as unknown as ServiceWorkerGlobalScope);

// Kept so previous versions of the service worker can transition their data
let pushSubscriptionOptions: any = null;
let pushSubscription: any = null;

type ModuleRepository = {
    ping?: PingServiceModule|null
    push?: PushServiceModule|null
    mercure?: MercureServiceModule|null
} | {
    [modname: string]: ServiceModule
}

let modules: ModuleRepository = {};

let event = (e: string, d: any = null) => Object.values(modules).forEach( m => m.event(e, d))

scope.addEventListener('install', () => {
    const f = () => scope;

    // Installer
    Console.debug('before', modules);
    modules.ping = modules.ping ?? new PingServiceModule(f);
    modules.push = modules.push ?? new PushServiceModule(f, pushSubscription, pushSubscriptionOptions);
    modules.mercure = modules.mercure ?? new MercureServiceModule(f);
    Console.debug('after', modules);

    // Clear old push subscription data
    pushSubscription = null;
    pushSubscriptionOptions = null;

    event('install');
})

scope.addEventListener('activate', () => {
    // Activation hook
    event('activate');
})

scope.addEventListener('message', e => {
    Console.log('From client', e.data, e.source);

    const request = (e.data?.request ?? '_none').split('.');
    const handler = modules[ request[0] ] ?? null;

    if (!handler) Console.warn(`No handler for ${request[0]} (from ${e.data?.request})`, e);
    else if (request.length === 1) handler.handle( e );
    else handler.handleMessage( e, request.slice(1).join('.') );
});

