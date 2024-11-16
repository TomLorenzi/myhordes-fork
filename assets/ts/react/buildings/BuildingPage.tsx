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
import {VaultBuildingEntry, VaultStorage} from "../../v2/typedef/vault_td";
import { Globals } from "./Wrapper";

declare var $: Global;
declare var c: Const;


interface mountProps {
    etag: string,
}

export const HordesBuildingPageWrapper = (props: mountProps) => {

    const [strings, setStrings] = useState<TranslationStrings>( null );

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
            <div>BONJOUR</div>
        </>}
    </Globals.Provider>
}