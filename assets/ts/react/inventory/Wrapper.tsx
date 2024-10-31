import * as React from "react";
import { createRoot } from "react-dom/client";
import {useContext, useEffect, useLayoutEffect, useRef, useState} from "react";
import {InventoryAPI, InventoryMods, InventoryResponse, Item} from "./api";
import {Tooltip} from "../tooltip/Wrapper";
import {Const, Global} from "../../defaults";
import {LogAPI} from "../log/api";
import {TranslationStrings} from "./strings";
import {Simulate} from "react-dom/test-utils";
import {Vault} from "../../v2/client-modules/Vault";
import {VaultItemEntry, VaultStorage} from "../../v2/typedef/vault_td";

declare var $: Global;
declare var c: Const;

interface mountProps {
    etag: string,
    locked: boolean,

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

function  sort(a: Item, b: Item): number {
    return a.s
        .map((v,i) => [v,b.s[i]])
        .reduce((carry, [a,b]) => carry === 0 ? b-a : carry, 0 );
}

const HordesInventoryWrapper = (props: mountProps) => {

    const [strings, setStrings] = useState<TranslationStrings>( null );
    const [loading, setLoading] = useState<boolean>( false );
    const api = useRef( new InventoryAPI() )

    useEffect(() => {
        api.current.index().then(s => setStrings(s));
    }, []);

    const [inventoryA, setInventoryA] = useState<InventoryResponse>(null);
    const [inventoryB, setInventoryB] = useState<InventoryResponse>(null);

    useEffect(() => {
        if (!props.inventoryAId) return;
        api.current.inventory( props.inventoryAId ).then(r => {
            setInventoryA(r);
        });
    }, [props.inventoryAId, props.etag]);

    useEffect(() => {
        if (!props.inventoryBId) return;
        api.current.inventory( props.inventoryBId ).then(r => {
            setInventoryB(r);
        });
    }, [props.inventoryBId, props.etag]);

    const manageTransfer = (item: number|null, from: number, to: number, direction: string, mod: string = null) =>{
        setLoading(true);
        api.current.transfer( item, from, to, direction, mod ).then(s => {
            if (s.messages)
                $.html.message(s.success ? 'notice' : 'error', s.messages);
            else if (s.errors)
                $.html.error( s.errors.map( e => c.errors[e] ?? null ).join('<hr/>') )

            if (direction === 'down' || direction === 'down-all') {
                setInventoryA( s.source );
                setInventoryB( s.target );
            }

            if (direction === 'up') {
                setInventoryA( s.target );
                setInventoryB( s.source );
            }

            setLoading(false);
        }).catch(() => setLoading(false))
    }

    const handleTransfer = (from: number, to: number, direction: string, mod: string = null) => {
        return (i:Item) => manageTransfer(i.i, from, to, direction, mod);
    }

    return <Globals.Provider value={{api: api.current, strings}}>
        <SingleInventory id={props.inventoryAId} type={props.inventoryAType} inventory={inventoryA} locked={props.locked || loading} onItemClick={handleTransfer( props.inventoryAId, props.inventoryBId, 'down' )} />
        { props.inventoryAType === 'rucksack' && props.inventoryBType === 'chest' && <>
            <button onClick={() => manageTransfer( null, props.inventoryAId, props.inventoryBId, 'down-all' )}>
                <img src={strings?.actions["down-all-icon"] ?? ''} alt={strings?.actions["down-all-home"] ?? ''}/>
                &nbsp;
                { strings?.actions["down-all-home"] ?? '' }
            </button>
        </> }
        { props.inventoryBType !== 'none' && <>
            <SingleInventory id={props.inventoryBId} type={props.inventoryBType} inventory={inventoryB} locked={props.locked || loading} onItemClick={handleTransfer( props.inventoryBId, props.inventoryAId, 'up' )} />
        </>}
    </Globals.Provider>
};

interface inventoryProps {
    id: number,
    "type": string,
    locked: boolean,
    inventory: InventoryResponse,
    onItemClick: (i:Item) => void,
}

const SingleInventory = (props: inventoryProps) => {

    const globals = useContext(Globals);

    const [vaultData, setVaultData] = useState<VaultStorage<VaultItemEntry>>(null);

    useEffect(() => {
        if (props.inventory === null) return;
        const vault = new Vault<VaultItemEntry>(props.inventory.items.map(v => v.p), 'items');
        vault.handle( data => {
            setVaultData(d => {return {
                ...(d ?? {}),
                ...Object.fromEntries( data.map( v => [ v.id, v ] ) )
            }})
        } );
        return () => vault.discard();
    }, [props.inventory]);

    const loaded = props.inventory && globals.strings;

    return <ul className={`inventory inventory-react ${props.type}`}>
        { !loaded && <li className="placeholder"><div className="loading" /></li> }
        { loaded && <>
            <li className="title">{globals.strings ? (globals.strings.type[props.type] ?? props.type) : '...'}</li>
            {props.inventory.items.sort(sort).map(i => <React.Fragment key={i.i}><SingleItem
                item={i} mods={props.inventory.mods} data={(vaultData ?? {})[i.p] ?? null}
                locked={props.locked || i.e} onClick={props.onItemClick}
            /></React.Fragment>)}
            {props.inventory.size && props.inventory.size > props.inventory.items.length && Array.from(Array(props.inventory.size - props.inventory.items.length).keys()).map(i =>
                <li key={i} className="free"/>)
            }
        </>}
    </ul>
}

const SingleItem = (props: { item: Item, data: VaultItemEntry | null, mods: InventoryMods, locked: boolean, onClick: (i:Item) => void, })=> {
    const globals = useContext(Globals);

    return props.data !== null
        ? <li
            className={`item ${(props.locked && 'locked') || ''} ${(props.item.b && 'broken') || ''} ${(props.item.h && 'banished_hidden') || ''} ${(props.item.c > 1 && 'counted') || ''}`}
            onClick={ props.locked ? null : i => props.onClick(props.item) }
        >
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