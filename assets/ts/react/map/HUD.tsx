import * as React from "react";

import {
    LocalControlProps,
    LocalZone,
    LocalZoneProps,
    MapControlProps,
} from "./typedef";

/*
                        {% if zone.x < map.map_x1 %}<div x-direction-x="1"  x-direction-y="0"  x-target-x="{{ zone.x + 1 }}" x-target-y="{{ zone.y }}" class="action-move action-move-danger-{{ arrow_danger_level_e }} action-move-east"></div>{% endif %}
                        {% if zone.x > map.map_x0 %}<div x-direction-x="-1" x-direction-y="0"  x-target-x="{{ zone.x - 1 }}" x-target-y="{{ zone.y }}" class="action-move action-move-danger-{{ arrow_danger_level_w }} action-move-west"></div>{% endif %}
                        {% if zone.y > map.map_y0 %}<div x-direction-x="0"  x-direction-y="-1" x-target-x="{{ zone.x }}" x-target-y="{{ zone.y - 1 }}" class="action-move action-move-danger-{{ arrow_danger_level_s }} action-move-south"></div>{% endif %}
                        {% if zone.y < map.map_y1 %}<div x-direction-x="0"  x-direction-y="1"  x-target-x="{{ zone.x }}" x-target-y="{{ zone.y + 1 }}" class="action-move action-move-danger-{{ arrow_danger_level_n }} action-move-north"></div>{% endif %}
 */

const ZoneControlMovementArrow = ( props: { direction: string, horizontal: boolean, zone: LocalZone|null } ) => {
    return (props.zone === null) ? <></> : (
        <>
            <div className={`action-move action-move-danger-${props.zone.se ?? 0} action-move-${props.direction}`}/>
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
    return (
        <div className={`zone-plane-controls ${props.fx ? 'retro' : ''} ${props.movement ? '' : 'blocked'}`}>
            <div className="marker-direction"/>
            <ZoneControlMovementArrow direction={'east'}  zone={props.planes.e} horizontal={true}/>
            <ZoneControlMovementArrow direction={'west'}  zone={props.planes.w} horizontal={true}/>
            <ZoneControlMovementArrow direction={'north'} zone={props.planes.n} horizontal={false}/>
            <ZoneControlMovementArrow direction={'south'} zone={props.planes.s} horizontal={false}/>
            { typeof props.planes["0"]?.x !== "undefined" && typeof props.planes["0"]?.y !== "undefined" && (
                <div className="current-location">{`${props.strings.position} ${props.planes["0"].x} / ${props.planes["0"].y}`}</div>
            ) }

        </div>
    )
}

export default ZoneControlParent;
