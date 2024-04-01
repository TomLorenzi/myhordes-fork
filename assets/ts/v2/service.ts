import Console from "./debug";

const scope = (self as unknown as ServiceWorkerGlobalScope);

let pushSubscriptionOptions: PushSubscriptionOptionsInit = {
    applicationServerKey: null,
    userVisibleOnly: true
};
let pushSubscription: PushSubscription = null;

function respond( event: ExtendableMessageEvent, payload: any ) {
    Console.log('Responding to', event, 'with', payload)
    event.source.postMessage({
        request: 'response',
        to: event.data.to,
        payload: JSON.stringify(payload)
    });
}

scope.addEventListener('activate', () => {
    // Activation hook

    // If the user has granted us Notification permissions, subscribe for push messages
    if (Notification.permission === 'granted' && pushSubscriptionOptions.applicationServerKey)
        scope.registration.pushManager.subscribe(pushSubscriptionOptions).then(s => { pushSubscription = s })
})

scope.addEventListener('push', (e) => {
    const data = e.data?.json() ?? null;
    if (data?.title && data?.options?.body) {
        // Do not display test notifications
        if (!data?.options?.data?.test) scope.registration.showNotification(data.title, data.options);
    }
})

scope.addEventListener('message', e => {
    Console.log('From client', e.data);
    switch (e.data.request) {
        // Respond to "ping" with "pong"
        case 'ping':
            e.source.postMessage({request: 'pong'});
            break;

        // Respond with current push subscription (if available)
        case 'pushSubscription':
            // There can be no subscription without notification permissions
            if (Notification.permission !== 'granted') respond(e, null);
            // If we have already subscribed in the past, return existing subscription
            else if (pushSubscription) respond(e, pushSubscription);
            // Without a key, we cannot activate a new subscription
            else if (!e.data.key) respond(e, null);
            // Otherwise fetch a new subscription
            else {
                pushSubscriptionOptions.applicationServerKey = e.data.key;
                scope.registration.pushManager.subscribe(pushSubscriptionOptions)
                    .then(s => respond(e, pushSubscription = s))
                    .catch(error => {
                        Console.error(error);
                        respond(e, null)
                    });
            }
            break;
    }
});

