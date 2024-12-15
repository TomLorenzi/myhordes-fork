import Console from "../debug";
import {string} from "prop-types";

export type PortQueryFunction = (except: Array<String>, only: Array<String>) => Array<MessagePort>;

export default abstract class SharedModule {

    protected ports: PortQueryFunction;

    protected constructor(ports: PortQueryFunction) {
        this.ports = ports;
    }

    protected respond( event: MessageEvent, payload: any ) {
        Console.debug('Responding to', event, 'with', payload)

        const ports = (!event.data.for) ? event.ports : this.ports([], event.data.for);
        ports.forEach(p => {
            p.postMessage({
                request: 'response',
                to: event.data.to,
                payload: payload
            })
        });
    }

    protected broadcast( request: string, payload: object, except: Array<String> = [] ) {
        Console.debug('Broadcasting', request, 'with', payload);
        this.ports(except, null).forEach( port => {
            port.postMessage({
                request, payload
            })
        } );
    }

    public abstract event(message: string, data: any): void;

    public abstract handleMessage(event: MessageEvent, message: string): void

    public abstract handle(event: MessageEvent): void

}