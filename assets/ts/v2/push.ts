import {serviceWorkerCall} from "./init";

export function pushAPIIsSupported() {
    return 'PushManager' in window;
}
export async function registerForPushNotifications() {
    if (!pushAPIIsSupported()) {
        console.warn('No push API support.');
        return false;
    }

    const permission = await window.Notification.requestPermission();
    if (permission !== 'granted'){
        console.warn('Permission rejected.');
        return false;
    }

    return true;
}

//export async function displayPushMessage(title: string, body: string) {
//    if (!pushAPIIsSupported() || !worker) return null;
//    return await worker.showNotification(title, {
//        body
//    });
//}

export async function getPushServiceRegistration(): Promise<PushSubscription> {
    const subscription = await serviceWorkerCall('pushSubscription');
    if (!subscription) throw Error("Could not get subscription from service worker.");
    else return subscription;
}