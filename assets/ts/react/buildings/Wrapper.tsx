import * as React from "react";
import {Const, Global} from "../../defaults";
import {HordesBuildingListWrapper} from "./BuildingList";
import {BuildingAPI} from "./api";
import {TranslationStrings} from "./strings";
import {HordesBuildingPageWrapper} from "./BuildingPage";
import {BaseMounter} from "../index";

declare var $: Global;
declare var c: Const;


interface mountProps {
    etag: string,
}

export interface BuildingListGlobal {
    api: BuildingAPI,
    strings: TranslationStrings|null
}

export const Globals = React.createContext<BuildingListGlobal>(null);

export class HordesBuildingList extends BaseMounter<mountProps> {
    protected render(props: mountProps): React.ReactNode {
        return <HordesBuildingListWrapper {...props}/>
    }
}

export interface mountPageProps {
    etag: string,
    apRatio: number,
    hpRatio: number,
    bank: number
}

export class HordesBuildingPage extends BaseMounter<mountPageProps> {
    protected render(props: mountPageProps): React.ReactNode {
        return <HordesBuildingPageWrapper {...props}/>
    }

}