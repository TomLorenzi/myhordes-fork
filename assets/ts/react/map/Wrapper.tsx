import * as React from "react";
import {ReactData} from "../index";

type MapGeometry = {
    x0: number,
    x1: number,
    y0: number,
    y1: number
}

export type MapCoreProps = {
    displayType: string;
    className: string;
    geometry: MapGeometry;
}

interface ReactDataMapCore extends ReactData<MapCoreProps> {}

const MapWrapper = ( props: ReactDataMapCore ) => {
    const m = Math.random();
    return (
            <div className={'map_area'}>
                <div className={`map ${props.data.className}`}>MAP</div>
                <div>{m}</div>
            </div>
        )
}

export default MapWrapper;
