import {useState} from "react";
import {Tooltip} from "./tooltip/Wrapper";
import * as React from "react";
import {Item} from "./inventory/api";
import {VaultItemEntry} from "../v2/typedef/vault_td";

/**
 * Generates two boolean states and a setter; the first one can be directly set by the setter, the second one will
 * become true once the setter sets a truthful value, and then never becomes false again.
 * @param {boolean} init
 */
export function useStickyToggle(init: boolean): [boolean, boolean, (v: boolean) => void] {

    const [show, setShow] = useState(init);
    const [render, setRender] = useState(init);

    return [
        show, render, (value: boolean) => {
            setShow(value);
            if (value) setRender(value);
        }
    ]

}

export function ItemTooltip(props: {
    data: VaultItemEntry,
    addendum?: {className: string, text: string}|false|null,
    children?: any
}) {
    return <Tooltip additionalClasses="item">
        <h1>
            {props.data?.name ?? '???'}
            {props.addendum && <span className={props.addendum.className}>{props.addendum.text}</span>}
            &nbsp;
            <img src={props.data?.icon ?? ''} alt={props.data?.name ?? '...'}/>
        </h1>
        { props.data?.desc ?? '???' }
        { props.children ?? null }
    </Tooltip>
}