import * as React from "react";
import { createRoot } from "react-dom/client";

import {ResponseIndex, NotificationManagerAPI, NotificationSubscription} from "./api";
import {ChangeEvent, MouseEventHandler, useContext, useEffect, useLayoutEffect, useRef, useState} from "react";
import {TranslationStrings} from "./strings";
import {Global} from "../../defaults";
import {Tooltip} from "../tooltip/Wrapper";
import {getPushServiceRegistration, pushAPIIsSupported, registerForPushNotifications} from "../../v2/push";
import {md5} from "js-md5";

declare var $: Global;

export class HordesNotificationManager {

    #_root = null;

    public mount(parent: HTMLElement, props: { }): void {
        if (!this.#_root) this.#_root = createRoot(parent);
        this.#_root.render( <NotificationManagerWrapper {...props} /> );
    }

    public unmount(parent: HTMLElement): void {
        if (this.#_root) {
            this.#_root.unmount();
            this.#_root = null;
        }
    }
}

type NotificationManagerGlobals = {
    api: NotificationManagerAPI,
    strings: TranslationStrings,
}

export const Globals = React.createContext<NotificationManagerGlobals>(null);

const NotificationManagerWrapper = ( {}: {} ) => {

    const supported = pushAPIIsSupported();

    const apiRef = useRef<NotificationManagerAPI>();

    const [index, setIndex] = useState<ResponseIndex>(null)
    const [list, setList] = useState<Array<NotificationSubscription>>(null)
    const [permission, setPermission] = useState<string|boolean>(supported && Notification.permission)
    const [self, setSelf] = useState<PushSubscription|null|boolean>(false)

    const ready = index && list !== null;

    const getSubscriptionFromWorker = () => {
        getPushServiceRegistration()
            .then(v => setSelf(v))
            .catch( () => setSelf(null) )
    }

    useEffect( () => {
        apiRef.current = new NotificationManagerAPI();
        apiRef.current.index().then( index => setIndex(index) );
        apiRef.current.list('webpush').then( list => setList(list.subscriptions) );
        return () => { setIndex(null); }
    }, [] )

    useEffect( () => {
        getSubscriptionFromWorker();
    }, [permission] )

    const register = async () => {
        const success = await registerForPushNotifications();
        if (success) {
            setPermission(Notification.permission);
            const sub = await apiRef.current.put('webpush', await getPushServiceRegistration(), navigator.userAgent);
            if (sub) setList( [...list,sub.subscription] );
        }
        else alert('Um diese Funktion zu verwenden musst du MyHordes gestatten, dir Benachrichtigungen zu senden.')
    }

    const deleteDevice = async (id: string) => {
        const ok = await apiRef.current.delete('webpush', id);
        if (ok) setList( [...list.filter(s => s.id !== id)] );
    }

    const testDevice = async (id: string) => {
        console.log(await apiRef.current.test('webpush', id));
    }

    const hash = self ? md5((self as PushSubscription).endpoint) : null;

    const deviceIsRegistered = (list === null || self === false || permission !== 'granted') ? null : list.reduce(
        (carry: boolean,sub:NotificationSubscription) => carry || sub.hash === hash, false);

    return (
        <Globals.Provider value={{ api: apiRef.current, strings: index?.strings }}>
            { !ready && <div className="loading"></div> }
            { ready && <>
                { list.length === 0 &&
                    <div className="row">
                        <div className="padded cell rw-12">
                            <span className="small">Es sind keine Empfänger für Benachrichtigungen registriert.</span>
                        </div>
                    </div>
                }
                { list.map( s => <div className="row" key={s.id}>
                    <div className="padded cell rw-8">
                        { hash === s.hash && <strong className="small">Dieses Gerät: </strong> }
                        <span className="small">{ s.desc ?? s.id }</span>
                    </div>
                    <div className="padded cell rw-2">
                        <button onClick={() => deleteDevice( s.id )}>Entfernen</button>
                    </div>
                    <div className="padded cell rw-2">
                        <button onClick={() => testDevice( s.id )}>Test</button>
                    </div>
                </div> ) }
                { supported && (permission !== "granted" || deviceIsRegistered === false) &&
                    <div className="row">
                        <div className="cell ro-6 rw-6">
                            <button onClick={() => register()}>Benachrichtigungen auf diesem Gerät erhalten</button>
                        </div>
                    </div>
                }
            </>}
        </Globals.Provider>
    )
};
