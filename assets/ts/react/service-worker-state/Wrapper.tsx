import * as React from "react";
import {useEffect, useLayoutEffect, useRef, useState} from "react";
import {Const, Global} from "../../defaults";
import {createRoot} from "react-dom/client";
import {html, sharedWorkerCall} from "../../v2/init";
import {Tooltip} from "../tooltip/Wrapper";

declare var c: Const;
declare var $: Global;

type Props = {
    selector: string,
    title: string,
    pass: object
}

export class HordesServiceWorkerIndicator {

    #_root = null;

    public mount(parent: HTMLElement, props: any): void {
        if (!this.#_root) this.#_root = createRoot(parent);
        this.#_root.render( <ServiceWorkerIndicator {...props} /> );
    }

    public unmount(parent: HTMLElement): void {
        if (this.#_root) {
            this.#_root.unmount();
            this.#_root = null;
        }
    }
}

type State = {
    connected: boolean,
    state: "closed"|"connecting"|"open"|"indeterminate",
    auth: boolean
}

export const ServiceWorkerIndicator = (props: {
    textTitle?: string
    textHelp?: string
    textNoSw?: string,
    textOffline?: string,
    textConnecting?: string,
    textUpgrading?: string,
    textOnline?: string,
    connection: string,
}) => {

    const [state, setState] = useState<State | null>()
    const [ping, setPing] = useState(0);
    const pingRef = useRef(0);

    pingRef.current = ping;

    useEffect(() => {
        sharedWorkerCall('mercure.state', {connection: props.connection})
            .then(s => {
                if (!state) setState(s);
            })
            .catch(() => {
                setState({connected: false, auth: false, state: "indeterminate"});
            })

        const stateUpdate = e => {
            if (e.detail.connection === props.connection)
                setState(e.detail.state as State);
        };

        let timeout = null;
        const pingUpdate = e => {
            if (e.detail.connection === props.connection) {
                setPing(pingRef.current + 1);
                timeout = setTimeout(() => setPing(pingRef.current - 1), 100);
            }
        };

        html().addEventListener('mercureState', stateUpdate);
        html().addEventListener('mercureMessage', pingUpdate);
        return () => {
            clearTimeout(timeout);
            html().removeEventListener('mercureState', stateUpdate);
            html().removeEventListener('mercureMessage', pingUpdate);
        }
    }, []);




    const getStateColor = (s: string, c: boolean, o: number): string => {
        if (c) return `rgba(0,255,0,${o})`;
        else if (s === 'connecting') return `rgba(255,255,0,${o})`;
        else if (s === 'closed') return `rgba(255,0,0,${o})`;
        else return `rgba(128,128,128,${o})`;
    }

    const getStateString = (s:string, c: boolean): null|string => {
        if (c && s === 'connecting') return props.textUpgrading ?? null;
        else if (c) return props.textOnline ?? null;
        else if (s === 'connecting') return props.textConnecting ?? null;
        else if (s === 'closed') return props.textOffline ?? null;
        else return null;
    }

    const makeDiv = () => <div style={{
        height: '4px',
        width: '4px',
        borderRadius: '2px',
        border: `1px solid ${getStateColor(state.state, state.connected, ping > 0 ? 1 : 0.5)}`,
        background: `${getStateColor(state.state, state.connected, ping > 0 ? 1 : 0.5)}`
    }}/>

    const stateString = state ? getStateString( state.state, state.connected ) : null;

    return !state ? <></> : <>
        <div style={{display: 'inline-flex', alignItems: 'center', gap: '4px'}}>
            <div>{ makeDiv() }</div>
            {stateString && <div style={{fontSize: '0.75rem', fontWeight: 'bold'}}>{ stateString }</div>}
            { (props.textTitle || props.textHelp || props.textNoSw) &&
                <Tooltip additionalClasses="help">
                    {props.textTitle && <div><b>{ props.textTitle }</b></div>}
                    {props.textHelp && <div>{ props.textHelp }</div>}
                    {props.textNoSw && <div>{ !state.connected && props.textNoSw }</div>}
                </Tooltip>
            }
        </div>
    </>

}