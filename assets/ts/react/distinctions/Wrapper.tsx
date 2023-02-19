import * as React from "react";
import { createRoot } from "react-dom/client";
import {PointerEventHandler, useEffect, useLayoutEffect, useRef, useState} from "react";
import {Global} from "../../defaults";
import Components from "../index";
import {TranslationStrings} from "./strings";
import {DistinctionAward, DistinctionPicto, ResponseDistinctions, SoulDistinctionAPI} from "./api";
import {number} from "prop-types";
import HTML from "../../html";

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
    {user, source, plain, interactive}: {
        user?: number
        source?: string
        plain?: boolean
        interactive?: boolean
    }) => {

    const apiRef = useRef<SoulDistinctionAPI>( new SoulDistinctionAPI() );

    const wrapper = useRef<HTMLDivElement>();

    const [strings, setStrings] = useState<TranslationStrings>(null)
    const [data, setData] = useState<ResponseDistinctions>(null)
    const [showingAwards, setShowingAwards] = useState<boolean>(false);
    const [dragging, setDragging] = useState<{ x: number, y: number, id: number, orig: {x: number, y: number} }>(null);

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

    const ev_pointerDown = (id: number): PointerEventHandler<HTMLDivElement> => {
        return event => {
            setDragging({id, x: 0, y: 0, orig: { x: event.pageX, y: event.pageY }} );
            event.preventDefault();
        }
    }

    const t3conf = (data?.top3 ?? []).map( id => data?.pictos?.find( p => p.id === id ) ?? null ).filter(s => s !== null);

    const ev_targetPointerUp = (id: number): PointerEventHandler<HTMLDivElement> => {
        return event => {
            let newt3 = [...data?.top3];
            const existing_key = newt3.findIndex( v => v === dragging.id );
            if (existing_key === id) return;

            if (existing_key >= 0) newt3[ existing_key ] = newt3[ id ];
            newt3[ id ] = dragging.id;

            setData( {...data, top3: newt3} );

            apiRef.current.top3( user, newt3 ).then(d => {
                d.updated = d.updated.map((v,i) => v ?? newt3[i] );
                setData( {...data, top3: d.updated} );
            })

            event.preventDefault();
        }
    }

    useEffect( () => {
        if (!dragging?.id) return;

        const ev_pointerUp = function(this: HTMLBodyElement, event: PointerEvent) {
            setDragging(null );
            event.preventDefault();
        }

        const ev_pointerMove = function(this: HTMLBodyElement, event: PointerEvent) {
            setDragging({...dragging, x: event.pageX - dragging.orig.x, y: event.pageY - dragging.orig.y });
            event.preventDefault();
        }

        document.body.style.setProperty('cursor', 'move', 'important');
        document.body.addEventListener( "pointerup", ev_pointerUp );
        document.body.addEventListener( "pointermove", ev_pointerMove );
        return () => {
            document.body.style.removeProperty('cursor');
            document.body.removeEventListener( "pointerup", ev_pointerUp );
            document.body.removeEventListener( "pointermove", ev_pointerMove );
        }
    }, [ dragging ] )

    return (
        <div className="distinctions" ref={wrapper}>
            <div className="distinctions-head center">
                { !plain && load_complete && strings?.common?.header }
            </div>

            { load_complete && data?.points !== null && <div className="distinctions-points">
                { strings?.common?.points?.replace( '{points}', `${data?.points ?? 0}` ) }
            </div> }

            { load_complete && (data?.top3 ?? null) !== null &&
                <div className={`distinctions-top ${dragging !== null ? 'targeting' : ''}`}>
                    { t3conf.map( (p,pos) =>
                        <div
                            key={p.id} className={`picto ${p.rare ? 'rare' : ''}`}
                            onPointerUp={ dragging ? ev_targetPointerUp(pos) : ()=>{} }
                        >
                            <div className="counter-wrapper">
                                <div className="counter">
                                    { `${p.count}`.split('').map((s,i) => <span key={i} className="count" data-num={s}>{s}</span> ) }
                                </div>
                            </div>
                            <div className="infos">
                                <img alt="" src={p.icon}/><br/>
                                <div className="label">{ p.label }</div>
                            </div>
                        </div>
                    ) }
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
                            { (data?.pictos ?? []).filter(p => p.count > 0)?.map( p =>
                                <div
                                    key={p.id} className={`picto ${p.rare ? 'rare' : ''} ${interactive ? 'draggable' : ''} ${p.id === dragging?.id ? 'dragging' : ''}`}
                                    onPointerDown={interactive ? ev_pointerDown(p.id) : ()=>{}}
                                    style={ p.id === dragging?.id ? {left: `${dragging.x}px`, top: `${dragging.y}px`} : {} }
                                >
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
                                </div>
                            ) }
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