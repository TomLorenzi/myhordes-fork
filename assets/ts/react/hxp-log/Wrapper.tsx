import * as React from "react";
import { createRoot } from "react-dom/client";
import {useContext, useEffect, useLayoutEffect, useRef, useState} from "react";
import {HxpLogAPI, LogEntry} from "./api";
import {TranslationStrings} from "./strings";
import {Tooltip} from "../tooltip/Wrapper";
import {Global} from "../../defaults";

declare var $: Global;

interface mountProps {
    focus: number
}


export class HordesHxpLog {

    #_root = null;

    public mount(parent: HTMLElement, props: mountProps): any {
        if (!this.#_root) this.#_root = createRoot(parent);
        this.#_root.render( <HordesHxpLogWrapper {...props} /> );
    }

    public unmount() {
        if (this.#_root) {
            this.#_root.unmount();
            this.#_root = null;
        }
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

    return (
        <div className="log-container">
            <div className="log">
                <div className="log-content" ref={parent}>
                    { strings && logs.map( log => <div className={`log-entry log-entry-hxp log-entry-${log.value < 0 ? 'minus' : 'plus'} ${log.reset ? 'log-entry-hxp-reset' : ''}`} key={log.id}>
                        <span className="log-part-value">{ log.value > 0 ? `+${log.value}` : (log.value < 0 ? `${log.value}` : '')  }</span>
                        <span className="log-part-content">
                            <div className="log-part-header">
                                <div>{(new Date(log.timestamp * 1000)).toLocaleDateString()}</div>
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
