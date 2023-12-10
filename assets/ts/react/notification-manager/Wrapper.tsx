import * as React from "react";
import { createRoot } from "react-dom/client";

import {ResponseIndex, NotificationManagerAPI, NotificationSubscription} from "./api";
import {ChangeEvent, MouseEventHandler, useContext, useEffect, useLayoutEffect, useRef, useState} from "react";
import {TranslationStrings} from "./strings";
import {Global} from "../../defaults";
import {UAParser} from "ua-parser-js";
import {getPushServiceRegistration, pushAPIIsSupported, registerForPushNotifications} from "../../v2/push";
import {md5} from "js-md5";
import Console from "../../v2/debug";

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

            const device = UAParser();
            const ua_browser = `${ device.browser.name ?? 'UnknownBrowser' }`;
            const ua_device = [
                [ device.device.vendor, device.device.model ].filter(s => !!s).join(' '),
                [ device.os.name, device.os.version ].filter(s => !!s).join(' '),
            ].filter(s => !!s).join(', ');

            const sub = await apiRef.current.put('webpush', await getPushServiceRegistration(),
                ua_device
                        ? `${ua_browser} (${ua_device})`
                        : ua_browser
            );
            if (sub) setList( [...list,sub.subscription] );
            $.html.notice( index.strings.actions.registered );
        }
        else alert('Um diese Funktion zu verwenden musst du MyHordes gestatten, dir Benachrichtigungen zu senden.')
    }

    const deleteDevice = async (id: string) => {
        const ok = await apiRef.current.delete('webpush', id);
        if (ok) setList( [...list.filter(s => s.id !== id)] );
        $.html.notice( index.strings.actions.removed );
    }

    const editDevice = async (id: string, old_desc: string) => {
        const new_desc = prompt( index.strings.actions.edit, old_desc );
        if (new_desc) {
            const sub = await apiRef.current.edit('webpush', id, new_desc);
            if (sub) setList( [...list].map( s => s.id === sub.subscription.id ? sub.subscription : s ) );
        }
    }

    const testDevice = async (id: string) => {
        const data = await apiRef.current.test('webpush', id);
        if (data.success) $.html.notice( index.strings.actions.test_ok );
        else if (data.expired) $.html.error( index.strings.actions.test_expired );
        else $.html.error( index.strings.actions.test_error.replace('{code}', `${data.status}`) )
    }

    const hash = self ? md5((self as PushSubscription).endpoint) : null;

    const deviceIsRegistered = (list === null || self === false || permission !== 'granted') ? null : list.reduce(
        (carry: boolean,sub:NotificationSubscription) => carry || sub.hash === hash, false);

    return (
        <Globals.Provider value={{ api: apiRef.current, strings: index?.strings }}>
            { !ready && <div className="loading"></div> }
            { ready && <>
                <div className="help">
                    <p>{index.strings.common.infoText1}</p>
                    {!deviceIsRegistered && supported && <p>{index.strings.common.infoText2}</p>}
                    <p>{index.strings.common.infoText3}</p>
                </div>
                { !supported && <div className="note note-critical">
                    { index.strings.common.unsupported }
                </div>}
                {list.length === 0 &&
                    <div className="row">
                        <div className="padded cell rw-12">
                            <span className="small">{ index.strings.table.none }</span>
                        </div>
                    </div>
                }
                { list.length > 0 &&
                    <div className="row-table">
                        <div className="row header">
                            <div className="padded cell rw-8">{ index.strings.table.device }</div>
                            <div className="padded cell rw-4"/>
                        </div>
                        { list.map( s => <div className={`row ${hash === s.hash ? 'highlight' : ''}`} key={s.id}>
                            <div className="padded cell rw-8">
                                { s.expired && <div>
                                    <img alt="!" src={index.strings.table.expired_icon}/>
                                    &nbsp;
                                    <span className="small critical">{ index.strings.table.expired }</span>
                                </div>}
                                <span className="small">{s.desc ?? s.id}</span>
                            </div>
                            <div className="padded cell rw-4 right">
                                { !s.expired && <>
                                    <button className="inline small icon-only" onClick={() => editDevice(s.id, s.desc)}>
                                        <img title={index.strings.table.edit} alt={index.strings.table.edit}
                                             src={index.strings.table.edit_icon}/>
                                    </button>
                                    <button className="inline small icon-only" onClick={() => testDevice(s.id)}>
                                        <img title={index.strings.table.test} alt={index.strings.table.test}
                                             src={index.strings.table.test_icon}/>
                                    </button>
                                </>}
                                <button className="inline small icon-only" onClick={() => deleteDevice(s.id)}>
                                    <img title={index.strings.table.remove} alt={index.strings.table.remove}
                                         src={index.strings.table.remove_icon}/>
                                </button>
                            </div>
                        </div>)}
                    </div>
                }

                {supported && (permission !== "granted" || deviceIsRegistered === false) &&
                    <div className="row">
                        <div className="cell ro-6 rw-6">
                        <button onClick={() => register()}>{ index.strings.actions.add }</button>
                        </div>
                    </div>
                }
            </>}
        </Globals.Provider>
    )
};
