import * as React from "react";

import {
    MapControlProps,
} from "./typedef";

const MapControls = ( props: MapControlProps ) => {

    const zoom_handler = (n:number) => {
        if (props.zoom === 0 && n === 1 && props.scrollAreaRef.current) {
            if ((props.scrollAreaRef.current.querySelector('.zone')?.clientWidth ?? 0) > 16)
                n=2;
            if ((props.scrollAreaRef.current.querySelector('.zone')?.clientWidth ?? 0) > 30)
                n=0;
        }
        if (props.zoom === 2 && n === -1 && props.scrollAreaRef.current) {
            if ((props.scrollAreaRef.current.clientWidth * 2/3) / props.scrollAreaRef.current.parentElement.clientWidth < 1.1)
                n=-2;
        }
        props.wrapDispatcher({zoom: Math.max( 0, Math.min( props.zoom + n, 2 ) ) });
    }

    const center_handler = () => {
        if (props.zoom !== 0) props.scrollAreaRef.current.dispatchEvent(new CustomEvent('_mv_center'));
    }

    return (
        <div className="controls">
            <div className="tilemap_controls">
                <div className="row">
                    <div className="float-left">
                        { props.showZoneViewerButtons && (
                            <button onClick={()=>props.wrapDispatcher({showViewer: true, showPanel: false})}
                                className="small inline map_button map_button_left">
                                <div>{ props.strings.close }</div>
                            </button>
                        ) }
                        <button
                            className={`small inline ${props.markEnabled ? 'show-tags' : 'hide-tags'} map_button map_button_left`}
                            onClick={()=>props.wrapDispatcher({markEnabled: !props.markEnabled})}
                        >
                            <div>{props.strings.mark}</div>
                        </button>
                        { props.showGlobalButton && (
                            <button
                                className={`small inline ${props.globalEnabled ? 'show-tags' : 'hide-tags'} map_button map_button_left`}
                                onClick={()=>props.wrapDispatcher({globalEnabled: !props.globalEnabled})}
                            >
                                <div>{props.strings.global}</div>
                            </button>
                        ) }
                    </div>
                    <div className="float-right">
                        <button onClick={()=>zoom_handler(1)} disabled={props.zoom >= 2}
                            className={`small inline map_button map_button_icon map_button_right map_button_zoom_in`}
                        >
                            &nbsp;
                        </button>
                        <button onClick={()=>center_handler()} disabled={props.zoom <= 0}
                            className={`small inline map_button map_button_icon map_button_right`}
                        >
                            <i className="fa fa-map-marker-alt"/>
                        </button>
                        <button onClick={()=>zoom_handler(-1)} disabled={props.zoom <= 0}
                            className={`small inline map_button map_button_icon map_button_right map_button_zoom_out`}
                        >
                            &nbsp;
                        </button>
                        { props.showRoutes && (
                            <button
                                className="small inline map_button map_button_right route_button"
                                onClick={()=>props.wrapDispatcher({showPanel: !props.showRoutesPanel})}
                            >
                                <div>{props.strings.routes}</div>
                            </button>
                        ) }

                    </div>
                </div>
            </div>
            { props.showZoneViewerButtons && (
                <div className="zonemap_controls">
                    <button onClick={()=>props.wrapDispatcher({showViewer: false})}
                        className="small inline map-icon map_button map_button_left">
                        <div>{ props.strings.map }</div>
                    </button>
                </div>
            ) }
        </div>
    )
}

export default MapControls;
