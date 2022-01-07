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

export interface LocalZone {
    xr: number,     // Relative X coordinate (current citizen position is 0/0)
    yr: number,     // Relative Y coordinate (current citizen position is 0/0)
    x?: number,     // Absolute X coordinate
    y?: number,     // Absolute Y coordinate
    v?: boolean,    // Visited?
    c?: number,     // Exact number of citizens
    z?: number,     // Exact number of zombies
    zc?: number,    // Exact number of killed zombies
    r?: string,     // URL to ruin icon
    n?: string,     // Name of the local ruin

    ss?: boolean    // Scout sense
    sh?: number     // Scavenger sense
    se?: number     // Arrow coloring
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
    zones: MapZone[],
    local: LocalZone[],
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
    'global': string,
    routes: string,
    map: string,
    close: string,
    position: string,
}

export type RuntimeMapSettings = {
    enableZoneMarking: boolean,
    enableGlobalButton: boolean,
    enableZoneRouting: boolean,

    enableLocalView: boolean,
    enableMovementControls: boolean,
    enableSimpleZoneRouting: boolean,
    enableComplexZoneRouting: boolean,
}

export type RuntimeMapState = {
    conf: RuntimeMapSettings,
    showPanel: boolean,
    showViewer: boolean,
    markEnabled: boolean,
    globalEnabled: boolean,
    activeRoute: number | undefined;
    activeZone: MapCoordinate | undefined;
    routeEditor: MapCoordinate[];
    zoom: number;
    zoomChanged: boolean;
}

export type RuntimeMapStateAction = {
    configure?: RuntimeMapSettings
    showPanel?: boolean,
    showViewer?: boolean,
    markEnabled?: boolean,
    globalEnabled?: boolean,
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
    zoom: number, zoomChanged: boolean,
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
    globalEnabled: boolean,
    showRoutes: boolean,
    showRoutesPanel: boolean,
    showGlobalButton: boolean,
    showZoneViewerButtons: boolean,
    wrapDispatcher: (RuntimeMapStateAction)=>void,
    zoom: number,
    scrollAreaRef: {current?: HTMLDivElement}
}

export type LocalZoneSurroundings = {
    n: LocalZone|null,
    s: LocalZone|null,
    e: LocalZone|null,
    w: LocalZone|null,
    '0': LocalZone
}

export type LocalControlProps = {
    fx: boolean,
    planes: LocalZoneSurroundings,
    movement: boolean,
    strings: RuntimeMapStrings,
}

export type LocalZoneProps = {
    fx: boolean,
    plane: LocalZone[],
    movement: boolean,
    strings: RuntimeMapStrings,
}