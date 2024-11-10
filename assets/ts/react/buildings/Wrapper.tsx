import * as React from "react";
import { createRoot } from "react-dom/client";
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
import {string} from "prop-types";
import {html} from "../../v2/init";

declare var $: Global;
declare var c: Const;


interface mountProps {
    etag: string,
}


export class HordesBuildingList {

    #_root = null;

    public mount(parent: HTMLElement, props: mountProps): any {
        if (!this.#_root) this.#_root = createRoot(parent);
        this.#_root.render( <HordesBuildingListWrapper {...props} />
        );
    }

    public unmount() {
        if (this.#_root) {
            this.#_root.unmount();
            this.#_root = null;
        }
    }

}

interface BuildingListGlobal {
    api: BuildingAPI,
    strings: TranslationStrings|null
}

export const Globals = React.createContext<BuildingListGlobal>(null);


const HordesBuildingListWrapper = (props: mountProps) => {

    const [strings, setStrings] = useState<TranslationStrings>( null );
    const [displayListOnMobile, setDisplayListOnMobile] = useState<boolean>( false );

    const [buildings, setBuildings] = useState<BuildingListResponse>( null );

    const api = useRef( new BuildingAPI() )

    const [vaultData, setVaultData] = useState<VaultStorage<VaultBuildingEntry>>(null);

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
        api.current.index().then(s => setStrings(s));
    }, []);

    useEffect(() => {
        //setLoading(true);
        api.current.list(true)
            .then(s => setBuildings(s))
            //.finally(() => setLoading(false));
    }, [props.etag]);

    const loaded = strings && buildings;

    return <Globals.Provider value={{api: api.current, strings}}>
        { !loaded && <div className="loading" /> }
        { loaded && <>
            <div className={`row ${ displayListOnMobile ? 'hidden' : 'hide-desktop hide-lg hide-md'}`}>
                <div className="cell rw-12">
                    <button onClick={() => setDisplayListOnMobile(true)}>{ strings.common.show_list }</button>
                </div>
            </div>
            <div className={`town-buildings ${ displayListOnMobile ? '' : 'hide-sm'}`}>
                <ul>
                    {buildings.buildings.filter(b => b.c).map(b => <React.Fragment key={b.i}>
                        <BuildingListLine building={b} data={(vaultData ?? {})[b.p]}/>
                    </React.Fragment>)}
                </ul>
                <div className={`right small ${ !displayListOnMobile ? 'hidden' : 'hide-desktop hide-lg hide-md'}`}>
                    <span className="pointer" onClick={() => setDisplayListOnMobile(false)}>{strings.common.close}</span>
                </div>
            </div>
        </>}
    </Globals.Provider>
}

const BuildingListLine = (props: { building: Building, data?: VaultBuildingEntry }) => {
    const globals = useContext(Globals)

    const health = props.building.a[1] == 0
        ? 1
        : Math.max(0, Math.min(props.building.a[0] / props.building.a[1], 1))

    return <li className="cell padded-small rw-12">
        { props.data && <div className="flex top gap">
            <img alt={props.data.name} src={props.data.icon} className="symbol"/>
            <div className="flex column data">
                <span>{props.data.name}</span>
                {health < 1 && <div className="hide-desktop hide-sm">
                    <div className="life-bar">
                        <div className={`life-progress life-${health <= 0.15 ? 'critical' : 'warning'}`} style={{width: `${health * 100}%`}}/>
                    </div>
                </div>}
                {props.building.l > 0 && <div className="hide-desktop hide-sm">
                    <em>{globals.strings.common.level.replace('{lv}', `${props.building.l}`)}</em>
                </div>}
                {((props.data.defense > 0) || (props.building.d0 + props.building.db + props.building.dt > 0)) && <em>
                    +&nbsp;{props.building.d0 + props.building.db + props.building.dt}&nbsp;{globals.strings.common.defense}
                    { ((props.building.db + props.building.dt > 0) || (props.building.d0 < props.data.defense)) && <Tooltip additionalClasses="normal">
                        <div className="flex column"></div>
                        {props.building.d0 > 0 && <div>
                            <em>{globals.strings.common.defense_base}:</em>
                            &nbsp;
                            {props.building.d0 < props.data.defense &&
                                <span className="warning">{props.building.d0}&nbsp;/&nbsp;{props.data.defense}</span>}
                            {props.building.d0 >= props.data.defense && <span>{props.building.d0}</span>}
                        </div>}
                        {props.building.db > 0 &&
                            <div><em>{globals.strings.common.defense_bonus}:</em>&nbsp;{props.building.db}</div>}
                        {props.building.dt > 0 &&
                            <div><em>{globals.strings.common.defense_temp}:</em>&nbsp;{props.building.dt}</div>}
                        <hr/>
                        <em>+&nbsp;{props.building.d0 + props.building.db + props.building.dt}&nbsp;{globals.strings.common.defense}</em>
                    </Tooltip>}
                </em> }
            </div>
            {(health < 1 || props.building.l > 0) && <div className="symbol hide-md hide-lg">
                {health < 1 && <div className="life-bar">
                    <div className={`life-progress life-${health <= 0.15 ? 'critical' : 'warning'}`}
                         style={{width: `${health * 100}%`}}/>
                </div>}

                {props.building.l > 0 && <div>
                    <em>{globals.strings.common.level.replace('{lv}', `${props.building.l}`)}</em>
                </div>}

                <Tooltip additionalClasses="normal">
                    {props.building.l > 0 && <div>
                        <b><em>{globals.strings.common.level.replace('{lv}', `${props.building.l} / ${props.data.levels}`)}</em></b>
                    </div>}
                    {health < 1 && <div className="flex gap">
                        <b><em>{globals.strings.common.state}</em></b>
                        <em>{props.building.a[0]}</em>
                        <span>{'/'}</span>
                        <span>{props.building.a[1]}</span>
                    </div>}
                </Tooltip>
            </div>}
            <Tooltip additionalClasses="normal" html={props.data.desc}/>
        </div> }
    </li>

}