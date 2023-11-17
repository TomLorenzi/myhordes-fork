import * as React from "react";

import {
    LocalZone,
    MapCoordinate,
    MapGeometry, MapOverviewGridProps,
    MapOverviewParentProps,
    MapZone,
    RuntimeMapSettings, RuntimeMapStateAction
} from "./typedef";
import {useContext, useEffect, useLayoutEffect, useRef, useState} from "react";
import {Tooltip} from "../tooltip/Wrapper";
import {Globals} from "./Wrapper";

export type MapOverviewParentStateAction = {
    zoom?: number
}

const RouteRenderer = ( props: { route: MapCoordinate[], color: string, secondaryColor: string, opacity: number, simple: boolean, id: string } ) => {
    let pt_last = props.simple ? {x:0,y:0} : null;
    let last_pt = props.route.length > 0 ? props.route[ props.route.length-1 ] : null;

    let known = [];

    return (
        <>
            <defs>
                <g id={`mapsvg-${props.id}-content`}>
                    { props.route.map( (c,i) => {
                        const r = pt_last !== null
                            ? <React.Fragment key={`r${i}`}>
                                <line x1={pt_last.x + 0.5} x2={c.x + 0.5} y1={pt_last.y + 0.5} y2={c.y + 0.5} strokeWidth={0.19} strokeOpacity={props.opacity}/>
                            </React.Fragment>
                            : null;
                        pt_last = c;
                        return r;
                    } ) }
                    { props.simple && (
                        <>
                            { last_pt && last_pt.x !== last_pt.y && ( last_pt.x === 0 || last_pt.y === 0 ) && (
                                <line x1={last_pt.x + 0.5} x2={0.5} y1={last_pt.y + 0.5} y2={0.5} strokeWidth={0.15} strokeOpacity={props.opacity} strokeDasharray={'0.1'}/>
                            ) }
                            <rect x={0.25} y={0.25} width={0.5} height={0.5} rx={0.1} strokeWidth={0}/>
                        </>
                    ) }
                    { props.route.map( (c,i) =>
                        <React.Fragment key={`c${i}`}>
                            <rect x={c.x+0.25} y={c.y+0.25} width={0.5} height={0.5} rx={0.1} strokeWidth={0}/>
                        </React.Fragment>
                    ) }
                </g>
                <g id={`mapsvg-${props.id}-labels`}>
                    { props.route.map( (c,i) =>
                        !known.includes(`${c.x}-${c.y}`) && (known.push(`${c.x}-${c.y}`) || true) && i > 0 && <React.Fragment key={`c${i}`}>
                            <rect x={c.x+0.29} y={c.y+0.29} width={0.42} height={0.42} rx={0.1} strokeWidth={0}/>
                            <g transform={`translate(${c.x+0.5},${c.y+0.425})`}>
                                <text x={0} y={0} style={{transform: 'scaleY(-1)'}} textAnchor="middle" alignmentBaseline="central" strokeWidth={0.025} fontSize={0.3}>
                                    {i}
                                </text>
                            </g>
                        </React.Fragment>
                    ) }
                </g>
            </defs>
            <use href={`#mapsvg-${props.id}-content`} stroke="black" fill="black" filter="url(#mapsvg-blur)"/>
            <use href={`#mapsvg-${props.id}-content`} stroke={props.color} fill={props.color}/>
            <use href={`#mapsvg-${props.id}-labels`} stroke={props.color} fill={props.secondaryColor}/>
        </>
    )
}

const MapOverviewRoutePainter = ( props: MapOverviewParentProps ) => {
    return (
            <div className="svg">
                <svg viewBox={`${props.map.geo.x0} ${props.map.geo.y0} ${1+(props.map.geo.x1-props.map.geo.x0)} ${1+(props.map.geo.y1-props.map.geo.y0)}`} preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg" xmlnsXlink="http://www.w3.org/1999/xlink">
                    <filter id="mapsvg-blur">
                        <feGaussianBlur stdDeviation="0.1" />
                    </filter>
                    {props.marking && (
                        <rect x={props.marking.x} y={props.marking.y}
                              height={1} width={1}
                              fill={'transparent'} opacity={0.5} strokeWidth={0.08} stroke={'white'}
                        />
                    )}
                    <RouteRenderer route={props.routeViewer} color={'#b4da4c'} secondaryColor={'#2e3a0c'} opacity={1} simple={false} id="selected"/>
                    { props.settings.enableSimpleZoneRouting && (
                        <RouteRenderer route={props.routeEditor} color={'white'} secondaryColor={'#373737'} opacity={0.5}
                                       simple={!props.settings.enableComplexZoneRouting} id="editor"
                        />
                    ) }
                </svg>
            </div>
        )
}

type MapOverviewZoneTooltipProps = {
    zone: MapZone,
    local: LocalZone|null
}

const MapOverviewZoneTooltip = ( props: MapOverviewZoneTooltipProps ) => {
    const globals = useContext(Globals)

    const [horror, setHorror] = useState<string>(null)
    const timer = useRef<number>(null);

    const getHorrorValue = () => (globals.strings.horror ?? [null])[ Math.floor( Math.random() * (globals.strings.horror?.length ?? 0) ) ];

    useEffect(() => {
        if (horror) timer.current = window.setTimeout( () => setHorror( null ), 500 );
        return () => {
            if (timer.current) {
                window.clearTimeout( timer.current );
                timer.current = null;
            }
        };
    }, [horror])

    const displayLocalDistance = props.local && (props.local.x !== 0 || props.local.y !== 0) && (props.local.x !== props.zone.x || props.local.y !== props.zone.y);

    return (
        <Tooltip additionalClasses="tooltip-map"
                 onShowTooltip={ () => (Math.random() > 0.98) && setHorror( getHorrorValue() ) }
                 onHideTooltip={ () => horror && setHorror(null) }
        >
            { horror }
            { !horror &&
                <>
                    { props.zone.r && (
                        <div className="row">
                            <div className="cell rw-12 bold">{ props.zone.r.n }</div>
                        </div>
                    ) }
                    <div className="row">
                        <div className="cell rw-6 left">{globals.strings.zone}</div>
                        <div className="cell rw-6 right">[{props.zone.x} / {props.zone.y}]</div>
                    </div>
                    { (!displayLocalDistance || props.zone.x !== 0 || props.zone.x !== 0 ) && (
                        <div className="row">
                            <div className="cell rw-9 left">{displayLocalDistance ? globals.strings.distanceTown : globals.strings.distance}</div>
                            <div className="cell rw-3 right">
                                <div className="ap">{ Math.abs( props.zone.x ) + Math.abs( props.zone.y ) }</div>
                            </div>
                        </div>
                    ) }
                    { displayLocalDistance && (props.local.x !== 0 || props.local.y !== 0) && (
                        <div className="row">
                            <div className="cell rw-9 left">{globals.strings.distanceSelf}</div>
                            <div className="cell rw-3 right">
                                <div className="ap">{ Math.abs( props.zone.x - props.local.x ) + Math.abs( props.zone.y - props.local.y ) }</div>
                            </div>
                        </div>
                    ) }
                    { (props.zone.c ?? []).length > 0 && (
                        <div className="row">
                            { props.zone.c.map((c,i)=><div key={i} className="cell ro-6 rw-6 right">{c}</div>) }
                        </div>
                    ) }
                    { typeof props.zone.d !== "undefined" && props.zone.d > 0 && (
                        <div className="row">
                            <div className="cell rw-12">{ typeof globals.strings.danger[props.zone.d-1] !== "undefined" ? globals.strings.danger[props.zone.d-1] : globals.strings.danger[ globals.strings.danger.length - 1 ] }</div>
                        </div>
                    ) }
                    { typeof globals.strings.tags[ props.zone.tg ?? 0 ] !== "undefined" && globals.strings.tags[ props.zone.tg ?? 0 ] && (
                        <div className="row">
                            <div className="cell rw-12">{ globals.strings.tags[ props.zone.tg ?? 0 ] }</div>
                        </div>
                    ) }
                </>
            }
        </Tooltip>
    )
}

type MapOverviewZoneProps = {
    key: string,
    geo: MapGeometry,
    zone: MapZone,
    local: LocalZone|null
    conf: RuntimeMapSettings,
    wrapDispatcher: (RuntimeMapStateAction)=>void
}

const MapOverviewZone = ( props: MapOverviewZoneProps ) => {
    const click_handler = e=>{
        let data: RuntimeMapStateAction = {};

        if (props.conf.enableZoneMarking)
            data.activeZone = props.zone;

        if (props.conf.enableSimpleZoneRouting)
            data.routeEditorPush = props.zone;

        if (Object.entries(data).length > 0) props.wrapDispatcher(data);

        e.target.closest('hordes-map').dispatchEvent( new CustomEvent('zone-clicked', { bubbles: true, detail: { zone: props.zone }}) );
    };

    return (
        <div onClick={click_handler} className={`zone 
            ${typeof props.zone.td !== "undefined" ? `town ${props.zone.td ? 'devast' : ''}` : ''}
            ${props.zone.cc ? 'active' : ''}
            ${typeof props.zone.t  !== "undefined" ? (props.zone.t ? '' : 'past') : 'unknown'}
            ${props.zone.g ? 'global' : ''}
            ${(typeof props.zone.r !== "undefined" && typeof props.zone.td === "undefined") ? `ruin ${props.zone.r.b ? 'buried' : ''}` : ''}
            ${typeof props.zone.d  !== "undefined" ? `danger-${props.zone.d}` : ''}
            ${props.zone.s ? 'soul' : ''}
        `} style={{
            gridColumn: 1 + props.zone.x - props.geo.x0,
            gridRow: 1 + (props.geo.y1 - props.geo.y0) - (props.zone.y - props.geo.y0)
        }} x-id={props.zone.id}>
            { props.zone.s && <div className="soul-area"><span/></div> }
            <div className="icon"/>
            <div className="overlay"/>
            { props.zone.tg && <div className={`tag tag-${props.zone.tg}`}/> }
            { props.zone.z && <div className="count">{props.zone.z}</div> }
            { (props.zone.scoutLevel || props.zone.fractional) && <div className={`scoutLevelMark scout-${props.zone.scoutLevel}`}/> }
            { (props.zone.c ?? []).length > 0 && <div className="citizen_marker"/> }
            <MapOverviewZoneTooltip zone={props.zone} local={props.local} />
        </div>
    )
}

const MapOverviewGrid = React.memo(( props: MapOverviewGridProps ) => {
    let cache = {};
    props.map.zones.forEach( zone => cache[`${zone.y}-${zone.x}`] = zone );
    for (let x = props.map.geo.x0; x <= props.map.geo.x1; ++x)
        for (let y = props.map.geo.y0; y <= props.map.geo.y1; ++y)
            if (typeof cache[`${y}-${x}`] === "undefined")
                cache[`${y}-${x}`] = {x,y}

    const cell_num_x = 1+(props.map.geo.x1-props.map.geo.x0);
    const cell_num_y = 1+(props.map.geo.y1-props.map.geo.y0);
    const cell_size = props.zoom === 0 ? '1fr' : `${10 * (1+props.zoom)}px`;

    return (
            <div className={'zone-grid'} style={{
                gridTemplateColumns: `repeat(${cell_num_x}, ${cell_size})`,
                gridTemplateRows: `repeat(${cell_num_y}, ${cell_size})`
            }}>
                {Object.entries(cache).map(([k,z]) =>
                    <MapOverviewZone key={k} geo={props.map.geo} local={(props.map.local ?? []).filter(v => v.xr === 0 && v.yr === 0)[0] ?? null}
                                     zone={z as MapZone} conf={props.settings}
                                     wrapDispatcher={props.wrapDispatcher}
                    />)}
            </div>
        )
}, (prevProps:MapOverviewGridProps, nextProps:MapOverviewGridProps) => {
    if (prevProps.zoom !== nextProps.zoom || prevProps.etag !== nextProps.etag || prevProps.map?.lid !== nextProps.map?.lid) return false;
    return Object.entries(prevProps.settings).map(([k,v]) => nextProps.settings[k] === v).filter(v=>!v).length === 0;
});

const MapOverviewParent = ( props: MapOverviewParentProps ) => {

    const resetPlanePosition = () => {
        props.scrollAreaRef.current.style.left = props.scrollAreaRef.current.style.top =
            props.scrollAreaRef.current.dataset.ox = props.scrollAreaRef.current.dataset.oy = '0';
    }

    const setPlanePosition = (x: number, y: number, check_bounds: boolean = true) => {
        props.scrollAreaRef.current.dataset.ox = `${x}`;
        props.scrollAreaRef.current.dataset.oy = `${y}`;
        props.scrollAreaRef.current.style.left = `${x}px`;
        props.scrollAreaRef.current.style.top  = `${y}px`;
        if (check_bounds) props.scrollAreaRef.current.dispatchEvent(new CustomEvent('_mv_bounds'));
    }

    useLayoutEffect(()=>{
        const movement_bounds = ()=>{
            let update = false;
            let ox = parseFloat(props.scrollAreaRef.current.dataset.ox);
            let oy = parseFloat(props.scrollAreaRef.current.dataset.oy);

            let mx = 10 + props.scrollAreaRef.current.clientWidth - props.scrollAreaRef.current.parentElement.clientWidth;
            let my = 10 + props.scrollAreaRef.current.clientHeight - props.scrollAreaRef.current.parentElement.clientHeight;

            if (ox > 10) { ox = 10; update = true; }
            if (oy > 10) { oy = 10; update = true; }

            if (ox < -mx) { ox = -mx; update = true; }
            if (oy < -my) { oy = -my; update = true; }

            if (update) setPlanePosition(ox,oy,false);
        }

        const movement_center = ()=>{
            const refNode = (props.scrollAreaRef.current?.querySelector('.zone.active') ??
                props.scrollAreaRef.current?.querySelector('.zone.town')) as HTMLDivElement;

            if (!refNode || props.zoom === 0)
                resetPlanePosition();
            else
                setPlanePosition(
                    -((refNode.offsetLeft + refNode.clientWidth / 2) - (props.scrollAreaRef.current.parentElement.clientWidth )/2),
                    -((refNode.offsetTop + refNode.clientHeight / 2) - (props.scrollAreaRef.current.parentElement.clientHeight)/2)
                );
        }

        if (props.scrollAreaRef.current) {
            props.scrollAreaRef.current.addEventListener('_mv_bounds', movement_bounds);
            props.scrollAreaRef.current.addEventListener('_mv_center', movement_center);
            return ()=>{
                props.scrollAreaRef.current.removeEventListener('_mv_bounds', movement_bounds);
                props.scrollAreaRef.current.removeEventListener('_mv_center', movement_center);
            }
        } else return ()=>{}
    });

    useLayoutEffect(() => {
        if (props.zoomChanged && props.scrollAreaRef.current)
            props.scrollAreaRef.current.dispatchEvent(new CustomEvent('_mv_center'));
        return ()=>{};
    });

    let activePointer: number|null = null;
    const down=e=>{ if (activePointer === null) activePointer = e.pointerId; e.preventDefault(); }
    const move=e=>{
        if (activePointer === e.pointerId) {
            let ox = parseFloat(props.scrollAreaRef.current.dataset.ox) ?? 0;
            let oy = parseFloat(props.scrollAreaRef.current.dataset.oy) ?? 0;

            setPlanePosition( ox+e.movementX, oy+e.movementY );
        }
        e.preventDefault();
    }
    const up=e=>{ if (activePointer === e.pointerId) activePointer = null; e.preventDefault(); }

    return (
        <div ref={props.scrollAreaRef} className={`scroll-plane ${props.zoom === 0 ? 'auto-size' : ''}`}
             onPointerDown={props.zoom > 0 ? down : null} onPointerMove={props.zoom > 0 ? move : null}
             onPointerUp={props.zoom > 0 ? up : null} onPointerLeave={props.zoom > 0 ? up : null}
        >
            <MapOverviewRoutePainter map={props.map} settings={props.settings}
                                     scrollAreaRef={props.scrollAreaRef} zoomChanged={props.zoomChanged}
                                     marking={props.marking} wrapDispatcher={props.wrapDispatcher} etag={props.etag}
                                     routeEditor={props.routeEditor} routeViewer={props.routeViewer} zoom={props.zoom}
            />
            <MapOverviewGrid map={props.map} settings={props.settings} marking={props.marking}
                             wrapDispatcher={props.wrapDispatcher} routeEditor={props.routeEditor} etag={props.etag}
                             zoom={props.zoom} routeViewer={props.routeViewer} scrollAreaRef={props.scrollAreaRef}
                             zoomChanged={props.zoomChanged}/>
        </div>
    )
}

export default MapOverviewParent;
