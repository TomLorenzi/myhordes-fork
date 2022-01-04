import * as React from "react";

import {
    MapControlProps, MapControlState,
    MapGeometry,
    MapOverviewParentProps,
    MapOverviewParentState, MapRouteListProps, MapRouteListState,
    MapZone,
    RuntimeMapSettings,
    RuntimeMapStrings
} from "./typedef";

export type MapControlStateAction = {
    zoomIn?: boolean,
    zoomOut?: boolean
}

const MapControls = ( props: MapControlProps ) => {

    const [state, dispatch] = React.useReducer((state: MapControlState, action: MapControlStateAction): MapControlState => {
        const new_state = {...state};
        if (typeof action.zoomIn !== "undefined") new_state.canZoomIn = action.zoomIn;
        if (typeof action.zoomOut !== "undefined") new_state.canZoomOut = action.zoomOut;
        return new_state;
    }, {activeRoute: undefined});

    return (
        <div className="controls">
            <div className="tilemap_controls">
                <div className="row">
                    <div className="float-left">
                        <button
                            className={`small inline ${props.markEnabled ? 'show-tags' : 'hide-tags'} map_button map_button_left`}
                            onClick={()=>props.wrapDispatcher({markEnabled: !props.markEnabled})}
                        >
                            <div>{props.strings.mark}</div>
                        </button>
                    </div>
                    <div className="float-right">
                        { props.showRoutes && (
                            <button
                                className="small inline map_button map_button_right"
                                onClick={()=>props.wrapDispatcher({showPanel: !props.showRoutesPanel})}
                            >
                                <div>{props.strings.routes}</div>
                            </button>
                        ) }

                    </div>
                </div>
            </div>
        </div>
    )
}

export default MapControls;
