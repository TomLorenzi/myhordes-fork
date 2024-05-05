import Console from "../debug";

export default abstract class ServiceModule {

    protected scope: () => ServiceWorkerGlobalScope;

    constructor(scope: () => ServiceWorkerGlobalScope) {
        this.scope = scope;
    }

    protected respond( event: ExtendableMessageEvent, payload: any ) {
        Console.log('Responding to', event, 'with', payload)
        event.source.postMessage({
            request: 'response',
            to: event.data.to,
            payload: JSON.stringify(payload)
        });
    }

    protected async broadcast( request: string, payload: object ) {
        Console.log('Broadcasting', request, 'with', payload);
        (await this.scope().clients.matchAll({includeUncontrolled: true})).forEach( client => {
            Console.debug(client.id);
            client.postMessage({
                request,
                payload: JSON.stringify(payload)
            })
        } );
    }

    public abstract event(message: string, data: any): void;

    public abstract handleMessage(event: ExtendableMessageEvent, message: string): void

    public abstract handle(event: ExtendableMessageEvent): void

}