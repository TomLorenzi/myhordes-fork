import * as React from "react";
import {useContext, useEffect, useRef, useState} from "react";
import {
    Building,
    BuildingAPI, BuildingListResponse,
} from "./api";
import {Tooltip} from "../tooltip/Wrapper";
import {Const, Global} from "../../defaults";
import {TranslationStrings} from "./strings";
import {Vault} from "../../v2/client-modules/Vault";
import {VaultBuildingEntry, VaultItemEntry, VaultStorage} from "../../v2/typedef/vault_td";
import {BuildingListGlobal, mountPageProps} from "./Wrapper";
import {Tag} from "../index";
import {Simulate} from "react-dom/test-utils";
import load = Simulate.load;
import {InventoryAPI, InventoryResourceData} from "../inventory/api";

declare var $: Global;
declare var c: Const;


export interface BuildingPageGlobal {
    buildings: BuildingListResponse|null,
    vault: VaultStorage<VaultBuildingEntry>|null
    itemVault: VaultStorage<VaultItemEntry>|null,
    itemCount: {[p: number]: number}
}

const Globals = React.createContext<BuildingListGlobal & BuildingPageGlobal & mountPageProps>(null);

export const HordesBuildingPageWrapper = (props: mountPageProps) => {

    const [strings, setStrings] = useState<TranslationStrings>( null );

    const [buildings, setBuildings] = useState<BuildingListResponse>( null );
    const [observedItems, setObservedItems] = useState<number[]>( null );
    const [itemCount, setItemCount] = useState<{[p: number]: number}>( {} );

    const api = useRef( new BuildingAPI() )
    const inventoryApi = useRef( new InventoryAPI() )

    const [vaultData, setVaultData] = useState<VaultStorage<VaultBuildingEntry>>(null);
    const [itemVaultData, setItemVaultData] = useState<VaultStorage<VaultItemEntry>>(null);

    const getItemPrototypeIDs = (b: Building[]): number[] => [
        ...new Set(b
            .filter(v => !v.c)
            .map(v => vaultData[v.p]?.rsc?.map( v => v.p ))
            .filter(v => !!v)
            .reduce((v: number[], c: number[]) => [...v, ...c], [])
        )
    ]

    useEffect(() => {
        if (!buildings) return;
        const vault = new Vault<VaultBuildingEntry>(buildings.buildings.map(v => v.p), 'buildings');
        vault.handle( data => {
            setVaultData(d => {return {
                ...(d ?? {}),
                ...Object.fromEntries( data.map( v => [ v.id, v ] ) )
            }})
        } );
        return () => vault.discard();
    }, [buildings]);

    useEffect(() => {
        if (!buildings || !vaultData || !observedItems || observedItems?.length == 0) return;

        const vault = new Vault<VaultItemEntry>(observedItems, 'items');

        vault.handle( data => {
            setItemVaultData(d => {return {
                ...(d ?? {}),
                ...Object.fromEntries( data.map( v => [ v.id, v ] ) )
            }})
        } );

        inventoryApi.current.inventory( props.bank, observedItems )
            .then(r => {
                setItemCount(
                    Object.fromEntries(
                        ((r as InventoryResourceData).items).map(o => [o.p,o.c]) as any
                    ));
            });

        return () => vault.discard();
    }, [observedItems]);

    useEffect(() => {
        if (!buildings || !vaultData) return;
        setObservedItems( getItemPrototypeIDs(buildings.buildings) )
    }, [buildings, vaultData]);

    useEffect(() => {
        api.current.index().then(s => setStrings(s));
    }, []);

    useEffect(() => {
        //setLoading(true);
        api.current.list(false)
            .then(s => setBuildings(s))
            //.finally(() => setLoading(false));
    }, [props.etag]);

    const loaded = strings && buildings;

    const root_buildings = buildings?.buildings
        ?.filter(b => (vaultData ?? {})[b.p] && vaultData[b.p].parent === null)
        ?.sort((a, b) => {
            const pa = (vaultData ?? {})[a.p] ?? null;
            const pb = (vaultData ?? {})[b.p] ?? null;
            return (pa?.order ?? 0) - (pb?.order ?? 0) || (pa?.id ?? 0) - (pb?.id ?? 0);
        } )
        ?? [];

    return <Globals.Provider value={{...props, api: api.current, strings, buildings, vault: vaultData, itemVault: itemVaultData, itemCount}}>
        { (!loaded || root_buildings.length === 0) && <div className="loading" /> }
        { loaded && root_buildings.length > 0 && root_buildings.map(b => <React.Fragment key={b.i}>
            <BuildingRootGroup building={b}/>
        </React.Fragment>)}
    </Globals.Provider>
}

interface BuildingRootGroupProps {
    building: Building,
}

interface BuildingGroupProps extends BuildingRootGroupProps {
    level: number
    locked: boolean
}

const BuildingRootGroup = (props: BuildingRootGroupProps) => {
    const globals = React.useContext(Globals);
    const parent = globals.vault[props.building.p] ?? null;

    // {% set empty = (not buildable) or (building.complete and not needs_repair) %}

    return parent && (
        <div className={`buildings type_${parent.identifier}`}>
            <div className="row-flex wrap stretch buildings_header">
                <div className="type_indicator"/>
                <div className="stretch buildings_header_image"/>
            </div>

            <BuildingGroup level={0} building={props.building} locked={false}/>
        </div>
    )
}

const BuildingGroup = (props: BuildingGroupProps) => {
    const globals = useContext(Globals);

    const prototype = globals.vault[props.building.p] ?? null;

    const not_full = props.building.a[0] < props.building.a[1];
    const needs_repair = props.building.c && not_full;

    const missing_ap = props.building.c
        ? (Math.ceil((props.building.a[1] - props.building.a[0]) / globals.hpRatio))
        : (Math.round(props.building.a[1] / globals.apRatio) - props.building.a[0]);

    return prototype && <>
        <Tag className="building" classNames={{
        root: (props.level ?? 0) === 0,
        locked: props.locked,
        complete: props.building.c,
        voted: props.building.v,
        empty: props.locked || (props.building.c && !needs_repair),
        ...Object.fromEntries([[`lv-${Math.min(props.level,6)}`, props.level > 0]])
    }}>
            <div className="type_indicator"/>
            <div className="building_row">
                <BuildingInfos building={props.building} prototype={prototype} locked={props.locked} level={props.level} missing_ap={missing_ap} />
                <BuildingResources building={props.building} prototype={prototype} locked={props.locked} missing_ap={missing_ap}/>
                <BuildingActions building={props.building} prototype={prototype} locked={props.locked}  missing_ap={missing_ap}/>
            </div>
        </Tag>
        { globals.buildings.buildings
            .filter( b => globals.vault[b.p]?.parent === props.building.p)
            .sort((a, b) => {
                const pa = globals.vault[a.p] ?? null;
                const pb = globals.vault[b.p] ?? null;
                return (pa?.order ?? 0) - (pb?.order ?? 0) || (pa?.id ?? 0) - (pb?.id ?? 0);
            } )
            .map( b => <React.Fragment key={b.i}><BuildingGroup level={props.level + 1} building={b} locked={!props.building.c}/></React.Fragment> )
        }
    </>
}

interface BuildingCompleteProps {
    building: Building
    prototype: VaultBuildingEntry
    locked: boolean,
    missing_ap?: number
}

const BuildingInfos= (props: BuildingCompleteProps & {level: number}) => {
    const globals = useContext(Globals);

    return <div className="building_info cell">
        <Tooltip additionalClasses="help">
            <b className="building_name">{props.prototype.name}</b>
            <hr/>
            {props.building.c && props.prototype.defense > 0 && props.building.d0 < props.prototype.defense && <>
                <em>{globals.strings.common.defense_broken.replace('{defense}', `${props.building.d0}`).replace('{max}', `${props.prototype.defense}`)}</em>
                <hr/>
            </>}
            {props.prototype.desc}
        </Tooltip>
        {props.level > 1 && Array.from(Array(props.level - 1).keys()).map(i => <img key={i} alt=""
                                                                                    src={globals.strings.page.g2}/>)}
        {props.level > 0 && <img alt="" src={globals.strings.page.g1}/>}
        <img alt={props.prototype.name} src={props.prototype.icon} className="building_icon"/>
        <span className="building_name">{props.prototype.name}</span>
        {props.prototype.defense > 0 &&
            <Tag classNames={{
                defense: !props.building.c || props.building.d0 >= props.prototype.defense,
                'defense-broken': props.building.c && props.building.d0 < props.prototype.defense
            }}
            >{props.building.c ? props.building.d0 : props.prototype.defense}</Tag>
        }
    </div>
}

const BuildingResources = (props: BuildingCompleteProps) => {
    const globals = useContext(Globals);

    const ratio = props.building.c ? 1 : globals.apRatio;

    const needs_repair = (props.building.c && props.building.a[0] < props.building.a[1]);

    return <Tag className="building_resources padded cell" classNames={{to_repair: needs_repair}}>
        {((!props.building.c && props.building.a[0] > 0) || needs_repair) && <>
            <div className="ap-bar">
                <div className="bar"
                     style={{width: `${100 * props.building.a[0] / (Math.round(props.building.a[1] / ratio))}%`}}></div>
            </div>
            <img alt="" className="ap-bar-start"
                 src={props.building.c ? globals.strings.page.hp_bar : globals.strings.page.ap_bar}/>
            {props.building.c && <Tooltip additionalClasses="help">
                <div>
                    <em>{globals.strings.common.state}</em>
                    &nbsp;
                    {props.building.a[0]}/{props.building.a[1]}
                </div>
                <span dangerouslySetInnerHTML={{
                    __html:
                        globals.strings.page.hp_ratio_info
                            .replace('{divap}', '<div class="ap"></div>')
                            .replace('{hprepair}', `${globals.hpRatio}`)
                }}></span>
            </Tooltip> }
        </>}

        { (!props.building.c || props.building.a[0] < props.building.a[1]) && <>
            <div className="build-req">
                {props.building.c && <div className="ap">{props.missing_ap}</div>}
                {!props.building.c && <div className="ap-cost">
                    <div className="ap">{props.missing_ap}</div>
                    {!props.building.c && <Tooltip additionalClasses="help"
                                                   html={globals.strings.page.ap_ratio_help.replace('{ap}', `${props.missing_ap}`)}/>}
                </div>}

            </div>
        </>}

        { !props.building.c && props.prototype.rsc.length > 0 && !props.locked && <div className="build-req-items">
            { props.prototype.rsc.map(({p,c}) => <React.Fragment key={p}>
                <BuildingResourceItem item={(globals.itemVault ?? {})[p]} having={globals.itemCount[p] ?? null} needed={c}/>
            </React.Fragment>) }
        </div> }
    </Tag>
}

const BuildingActions= (props: BuildingCompleteProps) => {
    const globals = useContext(Globals);
    const res_ok = props.prototype.rsc.reduce((carry,{p,c}) => carry && (globals.itemCount[p] ?? 0) >= c, true)

    return <div className="building_action cell">
        { !props.locked && ( !props.building.c || props.building.a[0] < props.building.a[1] ) && <>
            <button className="inline build-btn" disabled={!res_ok}>
                <img alt="" src={ props.building.c ? globals.strings.page.action_repair : globals.strings.page.action_build } />
                { props.building.c && <Tooltip additionalClasses="help" html={globals.strings.page.hp_ratio_help.replace('{remaining}', `${props.missing_ap}`)} /> }
            </button>
        </>}
    </div>
}

interface BuildingResourceItemProps {
    item: VaultItemEntry | null,
    needed: number,
    having: number|null
}

const BuildingResourceItem = (props: BuildingResourceItemProps) => {
    return props.item && <div className="build-req">
        <img alt={props.item.name} src={props.item.icon} />
        <Tag tagName="span" className="resource current" classNames={{low: props.having !== null && props.having < props.needed}}>
            {props.having ?? '?'}
        </Tag>/<span className="resource needed">{ props.needed }</span>
        <Tooltip html={props.item.desc}></Tooltip>
    </div>
}