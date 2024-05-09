import Console from "../debug";

export default abstract class SharedModule {

    protected ports: () => Array<MessagePort>;

    protected constructor(ports: () => Array<MessagePort>) {
        this.ports = ports;
    }

    protected respond( event: MessageEvent, payload: any ) {
        Console.log('Responding to', event, 'with', payload)
        event.ports.forEach(p => p.postMessage({
            request: 'response',
            to: event.data.to,
            payload: payload
        }));
    }

    protected broadcast( request: string, payload: object ) {
        Console.log('Broadcasting', request, 'with', payload);
        this.ports().forEach( port => {
            port.postMessage({
                request, payload
            })
        } );
    }

    public abstract event(message: string, data: any): void;

    public abstract handleMessage(event: MessageEvent, message: string): void

    public abstract handle(event: MessageEvent): void

}