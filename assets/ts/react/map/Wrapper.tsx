import * as React from "react";
import {ReactData} from "../index";

import {
    MapCoordinate,
    MapCoreProps,
    MapOverviewParentState,
    RuntimeMapSettings,
    RuntimeMapState,
    RuntimeMapStateAction
} from "./typedef";
import MapOverviewParent, {MapOverviewParentStateAction} from "./Overview";
import MapRouteList from "./RouteList";
import MapControls from "./Controls";
import {act} from "react-dom/test-utils";

interface ReactDataMapCore extends ReactData<MapCoreProps> {}

interface EventDataPlanningBegin { complex: boolean }

const validRoute = (route: MapCoordinate[], complex: boolean): boolean => {
    if (route.length === 0) return false;

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

const MapWrapper = ( props: ReactDataMapCore ) => {
    const [state, dispatch] = React.useReducer((state: RuntimeMapState, action: RuntimeMapStateAction): RuntimeMapState => {
        const new_state = {...state};
        if (typeof action.configure   !== "undefined") new_state.conf        = action.configure;
        if (typeof action.showPanel   !== "undefined") new_state.showPanel   = action.showPanel;
        if (typeof action.markEnabled !== "undefined") new_state.markEnabled = action.markEnabled;
        if (typeof action.activeRoute !== "undefined") new_state.activeRoute = action.activeRoute === false ? undefined : action.activeRoute as number;
        if (typeof action.activeZone  !== "undefined") {
            if (action.activeZone === false ) new_state.activeZone = undefined;
            else if (action.activeZone !== true ) {
                if (typeof new_state.activeZone  === "undefined") new_state.activeZone = action.activeZone;
                else if ( new_state.activeZone.x !== action.activeZone.x || new_state.activeZone.y !== action.activeZone.y )
                    new_state.activeZone = action.activeZone;
                else new_state.activeZone = undefined;
            }
        }
        if (typeof action.routeEditorPop  !== "undefined") {
            new_state.routeEditor = action.routeEditorPop === true ? [] : new_state.routeEditor.slice(0,-1);
            props.eventGateway('planner-step', { valid: validRoute(new_state.routeEditor, new_state.conf.enableComplexZoneRouting), route: new_state.routeEditor.map(c => [c.x,c.y]) });
        }
        if (typeof action.routeEditorPush !== "undefined" && validateRouteStep(new_state.routeEditor, action.routeEditorPush, new_state.conf.enableComplexZoneRouting)) {
            new_state.routeEditor = new_state.routeEditor.slice(0);
            new_state.routeEditor.push(action.routeEditorPush);
            props.eventGateway('planner-step', { valid: validRoute(new_state.routeEditor, new_state.conf.enableComplexZoneRouting), route: new_state.routeEditor.map(c => [c.x,c.y]) });
        }

        return new_state;
    }, {
        showPanel: false, markEnabled: false, activeRoute: undefined, activeZone: undefined, routeEditor: [],
        conf: {
            showGlobal:         props.data.displayType.split('-')[0] === 'beyond',
            enableZoneMarking:  props.data.displayType.split('-')[0] === 'beyond',
            enableZoneRouting:  props.data.displayType === 'door-planner',

            enableSimpleZoneRouting:  false,
            enableComplexZoneRouting:  false,
        }
    });

    if (state.conf.enableZoneRouting) {
        props.eventRegistrar('planning-begin', (data) => {
            let new_conf = {...state.conf};
            new_conf.enableSimpleZoneRouting = (new_conf.enableComplexZoneRouting = (data as EventDataPlanningBegin).complex) || true;
            dispatch({configure: new_conf, routeEditorPop: true})
        });
        props.eventRegistrar('planning-undo', () => {
            dispatch({routeEditorPop: false})
        });
        props.eventRegistrar('planning-end',   () => {
            let new_conf = {...state.conf};
            new_conf.enableSimpleZoneRouting = new_conf.enableComplexZoneRouting = false;
            dispatch({configure: new_conf, routeEditorPop: true})
        });
    }


    return (
            <div className={'react_map_area'}>
                <div className={`map map-inner-react ${props.data.className} ${state.conf.showGlobal ? 'show-global' : ''} ${state.markEnabled ? 'show-tags' : ''}`}>
                    <div className="frame-plane">
                        { ['tl','tr','bl','br','t0l','t1','t0r','l0t','l1','l0m','l0b','l2','r0t','r1','r0b','b']
                            .map(s=><div key={s} className={s}/>) }
                    </div>
                    <MapOverviewParent map={props.data.map} strings={props.data.strings} settings={state.conf} marking={state.activeZone} wrapDispatcher={dispatch} routeEditor={state.routeEditor}/>
                    <MapRouteList visible={state.showPanel} routes={props.data.routes} strings={props.data.strings} activeRoute={state.activeRoute} wrapDispatcher={dispatch} />
                    { props.data.fx && [0,1,2,3,4].map(k=><div key={k} className="retro-effect"/>) }
                </div>
                <MapControls
                    strings={props.data.strings} markEnabled={state.markEnabled} wrapDispatcher={dispatch}
                    showRoutes={props.data.routes.length > 0} showRoutesPanel={state.showPanel}
                />
            </div>
        )
}

export default MapWrapper;
