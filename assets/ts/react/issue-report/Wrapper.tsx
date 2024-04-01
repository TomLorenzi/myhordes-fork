import * as React from "react";
import {useEffect, useLayoutEffect, useRef, useState} from "react";
import {Const, Global} from "../../defaults";
import {FileUpload, IssueReportAPI, ResponseIndex} from "./api";
import {ReactDialogMounter} from "../index";
import {btoa} from "buffer";
import {byteToText} from "../../v2/utils";

declare var c: Const;
declare var $: Global;

type Props = {
    selector: string,
    title: string,
    pass: object
}

export class HordesIssueReport extends ReactDialogMounter<Props> {

    protected findActivator(parent: HTMLElement, props: Props): HTMLElement {
        return parent.querySelector(props.selector);
    }

    protected renderReact(callback: (a:any)=>void, props: Props) {
        return <ReportIssueDialog
            setCallback={callback}
            title={props.title}
            pass={props.pass}
        />
    }
}

type FileUploadWithSize = FileUpload & {size: number, display: boolean, mime: string};

const ReportIssueDialog = (props: {
    title: string,
    pass: object,
    setCallback: (any)=>void
}) => {
    const [open, setOpen] = useState<boolean>(false);
    const [sending, setSending] = useState<boolean>(false);
    const [index, setIndex] = useState<ResponseIndex>(null);

    const [animateImg, setAnimateImg] = useState<number>(null);

    const [attachedFiles, setAttachedFiles] = useState<FileUploadWithSize[]>([]);

    const dialog = useRef<HTMLDialogElement>(null);
    const form = useRef<HTMLFormElement>(null);
    const fileselect = useRef<HTMLInputElement>(null);
    const animatedImage = useRef<HTMLImageElement>(null);

    const api = useRef(new IssueReportAPI())

    useEffect(() => {
        props.setCallback( () => setOpen(true) );
        return () => props.setCallback(null);
    }, []);

    const take_screenshot = (): Promise<FileUploadWithSize> => {

        return new Promise<FileUploadWithSize>( (resolve, reject) => {
            const video = document.createElement("video");

            const do_reject = () => {
                dialog.current.classList.remove('invisible');
                reject(null);
            }

            try {
                navigator.mediaDevices.getDisplayMedia().then(captureStream => {
                    dialog.current.classList.add('invisible');
                    video.srcObject = captureStream;
                    video.play();
                    video.addEventListener('loadedmetadata', e => {

                        window.setTimeout(() => {
                            const w = (video as any).videoWidth;
                            const h = (video as any).videoHeight;

                            const scale = Math.max(w,h) <= 1920 ? 1 : (1920/Math.max(w,h))

                            const canvas = document.createElement("canvas");

                            canvas.height = Math.floor( h * scale );
                            canvas.width = Math.floor( w * scale );

                            const context = canvas.getContext("2d");
                            context.drawImage(video, 0, 0, w, h, 0, 0, w * scale, h * scale);

                            captureStream.getTracks().forEach(track => track.stop());
                            dialog.current.classList.remove('invisible');

                            canvas.toBlob(blob => {
                                if (!blob) do_reject();
                                else blob.arrayBuffer().then(a => {
                                    resolve({
                                        file: blob.type === 'image/webp' ? 'screenshot.webp' : 'screenshot.png',
                                        ext: blob.type === 'image/webp' ? '.webp' : '.png',
                                        size: blob.size,
                                        display: true,
                                        mime: blob.type,
                                        content: Buffer.from( a ).toString('base64')
                                    })
                                })
                            }, 'image/webp')
                        }, 250)

                    })
                }).catch(() => do_reject())

            } catch (err) {
                do_reject();
            }
        } );
    };

    const confirmDialog = () => {
        setSending(true);
        api.current.report({
            ...$.html.serializeForm(form.current),
            pass: {
                ...props.pass,
                'Render Resolution': `${window.innerWidth}px × ${window.innerHeight}px`,
                'Screen Resolution': `${screen.width}px × ${screen.height}px`
            }
        }, attachedFiles)
            .then( r => {
                $.html.notice( index.strings.common.success );
                setSending(false);
                cancelDialog();
            }).catch(error => {
                setSending(false);
                if (typeof error === "object") switch ( error.status ?? -1 ) {
                    case 400: $.html.error( index.strings.errors.error_400 ); break;
                    case 407: $.html.error( index.strings.errors.error_407 ); break;
                    case 412: $.html.error( index.strings.errors.error_412 ); break;
                    default:
                        console.log(error);
                        $.html.error( c.errors['com'] )
                } else if (error !== null) $.html.error( c.errors['com'] )
            })

    }

    const cancelDialog = () => {
        dialog.current.close();
        setOpen(false);
        setAttachedFiles([]);
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

    const appendFile = () => {
        let new_files: FileUploadWithSize[] = [];

        let size = 0;
        let files: File[] = [];
        for (let i = 0; i < fileselect.current.files.length; i++) {
            const file = fileselect.current.files[i];
            if ((size + file.size) > 3145728) continue;
            size += file.size;
            files.push(file);
        }

        let counter = files.length;
        if (counter < fileselect.current.files.length)
            $.html.error( index.strings.errors.too_large );

        if (files.length > 0) setSending(true);

        for (const file of files) {
            if ((size + file.size) > 3145728) return;
            size += file.size;

            const reader = new FileReader();
            reader.onload = function(r) {
                new_files.push({
                    file: file.name,
                    ext: `.${file.name.split('.').pop() ?? 'blob'}`,
                    content: (r.target.result as string).slice((r.target.result as string).indexOf(',') + 1),
                    size: file.size,
                    mime: file.type,
                    display: file.type.slice(0,6) === 'image/'
                });
                if (--counter <= 0) {
                    setSending(false);
                    setAttachedFiles( [...attachedFiles, ...new_files] );
                    fileselect.current.value = null;
                }

            };
            reader.readAsDataURL(file);
        }
    }

    const aniFile = attachedFiles[animateImg] ?? null;
    useLayoutEffect(() => {

        let target = null;
        if (animatedImage.current && (target = document.querySelector(`[data-image-attachment="${animateImg}"]`))) {
            animatedImage.current.style.opacity = '0';
            animatedImage.current.style.position = 'fixed';
            window.setTimeout(() => {
                const goal = target.getBoundingClientRect();
                const goal_pos = {
                    top: `${goal.top}px`,
                    left: `${goal.left}px`,
                    height: `${goal.height}px`,
                    width: `${goal.width}px`,
                }

                animatedImage.current.animate([
                    {
                        offset: 0.00,
                        zIndex: 1,
                        position: 'fixed',
                        objectFit: 'contain',
                        top: `${window.innerHeight * 0.1}px`,
                        left: `${window.innerWidth * 0.1}px`,
                        height: `${window.innerHeight * 0.8}px`,
                        width: `${window.innerWidth * 0.8}px`,
                        opacity: 0,
                        filter: 'brightness(10)'
                    },
                    {
                        offset: 0.01,
                        zIndex: 1,
                        position: 'fixed',
                        objectFit: 'contain',
                        top: `${window.innerHeight * 0.1}px`,
                        left: `${window.innerWidth * 0.1}px`,
                        height: `${window.innerHeight * 0.8}px`,
                        width: `${window.innerWidth * 0.8}px`,
                        opacity: 1,
                        filter: 'brightness(10)'
                    },
                    {
                        offset: 0.02,
                        zIndex: 1,
                        position: 'fixed',
                        objectFit: 'contain',
                        top: `${window.innerHeight * 0.1}px`,
                        left: `${window.innerWidth * 0.1}px`,
                        height: `${window.innerHeight * 0.8}px`,
                        width: `${window.innerWidth * 0.8}px`,
                        opacity: 1,
                        filter: 'brightness(1)'
                    },
                    {
                        offset: 0.96,
                        zIndex: 1,
                        position: 'fixed',
                        objectFit: 'contain',
                        top: `${window.innerHeight * 0.1}px`,
                        left: `${window.innerWidth * 0.1}px`,
                        height: `${window.innerHeight * 0.8}px`,
                        width: `${window.innerWidth * 0.8}px`,
                        opacity: 1,
                        filter: 'brightness(1)'
                    },
                    {
                        offset: 0.98,
                        zIndex: 1,
                        position: 'fixed',
                        objectFit: 'contain',
                        ...goal_pos,
                        opacity: 1,
                        filter: 'brightness(1)'
                    },
                    {
                        offset: 1.00,
                        zIndex: 1,
                        position: 'fixed',
                        objectFit: 'contain',
                        ...goal_pos,
                        opacity: 0,
                    },
                ], {
                    fill: "forwards",
                    duration: 3000,
                    composite: "accumulate",
                    iterationComposite: "accumulate",
                    easing: 'ease-in-out'
                }).addEventListener('finish', () => setAnimateImg(null));
            }, 1)
        }
    }, [animateImg]);

    return open && <>
        <dialog ref={dialog}>
            <div className="modal-title">{props.title}</div>
            {aniFile && <img ref={animatedImage} alt="" src={`data:${aniFile.mime};base64,${aniFile.content}`}/>}
            <form method="dialog" ref={form} onKeyDown={e => {
                if (e.key === "enter") confirmDialog();
            }} onSubmit={() => confirmDialog()}>
                <div className="modal-content">
                    {index === null && <div className="loading"></div>}
                    {index && <>
                        <p className="small bold">{index.strings.common.prompt}</p>
                        <div className="note note-warning">{index.strings.common.warn}</div>
                        <p className="small">
                            <span>{index.strings.fields.title.title}</span><br/>
                            {index.strings.fields.title.hint}

                            <input type="text" name="issue_title" autoComplete="off" placeholder={index.strings.fields.title.example}/>
                        </p>
                        <p className="small">
                            <span>{index.strings.fields.desc.title}</span><br/>
                            {index.strings.fields.desc.hint}

                            <textarea maxLength={255} style={{minHeight: '70px', maxHeight: '400px', height: '120px'}}
                                      name="issue_details" placeholder={index.strings.fields.desc.example}/>
                        </p>
                        <div className="small">
                            <span>{index.strings.fields.attachment.title}</span><br/>
                            {index.strings.fields.attachment.hint}<br/>

                            <div className="row-flex gap-x">
                                <button type="button" disabled={sending} className="modal-button small inline"
                                        onClick={() => fileselect.current.click()}>{index.strings.common.add_file}
                                </button>
                                <button type="button" disabled={sending} className="modal-button small inline"
                                        onClick={() => take_screenshot().then(file => {
                                            setAttachedFiles([...attachedFiles, file]);
                                            setAnimateImg( attachedFiles.length );
                                        })}>{index.strings.common.add_screenshot}
                                </button>
                            </div>
                            <input ref={fileselect} multiple={true} className="hidden" type="file"
                                   data-no-serialization="1" onChange={() => appendFile()}/>
                        </div>
                        <br />
                        {attachedFiles.length > 0 && <div className="row-flex vertical gap-small-y">
                            {attachedFiles.map((file, i) => <div key={i} className="row-flex gap-x ">
                                {file.display && <div className="cell grow-0">
                                    <img data-image-attachment={i} style={{maxWidth: '100px'}} alt="" src={`data:${file.mime};base64,${file.content}`}/>
                                </div> }
                                <div className="cell grow-1 text-wrap-word">
                                    <b>{ file.file }</b>, { byteToText( file.size ) }
                                </div>
                                <div className="cell grow-0">
                                    <button type="button" disabled={sending} className="modal-button small inline"
                                            onClick={() => setAttachedFiles( [...attachedFiles.slice(0, i), ...attachedFiles.slice(i + 1)] )}>{index.strings.common.delete_file}
                                    </button>
                                </div>
                            </div>)}
                        </div>}
                    </>}
                </div>
                {index && <div id="modal-actions">
                    <button type="button" disabled={sending} className="modal-button small inline"
                            onClick={() => confirmDialog()}>{index.strings.common.ok}
                    </button>
                    <button type="button" disabled={sending} className="modal-button small inline"
                            onClick={() => cancelDialog()}>{index.strings.common.cancel}</button>
                </div>
                }
            </form>

        </dialog>
    </>
}