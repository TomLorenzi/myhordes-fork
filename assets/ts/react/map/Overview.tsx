import * as React from "react";

import {
    MapGeometry,
    MapOverviewParentProps,
    MapOverviewParentState,
    MapZone,
    RuntimeMapSettings,
    RuntimeMapStrings
} from "./typedef";

export type MapOverviewParentStateAction = {
    zoom?: number
}

const MapOverviewRoutePainter = ( props: MapOverviewParentProps ) => {
    return (
            <div className="svg">
                <svg viewBox={`${props.map.geo.x0} ${props.map.geo.y0} ${1+(props.map.geo.x1-props.map.geo.x0)} ${1+(props.map.geo.y1-props.map.geo.y0)}`} xmlns="http://www.w3.org/2000/svg">
                    <g x-local-id="map-marker-layer"/>
                    <g x-local-id="map-expedition-layer">
                        <g x-local-id="map-expedition-context"/>
                        <g x-local-id="map-expedition-focus"/>
                        <g x-local-id="map-expedition-live-editor"/>
                    </g>
                </svg>
            </div>
        )
}

type MapOverviewZoneTooltipProps = {
    zone: MapZone,
    strings: RuntimeMapStrings,
}

const MapOverviewZoneTooltip = ( props: MapOverviewZoneTooltipProps ) => {
    return (
        <div className="tooltip tooltip-map">
            { props.zone.r && (
                <div className="row">
                    <div className="cell rw-12 bold">{ props.zone.r.n }</div>
                </div>
            ) }
            <div className="row">
                <div className="cell rw-6 left">{props.strings.zone}</div>
                <div className="cell rw-6 right">[{props.zone.x} / {props.zone.y}]</div>
            </div>
            <div className="row">
                <div className="cell rw-6 left">{props.strings.distance}</div>
                <div className="cell rw-6 right">
                    <div className="ap">{ Math.abs( props.zone.x ) + Math.abs( props.zone.y ) }</div>
                </div>
            </div>
            { (props.zone.c ?? []).length > 0 && (
                <div className="row">
                    { props.zone.c.map((c,i)=><div key={i} className="cell ro-6 rw-6 right">{c}</div>) }
                </div>
            ) }
            { typeof props.zone.d !== "undefined" && props.zone.d > 0 && (
                <div className="row">
                    <div className="cell rw-12">{ typeof props.strings.danger[props.zone.d-1] !== "undefined" ? props.strings.danger[props.zone.d-1] : props.strings.danger[ props.strings.danger.length - 1 ] }</div>
                </div>
            ) }
            { typeof props.strings.tags[ props.zone.tg ?? 0 ] !== "undefined" && props.strings.tags[ props.zone.tg ?? 0 ] && (
                <div className="row">
                    <div className="cell rw-12">{ props.strings.tags[ props.zone.tg ?? 0 ] }</div>
                </div>
            ) }
        </div>
    )
}

type MapOverviewZoneProps = {
    key: string,
    geo: MapGeometry,
    zone: MapZone,
    strings: RuntimeMapStrings,
}

const MapOverviewZone = ( props: MapOverviewZoneProps ) => {
    return (
        <div className={`zone 
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
        }}>
            { props.zone.s && <div className="soul-area"><span/></div> }
            <div className="icon"/>
            <div className="overlay"/>
            { props.zone.tg && <div className={`tag tag-${props.zone.tg}`}/> }
            { props.zone.z && <div className="count">{props.zone.z}</div> }
            { (props.zone.c ?? []).length > 0 && <div className="citizen_marker"/> }
            <MapOverviewZoneTooltip zone={props.zone} strings={props.strings} />
        </div>
    )
}

const MapOverviewParent = ( props: MapOverviewParentProps ) => {

    const [state, dispatch] = React.useReducer((state: MapOverviewParentState, action: MapOverviewParentStateAction): MapOverviewParentState => {
        const new_state = {...state};
        if (typeof action.zoom !== "undefined") new_state.zoom = action.zoom;
        return new_state;
    }, {zoom: 0});

    let cache = {};
    props.map.zones.forEach( zone => cache[`${zone.y}-${zone.x}`] = zone );
    for (let x = props.map.geo.x0; x <= props.map.geo.x1; ++x)
        for (let y = props.map.geo.y0; y <= props.map.geo.y1; ++y)
            if (typeof cache[`${y}-${x}`] === "undefined")
                cache[`${y}-${x}`] = {x,y}

    const cell_num_x = 1+(props.map.geo.x1-props.map.geo.x0);
    const cell_num_y = 1+(props.map.geo.y1-props.map.geo.y0);
    const cell_size = state.zoom === 0 ? '1fr' : `${10 * state.zoom}px`;

    return (
        <div className={'scroll-plane'}>
            <MapOverviewRoutePainter map={props.map} settings={props.settings} strings={props.strings} />
            <div className={'zone-grid'} style={{
                gridTemplateColumns: `repeat(${cell_num_x}, ${cell_size})`,
                gridTemplateRows: `repeat(${cell_num_y}, ${cell_size})`
            }}>
                {Object.entries(cache).map(([k,z]) => <MapOverviewZone key={k} geo={props.map.geo} zone={z as MapZone} strings={props.strings}/>)}
            </div>
        </div>
    )
}

export default MapOverviewParent;
