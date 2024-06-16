import * as React from "react";
import { createRoot } from "react-dom/client";

import {AvatarCreatorAPI, ResponseIndex, ResponseMedia} from "./api";
import {useContext, useEffect, useLayoutEffect, useRef, useState} from "react";
import {TranslationStrings} from "./strings";
import {Global} from "../../defaults";
import {Tooltip} from "../tooltip/Wrapper";
import {byteToText} from "../../v2/utils";

declare var $: Global;

export class HordesAvatarCreator {

    #_root = null;

    public mount(parent: HTMLElement, props: { maxSize: number }): void {
        if (!this.#_root) this.#_root = createRoot(parent);
        this.#_root.render( <AvatarCreatorWrapper {...props} /> );
    }

    public unmount(parent: HTMLElement): void {
        if (this.#_root) {
            this.#_root.unmount();
            this.#_root = null;
        }
    }
}

type AvatarCreatorGlobals = {
    api: AvatarCreatorAPI,
    strings: TranslationStrings,
}

export const Globals = React.createContext<AvatarCreatorGlobals>(null);

const AvatarCreatorWrapper = ( {maxSize}: {maxSize: number} ) => {

    const apiRef = useRef<AvatarCreatorAPI>();
    const uploadRef = useRef<HTMLInputElement>();

    const [index, setIndex] = useState<ResponseIndex>(null)
    const [media, setMedia] = useState<ResponseMedia>(null)

    const [loading, setLoading] = useState<boolean>(false)
    const [editMode, setEditMode] = useState<{ mime: string, data: ArrayBuffer }>(null)

    const [editBlockedByResolution, setEditBlockedByResolution] = useState<boolean>(false);

    useEffect( () => {
        apiRef.current = new AvatarCreatorAPI();
        apiRef.current.index().then( index => setIndex(index) );
        apiRef.current.getMedia().then( media => setMedia(media) );
        return () => { setIndex(null); }
    }, [] )

    return (
        <Globals.Provider value={{ api: apiRef.current, strings: index?.strings }}>
            { (!index || !media) && <div className="loading"></div> }
            { !editMode && <>
                <input ref={uploadRef} className="hidden" type="file" accept=".gif,.jpg,.jpeg,.jif,.jfif,.png,.webp,.bmp" onChange={(e) => {
                    if (e.target.files.length !== 1) {
                        $.html.error(index.strings.common.error_single_file);
                        return;
                    }

                    const file = e.target.files[0];
                    if (maxSize > 0 && file.size >= maxSize) {
                        $.html.error(index.strings.common.error_too_large);
                        return;
                    }

                    const type_info = file.type.split('/',2);
                    if (type_info.length < 2 || type_info[0] !== 'image') {
                        $.html.error(index.strings.common.error_unknown_format);
                        return;
                    }

                    setLoading(true);
                    const reader = new FileReader();
                    reader.onload = function(r) {
                        setEditMode({mime: file.type, data: r.target.result as ArrayBuffer});
                        setLoading(false);
                    };
                    reader.readAsArrayBuffer(file);
                }}/>
                { index && media && <>
                    <AvatarDisplay media={media} defaultResolutionCallback={(x,y) => setEditBlockedByResolution(Math.max(x,y) < 100)}/>
                </> }
                { loading && <div className="loading"/> }
                { !loading && <>
                    <div className="row">
                        { index && media && (media.default || media.round || media.small) && <>
                            <div className="padded cell rw-4 rw-md-6 rw-sm-12">
                                <button onClick={()=>uploadRef.current?.click()}>
                                    { index.strings.common.action_edit }
                                </button>
                            </div>
                            { media.default && !editBlockedByResolution &&
                                <div className="padded cell rw-4 rw-md-6 rw-sm-12">
                                    <button onClick={()=>{
                                        setLoading(true);
                                        fetch( media.default?.url || media.round?.url || media.small?.url, {
                                            method: "GET"
                                        } ).then( response => {
                                            response.blob()
                                                .then( blob => blob.arrayBuffer() )
                                                .then( blob => {
                                                    setEditMode({mime: response.headers.get('Content-Type'), data: blob});
                                                    setLoading(false);
                                                })
                                        })
                                    }}>
                                        { index.strings.common.action_modify }
                                    </button>
                                </div>
                            }
                            <div className="padded cell rw-4 rw-md-6 rw-sm-12">
                                <button onClick={async () => {
                                    if (confirm(index.strings.common.confirm)) {
                                        setLoading(true);
                                        await apiRef.current.deleteMedia();
                                        const media = await apiRef.current.getMedia();
                                        setMedia(media);
                                        setLoading(false);
                                    }
                                }}>{ index.strings.common.action_delete }</button>
                            </div>
                        </> }
                        { index && media && !(media.default || media.round || media.small) && <>
                            <div className="padded cell rw-4 rw-md-6 rw-sm-12">
                                <button onClick={()=>uploadRef.current?.click()}>
                                    { index.strings.common.action_create }
                                </button>
                            </div>
                        </> }
                    </div>
                </>}
            </> }
            { editMode && <AvatarEditor {...editMode} cancel={()=>setEditMode(null)} confirm={()=>{
                setEditMode(null);
                setMedia(null);
                apiRef.current.getMedia().then( media => setMedia(media) );
            }} /> }
        </Globals.Provider>
    )
};

interface ImageDimensions {
    x: number,
    y: number
}

const getMediaDimensions = ( url: string, callback: (ImageDimensions)=>void ) => {
    const i = new Image();
    i.onload = () => callback({x: i.width, y: i.height});
    i.src = url;
}

const AvatarDisplay = ({media,defaultResolutionCallback}:{media:ResponseMedia, defaultResolutionCallback: (x: number, y: number) => void}) => {

    const globals = useContext(Globals)
    const [defaultImageDimensions, setDefaultImageDimensions] = useState<ImageDimensions>(null)
    const [roundImageDimensions, setRoundImageDimensions] = useState<ImageDimensions>(null)
    const [smallImageDimensions, setSmallImageDimensions] = useState<ImageDimensions>(null)

    useEffect(() => {
        if (media.default) getMediaDimensions(media.default.url, d=> {
            setDefaultImageDimensions(d);
            defaultResolutionCallback(d.x,d.y)
        });
        if (media.round) getMediaDimensions(media.round.url, d=>setRoundImageDimensions(d));
        if (media.small) getMediaDimensions(media.small.url, d=>setSmallImageDimensions(d));
    }, [])

    return <div>
        { (media.default || media.round || media.small) && <>
            <div className="row-flex h-center">
                <div className="padded cell center">
                    <div className="small"><strong>{ globals.strings.common.format_default }</strong></div>
                    <hr/>
                    { media.default && <div className="avatar no-arma"><img alt="" src={media.default.url}/></div> }
                    <div className="small">
                        { media.default && defaultImageDimensions && <><hr/>{globals.strings.common.info
                            .replace('{x}', `${defaultImageDimensions.x}`)
                            .replace('{y}', `${defaultImageDimensions.y}`)
                            .replace('{size}', byteToText(media.default.size))
                        }</>}
                        { !media.default && <><hr/>{globals.strings.common.none}</> }
                    </div>
                </div>
                <div className="padded cell center">
                    <div className="small"><strong>{ globals.strings.common.format_round }</strong></div>
                    <hr/>
                    <div className="avatar round no-arma"><img alt="" src={media.round?.url ?? media.default?.url ?? media.small?.url}/></div>
                    <div className="small">
                        { media.round && roundImageDimensions && <><hr/>{globals.strings.common.info
                            .replace('{x}', `${roundImageDimensions.x}`)
                            .replace('{y}', `${roundImageDimensions.y}`)
                            .replace('{size}', byteToText(media.round.size))
                        }</>}
                        { !media.round && <><hr/>{globals.strings.common.fallback}</> }
                    </div>
                </div>
                <div className="padded cell center">
                    <div className="small"><strong>{ globals.strings.common.format_small }</strong></div>
                    <hr/>
                    <div className="avatar small no-arma"><img alt="" src={media.small?.url ?? media.default?.url ?? media.round?.url}/></div>
                    <div className="small">
                        { media.small && smallImageDimensions && <><hr/>{globals.strings.common.info
                            .replace('{x}', `${smallImageDimensions.x}`)
                            .replace('{y}', `${smallImageDimensions.y}`)
                            .replace('{size}', byteToText(media.small.size))
                        }</>}
                        { !media.small && <><hr/>{globals.strings.common.fallback}</> }
                    </div>
                </div>
            </div>
        </>}
        { !media.default && !media.round && !media.small && <div className="row">
            <div className="padded cell rw-12">
                <div className="help">{ globals.strings.common.no_avatar }</div>
            </div>
        </div> }
    </div>

}

interface Geometry {
    x0: number, x1: number, y0: number, y1: number
}

const AvatarEditor = ({data, mime, cancel, confirm}:{data:ArrayBuffer, mime: string, cancel: ()=>void, confirm: ()=>void}) => {

    const globals = useContext(Globals)
    const [imageDimensions, setImageDimensions] = useState<ImageDimensions>(null)
    const [loading, setLoading] = useState<boolean>(false)

    const [specifySmallSection, setSpecifySmallSection] = useState<boolean>(false)
    const [editSmallSection, setEditSmallSection] = useState<boolean>(false)

    const base64 = Buffer.from(data).toString('base64');
    const source = `data:${mime};base64,${base64}`;

    const metaFull = useRef<Geometry>({x0: 0, x1: 0, y0: 0, y1: 0});
    const metaSmall = useRef<Geometry>({x0: 0, x1: 0, y0: 0, y1: 0});

    const aspect = useRef<number>(1);
    const geometry = useRef<Geometry>({x0: 0, x1: 1, y0: 0, y1: 1});
    const geometrySmall = useRef<Geometry>({x0: 0, x1: 1, y0: 0, y1: 1});

    const freeGeometry = useRef<Geometry>({x0: 0, x1: 1, y0: 0, y1: 1});
    const aspectGeometry = useRef<Geometry>({x0: 0, x1: 1, y0: 0, y1: 1});

    const dragging = useRef<{x0: boolean, x1: boolean, y0: boolean, y1: boolean}>({x0: false, x1: false, y0: false, y1: false});
    const selector = useRef<HTMLDivElement>();
    const selectorSmall = useRef<HTMLDivElement>();

    const getSelector = () => {
        return editSmallSection ? selectorSmall.current : selector.current;
    }

    const getGeometry = () => {
        return editSmallSection ? geometrySmall : geometry;
    }

    const getMeta = () => {
        return editSmallSection ? metaSmall : metaFull;
    }

    const currentDimensions = useRef<HTMLSpanElement>();
    const currentDimensionsSmall = useRef<HTMLSpanElement>();

    useEffect(() => {
        getMediaDimensions(source, d => {
            if (d.x < 100 || d.y < 100) {
                setLoading(true);
                globals.api.uploadMedia( mime, base64 ).then(()=> {
                    confirm();
                    setLoading(false);
                });
            }
            setImageDimensions(d);
        });
    }, [])

    useLayoutEffect(() => {
        if (imageDimensions && currentDimensions) {
            metaFull.current = { x0: 0, x1: imageDimensions.x, y0: 0, y1: imageDimensions.y }
            updateDimensionDisplay();
        }
    }, [imageDimensions])

    useLayoutEffect(() => {
        if (specifySmallSection) {

            const aspect = 9/3;

            geometry.current.x0 *= getSelector().parentElement.clientWidth;
            geometry.current.y0 *= getSelector().parentElement.clientHeight;
            geometry.current.x1 *= getSelector().parentElement.clientWidth;
            geometry.current.y1 *= getSelector().parentElement.clientHeight;

            geometrySmall.current.x0 = geometry.current.x0;
            geometrySmall.current.y0 = geometry.current.y0;
            geometrySmall.current.x1 = geometry.current.x0 + Math.round(Math.min( geometry.current.x1 - geometry.current.x0, (geometry.current.y1 - geometry.current.y0) * aspect ));
            geometrySmall.current.y1 = geometry.current.y0 + Math.round(Math.min( geometry.current.y1 - geometry.current.y0, (geometry.current.x1 - geometry.current.x0) / aspect ));

            const shiftX =  Math.round((geometry.current.x1 - geometrySmall.current.x1)/2)
            const shiftY =  Math.round((geometry.current.y1 - geometrySmall.current.y1)/2)
            geometrySmall.current.x0 += shiftX;
            geometrySmall.current.x1 += shiftX;
            geometrySmall.current.y0 += shiftY;
            geometrySmall.current.y1 += shiftY;

            geometry.current.x0 /= getSelector().parentElement.clientWidth;
            geometry.current.y0 /= getSelector().parentElement.clientHeight;
            geometry.current.x1 /= getSelector().parentElement.clientWidth;
            geometry.current.y1 /= getSelector().parentElement.clientHeight;

            geometrySmall.current.x0 /= getSelector().parentElement.clientWidth;
            geometrySmall.current.y0 /= getSelector().parentElement.clientHeight;
            geometrySmall.current.x1 /= getSelector().parentElement.clientWidth;
            geometrySmall.current.y1 /= getSelector().parentElement.clientHeight;

            metaSmall.current.x0 = Math.round( geometrySmall.current.x0 * imageDimensions.x );
            metaSmall.current.x1 = Math.round( (geometrySmall.current.x1 - geometrySmall.current.x0) * imageDimensions.x );
            metaSmall.current.y0 = Math.round( geometrySmall.current.y0 * imageDimensions.y );
            metaSmall.current.y1 = Math.round( (geometrySmall.current.y1 - geometrySmall.current.y0) * imageDimensions.y );

            updateSelector(false);
            updateDimensionDisplay();
        }
    }, [ specifySmallSection])

    useLayoutEffect(() => {
        updateDimensionDisplay();
    }, [editSmallSection, specifySmallSection])

    useLayoutEffect( () => {
        if (!getSelector()) return;

        const geo = getGeometry().current;
        const sel = getSelector();
        const mta = getMeta().current;
        
        const onResize = () => {
            updateSelector( dragging.current.x0 || dragging.current.y0 || dragging.current.x1 || dragging.current.y1 );
        }

        const onMouseUp = (e: PointerEvent) => {
            dragging.current.x0 = dragging.current.y0 = dragging.current.x1 = dragging.current.y1 = false;

            geo.x0 /= sel.parentElement.clientWidth;
            geo.x1 /= sel.parentElement.clientWidth;
            geo.y0 /= sel.parentElement.clientHeight;
            geo.y1 /= sel.parentElement.clientHeight;

            document.body.removeEventListener('pointermove', onMouseMove)
            document.body.removeEventListener('mousemove', onPreventDefault)
        }

        const onMouseDown = (e: PointerEvent) => {
            const target = (e.target as HTMLDivElement);

            dragging.current.x0 = target.dataset.handleX === '+' || parseInt((e.target as HTMLDivElement).dataset.handleX ?? '0') < 0;
            dragging.current.x1 = target.dataset.handleX === '+' || parseInt((e.target as HTMLDivElement).dataset.handleX ?? '0') > 0;
            dragging.current.y0 = target.dataset.handleY === '+' || parseInt((e.target as HTMLDivElement).dataset.handleY ?? '0') < 0;
            dragging.current.y1 = target.dataset.handleY === '+' || parseInt((e.target as HTMLDivElement).dataset.handleY ?? '0') > 0;

            geo.x0 = Math.round(geo.x0 * sel.parentElement.clientWidth);
            geo.x1 = Math.round(geo.x1 * sel.parentElement.clientWidth);
            geo.y0 = Math.round(geo.y0 * sel.parentElement.clientHeight);
            geo.y1 = Math.round(geo.y1 * sel.parentElement.clientHeight);

            aspect.current = editSmallSection ? (9/3) : ((geo.x1 - geo.x0) / (geo.y1 - geo.y0));
            freeGeometry.current = JSON.parse( JSON.stringify( geo ) );
            aspectGeometry.current = JSON.parse( JSON.stringify( geo ) );

            document.body.addEventListener('pointerup', onMouseUp, {once: true})
            document.body.addEventListener('pointermove', onMouseMove)
            document.querySelector('hordes-avatar-creator [data-purpose="avatar-editor-area"]')?.addEventListener('mousedown', onPreventDefault)
        }

        const onMouseMove = (e: PointerEvent) => {
            if (dragging.current.x0 || dragging.current.x1 || dragging.current.y0 || dragging.current.y1 ) {

                let relX = e.movementX;
                let relY = e.movementY;

                if (dragging.current.x0 && dragging.current.x1) relX = Math.max(-freeGeometry.current.x0, Math.min(relX, sel.parentElement.clientWidth - freeGeometry.current.x1));
                if (dragging.current.y0 && dragging.current.y1) relY = -Math.max(-freeGeometry.current.y0, Math.min(-relY, sel.parentElement.clientHeight - freeGeometry.current.y1));

                // FREE GEOMETRY
                if (dragging.current.x0 && dragging.current.x1) {
                    freeGeometry.current.x0 += relX;
                    freeGeometry.current.x1 += relX;
                } else {
                    if (dragging.current.x0) freeGeometry.current.x0 = Math.min( Math.max( 0, freeGeometry.current.x0 + relX ), freeGeometry.current.x1 - 64, sel.parentElement.clientWidth );
                    if (dragging.current.x1) freeGeometry.current.x1 = Math.min( Math.max( 0, freeGeometry.current.x0 + 64, freeGeometry.current.x1 + relX ), sel.parentElement.clientWidth );
                }

                if (dragging.current.y0 && dragging.current.y1) {
                    freeGeometry.current.y0 -= relY;
                    freeGeometry.current.y1 -= relY;
                } else {
                    if (dragging.current.y0) freeGeometry.current.y0 = Math.min( Math.max( 0, freeGeometry.current.y0 - relY ), freeGeometry.current.y1 - 64, sel.parentElement.clientHeight );
                    if (dragging.current.y1) freeGeometry.current.y1 = Math.min( Math.max( 0, freeGeometry.current.y0 + 64, freeGeometry.current.y1 - relY ), sel.parentElement.clientHeight );
                }

                // ASPECT GEOMETRY
                aspectGeometry.current.x0 = freeGeometry.current.x0;
                aspectGeometry.current.y0 = freeGeometry.current.y0;
                aspectGeometry.current.x1 = freeGeometry.current.x0 + Math.round(Math.min( freeGeometry.current.x1 - freeGeometry.current.x0, (freeGeometry.current.y1 - freeGeometry.current.y0) * aspect.current ));
                aspectGeometry.current.y1 = freeGeometry.current.y0 + Math.round(Math.min( freeGeometry.current.y1 - freeGeometry.current.y0, (freeGeometry.current.x1 - freeGeometry.current.x0) / aspect.current ));

                if (dragging.current.x0 && !dragging.current.x1) {
                    aspectGeometry.current.x0 += freeGeometry.current.x1 - aspectGeometry.current.x1;
                    aspectGeometry.current.x1 += freeGeometry.current.x1 - aspectGeometry.current.x1;
                }
                if (dragging.current.y0 && !dragging.current.y1) {
                    aspectGeometry.current.y0 += freeGeometry.current.y1 - aspectGeometry.current.y1;
                    aspectGeometry.current.y1 += freeGeometry.current.y1 - aspectGeometry.current.y1;
                }

                const useAspectGeometry = e.shiftKey || editSmallSection;

                const tmp = {
                    x0: useAspectGeometry ? aspectGeometry.current.x0 : freeGeometry.current.x0,
                    x1: useAspectGeometry ? aspectGeometry.current.x1 : freeGeometry.current.x1,
                    y0: useAspectGeometry ? aspectGeometry.current.y0 : freeGeometry.current.y0,
                    y1: useAspectGeometry ? aspectGeometry.current.y1 : freeGeometry.current.y1
                }

                if (tmp.x0 >= 0 && tmp.x1 <= sel.parentElement.clientWidth && tmp.y0 >= 0 && tmp.y1 <= sel.parentElement.clientHeight) {
                    geo.x0 = tmp.x0;
                    geo.x1 = tmp.x1;
                    geo.y0 = tmp.y0;
                    geo.y1 = tmp.y1;

                    updateSelector(true);

                    mta.x0 = Math.round( geo.x0 / sel.parentElement.clientWidth * imageDimensions.x );
                    mta.x1 = Math.round( (geo.x1 - geo.x0) / sel.parentElement.clientWidth * imageDimensions.x );
                    mta.y0 = Math.round( geo.y0 / sel.parentElement.clientHeight * imageDimensions.y );
                    mta.y1 = Math.round( (geo.y1 - geo.y0) / sel.parentElement.clientHeight * imageDimensions.y );

                    updateDimensionDisplay();
                }

                e.preventDefault();
            }
        }
        const onPreventDefault = (e: MouseEvent) => {
            e.preventDefault();
            return false;
        }

        window.addEventListener('resize', onResize);
        sel.querySelectorAll('[data-handle-x][data-handle-y]').forEach(
            n => n.addEventListener('pointerdown', onMouseDown)
        )

        return () => {
            window.removeEventListener('resize', onResize);
            document.body.removeEventListener('pointermove', onMouseMove)
            document.body.removeEventListener('pointerup', onMouseUp)
            document.querySelector('hordes-avatar-creator [data-purpose="avatar-editor-area"]')?.removeEventListener('mousemove', onPreventDefault)
            sel.querySelectorAll('[data-handle-x][data-handle-y]').forEach(
                n => n.removeEventListener('pointerdown', onMouseDown)
            )
        }
    } )

    const updateDimensionDisplay = () => {
        if (currentDimensions.current)
            currentDimensions.current.innerText = globals.strings.common.dimensions
                .replace('{px}', `${metaFull.current.x0}`)
                .replace('{x}', `${metaFull.current.x1}`)
                .replace('{py}', `${metaFull.current.y0}`)
                .replace('{y}', `${metaFull.current.y1}`)
        if (currentDimensionsSmall.current) {
            if (!specifySmallSection) currentDimensionsSmall.current.innerText = globals.strings.common.fallback;
            else currentDimensionsSmall.current.innerText = globals.strings.common.dimensions
                .replace('{px}', `${metaSmall.current.x0}`)
                .replace('{x}', `${metaSmall.current.x1}`)
                .replace('{py}', `${metaSmall.current.y0}`)
                .replace('{y}', `${metaSmall.current.y1}`)
        }
    }

    const updateSelector = (absolute: boolean) => {
        const defaultAbs = absolute && !editSmallSection;
        let top = defaultAbs ? (selector.current.parentElement.clientHeight - geometry.current.y1) : ((1 - geometry.current.y1) * selector.current.parentElement.clientHeight);
        let bottom = defaultAbs ? geometry.current.y0 : (geometry.current.y0 * selector.current.parentElement.clientHeight);
        let left = defaultAbs ? geometry.current.x0 : (geometry.current.x0 * selector.current.parentElement.clientWidth);
        let right = defaultAbs ? (selector.current.parentElement.clientWidth - geometry.current.x1) : ((1 - geometry.current.x1) * selector.current.parentElement.clientWidth);

        selector.current.style.top = `${top}px`
        selector.current.style.bottom = `${bottom}px`
        selector.current.style.left = `${left}px`
        selector.current.style.right = `${right}px`

        const smallAbs = absolute && editSmallSection;
        top = smallAbs ? (selectorSmall.current.parentElement.clientHeight - geometrySmall.current.y1) : ((1 - geometrySmall.current.y1) * selectorSmall.current.parentElement.clientHeight);
        bottom = smallAbs ? geometrySmall.current.y0 : (geometrySmall.current.y0 * selectorSmall.current.parentElement.clientHeight);
        left = smallAbs ? geometrySmall.current.x0 : (geometrySmall.current.x0 * selectorSmall.current.parentElement.clientWidth);
        right = smallAbs ? (selectorSmall.current.parentElement.clientWidth - geometrySmall.current.x1) : ((1 - geometrySmall.current.x1) * selectorSmall.current.parentElement.clientWidth);

        selectorSmall.current.style.top = `${top}px`
        selectorSmall.current.style.bottom = `${bottom}px`
        selectorSmall.current.style.left = `${left}px`
        selectorSmall.current.style.right = `${right}px`
    }

    const format = useRef<HTMLSelectElement>();

    return <div>
        { (!imageDimensions || loading) && <div className="loading"/> }
        { imageDimensions && !loading && <>
            <div className="row">
                <div className="padded cell rw-12">
                    <div className="help">{ globals.strings.common.edit_help }<br/>{ globals.strings.common.edit_help2 }</div>
                </div>
            </div>
            <div className="row">
                <div className="padded cell rw-3 rw-md-4 rw-sm-12">
                    <div className="note note-lightest">
                        { globals.strings.common.compression }
                        &nbsp;
                        <a className="help-button">
                            { globals.strings.common.help }
                            <Tooltip additionalClasses="help" html={globals.strings.common.compression_help}/>
                        </a>
                    </div>
                </div>
                <div className="padded cell rw-9 rw-md-8 rw-sm-12">
                    <select ref={format}>
                        <option value="avif">{ globals.strings.common.compression_avif }</option>
                        <option value="webp">{ globals.strings.common.compression_webp }</option>
                        <option value="lossless">{ globals.strings.common.compression_noloss }</option>
                    </select>
                </div>
            </div>
            <div className="row">
                <div className="padded cell rw-3 rw-md-4 rw-sm-12">
                    <div className="note note-lightest">
                        { globals.strings.common.format_small }
                    </div>
                </div>
                <div className="padded cell rw-9 rw-md-8 rw-sm-12">
                    <div><label className="small"><input checked={!specifySmallSection} type="radio" name="specifySmallSection" value="no" onChange={e=> {
                        setSpecifySmallSection(e.target.value === 'yes')
                        if (e.target.value !== 'yes') setEditSmallSection(false);
                    }}/> {globals.strings.common.edit_auto}</label></div>
                    <div><label className="small"><input checked={specifySmallSection} type="radio" name="specifySmallSection" value="yes" onChange={e=> {
                        setSpecifySmallSection(e.target.value === 'yes')
                        if (e.target.value !== 'yes') setEditSmallSection(false);
                    }}/> {globals.strings.common.edit_manual}</label></div>
                </div>
            </div>
            { specifySmallSection && <>
                <div className="row">
                    <div className="padded cell rw-3 rw-md-4 rw-sm-12">
                        <div className="note note-lightest">
                            { globals.strings.common.edit_now }
                        </div>
                    </div>
                    <div className="padded cell rw-9 rw-md-8 rw-sm-12">
                        <div><label className="small"><input checked={!editSmallSection} type="radio" name="editSmallSection" value="default" onChange={e=>setEditSmallSection(e.target.value === 'small')}/> {globals.strings.common.format_default}</label></div>
                        <div><label className="small"><input checked={editSmallSection} type="radio" name="editSmallSection" value="small" onChange={e=>setEditSmallSection(e.target.value === 'small')}/> {globals.strings.common.format_small}</label></div>
                    </div>
                </div>
            </> }
            <div className="row">
                <div className="padded cell rw-4 rw-md-6 rw-sm-12">
                    <div className="small">
                        <strong>{ globals.strings.common.format_upload }</strong>
                        <hr/>
                        {globals.strings.common.info
                            .replace('{x}', `${imageDimensions.x}`)
                            .replace('{y}', `${imageDimensions.y}`)
                            .replace('{size}', byteToText(data.byteLength))
                        }
                    </div>
                </div>
                <div className="padded cell rw-4 rw-md-6 rw-sm-12">
                    <div className="small">
                        <strong>{ globals.strings.common.format_default }</strong>
                        <hr/>
                        <span ref={currentDimensions}></span>
                    </div>
                </div>
                <div className="padded cell rw-4 rw-md-6 rw-sm-12">
                    <div className="small">
                        <strong>{ globals.strings.common.format_small }</strong>
                        <hr/>
                        <span ref={currentDimensionsSmall}>{ globals.strings.common.fallback }</span>
                    </div>
                </div>
                <div data-purpose="avatar-editor-area" className="padded cell rw-12 center">
                    <div className="relative avatar full" style={{touchAction: "none"}}>
                        <img alt="" src={source} style={{maxHeight: '75vh'}}/>
                        <div className={"image-selector" + (!editSmallSection ? ' active' : '')} ref={selector}>
                            <div className="circle-preview"><div/></div>
                            { !specifySmallSection && <div className="small-preview"><div/></div> }
                            { !editSmallSection && <>
                                <div className="move-handle" data-handle-x="+" data-handle-y="+"/>
                                <div className="corner-handle" data-handle-x="1" data-handle-y="1"/>
                                <div className="corner-handle" data-handle-x="1" data-handle-y="-1"/>
                                <div className="corner-handle" data-handle-x="-1" data-handle-y="1"/>
                                <div className="corner-handle" data-handle-x="-1" data-handle-y="-1"/>
                                <div className="edge-handle" data-handle-x="1" data-handle-y="0"/>
                                <div className="edge-handle" data-handle-x="0" data-handle-y="1"/>
                                <div className="edge-handle" data-handle-x="-1" data-handle-y="0"/>
                                <div className="edge-handle" data-handle-x="0" data-handle-y="-1"/>
                            </> }
                        </div>
                        <div className={"image-selector image-selector-small" + (!specifySmallSection ? ' hidden' : '') + (editSmallSection ? ' active' : '')} ref={selectorSmall}>
                            { editSmallSection && <>
                                <div className="move-handle" data-handle-x="+" data-handle-y="+"/>
                                <div className="corner-handle" data-handle-x="1" data-handle-y="1"/>
                                <div className="corner-handle" data-handle-x="1" data-handle-y="-1"/>
                                <div className="corner-handle" data-handle-x="-1" data-handle-y="1"/>
                                <div className="corner-handle" data-handle-x="-1" data-handle-y="-1"/>
                                <div className="edge-handle" data-handle-x="1" data-handle-y="0"/>
                                <div className="edge-handle" data-handle-x="0" data-handle-y="1"/>
                                <div className="edge-handle" data-handle-x="-1" data-handle-y="0"/>
                                <div className="edge-handle" data-handle-x="0" data-handle-y="-1"/>
                            </> }
                        </div>
                    </div>

                </div>
                <div className="padded cell rw-4 rw-md-6 rw-sm-12">
                    <button onClick={()=>{
                        setLoading(true);
                        globals.api.uploadMedia( mime, base64, {
                            x: metaFull.current.x0,
                            y: imageDimensions.y - metaFull.current.y0 - metaFull.current.y1,
                            width: metaFull.current.x1,
                            height: metaFull.current.y1,
                        }, specifySmallSection ? {
                            x: metaSmall.current.x0,
                            y: imageDimensions.y - metaSmall.current.y0 - metaSmall.current.y1,
                            width: metaSmall.current.x1,
                            height: metaSmall.current.y1,
                        } : null, format.current?.value ).then(()=> {
                            confirm();
                            setLoading(false);
                        }).catch(() => setLoading(false));

                    }}>
                        { globals.strings.common.action_upload }
                    </button>
                </div>
                <div className="padded cell rw-4 rw-md-6 rw-sm-12">
                    <button onClick={()=>cancel()}>
                        { globals.strings.common.action_cancel }
                    </button>
                </div>
            </div>
        </> }


    </div>

}