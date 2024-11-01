import * as React from "react";
import { createRoot } from "react-dom/client";
import {useContext, useEffect, useRef, useState} from "react";
import {InventoryAPI, InventoryBagData, InventoryBankData, InventoryMods, InventoryResponse, Item} from "./api";
import {Tooltip} from "../tooltip/Wrapper";
import {Const, Global} from "../../defaults";
import {TranslationStrings} from "./strings";
import {Vault} from "../../v2/client-modules/Vault";
import {VaultItemEntry, VaultStorage} from "../../v2/typedef/vault_td";
import {string} from "prop-types";
import {html} from "../../v2/init";

declare var $: Global;
declare var c: Const;

interface TutorialStateConfig {
    tutorial: number,
    stage: string,
}

interface TutorialConfig {
    from: TutorialStateConfig,
    to: TutorialStateConfig|null,
    restrict: "a"|"b"|null,
}

interface mountProps {
    etag: string,
    locked: boolean,

    inventoryAId: number,
    inventoryAType: string,

    inventoryBId: number,
    inventoryBType: string,

    steal: boolean,
    log: boolean,

    tutorial: TutorialConfig|null,
}

interface passiveMountProps {
    parent: HTMLElement,
    id: number,
    max: number,
}


export class HordesInventory {

    #_root = null;
    #_item_cache: {[key:number]: InventoryBagData} = {}

    public mount(parent: HTMLElement, props: mountProps): any {
        if (!this.#_root) this.#_root = createRoot(parent);
        this.#_root.render( <HordesInventoryWrapper {...props} setCache={(id: number, items: InventoryBagData|null): void => { this.#_item_cache[id] = items; } } /> );
    }

    public unmount() {
        if (this.#_root) {
            this.#_root.unmount();
            this.#_root = null;
        }
    }

    public cachedBagData(id: number) {
        return this.#_item_cache[id] ?? null;
    }
}

export class HordesPassiveInventory {

    #_root = null;

    public mount(parent: HTMLElement, props: passiveMountProps): any {
        if (!this.#_root) this.#_root = createRoot(parent);
        this.#_root.render( <HordesPassiveInventoryWrapper {...props} parent={parent} /> );
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

const extractAllItems = (data: InventoryBankData | InventoryBagData) => {
    return data.bank
        ? (data as InventoryBankData).categories.map(c => c.items).reduce((c,v) => [...c,...v], [])
        : (data as InventoryBagData).items
}

const HordesInventoryWrapper = (props: mountProps & {setCache: (i:number,items:InventoryBagData|null) => void}) => {

    const [strings, setStrings] = useState<TranslationStrings>( null );
    const [loading, setLoading] = useState<boolean>( false );

    const [theftMode, setTheftMode] = useState<boolean>( false );

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
            setCache(props.inventoryAId, r)
        });
    }, [props.inventoryAId, props.etag]);

    useEffect(() => {
        if (!props.inventoryBId) return;
        api.current.inventory( props.inventoryBId ).then(r => {
            setInventoryB(r);
            setCache(props.inventoryBId, r)
        });
    }, [props.inventoryBId, props.etag]);

    const setCache = (id: number, inventory: InventoryResponse) => {
        props.setCache(id,inventory.bank ? null : (inventory as InventoryBagData));
        if (!inventory.bank)
            html().dispatchEvent(new CustomEvent('inventory-bag-loaded', { detail: {id,inventory} }));
    }

    const manageTransfer = (item: number|null, from: number, to: number, direction: string) =>{
        setLoading(true);

        let mod = null;
        if (theftMode) mod = 'theft';

        api.current.transfer( item, from, to, direction, mod ).then(s => {
            // Display messages
            if (s.messages?.length > 0)
                $.html.message(s.success ? 'notice' : 'error', s.messages);
            else if (s.errors?.length > 0)
                $.html.error( s.errors.map( e => c.errors[e] ?? null ).join('<hr/>') )

            // Update individual inventories
            const toA = (direction === 'down' || direction === 'down-all') ? s.source : s.target;
            const toB = (direction === 'down' || direction === 'down-all') ? s.target : s.source;
            setInventoryA(toA);
            setCache(props.inventoryAId, toA)
            setInventoryB(toB);
            setCache(props.inventoryBId, toB)

            // If a tutorial dataset was attached to this element, apply it
            if (props.tutorial && (!props.tutorial.restrict || ( props.tutorial.restrict === 'a' && direction !== 'up' ) || ( props.tutorial.restrict === 'b' && direction === 'up' ))) {
                if (props.tutorial.to === null) $.html.conditionalFinishTutorialStage( props.tutorial.from.tutorial, props.tutorial.from.stage, true );
                else $.html.conditionalSetTutorialStage( props.tutorial.from.tutorial, props.tutorial.from.stage, props.tutorial.to.tutorial, props.tutorial.to.stage );
            }

            // Apply incidentals to the surrounding DOM
            Object.entries( s.incidentals ).forEach(([prop,value]) =>
                document.querySelectorAll(`[data-incidental-target="${prop}"]`).forEach( e => e.innerHTML = value ));

            // If the log mode is enabled, update all surrounding logs
            if (props.log)
                document.querySelectorAll('hordes-log[data-etag]').forEach((log) => {
                    const [et_static, et_custom = '0'] = (log as HTMLElement).dataset.etag.split('-');
                    (log as HTMLElement).dataset.etag = `${et_static}-${parseInt(et_custom)+1}`;
                });

            setLoading(false);
            setTheftMode(false);
        }).catch(() => setLoading(false))
    }

    const handleTransfer = (from: number, to: number, direction: string) => {
        return (i:Item) => manageTransfer(i.i, from, to, direction);
    }

    return <Globals.Provider value={{api: api.current, strings}}>
        <SwitchInventory id={props.inventoryAId} type={props.inventoryAType} inventory={inventoryA} locked={props.locked || loading || theftMode} onItemClick={handleTransfer( props.inventoryAId, props.inventoryBId, 'down' )} />
        { props.inventoryAType === 'rucksack' && props.inventoryBType === 'chest' && <>
            <button onClick={() => manageTransfer( null, props.inventoryAId, props.inventoryBId, 'down-all' )}>
                <img src={strings?.actions["down-all-icon"] ?? ''} alt={strings?.actions["down-all-home"] ?? ''}/>
                &nbsp;
                { strings?.actions["down-all-home"] ?? '' }
            </button>
        </> }
        { props.inventoryAType === 'rucksack' && props.inventoryBType === 'bank' && <>
            <button onClick={() => manageTransfer( null, props.inventoryAId, props.inventoryBId, 'down-all' )}>
                <img src={strings?.actions["down-all-icon"] ?? ''} alt={strings?.actions["down-all-bank"] ?? ''}/>
                &nbsp;
                { strings?.actions["down-all-bank"] ?? '' }
            </button>
            { props.steal && !theftMode && <button onClick={() => {
                if (confirm(strings?.actions["steal-confirm"] ?? '?')) setTheftMode(true);
            }}>
                <img src={strings?.actions["steal-icon"] ?? ''} alt={strings?.actions["steal-btn"] ?? ''}/>
                &nbsp;
                { strings?.actions["steal-btn"] ?? '' }
                <Tooltip additionalClasses="help" html={strings?.actions["steal-tooltip"] ?? ''}/>
            </button> }
            { props.steal && theftMode && <>
                <div className="help">{strings.actions["steal-box"]}</div>
                <button onClick={() => setTheftMode(false)}>{strings.global.abort ?? ''}</button>
            </> }
        </> }
        { props.inventoryBType !== 'none' && <>
            <SwitchInventory
                id={props.inventoryBId} type={props.inventoryBType} inventory={inventoryB}
                locked={props.locked || loading} theft={theftMode}
                onItemClick={handleTransfer( props.inventoryBId, props.inventoryAId, 'up' )} />
        </>}
    </Globals.Provider>
};

interface InventoryProps {
    id: number,
    "type": string,
    locked: boolean,
    inventory: InventoryResponse,
    onItemClick: (i:Item) => void,
    theft?: boolean,
}

interface InventoryPropsBag extends InventoryProps {
    inventory: InventoryBagData,
}

interface InventoryPropsBank extends InventoryProps {
    inventory: InventoryBankData,
}

const SwitchInventory = (props: InventoryProps) => {
    const globals = useContext(Globals);
    const loaded = props.inventory && globals.strings;

    return <>
        { !loaded && <ul className={`inventory inventory-react ${props.type}`}>
            <li className="placeholder">
                <div className="loading"/>
            </li>
        </ul> }
        { loaded && props.inventory.bank && <BankInventory {...(props as InventoryPropsBank)} /> }
        { loaded && !props.inventory.bank && <BagInventory {...(props as InventoryPropsBag)} /> }
    </>
}

const BagInventory = (props: InventoryPropsBag) => {

    const globals = useContext(Globals);

    const [vaultData, setVaultData] = useState<VaultStorage<VaultItemEntry>>(null);

    useEffect(() => {
        const vault = new Vault<VaultItemEntry>(props.inventory.items.map(v => v.p), 'items');
        vault.handle( data => {
            setVaultData(d => {return {
                ...(d ?? {}),
                ...Object.fromEntries( data.map( v => [ v.id, v ] ) )
            }})
        } );
        return () => vault.discard();
    }, [props.inventory]);

    return <ul className={`inventory inventory-react ${props.type}`}>
        <li className="title">{globals.strings.type[props.type] ?? props.type}</li>
        {props.inventory.items.sort(sort).map(i => <React.Fragment key={i.i}><SingleItem
            blur={null}
            item={i} mods={props.inventory.mods} data={(vaultData ?? {})[i.p] ?? null}
            locked={props.locked || i.e} onClick={props.onItemClick}
        /></React.Fragment>)}
        {props.inventory.size && props.inventory.size > props.inventory.items.length && Array.from(Array(props.inventory.size - props.inventory.items.length).keys()).map(i =>
            <li key={i} className="free"/>)
        }
    </ul>
}

const BankInventory = (props: InventoryPropsBank) => {

    const globals = useContext(Globals);
    const datalistUuid = useRef(window.crypto.randomUUID());

    const [searchString, setSearchString] = useState<string>('');
    const [vaultData, setVaultData] = useState<VaultStorage<VaultItemEntry>>(null);

    const [showCategories, setShowCategories] = useState<boolean>($.client.config.showBankCategories.get());

    const category_map = {};
    globals.strings.categories.forEach( ([id,name,sort]) => category_map[id] = [name,sort] );

    useEffect(() => {
        if (props.inventory === null) return;
        const vault = new Vault<VaultItemEntry>(props.inventory.categories
            .map(c => c.items.map(i => i.p)).reduce((c,a) => [...c,...a], [])
            , 'items');
        vault.handle( data => {
            setVaultData(d => {return {
                ...(d ?? {}),
                ...Object.fromEntries( data.map( v => [ v.id, v ] ) )
            }})
        } );
        return () => vault.discard();
    }, [props.inventory]);

    return <>
        <p>
            <input type="search" placeholder={globals.strings.actions.search} list={`bk-list-${datalistUuid.current}`} value={searchString} onChange={e => setSearchString(e.target.value)} />
            <datalist id={`bk-list-${datalistUuid.current}`}>
                { Object.values( vaultData ?? {} ).map(v => <option key={v.id} value={v.name}/>) }
            </datalist>
        </p>
        <ul className={`inventory inventory-react ${props.type} ${props.theft ? 'theft' : ''}`}>
            {props.inventory.categories.sort((c1, c2) => category_map[c1.id][1] - category_map[c2.id][1] || c1.id - c2.id).map(c => <React.Fragment key={c.id}>
                { showCategories && <li className="category">{category_map[c.id][0]}</li> }
                { c.items.map(i => <React.Fragment key={i.i}>
                    <SingleItem
                        blur={ searchString === '' ? null : !(vaultData ?? {})[i.p]?.name?.toLowerCase()?.includes( searchString.toLowerCase() ) }
                        item={i} mods={props.inventory.mods} data={(vaultData ?? {})[i.p] ?? null}
                        locked={props.locked || i.e} onClick={props.onItemClick}
                    />
                </React.Fragment>) }
            </React.Fragment>)}
        </ul>
        <label className="small">
            <input type="checkbox" checked={showCategories} onChange={e => {
                setShowCategories(!showCategories);
                $.client.config.showBankCategories.set(!showCategories);
            }} />
            &nbsp;
            { globals.strings.actions["show-categories"] ?? '' }
        </label>
    </>
}

const SingleItem = (props: { item: Item, data: VaultItemEntry | null, mods: InventoryMods, locked: boolean, onClick?: (i:Item) => void, blur: null|boolean, className?: string })=> {
    const globals = useContext(Globals);

    return props.data !== null
        ? <li
            className={`item ${props.className ?? ''} ${(props.blur === true && 'blur') || ''} ${(props.blur === false && 'focus') || ''} ${(props.locked && 'locked') || ''} ${(props.item.b && 'broken') || ''} ${(props.item.h && 'banished_hidden') || ''} ${(props.item.c > 1 && 'counted') || ''}`}
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

const HordesPassiveInventoryWrapper = (props: passiveMountProps) => {

    const api = useRef( new InventoryAPI() )

    const [strings, setStrings] = useState<TranslationStrings>( null );
    const [vaultData, setVaultData] = useState<VaultStorage<VaultItemEntry>>(null);
    const [bag, setBag] = useState<InventoryBagData>(null);

    useEffect(() => {
        api.current.index().then(s => setStrings(s));
    }, []);

    useEffect(() => {
        if (!props.id) return;

        // Attempt to find an active bag
        const i = document.querySelector(`hordes-inventory[data-inventory-a-id="${props.id}"],hordes-inventory[data-inventory-b-id="${props.id}"]`);
        if (i) {
            setBag( (i as any).bag(props.id) ?? null )
        } else {
            api.current.inventory(props.id).then(r => {
                if (!r.bank) setBag(r as InventoryBagData);
            });
        }

        const handler = (e: CustomEvent)=> {
            if (e.detail.id === props.id) setBag(e.detail.inventory);
        }

        html().addEventListener( 'inventory-bag-loaded', handler );
        return () => html().removeEventListener( 'inventory-bag-loaded', handler );

    }, [props.id]);

    useEffect(() => {
        if (props.max <= 0 || !bag) return;

        props.parent.classList.toggle('expanded', bag.items.length > props.max);
    }, [bag]);

    useEffect(() => {
        if (!bag) return;
        const vault = new Vault<VaultItemEntry>( extractAllItems( bag ).map(i => i.p) , 'items');
        vault.handle( data => {
            setVaultData(d => {return {
                ...(d ?? {}),
                ...Object.fromEntries( data.map( v => [ v.id, v ] ) )
            }})
        } );
        return () => vault.discard();
    }, [bag]);

    return <Globals.Provider value={{api: api.current, strings}}>
        {strings && bag && <>
            {bag?.items?.sort(sort).map((item,index) => <React.Fragment key={item.i}><SingleItem
                item={item} data={(vaultData ?? {})[item.p] ?? null} mods={bag.mods} locked={true}
                blur={null} className={(props.max > 0 && index >= props.max) ? 'over' : ''}/>
            </React.Fragment>)}
            <li className="more">
                <img src={strings.actions.more} alt="+"/>
            </li>
        </>}
    </Globals.Provider>

}