import {useContext, useEffect, useLayoutEffect, useRef, useState} from "react";
import {Globals} from "./Wrapper";
import * as React from "react";
import {EventCore, EventCreationAPI, EventMeta, TownPresetData} from "./api";
import Components from "../index";
import {HordesEventCreatorModuleMeta} from "./modules/Meta";
import {HordesEventCreatorModuleTownPreset} from "./modules/TownPreset";
import {Tab, TabbedSection} from "../tab-list/TabList";
import {TranslationStrings} from "./strings";
import {Global} from "../../defaults";

declare var $: Global;

type EventCreatorEditGlobals = {
    writable: boolean
}

export const EditorGlobals = React.createContext<EventCreatorEditGlobals>(null);

export const HordesEventCreatorWizard = ( {cancel, uuid, proposed, published, started, owning, expedited}: {
    cancel: ()=>void,
    uuid: string,
    proposed?: boolean,
    published?: boolean,
    started?: boolean,
    owning?: boolean,
    expedited?: boolean,
} ) => {
    const globals = useContext(Globals)

    const writable = !published && ( !proposed || globals.is_reviewer );

    return (
        <EditorGlobals.Provider value={{ writable }}>
            <h5>{ globals.strings.editor.edit }</h5>

            <TabbedSection mountOnlyActive={true}>
                <Tab title={globals.strings.editor.title} id="ec_town"><HordesEventCreatorModuleMeta uuid={uuid} expedited={expedited}/></Tab>
                <Tab title={globals.strings.towns.title} id="ec_meta"><HordesEventCreatorModuleTownPreset uuid={uuid} published={published} owning={owning} expedited={expedited} cancel={cancel}/></Tab>
            </TabbedSection>

            <hr className="section"/>

            <div className="row">

                { started && <>
                    <div className="padded cell rw-4 ro-8 rw-md-6 ro-md-6 rw-sm-12 ro-sm-0">
                        <button onClick={()=>{
                            globals.api.finish( uuid ).then(() => cancel())
                        }}>{ globals.strings.common.mark_end }</button>
                    </div>
                </> }

                { !published && <>

                    { !proposed && <>
                        <div className="padded cell rw-4 ro-8 rw-md-6 ro-md-6 rw-sm-12 ro-sm-0">
                            <button onClick={()=>{
                                globals.api.propose( uuid ).then(() => {
                                    $.html.notice( globals.strings.messages.verification_started )
                                    cancel()
                                })
                            }}>{ globals.strings.common.init_verification }</button>
                        </div>
                    </> }

                    { proposed && <>
                        { globals.is_reviewer && <>
                            <div className="padded cell rw-4 rw-md-6 rw-sm-12 ro-4 ro-md-0">
                                <button onClick={()=>{
                                    globals.api.publish( uuid ).then(() => {
                                        $.html.notice( globals.strings.messages.verification_confirmed )
                                        cancel()
                                    })
                                }}>{ globals.strings.common.do_verification }</button>
                            </div>
                        </> }
                        <div className={`padded cell rw-4 rw-md-6 rw-sm-12 ${globals.is_reviewer ? '' : 'ro-8 ro-md-6 ro-sm-0'}`}>
                            <button onClick={()=>{
                                globals.api.cancelProposal( uuid ).then(() => {
                                    $.html.notice( globals.strings.messages.verification_cancelled )
                                    cancel()
                                })
                            }}>{ globals.strings.common.cancel_verification }</button>
                        </div>
                    </> }

                </> }

                <div className="padded cell rw-4 ro-8 rw-md-6 ro-md-6 rw-sm-12 ro-sm-0">
                    <button onClick={()=>cancel()}>{ globals.strings.common.cancel_create }</button>
                </div>
            </div>
        </EditorGlobals.Provider>
    )
};