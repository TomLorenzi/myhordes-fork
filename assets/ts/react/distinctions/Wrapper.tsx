import * as React from "react";
import {PointerEventHandler, useEffect, useLayoutEffect, useRef, useState} from "react";
import Components, {BaseMounter} from "../index";
import {TranslationStrings} from "./strings";
import {DistinctionAward, DistinctionPicto, ResponseDistinctions, SoulDistinctionAPI} from "./api";
import {Tooltip} from "../tooltip/Wrapper";


export class HordesDistinctions extends BaseMounter<{  }>{
    protected render(props: {}): React.ReactNode {
        return <Distinctions {...props} />;
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
    const [dragging, setDragging] = useState<{ id: number }>(null);

    const currentNode = useRef<HTMLDivElement>();
    const currentDrag = useRef<{ cur: { x: number, y: number }, orig: {x: number, y: number}, handled: boolean }>( { cur: { x: 0, y: 0 }, orig: {x: 0, y: 0}, handled: false } );

    const allTargets = useRef<HTMLDivElement>(null);
    const currentTarget = useRef<HTMLDivElement>(null);

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
            currentNode.current = (event.target as HTMLDivElement).closest('.picto') as HTMLDivElement;
            currentDrag.current = { cur: {x: 0, y: 0}, orig: {x: event.pageX, y: event.pageY}, handled: false }
            setDragging({id} );
            event.preventDefault();
        }
    }

    const t3conf = (data?.top3 ?? []).map( id => data?.pictos?.find( p => p.id === id ) ?? null ).filter(s => s !== null);

    const setTarget = (id: number) => {
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

        currentDrag.current.handled = true;
        const target = currentNode.current;
        const animation = target.animate([
            {transform: 'scale(1)', opacity: 1, left: `${currentDrag.current.cur.x}px`, top: `${currentDrag.current.cur.y}px`,  pointerEvents: 'none'},
            {transform: 'scale(0)', opacity: 0, left: `${currentDrag.current.cur.x}px`, top: `${currentDrag.current.cur.y}px`,  pointerEvents: 'none', offset: 0.90},
            {transform: 'scale(0)', opacity: 0, left: "0", top: "0",  pointerEvents: 'none', offset: 0.95},
            {transform: 'scale(1)', opacity: 1, left: "0", top: "0", pointerEvents: 'none'}
        ], {duration: 500, easing: 'ease-out'});
        animation.oncancel = animation.onfinish = () => target.style.pointerEvents = target.style.left = target.style.top = null;
    }

    const ev_targetPointerUp = (id: number): PointerEventHandler<HTMLDivElement> => {
        return event => {
            setTarget(id);
            event.preventDefault();
        }
    }

    useEffect( () => {
        if (!dragging?.id) return;

        const ev_pointerUp = function(this: HTMLBodyElement, event: PointerEvent) {
            setDragging(null );
            if (!currentDrag.current.handled && currentTarget.current)
                setTarget( parseInt(currentTarget.current.dataset.key) );

            if (!currentDrag.current.handled) {
                const target = currentNode.current;
                const animation = target.animate([
                    {left: `${currentDrag.current.cur.x}px`, top: `${currentDrag.current.cur.y}px`, pointerEvents: 'none'},
                    {left: "0", top: "0", pointerEvents: 'none'}
                ], {duration: 100, easing: 'ease-out'});
                animation.oncancel = animation.onfinish = () => target.style.pointerEvents = target.style.left = target.style.top = null;
            }

            currentTarget.current = null;
            allTargets.current.querySelectorAll('[data-key]').forEach( n => n.classList.remove('hover') )

            event.preventDefault();
        }

        const overlapping = (a: DOMRect, b: DOMRect) => {
            return !(
                a.right < b.left || a.left > b.right ||
                a.bottom < b.top || a.top > b.bottom
            )
        }

        const ev_pointerMove = function(this: HTMLBodyElement, event: PointerEvent) {
            currentNode.current.style.left = `${currentDrag.current.cur.x = event.pageX - currentDrag.current.orig.x}px`;
            currentNode.current.style.top  = `${currentDrag.current.cur.y = event.pageY - currentDrag.current.orig.y}px`;

            const c_rect = currentNode.current.getBoundingClientRect();
            const p_rect = allTargets.current.getBoundingClientRect();
            if (overlapping(c_rect, p_rect)) {

                currentTarget.current = Array.from(allTargets.current.querySelectorAll('[data-key]'))
                    .map( (node) => { node.classList.remove('hover'); return node as HTMLDivElement; } )
                    .filter( (node) => overlapping( c_rect, node.getBoundingClientRect() ) )[0] ?? null;
                currentTarget.current?.classList.add('hover');

            } else if (currentTarget.current) {
                allTargets.current.querySelectorAll('[data-key]').forEach( n => n.classList.remove('hover') )
                currentTarget.current = null;
            }

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
        <div className="distinctions" ref={wrapper} style={interactive ? {touchAction: "none"} : {}}>
            <div className="distinctions-head center">
                { !plain && load_complete && strings?.common?.header }
            </div>

            { load_complete && data?.points !== null && <div className="distinctions-points">
                { strings?.common?.points?.replace( '{points}', `${data?.points ?? 0}` ) }
            </div> }

            { load_complete && (data?.top3 ?? null) !== null &&
                <div ref={allTargets} className={`distinctions-top ${dragging !== null ? 'targeting' : ''}`}>
                    { t3conf.map( (p,pos) =>
                        <div data-key={pos}
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
                                >
                                    <div>
                                        <img alt="" src={p.icon}/><br/>
                                        <div className="counter">
                                            { `${p.count}`.split('').map((s,i) => <span key={i} className="count" data-num={s}>{s}</span> ) }
                                        </div>
                                    </div>
                                    <Tooltip additionalClasses="forum-tooltip">
                                        <h1>{ p.label } ({p.count})</h1>
                                        <em>{ p.description }</em>
                                        { p.comments.length > 0 && <ul>
                                            { p.comments.map( (s,i) => <li key={i}>{ `« ${s} »` }</li> ) }
                                        </ul> }
                                    </Tooltip>
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
                                        <Tooltip additionalClasses="forum-tooltip">
                                            <h1>{ (p as DistinctionPicto).label }</h1>
                                            <em>{ (p as DistinctionPicto).description }</em>
                                        </Tooltip>
                                    </> }
                                </li>

                                { (awards as DistinctionAward[]).map( award => <li key={award.id}>
                                    "{ award.label }"
                                    <Tooltip additionalClasses="forum-tooltip">
                                        <>
                                            { award.id > 0 && award.picto && <em>{ (p as DistinctionPicto).label } × { award.picto.count }</em> }
                                            { award.id > 0 && award.picto === null && <em>{ strings?.awards?.single_desc }</em> }
                                            { award.id === 0 && <em>{ strings?.awards?.unique_desc }</em> }
                                        </>
                                    </Tooltip>
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