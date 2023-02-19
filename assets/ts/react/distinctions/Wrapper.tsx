import * as React from "react";
import { createRoot } from "react-dom/client";
import {useEffect, useLayoutEffect, useRef, useState} from "react";
import {Global} from "../../defaults";
import Components from "../index";
import {TranslationStrings} from "./strings";
import {DistinctionAward, DistinctionPicto, ResponseDistinctions, SoulDistinctionAPI} from "./api";

declare var $: Global;

export class HordesDistinctions {

    #_root = null;

    public mount(parent: HTMLElement, props: {  }): any {
        if (!this.#_root) this.#_root = createRoot(parent);
        this.#_root.render( <Distinctions {...props} /> );
    }

    public unmount() {
        if (this.#_root) {
            this.#_root.unmount();
            this.#_root = null;
        }
    }
}

const Distinctions = (
    {user, source, plain}: {
        user?: number
        source?: string
        plain?: boolean
    }) => {

    const apiRef = useRef<SoulDistinctionAPI>( new SoulDistinctionAPI() );

    const wrapper = useRef<HTMLDivElement>();

    const [strings, setStrings] = useState<TranslationStrings>(null)
    const [data, setData] = useState<ResponseDistinctions>(null)
    const [showingAwards, setShowingAwards] = useState<boolean>(false);

    useEffect( () => {
        apiRef.current.index().then(s => setStrings(s.strings) );
    }, [] )

    useEffect( () => {
        setData(null);
        apiRef.current.data(user,source).then(d => setData(d) );
    }, [user,source] )

    useLayoutEffect( () => Components.vitalize( wrapper.current ) )

    const titlesFromPicto = (id: number) => (data.awards ?? [])
        .filter( a => ((id === 0 ? a?.id : a.picto?.id) ?? null) === id )
        .sort( (a1,a2) => (a1.picto?.count ?? 0) - (a2.picto?.count ?? 0) )

    const load_complete = data !== null && strings !== null;

    return (
        <div className="distinctions" ref={wrapper}>
            <div className="distinctions-head center">
                { !plain && load_complete && strings?.common?.header }
            </div>

            { load_complete && data?.points !== null && <div className="distinctions-points">
                { strings?.common?.points?.replace( '{points}', `${data?.points ?? 0}` ) }
            </div> }

            { load_complete && (data?.top3 ?? null) !== null &&
                <div className="distinctions-top">
                    { (data?.top3 ?? []).map( id => data?.pictos?.find( p => p.id === id ) ?? null ).filter(s => s !== null).map( p => <div key={p.id} className={`picto ${p.rare ? 'rare' : ''}`}>
                        <div className="counter-wrapper">
                            <div className="counter">
                                { `${p.count}`.split('').map((s,i) => <span key={i} className="count" data-num={s}>{s}</span> ) }
                            </div>
                        </div>
                        <div className="infos">
                            <img alt="" src={p.icon}/><br/>
                            <div className="label">{ p.label }</div>
                        </div>
                    </div> ) }
                </div>
            }

            <div className="distinctions-list center">

                { !load_complete && <>
                    <div className="loading"></div>
                </> }

                { load_complete && <>
                    { !plain && data?.awards?.length > 0 && <>
                        <ul className="tabs plain">
                            <li className={`tab-soul-distinctions ${showingAwards ? '' : 'selected'}`} onClick={() => setShowingAwards(false)}>
                                <div className="tab-link">{ strings?.common?.tab_picto }</div>
                            </li>
                            <li className={`tab-soul-distinctions ${showingAwards ? 'selected' : ''}`} onClick={() => setShowingAwards(true)}>
                                <div className="tab-link">{ strings?.common?.tab_award } ({ data?.awards?.length })</div>
                            </li>
                        </ul>
                    </> }

                    { !showingAwards && <>
                        <div className={`list ${data?.pictos?.length > 0 ? '' : 'empty'}`}>
                            { !data?.pictos?.length && strings?.pictos?.empty }
                            { (data?.pictos ?? []).filter(p => p.count > 0)?.map( p => <div key={p.id} className={`picto ${p.rare ? 'rare' : ''}`}>
                                <div>
                                    <img alt="" src={p.icon}/><br/>
                                    <div className="counter">
                                        { `${p.count}`.split('').map((s,i) => <span key={i} className="count" data-num={s}>{s}</span> ) }
                                    </div>
                                </div>
                                <div className="tooltip forum-tooltip">
                                    <h1>{ p.label } ({p.count})</h1>
                                    <em>{ p.description }</em>
                                </div>
                            </div>) }
                        </div>
                    </> }

                    { showingAwards && <>
                        <ul className="title-list">
                            { [{id: null}, {id: 0}].concat(data?.pictos ?? []).map( p => [p,titlesFromPicto(p.id)]).filter(([p,awards]) => (awards as object[]).length > 0).map( ([p,awards]) => <>
                                <li className="chapter" key={(p as DistinctionPicto).id ?? 'unique'}>
                                    { (p as DistinctionPicto).id === null && <>
                                        <img alt="" src={strings?.awards?.unique_url} />
                                        &nbsp;
                                        {strings?.awards?.unique}
                                    </> }

                                    { (p as DistinctionPicto).id === 0 && <>
                                        {strings?.awards?.single}
                                    </> }

                                    { (p as DistinctionPicto).id > 0 && <>
                                        <img alt="" src={(p as DistinctionPicto).icon}/>
                                        &nbsp;
                                        {(p as DistinctionPicto).label}
                                        <div className="tooltip forum-tooltip">
                                            <h1>{ (p as DistinctionPicto).label }</h1>
                                            <em>{ (p as DistinctionPicto).description }</em>
                                        </div>
                                    </> }
                                </li>

                                { (awards as DistinctionAward[]).map( award => <li key={award.id}>
                                    "{ award.label }"
                                    <div className="tooltip forum-tooltip">
                                        { award.id > 0 && award.picto && <em>{ (p as DistinctionPicto).label } × { award.picto.count }</em> }
                                        { award.id > 0 && award.picto === null && <em>{ strings?.awards?.single_desc }</em> }
                                        { award.id === 0 && <em>{ strings?.awards?.unique_desc }</em> }
                                    </div>
                                </li> ) }
                            </>) }
                        </ul>
                    </> }
                </> }

            </div>

            <div className="distinctions-foot"></div>
        </div>

    )
};