import * as React from "react";
import * as ReactDOM from "react-dom";

import MapWrapper, {MapCoreProps} from "./map/Wrapper";

export interface ReactData<Type=object> {
    data: Type
}

export default class Components {

    generate(parent: HTMLElement, reactClass: string, data: object = {}) {
        switch (reactClass) {
            case 'map':
                ReactDOM.render(<MapWrapper data={data as MapCoreProps} />, parent);
                break;
            default:
                console.error('Invalid react class definition: "' + reactClass + "'.", data)
        }
    }
}