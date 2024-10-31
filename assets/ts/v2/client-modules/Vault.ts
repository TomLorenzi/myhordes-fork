import {html, sharedWorkerCall} from "../init";
import {VaultEntry} from "../typedef/vault_td";

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