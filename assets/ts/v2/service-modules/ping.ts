import ServiceModule from "./common";
import Console from "../debug";


export default class PingServiceModule extends ServiceModule {
    handle(event: ExtendableMessageEvent): void {
        Console.info('Got ping.', event);
        event.source.postMessage({request: 'pong'});
    }

    handleMessage(event: ExtendableMessageEvent, message: string): void {
        Console.warn('PingServiceModule: Invalid scoped call.', message, event);
    }

    event(message: string, data: any = null): void {
        Console.info('PingServiceModule', message, data);
    }

}