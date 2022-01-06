import * as React from "react";

import {
    MapControlProps,
} from "./typedef";

export type MapControlStateAction = {
    zoomIn?: boolean,
    zoomOut?: boolean
}

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
                        <button
                            className={`small inline ${props.markEnabled ? 'show-tags' : 'hide-tags'} map_button map_button_left`}
                            onClick={()=>props.wrapDispatcher({markEnabled: !props.markEnabled})}
                        >
                            <div>{props.strings.mark}</div>
                        </button>
                    </div>
                    <div className="float-right">
                        <button onClick={()=>zoom_handler(1)}  disabled={props.zoom >= 2}
                            className={`small inline map_button map_button_icon map_button_right`}
                        >
                            <i className="fa fa-plus"/>
                        </button>
                        <button onClick={()=>center_handler()} disabled={props.zoom <= 0}
                            className={`small inline map_button map_button_icon map_button_right`}
                        >
                            <i className="fa fa-map-marker-alt"/>
                        </button>
                        <button onClick={()=>zoom_handler(-1)} disabled={props.zoom <= 0}
                            className={`small inline map_button map_button_icon map_button_right`}
                        >
                            <i className="fa fa-minus"/>
                        </button>
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
