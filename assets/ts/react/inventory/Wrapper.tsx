import * as React from "react";
import { createRoot } from "react-dom/client";
import {useContext, useEffect, useLayoutEffect, useRef, useState} from "react";
import {InventoryAPI, InventoryResponse, Item} from "./api";
import {Tooltip} from "../tooltip/Wrapper";
import {Global} from "../../defaults";
import {LogAPI} from "../log/api";
import {TranslationStrings} from "./strings";
import {Simulate} from "react-dom/test-utils";
import gotPointerCapture = Simulate.gotPointerCapture;

declare var $: Global;

interface mountProps {
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
        <SingleInventory id={props.inventoryAId} type={props.inventoryAType} />
        { props.inventoryBType !== 'none' && <>
            <SingleInventory id={props.inventoryBId} type={props.inventoryBType} />
        </>}
    </Globals.Provider>
};

interface inventoryProps {
    id: number,
    "type": string,
}

const SingleInventory = (props: inventoryProps) => {

    const globals = useContext(Globals);

    const [inventory, setInventory] = useState<InventoryResponse>(null);

    useEffect(() => {
        if (!props.id) return;

        globals.api.inventory( props.id ).then(r => setInventory(r));
        return () => setInventory(null);
    }, [props.id]);

    return <ul className={`inventory ${props.type}`}>
        <li className="title">{globals.strings ? (globals.strings.type[props.type] ?? props.type) : '...'}</li>
        { inventory === null && Array.from(Array(7).keys()).map( i => <li key={i} className="free pending"/> ) }
        { inventory !== null && <>
            { inventory.items.map(i => <React.Fragment key={i.i}><SingleItem item={i}/></React.Fragment>) }
            { inventory.size && inventory.size > inventory.items.length && Array.from(Array(inventory.size - inventory.items.length).keys()).map( i => <li key={i} className="free"/> ) }
        </> }
    </ul>
}

const SingleItem = (props: {item: Item})=> {
    return <li className={`item ${(props.item.b && 'broken') || ''} ${(props.item.h && 'banished_hidden') || ''} ${(props.item.c > 1 && 'counted') || ''}`}>
        <span className="item-icon"><img src="" alt={`${props.item.i}/${props.item.p}`}/></span>
        {props.item.c > 1 && <span>{props.item.c}</span>}
    </li>
}