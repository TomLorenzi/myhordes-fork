import {html, sharedWorkerCall} from "../init";
import {VaultBuildingEntry, VaultEntry, VaultStorage} from "../typedef/vault_td";
import {useEffect, DependencyList, useRef, useState, EffectCallback} from "react";

export class Vault<V extends VaultEntry> {

    private eventHandler: any = null;

    constructor(public readonly ids: number[], public readonly storage: string) {}

    public handle(callback: (entries: V[]) => void) {
        this.discard();
        this.eventHandler = (e: { detail: { storage: string; data: V[]; }; }) => {
            const s = e.detail.storage;
            if (s !== this.storage) return;

            const d = (e.detail.data as V[]).filter( v => this.ids.includes(v.id) );
            if (d.length > 0) callback(d);
        }

        html().addEventListener('vaultUpdate', this.eventHandler);
        sharedWorkerCall(`vault.${this.storage}`, {payload: {ids: this.ids}}).then(r => null)
    }

    public discard() {
        if (this.eventHandler)
            html().removeEventListener('vaultUpdate', this.eventHandler);
        this.eventHandler = null;
    }

}

export function useVault<V extends VaultEntry>(
    type: string,
    ids: null|Array<number>,
    effect: EffectCallback = null,
): VaultStorage<V> {
    const idSet = useRef<Set<number>>(new Set)
    const [state, setState] = useState<VaultStorage<V>>({})

    const missing = [...new Set(
        (ids ?? [])
            .filter(id => !idSet.current.has(id))
            .sort((a, b) => a - b)
    )];

    useEffect(() => {
        if (missing?.length > 0) {
            const vault = new Vault<V>(missing, type);
            vault.handle( data => {
                missing.forEach(id => idSet.current.add(id));
                setState(d => {return {
                    ...(d ?? {}),
                    ...Object.fromEntries( data.map( v => [ v.id, v ] ) )
                }})
            } );

            const undo = effect ? effect() : null;
            return () => {
                vault.discard();
                if (undo) undo();
            }
        }
    }, [JSON.stringify(missing)]);

    return state;
}