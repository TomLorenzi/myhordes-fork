import ServiceModule from "./common";
import Console from "../debug";


export default class PushServiceModule extends ServiceModule {

    private pushSubscription: PushSubscription = null;
    private pushSubscriptionOptions: PushSubscriptionOptionsInit = {
        applicationServerKey: null,
        userVisibleOnly: true
    };

    constructor(scope: () => ServiceWorkerGlobalScope, existingSubscription: PushSubscription|null, existingOptions: PushSubscriptionOptionsInit|null) {
        super(scope);
        if (existingSubscription) this.pushSubscription = existingSubscription;
        if (existingOptions) this.pushSubscriptionOptions = existingOptions;

        scope().addEventListener('push', (e) => {
            const data = e.data?.json() ?? null;
            if (data?.title && data?.options?.body) {
                // Do not display test notifications
                if (!data?.options?.data?.test) scope().registration.showNotification(data.title, data.options);
            }
        })
    }

    handle(event: ExtendableMessageEvent): void {
        Console.warn('PushServiceModule: Invalid unscoped call.');
    }

    handleMessage(event: ExtendableMessageEvent, message: string): void {
        switch (message) {
            // Respond with current push subscription (if available)
            case 'subscribe':
                // There can be no subscription without notification permissions
                if (Notification.permission !== 'granted') this.respond(event, null);
                // If we have already subscribed in the past, return existing subscription
                else if (this.pushSubscription) this.respond(event, this.pushSubscription);
                // Without a key, we cannot activate a new subscription
                else if (!event.data.key) this.respond(event, null);
                // Otherwise fetch a new subscription
                else {
                    this.pushSubscriptionOptions.applicationServerKey = event.data.key;
                    this.scope().registration.pushManager.subscribe(this.pushSubscriptionOptions)
                        .then(s => this.respond(event, this.pushSubscription = s))
                        .catch(error => {
                            Console.error(error);
                            this.respond(event, null)
                        });
                }
                break;

            default:
                Console.warn('PushServiceModule: Invalid scoped call.', message, event);
                break;
        }
    }

    event(message: string, data: any = null): void {
        switch (message) {
            case 'activate':
                // If the user has granted us Notification permissions, subscribe for push messages
                if (Notification.permission === 'granted' && this.pushSubscriptionOptions.applicationServerKey)
                    this.scope().registration.pushManager.subscribe(this.pushSubscriptionOptions).then(s => { this.pushSubscription = s })
                break;
        }
        Console.info('PushServiceModule', message, data);
    }

}