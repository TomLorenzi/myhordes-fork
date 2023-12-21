import * as React from "react";
import { createRoot } from "react-dom/client";

import {ChangeEvent, MouseEventHandler, useContext, useEffect, useLayoutEffect, useRef, useState} from "react";
import {TranslationStrings} from "./strings";
import {Global} from "../../defaults";
import {v4 as uuidv4} from 'uuid';

declare var $: Global;

interface HeaderConfig {
    header: string|null,
    username: string|null
}

type HTMLConfig = HeaderConfig & {
    context: string,
    features: string[],
    defaultFields: object
}

type FieldChangeEventTrigger = ( field: string, value: string|number|null, old_value: string|number|null, is_default: boolean ) => void
type FieldMutator = ( field: string, value: string|number|null ) => void
type FieldReader = ( field: string ) => string|number|null
type FeatureCheck = ( feature: "tags"|"title" ) => boolean

type TwinoEditorGlobals = {
    //api: NotificationManagerAPI,
    //strings: TranslationStrings,
    uuid: string,
    setField: FieldMutator,
    getField: FieldReader,
    isEnabled: FeatureCheck,
}

export const Globals = React.createContext<TwinoEditorGlobals>(null);


export class HordesTwinoEditor {

    #_root = null;

    private onFieldChanged( field: string, value: string|number|null, old_value: string|number|null, is_default: boolean ): void {
        this.#_root.dispatchEvent( new CustomEvent('change', {
            bubbles: false,
            detail: { field, value, old_value, is_default }
        }) )
    }

    public mount(parent: HTMLElement, props: HTMLConfig): void {
        if (!this.#_root) this.#_root = createRoot(parent);
        this.#_root.render( <TwinoEditorWrapper onFieldChanged={(f:string,v:string|number|null,v0:string|number|null,d:boolean) => this.onFieldChanged(f,v,v0,d)} {...props} /> );
    }

    public unmount(parent: HTMLElement): void {
        if (this.#_root) {
            this.#_root.unmount();
            this.#_root = null;
        }
    }
}

const TwinoEditorWrapper = ( props: HTMLConfig & { onFieldChanged: FieldChangeEventTrigger } ) => {

    let fields = useRef({...props.defaultFields});
    let setField = (field: string, value: string|number) => {
        const current = fields.current[field] ?? null;
        if (current !== value)
            props.onFieldChanged( field, fields.current[field] = value, current, value === (props.defaultFields[field] ?? null) );
    }

    return (
        <Globals.Provider value={{
            uuid: uuidv4(),
            setField: (f:string,v:string|number|null) => setField(f,v),
            getField: (f:string) => fields.current[f] ?? null,
            isEnabled: f => props.features.includes(f),
        }}>
            <div className={ props.context === 'global-pm' ? 'pm-editor' : 'forum-editor' }>
                { props.header && <TwinoEditorHeader {...props} /> }
            </div>
        </Globals.Provider>
    )
};

const TwinoEditorHeader = ( {header, username}: HeaderConfig ) => {
    return <div className="forum-editor-header">
        <i>{ header }</i>
        <b>{ username }</b>
    </div>
};

const TwinoEditorFields = () => {
    const globals = useContext(Globals)

    return <div>
        { globals.isEnabled("title") && <div className="row">
            <div className="cell rw-3 padded">
                <label htmlFor={`${globals.uuid}-title`}>Titel</label>
                <div className="cell rw-5 rw-sm-9 padded">
                    <input type="text" id={`${globals.uuid}-title`} defaultValue={globals.getField('title')} />
                </div>
            </div>
        </div>}
    </div>
}