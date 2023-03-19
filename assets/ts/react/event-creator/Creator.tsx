import {useContext, useEffect, useLayoutEffect, useRef, useState} from "react";
import {Globals} from "./Wrapper";
import * as React from "react";
import {EventCore, EventMeta, TownPresetData} from "./api";
import Components from "../index";
import {HordesEventCreatorModuleMeta} from "./modules/Meta";
import {HordesEventCreatorModuleTownPreset} from "./modules/TownPreset";
import {Tab, TabbedSection} from "../tab-list/TabList";

export const HordesEventCreatorWizard = ( {cancel, uuid, proposed, published}: {
    cancel: ()=>void,
    uuid: string,
    proposed?: boolean,
    published?: boolean,
} ) => {
    const globals = useContext(Globals)



    return (
        <>
            <h5>{ globals.strings.editor.edit }</h5>

            <TabbedSection mountOnlyActive={true}>
                <Tab title={globals.strings.editor.title} id="ec_town"><HordesEventCreatorModuleMeta uuid={uuid}/></Tab>
                <Tab title={globals.strings.towns.title} id="ec_meta"><HordesEventCreatorModuleTownPreset uuid={uuid}/></Tab>
            </TabbedSection>

            <hr className="section"/>

            <div className="row">

                { !published && <>

                    { !proposed && <>
                        <div className="padded cell rw-4 ro-8 rw-md-6 ro-md-6 rw-sm-12 ro-sm-0">
                            <button onClick={()=>{
                                globals.api.propose( uuid ).then(() => cancel())
                            }}>{ globals.strings.common.init_verification }</button>
                        </div>
                    </> }

                    { proposed && <>
                        <div className="padded cell rw-4 ro-8 rw-md-6 ro-md-6 rw-sm-12 ro-sm-0">
                            <button onClick={()=>{
                                globals.api.cancelProposal( uuid ).then(() => cancel())
                            }}>{ globals.strings.common.cancel_verification }</button>
                        </div>
                    </> }

                </> }

                <div className="padded cell rw-4 ro-8 rw-md-6 ro-md-6 rw-sm-12 ro-sm-0">
                    <button onClick={()=>cancel()}>{ globals.strings.common.cancel_create }</button>
                </div>
            </div>
        </>
    )
};