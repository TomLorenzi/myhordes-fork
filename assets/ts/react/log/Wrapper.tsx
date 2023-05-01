import * as React from "react";
import { createRoot } from "react-dom/client";
import {useContext, useEffect, useLayoutEffect, useRef, useState} from "react";
import {LogAPI, LogEntry} from "./api";
import {TranslationStrings} from "./strings";
import {string} from "prop-types";
import {Tooltip} from "../tooltip/Wrapper";

interface mountProps {
    domain: string,
    day: number,
    etag: number,
    citizen: number,
    category: number[]
}

interface DailyCache {
    entries: LogEntry[],
    completed: boolean
}

export class HordesLog {

    #_root = null;

    public mount(parent: HTMLElement, props: mountProps): any {
        if (!this.#_root) this.#_root = createRoot(parent);
        this.#_root.render( <HordesLogWrapper {...props} /> );
    }

    public unmount() {
        if (this.#_root) {
            this.#_root.unmount();
            this.#_root = null;
        }
    }
}

interface LogGlobal {
    api: LogAPI,
    strings: TranslationStrings
}

export const Globals = React.createContext<LogGlobal>(null);

const HordesLogWrapper = (props: mountProps) => {

    const api = new LogAPI();

    const [loading, setLoading] = useState<boolean>( false );
    const [strings, setStrings] = useState<TranslationStrings>( null );
    const [sleeping, setSleeping] = useState<boolean>( true );
    const [currentDay, setCurrentDay] = useState<number>( props.day );
    const [currentData, setCurrentData] = useState<DailyCache>( null );

    const cache = useRef<DailyCache[]>([]);

    const setCurrentDataCached = (data: DailyCache, day: number = null) => {
        if (day === null) day = currentDay;
        cache.current[day] = data;
        setCurrentData(data);
    }

    const container = useRef<HTMLDivElement>(null);
    const containerIntersectionObserver = useRef<IntersectionObserver>(
        new IntersectionObserver( (e) => sleeping && e[0].isIntersecting && setSleeping(false), {
            rootMargin: "50px 0px 50px 0px"
        })
    )

    useEffect( () => {
        api.index().then( v => setStrings(v) );
    }, [] );

    useLayoutEffect( () => {
        if (sleeping) {
            containerIntersectionObserver.current.observe( container.current );
            return () => containerIntersectionObserver.current.unobserve( container.current );
        }
    } );

    const applyData = (day: number, entries: LogEntry[], before: boolean, completed: boolean) => {
        const new_target = (cache.current[day] ?? null) === null ? { entries: [], completed } : {...cache.current[day]};
        new_target.entries = before ? [ ...entries, ...new_target.entries ] : [ ...new_target.entries, ...entries ];
        new_target.completed = new_target.completed || completed;
        setCurrentDataCached( new_target, day );
    }

    useEffect( () => {
        if (sleeping) return;
        const day = currentDay;
        const target = cache.current[day] ?? null;
        setCurrentData( target ? {...target} : null );
        setLoading(true);
        api.logs(props.domain, props.citizen, day, target === null ? 5 : -1, props.category, -1, target === null ? -1 : target.entries[0].id)
            .then(v => {
                applyData(day, v, true, false);
                setLoading(false);
            })
    }, [props.etag,currentDay,sleeping] );

    const loadMore = ( day: number ) => {
        if ((cache.current[day] ?? null) === null) return;
        setLoading( true );
        api.logs( props.domain, props.citizen, day, -1, props.category, cache.current[day].entries[cache.current[day].entries.length-1].id,  -1 )
            .then( v => {
                applyData( day, v, false, true );
                setLoading( false );
            } )
    }

    return <Globals.Provider value={{api, strings}}>
        <div ref={container} className="log-container">
            <div className="log">
                <HordesLogContentContainer placeholders={5} loading={loading} data={currentData} loadMore={()=>loadMore(currentDay)}/>
            </div>
            <HordesLogDaySelector loading={loading} days={props.day} selectedDay={currentDay} setDay={n => setCurrentDay(n)}/>
        </div>
    </Globals.Provider>
};

const HordesLogContentContainer = ({placeholders,loading,data,loadMore}: {placeholders: number, loading: boolean, data?: DailyCache, loadMore: ()=>void}) => {
    const globals = useContext(Globals);

    const getFlavour = () => (globals.strings?.content.flavour ?? [null])[ Math.floor( Math.random() * (globals.strings?.content.flavour.length ?? 0) ) ];

    return <div className="log-content">
        { data?.entries.map( entry => <div key={entry.id} className={`log-entry log-entry-type-${ entry['type'] } log-entry-class-${ entry['class'] }`}>
            <span className="log-part-time">{entry.timestring}</span>
            <span className={`log-part-content ${entry.hidden ? 'log-part-entry-hidden' : ''}`}>
                { entry.hidden && <img alt="" src={ globals.strings?.content.warning }/> }
                { entry.hideable && <span className="link undecorated remove-entry"><img src={globals.strings.content.falsify} alt="[X]" /><Tooltip additionalClasses="help" html={ globals.strings?.content.hide }></Tooltip></span>}
                <span className="container" dangerouslySetInnerHTML={{__html: (entry.hidden && !entry.text) ? globals.strings?.content.hidden : entry.text}}></span>
            </span>
        </div> )}
        { !data?.completed && !loading && <div className="log-complete-link" onClick={()=>loadMore()}>{ globals.strings?.content.more }</div> }
        { !data?.completed && loading && <div className="log-spinner">
            <i className="fa fa-pulse fa-spinner"></i>
            { getFlavour() }
        </div> }
    </div>
}

const HordesLogDaySelector = ({selectedDay, days, setDay, loading}: {selectedDay: number, days: number, setDay: (number)=>void, loading: boolean}) => {
    const globals = useContext(Globals);

    return <div className="log-day-select">
        <div>
            { [...Array(days).keys()].reverse().map(d=>d+1).map(d => <div key={d} onClick={()=>!loading && setDay(d)} className={`tab ${d === selectedDay ? 'current' : ''}`}>{ globals.strings?.wrapper.day } {d}</div>) }
        </div>
    </div>
}