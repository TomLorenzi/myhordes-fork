import * as React from "react";
import { createRoot } from "react-dom/client";
import {useContext, useEffect, useLayoutEffect, useRef, useState} from "react";
import {InventoryAPI, InventoryMods, InventoryResponse, Item} from "./api";
import {Tooltip} from "../tooltip/Wrapper";
import {Global} from "../../defaults";
import {LogAPI} from "../log/api";
import {TranslationStrings} from "./strings";
import {Simulate} from "react-dom/test-utils";
import {Vault} from "../../v2/client-modules/Vault";
import {VaultItemEntry, VaultStorage} from "../../v2/typedef/vault_td";

declare var $: Global;

interface mountProps {
    etag: string,

    inventoryAId: number,
    inventoryAType: string,

    inventoryBId: number,
    inventoryBType: string,
}


export class HordesInventory {

    #_root = null;

    public mount(parent: HTMLElement, props: mountProps): any {
        if (!this.#_root) this.#_root = createRoot(parent);
        this.#_root.render( <HordesInventoryWrapper {...props} /> );
    }

    public unmount() {
        if (this.#_root) {
            this.#_root.unmount();
            this.#_root = null;
        }
    }
}

interface InventoryGlobal {
    api: InventoryAPI,
    strings: TranslationStrings|null
}

export const Globals = React.createContext<InventoryGlobal>(null);

const HordesInventoryWrapper = (props: mountProps) => {

    const [strings, setStrings] = useState<TranslationStrings>( null );
    const api = useRef( new InventoryAPI() )

    useEffect(() => {
        api.current.index().then(s => setStrings(s));
    }, []);

    return <Globals.Provider value={{api: api.current, strings}}>
        <SingleInventory id={props.inventoryAId} type={props.inventoryAType} etag={props.etag} />
        { props.inventoryBType !== 'none' && <>
            <SingleInventory id={props.inventoryBId} type={props.inventoryBType} etag={props.etag} />
        </>}
    </Globals.Provider>
};

interface inventoryProps {
    id: number,
    "type": string,
    etag: string,
}

const SingleInventory = (props: inventoryProps) => {

    const globals = useContext(Globals);

    const [inventory, setInventory] = useState<InventoryResponse>(null);
    const [vaultData, setVaultData] = useState<VaultStorage<VaultItemEntry>>(null);

    useEffect(() => {
        if (!props.id) return;

        globals.api.inventory( props.id ).then(r => {
            setInventory(r);
            setVaultData(Object.fromEntries( r.items.map(i => [i.p, null]) ))
        });
    }, [props.id, props.etag]);

    useEffect(() => {
        if (inventory === null) return;
        const vault = new Vault<VaultItemEntry>(inventory.items.map(v => v.p), 'items');
        vault.handle( data => {
            setVaultData(d => {return {
                ...(d ?? {}),
                ...Object.fromEntries( data.map( v => [ v.id, v ] ) )
            }})
        } );
        return () => vault.discard();
    }, [inventory]);

    const loaded = inventory && globals.strings;

    return <ul className={`inventory inventory-react ${props.type}`}>
        { !loaded && <li className="placeholder"><div className="loading" /></li> }
        { loaded && <>
            <li className="title">{globals.strings ? (globals.strings.type[props.type] ?? props.type) : '...'}</li>
            {inventory.items.map(i => <React.Fragment key={i.i}><SingleItem item={i} mods={inventory.mods} data={(vaultData ?? {})[i.p] ?? null}/></React.Fragment>)}
            {inventory.size && inventory.size > inventory.items.length && Array.from(Array(inventory.size - inventory.items.length).keys()).map(i =>
                <li key={i} className="free"/>)
            }
        </>}
    </ul>
}

const SingleItem = (props: { item: Item, data: VaultItemEntry | null, mods: InventoryMods })=> {
    const globals = useContext(Globals);

    return props.data !== null
        ? <li className={`item ${(props.item.b && 'broken') || ''} ${(props.item.h && 'banished_hidden') || ''} ${(props.item.c > 1 && 'counted') || ''}`}>
            <span className="item-icon"><img src={ props.data?.icon ?? '' } alt={ props.data?.name ?? '...' }/></span>
            {props.item.c > 1 && <span>{props.item.c}</span>}
            <Tooltip additionalClasses="item">
                <h1>
                    {props.data?.name ?? '???'}
                    {props.item.b && <span className="broken">{globals.strings.props.broken}</span>}
                    &nbsp;
                    <img src={props.data?.icon ?? ''} alt={props.data?.name ?? '...'}/>
                </h1>
                { props.data?.desc ?? '???' }
                { props.mods.has_drunk && props.data.props.includes('is_water') && <div className="item-addendum">{ globals.strings.props["drink-done"] }</div> }
                { props.item.e && <div className="item-tag item-tag-essential">{ globals.strings.props.essential }</div> }
                { props.data.props.includes('single_use') && <div className="item-tag item-tag-use-1">{ globals.strings.props.single_use }</div> }
                { props.data.heavy && <div className="item-tag item-tag-heavy">{ globals.strings.props.heavy }</div> }
                { (props.data.deco > 0 || props.data.props.includes('deco')) && <div className="item-tag item-tag-deco">{ globals.strings.props.deco }</div> }
                { props.data.props.includes('defence') && <div className="item-tag item-tag-defense">{ globals.strings.props.defence }</div> }
                { props.data.props.includes('weapon') && <div className="item-tag item-tag-weapon">{ globals.strings.props.weapon }</div> }
                { props.data.watch != 0 && <div className="item-tag item-tag-weapon">
                    { globals.strings.props["nw-weapon"] }
                    {props.item.w && <>&nbsp;<em>{ props.item.w }</em></> }
                </div> }
            </Tooltip>
        </li>
        :
        <li className="item locked pending"/>
}