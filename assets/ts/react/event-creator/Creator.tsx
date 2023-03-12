import {useContext, useEffect, useLayoutEffect, useRef, useState} from "react";
import {Globals} from "./Wrapper";
import * as React from "react";
import {EventMeta, TownPresetData} from "./api";
import Components from "../index";
import {HordesEventCreatorModuleMeta} from "./modules/Meta";
import {HordesEventCreatorModuleTownPreset} from "./modules/TownPreset";

export const HordesEventCreatorWizard = ( {cancel, uuid}: {
    cancel: ()=>void,
    uuid: string,
} ) => {
    const globals = useContext(Globals)

    return (
        <>
            <h4>{ globals.strings.editor.edit }</h4>

            <HordesEventCreatorModuleMeta uuid={uuid}/>
            <HordesEventCreatorModuleTownPreset uuid={uuid}/>

            <br/>

            <div className="row">
                <div className="cell rw-4 ro-8 rw-md-6 ro-md-6 rw-sm-12 ro-sm-0">
                    <button onClick={()=>cancel()}>{ globals.strings.common.cancel_create }</button>
                </div>
            </div>
        </>
    )
};