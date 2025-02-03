import * as React from "react";
import {useContext, useEffect, useRef, useState} from "react";
import {
    Building,
    BuildingAPI, BuildingListResponse,
} from "./api";
import {Tooltip} from "../tooltip/Wrapper";
import {Const, Global} from "../../defaults";
import {TranslationStrings} from "./strings";
import {useVault, Vault} from "../../v2/client-modules/Vault";
import {VaultBuildingEntry, VaultItemEntry, VaultStorage} from "../../v2/typedef/vault_td";
import {BuildingListGlobal, mountPageProps} from "./Wrapper";
import {Tag} from "../index";
import {InventoryAPI, InventoryResourceData} from "../inventory/api";
import {Tab, TabbedSection} from "../tab-list/TabList";
import {ItemTooltip} from "../utils";

declare var $: Global;
declare var c: Const;


export interface BuildingPageGlobal {
    buildings: BuildingListResponse|null,
    vault: VaultStorage<VaultBuildingEntry>|null
    itemVault: VaultStorage<VaultItemEntry>|null,
    itemCount: {[p: number]: number}

    viewMode: "normal"|"needed",
    updateBuilding: (building: Building) => void,
    selectedBuilding: {
        value: Building|null,
        setValue: (value: Building|null) => void,
    }
}

const Globals = React.createContext<BuildingListGlobal & BuildingPageGlobal & mountPageProps>(null);

export const HordesBuildingPageWrapper = (props: mountPageProps) => {

    const [strings, setStrings] = useState<TranslationStrings>( null );

    const [buildings, setBuildings] = useState<BuildingListResponse>( null );
    const [observedItems, setObservedItems] = useState<number[]>( null );
    const [itemCount, setItemCount] = useState<{[p: number]: number}>( {} );

    const api = useRef( new BuildingAPI() )
    const inventoryApi = useRef( new InventoryAPI() )

    const [currentBuilding, setCurrentBuilding] = useState<Building>( null );
    const [currentViewMode, setCurrentViewMode] = useState<"normal"|"needed">( $.client.config.showShortConstrList.get() ? 'needed' : 'normal' );

    const getItemPrototypeIDs = (b: Building[]): number[] => [
        ...new Set(b
            .filter(v => !v.c)
            .map(v => vaultData[v.p]?.rsc?.map( v => v.p ))
            .filter(v => !!v)
            .reduce((v: number[], c: number[]) => [...v, ...c], [])
        )
    ]

    const vaultData = useVault<VaultBuildingEntry>(
        'buildings',
        buildings?.buildings?.map(v => v.p)
    );

    const itemVaultData = useVault<VaultItemEntry>(
        'items',
        observedItems
    );

    const updateItems = () => inventoryApi.current.inventory( props.bank, observedItems ?? [] )
        .then(r => {
            setItemCount(
                Object.fromEntries(
                    ((r as InventoryResourceData).items).map(o => [o.p,o.c]) as any
                ));
        });

    useEffect(() => {
        if (!observedItems || observedItems?.length == 0) return;
        updateItems().then(()=>null)
    }, [observedItems]);

    useEffect(() => {
        if (!buildings || !vaultData) return;
        setObservedItems( getItemPrototypeIDs(buildings.buildings) )
    }, [vaultData]);

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

    const voted_building = buildings?.buildings.find( b => b.v ) ?? null;

    return <Globals.Provider value={
        {
            ...props, api: api.current, strings, buildings, vault: vaultData, itemVault: itemVaultData, itemCount,
            viewMode: currentViewMode,
            updateBuilding: (building: Building) => {
                const list = [...(buildings?.buildings ?? [])];
                const existing = list.findIndex(b => b.i === building.i);
                const voted = building.v ? list.findIndex(b => b.v) : -1;
                if (existing < 0) list.push(building);
                else {
                    list[existing] = building;
                    updateItems().then(()=>null);
                }
                if (voted >= 0 && voted !== existing)
                    list[voted].v = false;

                setBuildings({
                    ...buildings,
                    buildings: list
                });
            },
            selectedBuilding: { value: currentBuilding, setValue: setCurrentBuilding },
        }
    }>
        { props.canVote && strings && <div className="hero-help">{strings.page.vote.help}</div> }

        { (!loaded || root_buildings.length === 0) && <div className="loading" /> }

        { loaded && root_buildings.length > 0 && <>

            { voted_building && <div className="voted-building">
                { strings.page.vote.current }<br/>
                <strong className="name">{ (vaultData ?? {})[voted_building.p]?.name }</strong>
                <Tooltip additionalClasses="help" html={ strings.page.vote.tooltip }/>
            </div> }

            <div className="row">
                <div className="cell ro-7 rw-5 ro-md-6 rw-md-6 ro-sm-0 rw-sm-12">
                    <select value={currentViewMode} onChange={e => {
                        setCurrentViewMode(e.target.value as "normal"|"needed");
                        $.client.config.showShortConstrList.set( e.target.value === "needed" );
                    }}>
                        <option value="normal">{strings?.page?.display_all}</option>
                        <option value="needed">{strings?.page?.display_needed}</option>
                    </select>
                </div>
            </div>

            <TabbedSection mountOnlyActive={true} keepInactiveMounted={false} className="buildings-tabs">
                { [
                    <Tab key={0} title={strings.page.all} id={`b_all`}>
                        { root_buildings.map(b =>
                            <React.Fragment key={b.p}>
                                <BuildingRootGroup building={b}/>
                            </React.Fragment>
                        )}
                    </Tab>,
                    ...root_buildings.map(b =>
                        <Tab key={b.p} title={(vaultData ?? {})[b.p]?.name} id={`b_root_${b.p}`}>
                            <BuildingRootGroup building={b}/>
                        </Tab>
                    )
                ]}
            </TabbedSection>
        </> }
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
        <div className="flex gap" style={{overflow: 'hidden'}}>
            <Tag
                tagName="span" classNames={{'action-vote': globals.canVote && !props.building.c}} className="building_name"
                onClick={() => {
                    if (globals.canVote && !props.building.c)
                        globals.api
                            .vote(props.building.i)
                            .then(m => {
                                if (m.message) $.html.message( m.success ? 'notice' : 'error', m.message );
                                if (m.building) globals.updateBuilding(m.building);
                            })
                }}
            >
                {props.prototype.name}
                { globals.canVote && !props.building.c && <Tooltip html={globals.strings.page.vote.can}/>}
            </Tag>
            { props.building.t &&
                <div>
                    <img alt={globals.strings.page.temp.title} src={globals.strings.page.temp.icon} />
                    <Tooltip additionalClasses="help">
                        <b>{globals.strings.page.temp.title}</b>
                        <hr/>
                        <span dangerouslySetInnerHTML={{__html: globals.strings.page.temp.text}} />
                    </Tooltip>
                </div>
            }
        </div>

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
    const res_ok = props.building.c || props.prototype.rsc.reduce((carry,{p,c}) => carry && (globals.itemCount[p] ?? 0) >= c, true)

    const input = useRef<HTMLInputElement>();
    const [loading, setLoading] = useState<boolean>(false);

    const [inputValid, setInputValid] = useState<boolean>(true);

    return <div className="building_action cell">
        { !props.locked && ( !props.building.c || props.building.a[0] < props.building.a[1] ) && <div className="relative">
            <button
                className="inline build-btn" disabled={!res_ok}
                onClick={() => globals.selectedBuilding.setValue( props.building ) }
            >
                <img alt="" src={ props.building.c ? globals.strings.page.action_repair : globals.strings.page.action_build } />
                { props.building.c && <Tooltip additionalClasses="help" html={globals.strings.page.hp_ratio_help.replace('{remaining}', `${props.missing_ap}`)} /> }
            </button>
            { res_ok && globals.selectedBuilding.value?.i === props.building.i && <div className="ap-prompt" data-disabled={loading ? "disabled" : ""}>
                <input
                    ref={input}
                    type="number"
                    defaultValue={Math.min(1, props.missing_ap)}
                    style={{marginBottom: "3px"}} min={Math.min(1, props.missing_ap)} max={Math.min(9, props.missing_ap)}
                    onChange={() => {
                        if (input.current.value.match(/^\d+$/) === null) setInputValid(false);
                        else if (parseInt( input.current.value ) < Math.min(1, props.missing_ap)) setInputValid(false);
                        else setInputValid(true);
                    }}
                />
                <button
                    disabled={!inputValid}
                    className="button center"
                    onClick={() => {
                        setLoading(true);
                        globals.api.build(props.building.i, parseInt(input.current.value))
                            .then(m => {
                                if (m.message) $.html.message( m.success ? 'notice' : 'error', m.message );
                                if (m.success) globals.selectedBuilding.setValue(null);
                                if (m.building) globals.updateBuilding(m.building);
                            })
                            .finally( () => setLoading(false) )
                    } }
                >{ globals.strings.page.participate }</button>
                <div onClick={()=> globals.selectedBuilding.setValue(null)} className="small link right">{ globals.strings.page.abort }</div>
            </div>}
        </div>}
    </div>
}

interface BuildingResourceItemProps {
    item: VaultItemEntry | null,
    needed: number,
    having: number | null
}

const BuildingResourceItem = (props: BuildingResourceItemProps) => {
    const globals = useContext(Globals);

    const show = props.item && (globals.viewMode === "normal" || props.having < props.needed);

    return show && <div className="build-req">
        <img alt={props.item.name} src={props.item.icon} />
        { globals.viewMode === "normal" && <>
            <Tag tagName="span" className="resource current" classNames={{low: props.having !== null && props.having < props.needed}}>
                {props.having ?? '?'}
            </Tag>/<span className="resource needed">{ props.needed }</span>
        </> }
        { globals.viewMode === "needed" && <span className="resource needed">{ props.needed - props.having}</span> }
        <ItemTooltip data={props.item}/>
    </div>
}