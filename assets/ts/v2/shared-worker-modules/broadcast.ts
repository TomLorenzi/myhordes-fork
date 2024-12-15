import ServiceModule from "./common";
import Console from "../debug";


export default class BroadcastServiceModule extends ServiceModule {

    constructor(p) { super(p); }

    handle(event: MessageEvent): void {
        this.broadcast('broadcast.incoming', event.data.payload, event.data.except ?? []);
    }

    handleMessage(event: MessageEvent, message: string): void {
        Console.warn('BroadcastServiceModule: Invalid scoped call.', message, event);
    }

    event(message: string, data: any = null): void {
        Console.debug('BroadcastServiceModule', message, data);
    }

}