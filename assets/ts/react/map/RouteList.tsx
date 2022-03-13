import * as React from "react";

import {
    MapRouteListProps,
} from "./typedef";

const MapRouteList = ( props: MapRouteListProps ) => {

    return (
        <div className={`routes-plane ${props.visible ? '' : 'hidden'}`}>
            <div>
                { props.routes.map( route => (
                    <div key={route.id} className="row" onClick={()=>props.wrapDispatcher({
                        activeRoute: props.activeRoute === route.id ? false : route.id,
                        showPanel: false,
                    })}>
                        <div className="padded cell rw-12">
                            <span className={props.activeRoute === route.id ? 'bold' : ''}>{ route.label }</span>
                            <div className="float-right">
                                <div className="ap">{route.length}</div>
                            </div>
                        </div>
                    </div>
                ) ) }
            </div>
        </div>
    )
}

export default MapRouteList;
