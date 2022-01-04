import * as React from "react";
import {ReactData} from "../index";

import {
    MapCoreProps,
    MapOverviewParentState,
    RuntimeMapSettings,
    RuntimeMapState,
    RuntimeMapStateAction
} from "./typedef";
import MapOverviewParent, {MapOverviewParentStateAction} from "./Overview";
import MapRouteList from "./RouteList";
import MapControls from "./Controls";

interface ReactDataMapCore extends ReactData<MapCoreProps> {}

const MapWrapper = ( props: ReactDataMapCore ) => {
    const conf: RuntimeMapSettings = {
        showGlobal: [].includes(props.data.displayType),
    };

    const [state, dispatch] = React.useReducer((state: RuntimeMapState, action: RuntimeMapStateAction): RuntimeMapState => {
        const new_state = {...state};
        if (typeof action.showPanel !== "undefined") new_state.showPanel = action.showPanel;
        if (typeof action.markEnabled !== "undefined") new_state.markEnabled = action.markEnabled;
        if (typeof action.activeRoute !== "undefined") new_state.activeRoute = action.activeRoute === false ? undefined : action.activeRoute as number;
        return new_state;
    }, {showPanel: false, markEnabled: false, activeRoute: undefined});

    return (
            <div className={'react_map_area'}>
                <div className={`map map-inner-react ${props.data.className} ${conf.showGlobal ? 'show-global' : ''} ${state.markEnabled ? 'show-tags' : ''}`}>
                    <div className="frame-plane">
                        { ['tl','tr','bl','br','t0l','t1','t0r','l0t','l1','l0m','l0b','l2','r0t','r1','r0b','b']
                            .map(s=><div key={s} className={s}/>) }
                    </div>
                    <MapOverviewParent map={props.data.map} strings={props.data.strings} settings={conf}/>
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
