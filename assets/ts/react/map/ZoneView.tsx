import * as React from "react";

import {
    LocalZone,
    LocalZoneProps, LocalZoneSurroundings,
    MapControlProps,
} from "./typedef";
import ZoneControlParent from "./HUD";
import {useContext} from "react";
import {Globals} from "./Wrapper";

const LocalZone = ( props: { zone: LocalZone, key: string } ) => {

    const defaultPositions:{x:number,y:number}[] = [];
    const [citizenPositions, setCitizenPositions] = React.useState(defaultPositions);
    const [zombiePositions, setZombiePositions]   = React.useState(defaultPositions);
    const [currentZone, setCurrentZone] = React.useState(props.zone);

    if (currentZone.xr === props.zone.xr && currentZone.yr === props.zone.yr) {
        if ( citizenPositions.length > (props.zone.c??0) ) setCitizenPositions( citizenPositions.slice(0, (props.zone.c??0)) );
        if ( zombiePositions.length  > ((props.zone.z??0) + (props.zone.zc??0)) ) setZombiePositions( zombiePositions.slice(0, (props.zone.z??0) + (props.zone.zc??0)) );

        if ( citizenPositions.length < (props.zone.c??0) ) {
            let tmp = [...citizenPositions];
            while (tmp.length < (props.zone.c??0)) tmp.push({ x: Math.random() * 40.0 + 35, y: Math.random() * 40.0 + 35} );
            setCitizenPositions( tmp );
        }

        if ( zombiePositions.length < ((props.zone.z??0) + (props.zone.zc??0)) ) {
            let tmp = [...zombiePositions];
            while (tmp.length < ((props.zone.z??0) + (props.zone.zc??0))) tmp.push({ x: Math.random() * 80.0 + 15, y: Math.random() * 80.0 + 15} );
            setZombiePositions( tmp );
        }
    } else {
        setCurrentZone(props.zone);
        setCitizenPositions([]);
        setZombiePositions([]);
    }

    let actors = [];
    citizenPositions.forEach( (p,i) => actors.push(<div key={`c${i}`} data-z={p.y} className="actor citizen" style={{left: `${p.x}%`, top: `${p.y}%`}}/>));
    zombiePositions.forEach(  (p,i) => actors.push(<div key={`z${i}`} data-z={p.y - (i<props.zone.z ? 0 : 666)} className={`actor ${i<props.zone.z ? 'zombie' : 'splatter'}`} style={{left: `${p.x}%`, top: `${p.y}%`}}/>));

    actors.sort( (a,b) => a.props['data-z'] - b.props['data-z'] )

    return (
            <div className={`zone-subplane ${(props.zone.xr === 0 && props.zone.yr === 0) ? 'center' : ''}`}>
                {props.zone.r && (
                    <div className="ruin" style={{backgroundImage: `url("${props.zone.r}")`}}/>
                )}
                { actors }
                { props.zone.n && (
                    <div className="hovertext">
                        <span>{props.zone.n}</span>
                    </div>
                ) }
            </div>
    );

}

const LocalCensorZone = ( props: { zone: LocalZone, key: string } ) => {
    return (
        <div className={`zone-subplane ${(props.zone.xr === 0 && props.zone.yr === 0) ? 'center' : ''}`}>
            { !props.zone.v && (
                <div className="censor"/>
            )}
        </div>
    );
}

const LocalZoneGrid = ( props: { cache: { [key: string]: LocalZone; } } ) => {
    const bar = [-2,-1,0,1,2];
    let zones = [];
    let censor = [];

    bar.forEach( yr =>  bar.forEach( xr => {
        zones.push(<LocalZone zone={props.cache[`${-yr}-${xr}`]} key={`${-yr}-${xr}`}/>)
        censor.push(<LocalCensorZone zone={props.cache[`${-yr}-${xr}`]} key={`${-yr}-${xr}`}/>)
    } ));

    return (
        <>
            <div className="zone-sub-container">{ zones }</div>
            <div className="zone-sub-container censor-master">{ censor }</div>
        </>
    )
}

const LocalZoneView = ( props: LocalZoneProps ) => {
    let cache = {};
    let surroundings: LocalZoneSurroundings = {n:null,s:null,e:null,w:null,'0':null};

    props.plane.forEach( zone => {
        cache[`${zone.yr}-${zone.xr}`] = zone;
        if (zone.xr * zone.yr === 0) {
            if (zone.xr === 1)  surroundings.e=zone;
            if (zone.xr === -1) surroundings.w=zone;
            if (zone.yr === 1)  surroundings.n=zone;
            if (zone.yr === -1) surroundings.s=zone;
            if (zone.xr === zone.yr) surroundings['0']=zone;
        }
    } );

    const bar = [-2,-1,0,1,2];
    bar.forEach( yr => bar.forEach( xr => {
        if (typeof cache[`${yr}-${xr}`] === "undefined")
            cache[`${yr}-${xr}`] = {xr,yr}
    }) )

    let style={
        left: `${-200 + props.dx * -40}%`,
        top:  `${-200 + props.dy *  40}%`,
    }

    return (
        <>
            <div className={`zone-plane ${props.fx ? 'retro' : ''}`} style={style}>
                { (props.fx ? [0,1,2,3,4] : []).map(i => <div key={i} className="retro-effect hide-lg hide-md hide-sm"/>) }
                <LocalZoneGrid cache={cache}/>
            </div>
            <ZoneControlParent fx={props.fx} movement={props.movement} planes={surroundings}
                               activeRoute={props.activeRoute} wrapDispatcher={props.wrapDispatcher} marker={props.marker}
                               dx={props.dx} dy={props.dy}
            />
        </>

    )
}

export default LocalZoneView;
