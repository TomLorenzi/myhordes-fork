import {useEffect, DependencyList, useLayoutEffect} from "react";
import {html} from "../helpers";

type SignalCallback<V extends object> = (details: V) => void;

function sig(signal: string): string { return `sig-${signal}` }

function useSignalInternal<V extends object>(
    signal: string,
    layout: boolean,
    effect: SignalCallback<V>,
    deps: DependencyList = []
): void {

    const fn = () => {
        const handler =  (e: CustomEvent<V>) => effect(e.detail);
        html().addEventListener(sig(signal), handler );
        return () => html().removeEventListener(sig(signal), handler );
    }

    if (layout)
        useLayoutEffect( fn, deps );
    else useEffect( fn, deps );
}
export function useSignal<V extends object>(
    signal: string,
    effect: SignalCallback<V>,
    deps: DependencyList = []
): void {
    useSignalInternal( signal, false, effect, deps );
}

export function useLayoutSignal<V extends object>(
    signal: string,
    effect: SignalCallback<V>,
    deps: DependencyList = []
): void {
    useSignalInternal( signal, true, effect, deps );
}

export function emitSignal<V extends object>(
    signal: string,
    detail: V|null
): V|null {
    html().dispatchEvent( new CustomEvent(sig(signal), detail ? {detail} : {}) );
    return detail;
}