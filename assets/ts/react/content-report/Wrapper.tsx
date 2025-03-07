import * as React from "react";
import { useEffect, useLayoutEffect, useRef, useState} from "react";
import {Const, Global} from "../../defaults";
import {ContentReportAPI, ResponseIndex} from "./api";
import {ReactDialogMounter} from "../index";
import {dialogShim} from "../../shims";

declare var c: Const;
declare var $: Global;

type Props = {
    principal: number
    type: string,
    selector: string,
    title: string
}

export class HordesContentReport extends ReactDialogMounter<Props> {

    protected findActivator(parent: HTMLElement, props: Props): HTMLElement {
        return parent.querySelector(props.selector);
    }

    protected renderReact(callback: (a:any)=>void, props: Props) {
        return <ReportCreatorDialog
            setCallback={callback}
            title={props.title}
            type={props.type}
            principal={props.principal}
        />
    }
}

const ReportCreatorDialog = (props: {
    type: string,
    principal: number,
    title: string,
    setCallback: (any)=>void
}) => {
    const [open, setOpen] = useState<boolean>(false);
    const [sending, setSending] = useState<boolean>(false);
    const [index, setIndex] = useState<ResponseIndex>(null);

    const dialog = useRef<HTMLDialogElement>(null);
    const form = useRef<HTMLFormElement>(null);

    const api = useRef(new ContentReportAPI())

    useEffect(() => {
        props.setCallback( () => setOpen(true) );
        return () => props.setCallback(null);
    }, []);

    const confirmDialog = () => {
        setSending(true);
        api.current.report( props.type, props.principal, $.html.serializeForm( form.current ))
            .then( r => {
                if (r?.message) $.html.notice(r.message);
                dialog.current.close();
                setSending(false);
                setOpen(false);
            }).catch(error => {
                setSending(false);
                if (typeof error === "object") switch ( error.status ?? -1 ) {
                    case 400: $.html.error( index.strings.texts.error_400 ); break;
                    case 404: $.html.error( index.strings.texts.error_404 ); break;
                    case 409: $.html.error( index.strings.texts.error_409 ); break;
                    case 429: $.html.error( index.strings.texts.error_429 ); break;
                    default:
                        console.log(error);
                        $.html.error( c.errors['com'] )
                } else if (error !== null) $.html.error( c.errors['com'] )
            })

    }

    const cancelDialog = () => {
        dialog.current.close();
        setOpen(false);
    }

    useEffect(() => {
        if (open && index === null) api.current.index( props.type ).then( s => setIndex(s) );
    }, [open]);

    useLayoutEffect(() => {
        dialogShim(dialog.current);
    }, [open]);

    useLayoutEffect(() => {
        if (open && dialog.current) {
            dialog.current.showModal();
        }
    }, [open]);

    return open && <>
        <dialog ref={dialog}>
            <div className="modal-title">{props.title}</div>
            <form method="dialog" ref={form} onKeyDown={e => {
                if (e.key === "enter") confirmDialog();
            }} onSubmit={() => confirmDialog()}>
                <div className="modal-content">
                    {index === null && <div className="loading"></div>}
                    {index && <>
                        <p className="small bold">{ index.strings.texts.header }</p>
                        <p className="small">
                            <span>{ index.strings.texts.subline }</span>
                            { index.strings.options.filter( ([,value]) => value !== null ).map( ([id,value]) => <label className="block" key={`opt_${id}`}><input type="radio" name="report_reason" value={id} />&nbsp;{value}</label> ) }
                        </p>
                        <p className="small">
                            <span>{index.strings.texts.additional}</span>
                            <textarea maxLength={255} style={{minHeight: '70px', maxHeight: '120px', height: '70px'}} name="report_details"/>
                        </p>
                    </>}
                </div>
                {index && <div id="modal-actions">
                        <button type="button" disabled={sending} className="modal-button small inline" onClick={() => confirmDialog()}>{ index.strings.texts.ok }</button>
                        <button type="button" disabled={sending} className="modal-button small inline" onClick={() => cancelDialog()}>{ index.strings.texts.abort }</button>
                    </div>
                }
            </form>

        </dialog>
    </>
}