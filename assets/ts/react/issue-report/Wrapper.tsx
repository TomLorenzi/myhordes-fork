import * as React from "react";
import {useEffect, useLayoutEffect, useRef, useState} from "react";
import {Const, Global} from "../../defaults";
import {IssueReportAPI, ResponseIndex} from "./api";
import {ReactDialogMounter} from "../index";
import {btoa} from "buffer";

declare var c: Const;
declare var $: Global;

type Props = {
    selector: string,
    title: string
}

export class HordesIssueReport extends ReactDialogMounter<Props> {

    protected findActivator(parent: HTMLElement, props: Props): HTMLElement {
        return parent.querySelector(props.selector);
    }

    protected renderReact(callback: (a:any)=>void, props: Props) {
        return <ReportIssueDialog
            setCallback={callback}
            title={props.title}
        />
    }
}

const ReportIssueDialog = (props: {
    title: string,
    setCallback: (any)=>void
}) => {
    const [open, setOpen] = useState<boolean>(false);
    const [sending, setSending] = useState<boolean>(false);
    const [index, setIndex] = useState<ResponseIndex>(null);

    const dialog = useRef<HTMLDialogElement>(null);
    const form = useRef<HTMLFormElement>(null);

    const api = useRef(new IssueReportAPI())

    useEffect(() => {
        props.setCallback( () => setOpen(true) );
        return () => props.setCallback(null);
    }, []);

    const confirmDialog = () => {
        setSending(true);
        api.current.report( $.html.serializeForm( form.current ), [{ file: 'example.txt', ext: '.txt', content: Buffer.from('This is a file.').toString('base64') }])
            .then( r => {
                //if (r?.message) $.html.notice(r.message);
                dialog.current.close();
                setSending(false);
                setOpen(false);
            }).catch(error => {
                setSending(false);
                if (typeof error === "object") switch ( error.status ?? -1 ) {
                    //case 400: $.html.error( index.strings.texts.error_400 ); break;
                    //case 404: $.html.error( index.strings.texts.error_404 ); break;
                    //case 409: $.html.error( index.strings.texts.error_409 ); break;
                    //case 429: $.html.error( index.strings.texts.error_429 ); break;
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
        if (open && index === null) api.current.index( ).then( s => {
            if (s.strings.redirect) {
                window.open(s.strings.redirect, '_blank');
                cancelDialog();
            } else setIndex(s)
        } );
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
                        <p className="small bold">{ index.strings.common.prompt }</p>
                        <div className="note note-warning">{ index.strings.common.warn }</div>
                        <p className="small">
                            <div><b>{ index.strings.fields.title.title }</b></div>
                            <div><span className="small">{ index.strings.fields.title.hint }</span></div>

                            <input type="text" name="issue_title" placeholder={index.strings.fields.title.example}/>
                        </p>
                        <p className="small">
                            <div><b>{index.strings.fields.desc.title}</b></div>
                            <div><span className="small">{index.strings.fields.desc.hint}</span></div>
                            <textarea maxLength={255} style={{minHeight: '70px', maxHeight: '400px', height: '120px'}}
                                      name="issue_details" placeholder={index.strings.fields.desc.example}/>
                        </p>
                    </>}
                </div>
                {index && <div id="modal-actions">
                    <button type="button" disabled={sending} className="modal-button small inline"
                            onClick={() => confirmDialog()}>{index.strings.common.ok}
                    </button>
                    <button type="button" disabled={sending} className="modal-button small inline" onClick={() => cancelDialog()}>{index.strings.common.cancel}</button>
                    </div>
                }
            </form>

        </dialog>
    </>
}