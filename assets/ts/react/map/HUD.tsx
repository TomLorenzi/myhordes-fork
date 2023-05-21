import * as React from "react";

import {
    LocalControlProps,
    LocalZone,
    MapCoordinate,
} from "./typedef";

import {useContext, useRef} from "react";
import {Globals} from "./Wrapper";

type ZoneControlArrowProps = {
    direction: string;
    horizontal: boolean;
    zone: LocalZone|null;
    onRoute: boolean;
    onClick: ()=>void,
}

const ZoneControlMovementArrow = ( props: ZoneControlArrowProps ) => {
    return (props.zone === null) ? <></> : (
        <>
            <div onClick={props.onClick}
                 className={`action-move action-move-danger-${props.zone.se ?? 0} action-move-${props.direction} ${props.onRoute ? 'on-route' : ''}`}
            />
            { typeof props.zone.ss !== "undefined" && (
                <div className={`scavenger-sense scavenger-sense-${props.direction} scavenger-sense-${props.zone.ss ? '1' : '0'}`}>
                    <div className="img"/>
                </div>
            )}
            { typeof props.zone.sh !== "undefined" && (
                <div className={`scout-sense scout-sense-${props.direction}`}>
                    <svg viewBox={`0 0 ${props.horizontal ? '14 39' : '39 14'}`} xmlns="http://www.w3.org/2000/svg">
                        <text x={props.horizontal ? 8 : 19.5} y={props.horizontal ? 22.5 : 9} textAnchor="middle" fontSize={12} fontFamily={'visitor2, sans-serif'} fill={'#a0be40'}>{props.zone.sh}</text>
                    </svg>
                </div>
            ) }
        </>
    )
}

const ZoneControlParent = ( props: LocalControlProps ) => {
    const globals = useContext(Globals)

    let m = false;

    let routeDirectionCache = {n:false,s:false,e:false,w:false};

    const between = (a:number,b:number,v:number) => Math.min(a,b) <= v && Math.max(a,b) >= v;
    const distance = (a:MapCoordinate,b:MapCoordinate) => Math.abs(a.x-b.x) + Math.abs(a.y-b.y);
    const count_recs = (r:{n:boolean,s:boolean,e:boolean,w:boolean}) =>
        (r.n?1:0) + (r.s?1:0) + (r.w?1:0) + (r.e?1:0);

    type mf=(f:MapCoordinate)=>void;
    const move = (a:MapCoordinate,b:MapCoordinate,f:mf) => {
        if (a.x<b.x&&a.y===b.y) for (let x = a.x; x <= b.x; ++x) f({x,y:a.y});
        if (a.x>b.x&&a.y===b.y) for (let x = a.x; x >= b.x; --x) f({x,y:a.y});
        if (a.x===b.x&&a.y<b.y) for (let y = a.y; y <= b.y; ++y) f({x:a.x,y});
        if (a.x===b.x&&a.y>b.y) for (let y = a.y; y >= b.y; --y) f({x:a.x,y});
        if (a.x===b.x&&a.y===b.y) f({x:a.x,y:a.y});
    }
    
    if (typeof props.planes["0"].x !== "undefined" && typeof props.planes["0"].y !== "undefined" && (props.activeRoute?.stops ?? []).length > 0) {
        const zone = props.planes["0"] as MapCoordinate;
        const stops = props.activeRoute.stops;

        // Check if we're on route
        stops.forEach( (co,i) => {
            if (i > 0) {
                if (co.x === zone.x && between(co.y,stops[i-1].y,zone.y)) {
                    if (zone.y < co.y) routeDirectionCache.n = m = true;
                    if (zone.y > co.y) routeDirectionCache.s = m = true;
                }
                if (co.y === zone.y && between(co.x,stops[i-1].x,zone.x)) {
                    if (zone.x < co.x) routeDirectionCache.e = m = true;
                    if (zone.x > co.x) routeDirectionCache.w = m = true;
                }
            }
        } )

        // Check if we're on the last stop of route. In this case, we won't show next direction.
        // (direction for simple routes on last stop we'll be shown because we'll find out that we're on a route)
        let onLastRouteStop = false;
        if (stops[stops.length - 1].x === zone.x && stops[stops.length - 1].y === zone.y) {
            onLastRouteStop = true;
        }
        
        // We're not on route and it's not the last stop. Try to find the way back
        if (!m && !onLastRouteStop) {

            let d = null; let closest = null;

            // Find the closest route point
           stops.forEach( (co,index)=> {
                if (index > 0) move( stops[index-1], stops[index], co => {
                    const dt = distance(co,zone);
                    if (d === null || dt <= d) { d = dt; closest = co; }
                } );
            } )

            const dx = Math.abs( zone.x - closest.x );
            const dy = Math.abs( zone.y - closest.y );

            if (dx >= dy) routeDirectionCache.w = !(routeDirectionCache.e = zone.x < closest.x);
            if (dx <= dy) routeDirectionCache.s = !(routeDirectionCache.n = zone.y < closest.y);
        }

        // We have multiple directional suggestions; cross-reference them with the zones the player has already visited
        if (count_recs(routeDirectionCache) > 1) {
            let tmp = {
                n: routeDirectionCache.n && !props.planes.n.vv,
                s: routeDirectionCache.s && !props.planes.s.vv,
                e: routeDirectionCache.e && !props.planes.e.vv,
                w: routeDirectionCache.w && !props.planes.w.vv,
            };
            if (count_recs(tmp) === 1) routeDirectionCache = tmp;
        }
    }

    const _rev_rotation = useRef(null);

    let marker_rotation = null;
    if (props.marker && typeof props.planes["0"].x !== "undefined") {
        const d_x = props.marker.x - props.planes["0"].x - props.dx;
        const d_y = props.planes["0"].y - props.marker.y + props.dy;

        if (d_x !== 0 || d_y !== 0) {
            let angle = Math.round(Math.acos( d_y / Math.sqrt( d_x*d_x + d_y*d_y ) ) * 57.2957795);
            if (d_x > 0) angle = 360 - angle;

            if (_rev_rotation.current !== null && Math.abs( _rev_rotation.current - angle ) >= 180) {
                const rot_count = Math.floor(_rev_rotation.current / 360.0);
                angle = [
                    angle + (rot_count - 1) * 360,
                    angle + (rot_count    ) * 360,
                    angle + (rot_count + 1) * 360,
                ].sort( (a,b) => Math.abs( _rev_rotation.current - a) - Math.abs( _rev_rotation.current - b) )[0];
            }

            marker_rotation = { transform: `rotate(${_rev_rotation.current = angle}deg)` }
        }
    }

    return (
        <div className={`zone-plane-controls ${props.fx ? 'retro' : ''} ${props.movement ? '' : 'blocked'}`}>
            { marker_rotation !== null && (
                <div style={marker_rotation} className="marker-direction"/>
            ) }
            { props.movement && props.dx === 0 && props.dy === 0 && (
                <>
                    <ZoneControlMovementArrow onClick={()=>props.wrapDispatcher({moveto: {x:props.planes["0"].x+1, y:props.planes["0"].y, dx:  1, dy:  0}})}
                                              direction={'east'}  zone={props.planes.e} onRoute={routeDirectionCache.e} horizontal={true}/>
                    <ZoneControlMovementArrow onClick={()=>props.wrapDispatcher({moveto: {x:props.planes["0"].x-1, y:props.planes["0"].y, dx: -1, dy:  0}})}
                                              direction={'west'}  zone={props.planes.w} onRoute={routeDirectionCache.w} horizontal={true}/>
                    <ZoneControlMovementArrow onClick={()=>props.wrapDispatcher({moveto: {x:props.planes["0"].x, y:props.planes["0"].y+1, dx:  0, dy:  1}})}
                                              direction={'north'} zone={props.planes.n} onRoute={routeDirectionCache.n} horizontal={false}/>
                    <ZoneControlMovementArrow onClick={()=>props.wrapDispatcher({moveto: {x:props.planes["0"].x, y:props.planes["0"].y-1, dx:  0, dy: -1}})}
                                              direction={'south'} zone={props.planes.s} onRoute={routeDirectionCache.s} horizontal={false}/>
                </>
            ) }
            { typeof props.planes["0"]?.x !== "undefined" && typeof props.planes["0"]?.y !== "undefined" && props.dx === 0 && props.dy === 0 && (
                <div className="current-location">{`${globals.strings.position} ${props.planes["0"].x} / ${props.planes["0"].y}`}</div>
            ) }
        </div>
    )
}

export default ZoneControlParent;
