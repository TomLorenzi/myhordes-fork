export type MapGeometry = {
    x0: number,
    x1: number,
    y0: number,
    y1: number
}

type MapZoneRuin = {
    n: string,      // Translated name
    b: boolean,     // Buried?
    e: boolean,     // Explorable?
}

export type MapCoordinate = {
    x: number,      // X coordinate
    y: number,      // Y coordinate
}

export interface MapZone extends MapCoordinate {
    z?: number,     // Exact number of zombies
    d?: number,     // Danger level
    r?: MapZoneRuin,
    td?: boolean    // Only defined for town zone: true - town is devastated, false - town is not devastated
    c?: string[],   // Names of all citizens on the zone
    co?: number,    // Number of additional citizens to display without names (only town zone)
    cc?: boolean    // True, if the active citizen is here
    t?: boolean     // Visited today?
    g?: boolean     // Global view
    s?: boolean     // Contains a soul
    tg?: number     // Tag Ref
}

export type MapRoute = {
    id: number,
    owner: string,
    label: string,
    length: number,
    stops: MapCoordinate[],
}

type MapData = {
    geo: MapGeometry,
    zones: MapZone[]
}

export type MapCoreProps = {
    displayType: string;
    className: string;
    etag: number,
    fx: boolean,
    map: MapData;
    routes: MapRoute[],
    strings: RuntimeMapStrings,
}

export type RuntimeMapStrings = {
    zone: string,
    distance: string,
    danger: string[],
    tags: string[],
    mark: string,
    routes: string,
}

export type RuntimeMapSettings = {
    showGlobal: boolean,
    enableZoneMarking: boolean,
    enableZoneRouting: boolean,

    enableSimpleZoneRouting: boolean,
    enableComplexZoneRouting: boolean,
}

export type RuntimeMapState = {
    conf: RuntimeMapSettings,
    showPanel: boolean,
    markEnabled: boolean,
    activeRoute: number | undefined;
    activeZone: MapCoordinate | undefined;
    routeEditor: MapCoordinate[];
    zoom: number;
}

export type RuntimeMapStateAction = {
    configure?: RuntimeMapSettings
    showPanel?: boolean,
    markEnabled?: boolean,
    activeRoute?: number | boolean,
    activeZone?: MapCoordinate | boolean,
    routeEditorPush?: MapCoordinate,
    routeEditorPop?: boolean,
    zoom?: number,
}

export type MapOverviewParentProps = {
    settings: RuntimeMapSettings,
    map: MapData,
    strings: RuntimeMapStrings,
    marking: MapCoordinate | undefined,
    wrapDispatcher: (RuntimeMapStateAction)=>void,
    routeEditor: MapCoordinate[],
    routeViewer: MapCoordinate[],
    etag: number,
    zoom: number,
    scrollAreaRef:  {current?: HTMLDivElement}
}

export interface MapOverviewGridProps extends MapOverviewParentProps {
    zoom: number
}

export type MapOverviewParentState = {}

export type MapRouteListProps = {
    visible: boolean,
    routes: MapRoute[],
    strings: RuntimeMapStrings,
    activeRoute: number | undefined,
    wrapDispatcher: (RuntimeMapStateAction)=>void
}

export type MapRouteListState = {
    activeRoute: number | undefined,
}

export type MapControlProps = {
    strings: RuntimeMapStrings,
    markEnabled: boolean,
    showRoutes: boolean,
    showRoutesPanel: boolean,
    wrapDispatcher: (RuntimeMapStateAction)=>void,
    zoom: number,
    scrollAreaRef: {current?: HTMLDivElement}
}