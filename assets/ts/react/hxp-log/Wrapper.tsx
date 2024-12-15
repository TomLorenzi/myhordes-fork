import * as React from "react";
import {useEffect, useLayoutEffect, useRef, useState} from "react";
import {HxpLogAPI, LogEntry} from "./api";
import {TranslationStrings} from "./strings";
import {Global} from "../../defaults";
import {BaseMounter} from "../index";

declare var $: Global;

interface mountProps {
    focus: number
}


export class HordesHxpLog extends BaseMounter<mountProps>{
    protected render(props: mountProps): React.ReactNode {
        return <HordesHxpLogWrapper {...props} />;
    }
}

const HordesHxpLogWrapper = (props: mountProps) => {

    const api = new HxpLogAPI();

    const [strings, setString] = useState<TranslationStrings>();
    const [loading, setLoading] = useState<boolean>(false);
    const [logs, setLogs] = useState<LogEntry[]>([]);
    const [additional, setAdditional] = useState<boolean>(true);

    const parent = useRef<HTMLDivElement>();
    const loader = useRef<HTMLDivElement>();

    useEffect(() => {
        api.index().then(s => setString(s));
    }, []);

    useLayoutEffect( () => {
        if (!loader.current || loading || !additional) return;

        const observer = new IntersectionObserver( v => {
            if (v[0].isIntersecting) {
                setLoading(true);
                api.logs(logs[logs.length - 1]?.id ?? null, props.focus ?? null).then(response => {
                    setLoading(false);
                    setLogs([...logs, ...response.entries]);
                    setAdditional(response.additional);
                })
            }
        }, {
            root: null,
            rootMargin: '64px'
        } );

        observer.observe( loader.current );
        return () => observer.unobserve( loader.current );
    } )

    useLayoutEffect(() => {
        let cache = [];
        parent.current?.querySelectorAll('div.username,span.username').forEach((elem:HTMLElement) => cache.push( $.html.handleUserPopup(elem) ));
        return ()=>cache.forEach( d => $.html.discardUserPopup(d) );
    });

    return (
        <div className="log-container">
            <div className="log">
                <div className="log-content" ref={parent}>
                    { strings && logs.map( log => <div data-disabled={log.past && 'disabled'} className={`log-entry log-entry-hxp log-entry-${log.value < 0 ? 'minus' : 'plus'} ${log.reset ? 'log-entry-hxp-reset' : ''}`} key={log.id}>
                        <span className="log-part-value">{ log.value > 0 ? `+${log.value}` : (log.value < 0 ? `${log.value}` : '')  }</span>
                        <span className="log-part-content">
                            <div className="log-part-header">
                                <div>
                                    {(new Date(log.timestamp * 1000)).toLocaleDateString()}
                                    { log.past && ` | ${log.past}` }
                                </div>
                                {!log.reset && log['type'] === 200 && <div>{strings.common.unique}</div>}
                                {log.reset && <div>{strings.common.reset}</div>}
                            </div>
                            <span className="container" dangerouslySetInnerHTML={{__html: log.text}}></span>
                        </span>
                    </div> ) }
                    { (additional || !strings) && <div ref={loader} className="loading"/> }
                    { strings && logs.length === 0 && !additional && <span className="small">{ strings.common.empty }</span>}
                </div>
            </div>
        </div>
    )
};
