import {useEffect, DependencyList, useLayoutEffect} from "react";
import {html, broadcast} from "../helpers";

type SignalCallback<V extends object> = (details: V|null) => void;

function sig(signal: string): string { return `sig-${signal}` }
function bc(signal: string): string { return `bc-${signal}` }

function useSignalInternal<V extends object>(
    signals: string|string[],
    layout: boolean,
    broadcast: boolean,
    effect: SignalCallback<V>,
    deps: DependencyList = []
): void {
    if (typeof signals === "string") signals = [signals];
    signals.forEach( signal => {
        const fn = () => {
            const event = broadcast ? bc(signal) : sig(signal);
            const handler = broadcast
                ? (e: CustomEvent<{message: string}>) => { if (e.detail.message === event) effect(null) }
                : (e: CustomEvent<V>) => effect(e.detail);

            html().addEventListener(broadcast ? 'broadcastMessage' : event, handler );
            return () => html().removeEventListener(broadcast ? 'broadcastMessage' : event, handler );
        }

        if (layout)
            useLayoutEffect( fn, deps );
        else useEffect( fn, deps );
    } )
}
export function useSignal<V extends object>(
    signal: string|string[],
    effect: SignalCallback<V>,
    deps: DependencyList = []
): void {
    useSignalInternal( signal, false, false, effect, deps );
}

export function useLayoutSignal<V extends object>(
    signal: string|string[],
    effect: SignalCallback<V>,
    deps: DependencyList = []
): void {
    useSignalInternal( signal, true, false, effect, deps );
}

export function useBroadcastSignal(
    signal: string|string[],
    effect: SignalCallback<null>,
    deps: DependencyList = []
): void {
    useSignalInternal( signal, false, true, effect, deps );
}

export function useLayoutBroadcastSignal(
    signal: string|string[],
    effect: SignalCallback<null>,
    deps: DependencyList = []
): void {
    useSignalInternal( signal, true, true, effect, deps );
}

export function emitSignal<V extends object>(
    signal: string,
    detail: V|null
): V|null {
    html().dispatchEvent( new CustomEvent(sig(signal), detail ? {detail} : {}) );
    broadcast( bc(signal) );
    return detail;
}