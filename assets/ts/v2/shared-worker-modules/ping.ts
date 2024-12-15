import ServiceModule from "./common";
import Console from "../debug";


export default class PingServiceModule extends ServiceModule {

    constructor(p) { super(p); }

    handle(event: MessageEvent): void {
        Console.debug('Got ping.', event);
        event.ports.forEach(p => p.postMessage({request: 'pong'}));
    }

    handleMessage(event: MessageEvent, message: string): void {
        Console.warn('PingServiceModule: Invalid scoped call.', message, event);
    }

    event(message: string, data: any = null): void {
        Console.debug('PingServiceModule', message, data);
    }

}