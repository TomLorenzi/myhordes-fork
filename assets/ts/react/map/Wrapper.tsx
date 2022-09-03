import * as React from "react";
import * as ReactDOM from "react-dom";

import Components, {ReactData} from "../index";

import {
    MapCoordinate,
    MapCoreProps,
    RuntimeMapState,
    RuntimeMapStateAction
} from "./typedef";
import MapOverviewParent from "./Overview";
import MapRouteList from "./RouteList";
import MapControls from "./Controls";
import {useEffect, useRef} from "react";
import {Global} from "../../defaults";
import LocalZoneView from "./ZoneView";
import Client from "../../client";

declare var $: Global;

interface ReactDataMapCore extends ReactData<MapCoreProps> {}

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

export class HordesMap {
    public static mount(parent: HTMLElement, data: object): void {
        ReactDOM.render(<MapWrapper {...$.components.kickstart(parent,data) as ReactData<MapCoreProps>} />, parent, () => Components.vitalize( parent ));
    }

    public static unmount(parent: HTMLElement): void {
        if (ReactDOM.unmountComponentAtNode( parent )) $.components.degenerate(parent);
    }
}

const MapWrapper = ( props: ReactDataMapCore ) => {
    let mk = $.client.get('marker','routes',null, Client.DomainScavenger);
    if (!mk) mk = undefined;
    else mk = {x: mk[0] ?? 0, y: mk[1] ?? 0}

    const scrollPlaneRef = useRef<HTMLDivElement>(null);
    let dx = 0, dy = 0;

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
            const f_resetPan = () => { dispatch({}) };
            props.eventRegistrar('movement-reset',  f_resetPan,    false);

            return () => {
                props.eventRegistrar('movement-reset',  f_resetPan,    true);
            }
        } else return ()=>{};
    });


    const activeRoute = props.data.routes.filter(r=>r.id===state.activeRoute)[0] ?? null;

    return (
            <div draggable={false} className={`react_map_area ${state.showViewer ? 'zone-viewer-mode' : ''}`}>
                <div className={`map map-inner-react ${props.data.className} ${state.globalEnabled ? '' : 'show-global'} ${state.markEnabled ? 'show-tags' : ''}`}>
                    <div className="frame-plane">
                        { ['tl','tr','bl','br','t0l','t1','t0r','l0t','l1','l0m','l0b','l2','r0t','r1','r0b','b']
                            .map(s=><div key={s} className={s}/>) }
                    </div>
                    <MapOverviewParent map={props.data.map} strings={props.data.strings} settings={state.conf}
                                       marking={state.activeZone} wrapDispatcher={dispatch} etag={props.data.etag}
                                       routeEditor={state.routeEditor} zoom={state.zoom} zoomChanged={state.zoomChanged}
                                       routeViewer={activeRoute?.stops ?? []}
                                       scrollAreaRef={scrollPlaneRef}
                    />
                    <MapRouteList visible={state.showPanel} routes={props.data.routes} strings={props.data.strings}
                                  activeRoute={state.activeRoute} wrapDispatcher={dispatch}
                    />
                    { state.conf.enableLocalView && (
                        <LocalZoneView fx={props.data.fx} plane={props.data.map.local} strings={props.data.strings}
                                       activeRoute={activeRoute} dx={dx} dy={dy} wrapDispatcher={dispatch} marker={state.activeZone ?? null}
                                       movement={state.conf.enableMovementControls && props.data.displayType !== 'beyond-static'} />
                    ) }
                    { props.data.fx && [0,1,2,3,4].map(k=><div key={k} className="retro-effect"/>) }
                </div>
                <MapControls
                    strings={props.data.strings} markEnabled={state.markEnabled} globalEnabled={state.globalEnabled} wrapDispatcher={dispatch}
                    showRoutes={props.data.routes.length > 0} showRoutesPanel={state.showPanel} zoom={state.zoom}
                    scrollAreaRef={scrollPlaneRef} showGlobalButton={state.conf.enableGlobalButton}
                    showZoneViewerButtons={state.conf.enableLocalView}
                />
            </div>
        )
};