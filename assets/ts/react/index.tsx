import * as React from "react";
import * as ReactDOM from "react-dom";

import {Global} from "../defaults";
import {MapCoreProps} from "./map/typedef";
import MapWrapper from "./map/Wrapper";

declare var $: Global;

export interface ReactData<Type=object> {
    data: Type
}

export default class Components {

    private static vitalize(parent: HTMLElement) {
        let tooltips = parent.querySelectorAll('div.tooltip');
        for (let t = 0; t < tooltips.length; t++)
            $.html.handleTooltip( tooltips[t] as HTMLElement );
    }

    generate(parent: HTMLElement, reactClass: string, data: object = {}) {
        switch (reactClass) {
            case 'map':
                ReactDOM.render(<MapWrapper data={data as MapCoreProps} />, parent, () => Components.vitalize( parent ));
                break;
            default:
                console.error('Invalid react class definition: "' + reactClass + "'.", data)
        }
    }
}