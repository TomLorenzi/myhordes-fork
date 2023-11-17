import {Fetch} from "../../v2/fetch";
import {MapData, MapRoute} from "./typedef";

export type RuntimeMapStrings = {
    zone: string,
    distance: string,
    distanceTown: string,
    distanceSelf: string,
    danger: string[],
    tags: string[],
    mark: string,
    'global': string,
    routes: string,
    map: string,
    close: string,
    position: string,
    horror: string[],
}

export class BeyondMapAPI {

    private fetch: Fetch;

    constructor() {
        this.fetch = new Fetch( 'game/map' );
    }

    public index(): Promise<RuntimeMapStrings> {
        return this.fetch.from('/index')
            .request().withCache().get() as Promise<RuntimeMapStrings>;
    }

    public map(endpoint: string): Promise<MapData> {
        return this.fetch.from(`/${endpoint}/map`)
            .request().secure().get() as Promise<MapData>;
    }

    public routes(endpoint: string): Promise<MapRoute[]> {
        return this.fetch.from(`/${endpoint}/routes`)
            .request().get() as Promise<MapRoute[]>;
    }
}