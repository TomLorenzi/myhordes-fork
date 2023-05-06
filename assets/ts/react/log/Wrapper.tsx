import * as React from "react";
import { createRoot } from "react-dom/client";
import {useContext, useEffect, useLayoutEffect, useRef, useState} from "react";
import {LogAPI, LogEntry} from "./api";
import {TranslationStrings} from "./strings";
import {Tooltip} from "../tooltip/Wrapper";
import {Global} from "../../defaults";

declare var $: Global;

interface mountProps {
    domain: string,
    day: number,
    etag: number,
    citizen: number,
    category: number[],
    entries: number
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
    const [interactive, setInteractive] = useState<boolean>( true );
    const [strings, setStrings] = useState<TranslationStrings>( null );
    const [sleeping, setSleeping] = useState<boolean>( true );
    const [currentDay, setCurrentDay] = useState<number>( props.day );
    const [currentData, setCurrentData] = useState<DailyCache>( null );
    const [manipulations, setManipulations] = useState<number>( 0 );

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

        let replaced = [];
        new_target.entries = new_target.entries.map( e => {
            const match = entries.find( f => f.id === e.id );
            if (match) {
                replaced.push( match.id );
                return match;
            } else return e;
        } );

        if (replaced.length > 0)
            entries = entries.filter( f => !replaced.find( ff => ff === f.id ) )

        if (entries.length > 0) {
            new_target.entries = before ? [...entries, ...new_target.entries] : [...new_target.entries, ...entries];
            new_target.completed = new_target.completed || (completed && !before);
        }
        setCurrentDataCached( new_target, day );
    }

    useEffect( () => {
        if (sleeping) return;
        const day = currentDay;
        const target = cache.current[day] ?? null;
        setCurrentData( target ? {...target} : null );
        if (target === null || currentDay === props.day) {
            setLoading(true);
            api.logs(props.domain, props.citizen, day, (target === null && day === props.day) ? props.entries : -1, props.category, -1, target === null ? -1 : (target.entries[0]?.id ?? -1))
                .then(v => {
                    applyData(day, v.entries, true, v.entries.length >= v.total);
                    setManipulations( v.manipulations );
                    setLoading(false);
                })
        }
    }, [props.etag,currentDay,sleeping] );

    const loadMore = ( day: number ) => {
        if ((cache.current[day] ?? null) === null) return;
        setLoading( true );
        api.logs( props.domain, props.citizen, day, -1, props.category, cache.current[day].entries[cache.current[day].entries.length-1].id,  -1 )
            .then( v => {
                applyData( day, v.entries, false, v.entries.length >= v.total);
                setManipulations( v.manipulations );
                setLoading( false );
            } )
    }

    const deleteEntry = ( id: number ) => {
        setInteractive( false );
        api.deleteLog( id ).then( v => {
            applyData( currentDay, v.entries, false, false);
            setManipulations( v.manipulations );
            setInteractive( true );
            $.html.notice(strings?.content.manipulated.replace( '{times}', `${v.manipulations}` ))
        })
    }

    return <Globals.Provider value={{api, strings}}>
        <div ref={container} className="log-container" data-disabled={(interactive || loading) ? 'none' : 'blocked'}>
            <div className="log">
                <HordesLogContentContainer day={currentDay} today={currentDay === props.day} manipulate={manipulations > 0}
                    loading={loading} data={currentData}
                    loadMore={()=>loadMore(currentDay)}
                    deleteEntry={(n)=>deleteEntry(n)}
                />
            </div>
            <HordesLogDaySelector days={props.day} selectedDay={currentDay} setDay={n => setCurrentDay(n)}/>
        </div>
    </Globals.Provider>
};

interface logContainerProps {
    day: number,
    today: boolean
    data?: DailyCache,
    loading: boolean,
    loadMore: ()=>void,
    deleteEntry: (number)=>void,
    manipulate: boolean
}

const HordesLogContentContainer = (props: logContainerProps) => {
    const globals = useContext(Globals);

    const [inHiding, setInHiding] = useState<number>(-1);

    useEffect(() => {
        setInHiding(-1)
    }, [props.data])

    const getFlavour = () => (globals.strings?.content.flavour ?? [null])[ Math.floor( Math.random() * (globals.strings?.content.flavour.length ?? 0) ) ];

    return <div className="log-content">
        <div className="log-day-header">{ globals.strings?.content.header.replace('{d}', `${props.day}`).replace('{today}', props.today ? `(${globals.strings?.content.header_part_today})` : '') }</div>
        { props.data?.entries.map( entry => <div key={entry.id} className={`log-entry log-entry-type-${ entry['type'] } log-entry-class-${ entry['class'] }`}>
            <span className="log-part-time">{entry.timestring}</span>
            <span style={inHiding === entry.id ? {opacity: 0.5} : null} className={`log-part-content ${entry.hidden ? 'log-part-entry-hidden' : ''}`}>
                { entry.hidden && <img alt="" src={ globals.strings?.content.warning }/> }
                { entry.hideable && props.manipulate &&
                    <span className="link undecorated remove-entry" onClick={ () => {
                        if (entry['protected']) $.html.error( globals.strings?.content['protected'] );
                        else {
                            setInHiding(entry.id);
                            props.deleteEntry(entry.id);
                        }
                    } }>
                        <img src={globals.strings.content.falsify} alt="[X]" />
                        &nbsp;
                        <Tooltip additionalClasses="help" html={ globals.strings?.content.hide }></Tooltip>
                    </span>
                }
                <span className="container" dangerouslySetInnerHTML={{__html: (entry.hidden && !entry.text) ? globals.strings?.content.hidden : entry.text}}></span>
            </span>
        </div> )}
        { props.data?.completed && !props.data?.entries?.length && <i>{ globals.strings?.content.empty }</i> }
        { !props.data?.completed && !props.loading && <div className="log-complete-link" onClick={()=>props.loadMore()}>{ globals.strings?.content.more }</div> }
        { !props.data?.completed && props.loading && <div className="log-spinner">
            <i className="fa fa-pulse fa-spinner"></i>&nbsp;{ getFlavour() }
        </div> }
    </div>
}

const HordesLogDaySelector = ({selectedDay, days, setDay}: {selectedDay: number, days: number, setDay: (number)=>void}) => {
    const globals = useContext(Globals);

    return <div className="log-day-select">
        <div>
            { [...Array(days).keys()].reverse().map(d=>d+1).map(d => <React.Fragment key={d}><div onClick={()=>setDay(d)} className={`tab ${d === selectedDay ? 'current' : ''}`}>{ globals.strings?.wrapper.day } {d}</div>&nbsp;</React.Fragment>) }
        </div>
    </div>
}