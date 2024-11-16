import * as React from "react";

import {BaseMounter, ReactData} from "../index";

import {
    MapCoordinate,
    MapData, MapRoute,
    RuntimeMapState,
    RuntimeMapStateAction
} from "./typedef";
import MapOverviewParent from "./Overview";
import MapRouteList from "./RouteList";
import MapControls from "./Controls";
import {useEffect, useLayoutEffect, useRef, useState} from "react";
import {Global} from "../../defaults";
import LocalZoneView from "./ZoneView";
import Client from "../../client";
import {BeyondMapAPI, RuntimeMapStrings} from "./api";

declare var $: Global;

interface ReactDataMapCore extends ReactData {}

interface EventDataPlanningBegin { complex: boolean }
interface EventDataSelectRoute { route: number|boolean }

const validRoute = (route: MapCoordinate[], complex: boolean): boolean => {
    if (route.length < 2) return false;

    let routeCopy = [...route];
    if (!complex) {
        routeCopy.splice(0,0, {x: 0, y: 0});
        routeCopy.push({x: 0, y: 0})
    }

    let valid = true, last_c = routeCopy[0];
    routeCopy.forEach( c => {
        valid = valid && (last_c.x === c.x || last_c.y === c.y);
        last_c = c;
    } )
    return valid;
}

const validateRouteStep = (route: MapCoordinate[], next: MapCoordinate, complex: boolean): boolean => {
    if (route.length === 0) return complex || ( (next.x === 0 || next.y === 0) && next.x !== next.y );

    let last = route[route.length - 1];
    return (last.x === next.x || last.y === next.y) && (last.x !== next.x || last.y !== next.y);
}

const processRoute = (route: MapCoordinate[], complex: boolean) => {
    let routeCopy = [...route];
    if (!complex) {
        routeCopy.splice(0,0, {x: 0, y: 0});
        routeCopy.push({x: 0, y: 0})
    }
    return routeCopy.map(c => [c.x,c.y]);
}

export class HordesMap extends BaseMounter<object> {
    protected render(props: object): React.ReactNode {
        return <MapWrapper {...$.components.kickstart(this.parent,props) as ReactData}/>;
    }
}

type MapGlobals = {
    //api: EventCreationAPI,
    strings: RuntimeMapStrings,
    etag: number,
}

export const Globals = React.createContext<MapGlobals>(null);

const MapWrapper = ( props: ReactDataMapCore ) => {
    let mk = $.client.get('marker','routes',null, Client.DomainScavenger);
    if (!mk) mk = undefined;
    else mk = {x: mk[0] ?? 0, y: mk[1] ?? 0}

    const scrollPlaneRef = useRef<HTMLDivElement>(null);
    let dx = 0, dy = 0;

    const [strings, setStrings] = useState<RuntimeMapStrings>( null );
    const [map, setMap] = useState<MapData>( null );
    const [routes, setRoutes] = useState<MapRoute[]>( [] );
    const [inc, setInc] = useState<number>( 0 );

    const [state, dispatch] = React.useReducer((state: RuntimeMapState, action: RuntimeMapStateAction): RuntimeMapState => {
        const new_state = {...state};
        if (typeof action.configure   !== "undefined") new_state.conf        = action.configure;
        if (typeof action.showPanel   !== "undefined") new_state.showPanel   = action.showPanel;
        if (typeof action.showViewer  !== "undefined") new_state.showViewer  = action.showViewer;
        if (typeof action.zoom        !== "undefined") {
            new_state.zoomChanged = new_state.zoom !== action.zoom
            new_state.zoom = action.zoom;
        } else new_state.zoomChanged = false;
        if (typeof action.markEnabled !== "undefined") {
            new_state.markEnabled = action.markEnabled;
            $.client.set('map', 'tags', new_state.markEnabled ? 'show' : 'hide', true);
        }
        if (typeof action.globalEnabled !== "undefined") {
            new_state.globalEnabled = action.globalEnabled;
            $.client.set('map', 'global', new_state.markEnabled ? 'show' : 'hide', true);
        }
        if (typeof action.scoutEnabled !== "undefined") {
            new_state.scoutEnabled = action.scoutEnabled;
            $.client.set('map', 'scout', new_state.markEnabled ? 'show' : 'hide', true);
        }
        if (typeof action.activeRoute !== "undefined") {
            new_state.activeRoute = action.activeRoute === false ? undefined : action.activeRoute as number;
            $.client.set('current','routes', new_state.activeRoute, false);
            props.eventGateway('route-selected', {route: new_state.activeRoute ?? null});
        }
        if (typeof action.activeZone  !== "undefined") {
            if (action.activeZone === false ) new_state.activeZone = undefined;
            else if (action.activeZone !== true ) {
                if (typeof new_state.activeZone  === "undefined") new_state.activeZone = action.activeZone;
                else if ( new_state.activeZone.x !== action.activeZone.x || new_state.activeZone.y !== action.activeZone.y )
                    new_state.activeZone = action.activeZone;
                else new_state.activeZone = undefined;
            }
            $.client.set('marker','routes',new_state.activeZone ? [new_state.activeZone.x,new_state.activeZone.y] : null, false)
        }
        if (typeof action.routeEditorPop  !== "undefined") {
            new_state.routeEditor = action.routeEditorPop === true ? [] : new_state.routeEditor.slice(0,-1);
            props.eventGateway('planner-step', { valid: validRoute(new_state.routeEditor, new_state.conf.enableComplexZoneRouting), route: processRoute(new_state.routeEditor, new_state.conf.enableComplexZoneRouting) });
        }
        if (typeof action.routeEditorPush !== "undefined" && validateRouteStep(new_state.routeEditor, action.routeEditorPush, new_state.conf.enableComplexZoneRouting)) {
            new_state.routeEditor = new_state.routeEditor.slice(0);
            new_state.routeEditor.push(action.routeEditorPush);
            props.eventGateway('planner-step', { valid: validRoute(new_state.routeEditor, new_state.conf.enableComplexZoneRouting), route: processRoute(new_state.routeEditor, new_state.conf.enableComplexZoneRouting) });
        }
        if (typeof action.moveto !== "undefined") {
            dx = action.moveto.dx;
            dy = action.moveto.dy;
            props.eventGateway('player-movement', {x:action.moveto.x, y:action.moveto.y});
        } else dx = dy = 0;

        return new_state;
    }, {
        markEnabled: $.client.get('map', 'tags', 'hide', Client.DomainScavenger) === 'show',
        globalEnabled: $.client.get('map', 'global', 'hide', Client.DomainScavenger) === 'show' || props.data.displayType.split('-')[0] !== 'beyond',
        scoutEnabled: $.client.get('map', 'scout', 'hide', Client.DomainScavenger) === 'show',
        activeRoute: $.client.get('current','routes', null, Client.DomainDaily) ?? undefined,
        zoomChanged: false,
        activeZone: mk,

        showPanel: false,
        showViewer: props.data.displayType.split('-')[0] === 'beyond',
        routeEditor: [],
        zoom: 0,

        conf: {
            enableZoneMarking:  props.data.displayType.split('-')[0] === 'beyond',
            enableGlobalButton: props.data.displayType.split('-')[0] === 'beyond',
            enableZoneRouting:  props.data.displayType === 'door-planner',

            enableLocalView: props.data.displayType.split('-')[0] === 'beyond',
            enableMovementControls: props.data.displayType.split('-')[0] === 'beyond',
            enableSimpleZoneRouting:  false,
            enableComplexZoneRouting:  false,
        }
    });

    useEffect(()=>{
        const f_selectRoute = data => dispatch({activeRoute: (data as EventDataSelectRoute).route});
        props.eventRegistrar('select-route', f_selectRoute, false);
        return () => {
            props.eventRegistrar('select-route',  f_selectRoute, true);
        }
    })
    useEffect(()=>{
        if (state.conf.enableZoneRouting) {
            const f_planningBegin = (data:EventDataPlanningBegin) => {
                let new_conf = {...state.conf};
                new_conf.enableSimpleZoneRouting = (new_conf.enableComplexZoneRouting = data.complex) || true;
                dispatch({configure: new_conf, routeEditorPop: true})
            };

            const f_planningUndo = () => {
                dispatch({routeEditorPop: false})
            }

            const f_planningEnd = () => {
                let new_conf = {...state.conf};
                new_conf.enableSimpleZoneRouting = new_conf.enableComplexZoneRouting = false;
                dispatch({configure: new_conf, routeEditorPop: true})
            }

            props.eventRegistrar('planning-begin',  f_planningBegin,    false);
            props.eventRegistrar('planning-undo',   f_planningUndo,     false);
            props.eventRegistrar('planning-end',    f_planningEnd,      false);

            return () => {
                props.eventRegistrar('planning-begin',  f_planningBegin,    true);
                props.eventRegistrar('planning-undo',   f_planningUndo,     true);
                props.eventRegistrar('planning-end',    f_planningEnd,      true);
            }
        } else return ()=>{};
    });
    useEffect(()=>{
        if (state.conf.enableLocalView) {
            const f_resetPan = () => {
                dispatch({});
                setInc(inc+1);
            };
            props.eventRegistrar('movement-reset',  f_resetPan,    false);

            return () => {
                props.eventRegistrar('movement-reset',  f_resetPan,    true);
            }
        } else return ()=>{};
    });

    const activeRoute = routes.filter(r=>r.id===state.activeRoute)[0] ?? null;

    const api = new BeyondMapAPI();

    useEffect(() => {
        Promise.all([api.map( props.data.endpoint ), api.routes( props.data.endpoint )]).then( ([m,r]) => {
            setMap(m as MapData);
            setRoutes(r as MapRoute[]);
        } )
    }, [props.data.etag])

    useEffect(() => {
        api.index().then( v => setStrings(v) );
    }, [])

    const reactRef = useRef<HTMLDivElement>();

    let node = null, revert = false, x = 0, y = 0;

    useLayoutEffect(() => {
        node = reactRef.current?.querySelector('.zone-plane-parent') as HTMLDivElement;
        return () => node = null;
    })

    let timeout = null;
    const apply = () => {
        timeout = null;
        if (!node || !reactRef.current) return;

        node.classList.toggle('revert', revert);
        if (revert || dx !== 0 || dy !== 0) node.style.transform = 'translate(0px,0px)';
        else {
            const bounds = reactRef.current.getBoundingClientRect();
            const nodeBounds = node.getBoundingClientRect();

            const lx = ((x - bounds.x) / bounds.width - 0.5) * -0.15;
            const ly = ((y - bounds.y) / bounds.height - 0.5) * -0.15;


            node.style.transform = `translate(${lx * nodeBounds.width}px,${ly * nodeBounds.height}px)`;
        }
    }

    const mouseLeaveHandler = e => {
        revert = true;
        timeout = timeout ?? window.setTimeout( apply, 16 );
    }

    const mouseEnterHandler = e => {
        revert = false;
        timeout = timeout ?? window.setTimeout( apply, 16 );
    }

    const mouseMoveHandler = e => {
        x = e.clientX;
        y = e.clientY;

        timeout = timeout ?? window.setTimeout( apply, 100 );
    }

    return (
        <Globals.Provider value={{ strings, etag: props.data.etag }}>
            <div
                draggable={false} ref={reactRef}
                className={`react_map_area ${state.showViewer ? 'zone-viewer-mode' : ''}`}
                onMouseMove={props.data.fx ? mouseMoveHandler : null}
                onMouseEnter={props.data.fx ? mouseEnterHandler : null}
                onMouseLeave={props.data.fx ? mouseLeaveHandler : null}
            >
                { (!map || !strings) && <div className={'map-load-container'}/> }
                <div className={`map map-inner-react ${props.data.className} ${state.globalEnabled ? '' : 'show-global'} ${state.markEnabled ? 'show-tags' : ''}  ${state.scoutEnabled ? 'show-scout' : ''}`}>
                    <div className="frame-plane">
                        { ['tl','tr','bl','br','t0l','t1','t0r','l0t','l1','l0m','l0b','l2','r0t','r1','r0b','b']
                            .map(s=><div key={s} className={s}/>) }
                    </div>
                    { map && strings && <MapOverviewParent map={map} settings={state.conf}
                        marking={state.activeZone} wrapDispatcher={dispatch} etag={props.data.etag}
                        routeEditor={state.routeEditor} zoom={state.zoom} zoomChanged={state.zoomChanged}
                        routeViewer={activeRoute?.stops ?? []}
                        scrollAreaRef={scrollPlaneRef}
                    /> }

                    { strings && <>
                        <MapRouteList visible={state.showPanel} routes={routes}
                                      activeRoute={state.activeRoute} wrapDispatcher={dispatch}
                        />
                        { state.conf.enableLocalView && map && (
                            <LocalZoneView fx={props.data.fx} plane={map.local} inc={inc}
                                           activeRoute={activeRoute} dx={dx} dy={dy} wrapDispatcher={dispatch} marker={state.activeZone ?? null}
                                           movement={state.conf.enableMovementControls && props.data.displayType !== 'beyond-static'} />
                        ) }
                    </> }

                    { props.data.fx && [0,1,2,3,4].map(k=><div key={k} className="retro-effect"/>) }
                </div>
                <MapControls
                    markEnabled={state.markEnabled} globalEnabled={state.globalEnabled} wrapDispatcher={dispatch}
                    showRoutes={routes.length > 0} showRoutesPanel={state.showPanel} zoom={state.zoom}
                    scrollAreaRef={scrollPlaneRef} showGlobalButton={state.conf.enableGlobalButton}
                    showZoneViewerButtons={state.conf.enableLocalView} scoutEnabled={state.scoutEnabled}
                    showScoutButton={map?.conf?.scout ?? false}
                />
            </div>
        </Globals.Provider>
        )
};