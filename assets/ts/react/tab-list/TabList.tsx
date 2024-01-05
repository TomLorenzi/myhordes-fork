import * as React from "react";
import {ReactElement, useLayoutEffect, useRef, useState} from "react";

export interface TabProps {
    title?: string,
    icon?: string,
    id: string,
    'if'?: boolean
}

type TabPropsLeaf = TabProps & {
    children: any|any[],
}

type TabPropsGroup = TabProps & {
    children: ReactElement<TabPropsLeaf>[],
}

export const TabbedSection = ( {defaultTab, children, mountOnlyActive, keepInactiveMounted, className}: {
    defaultTab?: string,
    children: (ReactElement<TabProps>)[],
    mountOnlyActive?: boolean
    keepInactiveMounted?: boolean,
    className?: string,
} ) => {
    const [selected, setSelected] = useState<string>(children.find(t => t.props.id === (defaultTab ?? 'default'))?.props?.id ?? (children.filter(t => typeof t.props['if'] === "undefined" || t.props['if']))[0]?.props.id ?? null);
    const [group, setGroup] = useState<string>(null);
    const [mounted, setMounted] = useState<string[]>([selected].filter(v => v !== null))

    const getType = (v: ReactElement<any>) => v.type === null ? null : (
        typeof v.type === "string" ? v.type : (
            (v.type as Function).name
        )
    );

    const renderTab = (t: ReactElement<TabProps>, hidden: boolean = false, inGroup: string|null = null) =>
        <li data-group={inGroup ?? ''} key={t.props.id} style={hidden ? {display: 'none'} : {}} className={`tab ${selected === t.props.id ? 'selected' : ''}`}>
            <div className="tab-link"
                onClick={selected === t.props.id ? () => {
                } : () => {
                    if (!inGroup && group !== null)
                        setGroup(null);
                    setSelected(t.props.id);
                    if (!mounted.includes(t.props.id)) setMounted([...mounted, t.props.id]);
                }}>
                {t.props.icon && <img alt="" src={t.props.icon}/>}
                {t.props.icon && t.props.title && <>&nbsp;</>}
                {t.props.title &&
                    <span className={t.props.icon ? 'hide-md hide-sm' : ''}>{t.props.title}</span>}
            </div>
        </li>

    let leafs: ReactElement<TabPropsLeaf>[] = [];
    children.forEach(child => {
        switch (getType(child)) {
            case "Tab":
                leafs.push( child as ReactElement<TabPropsLeaf> );
                break;
            case "TabGroup": (child as ReactElement<TabPropsGroup>).props.children.forEach( sub => leafs.push( sub ) )
                break;
        }
    })

    const me = useRef<HTMLUListElement>();
    const prevGroup = useRef<string>(null);
    const hideGroupAnimation = ((s:string) => {
        const affected = me.current.querySelectorAll(`li.tab[data-group="${s}"]`);
        const unaffected = me.current.querySelectorAll(`li.tab:not([data-group="${s}"])`);

        let unaffected_positions: [HTMLElement,number|null,number|null,number,number][] = [];
        unaffected.forEach(e => {
            unaffected_positions.push( [(e as HTMLElement),null,null,(e as HTMLElement).offsetLeft, (e as HTMLElement).offsetTop] )
        });

        affected.forEach( e => {
            (e as HTMLElement).style.display = null
        } );
        requestAnimationFrame(() => {
            unaffected_positions.map( ([e,,,left,top]) => [e,e.offsetLeft,e.offsetTop,left,top] ).filter(([,l1,t1,l2,t2]) => l1 !== l2 || t1 !== t2).forEach(([e,l1,t1,l2,t2]) => {
                (e as HTMLElement).animate([
                    {position: 'absolute', left: `${l1}px`, top: `${t1}px`},
                    {position: 'absolute', left: `${l2}px`, top: `${t2}px`}
                ], {
                    fill: "none",
                    duration: 200,
                    easing: "ease-in-out"
                })
            });

            affected.forEach( e => {
                (e as HTMLElement).animate([
                    {display: 'inline-block', top: '0px', maxHeight: `${(e as HTMLElement).offsetHeight}px`},
                    {display: 'inline-block', top: `${(e as HTMLElement).offsetHeight}px`, maxHeight: '0px'}
                ], {
                    fill: "none",
                    duration: 100,
                    easing: "ease-in-out"
                }).addEventListener('finish', () => (e as HTMLElement).style.display = 'none')
            } );
        })
    })
    const showGroupAnimation = ((s:string) => {
        const affected = me.current.querySelectorAll(`li.tab[data-group="${s}"]`);
        const unaffected = me.current.querySelectorAll(`li.tab:not([data-group="${s}"])`);

        let unaffected_positions: [HTMLElement,number|null,number|null,number,number][] = [];
        unaffected.forEach(e => {
            unaffected_positions.push( [(e as HTMLElement),null,null,(e as HTMLElement).offsetLeft, (e as HTMLElement).offsetTop] )
        });

        affected.forEach( e => {
            (e as HTMLElement).style.display = 'none';
        } );

        requestAnimationFrame(() => {
            unaffected_positions.map( ([e,,,left,top]) => [e,e.offsetLeft,e.offsetTop,left,top] ).filter(([,l1,t1,l2,t2]) => l1 !== l2 || t1 !== t2).forEach(([e,l1,t1,l2,t2]) => {
                (e as HTMLElement).animate([
                    {position: 'absolute', left: `${l1}px`, top: `${t1}px`},
                    {position: 'absolute', left: `${l2}px`, top: `${t2}px`}
                ], {
                    fill: "none",
                    duration: 200,
                    easing: "ease-in-out"
                })
            });

            affected.forEach( e => {
                (e as HTMLElement).style.display = null;

                (e as HTMLElement).animate([
                    {top: `${(e as HTMLElement).offsetHeight}px`, maxHeight: '0px'},
                    {top: '0px', maxHeight: `${(e as HTMLElement).offsetHeight}px`}
                ], {
                    fill: "none",
                    duration: 100,
                    easing: "ease-in-out"
                })
            } );
        })
    })

    useLayoutEffect(() => {
        if (prevGroup.current && !group) hideGroupAnimation( prevGroup.current );
        if (!prevGroup.current && group) showGroupAnimation( group );
        prevGroup.current = group;
    }, [group]);

    return <>
        <ul className={`tabs plain ${className}`} ref={me}>
            {children.filter(t => (typeof t.props['if'] === "undefined") || t.props['if']).map(t => <React.Fragment key={t.props.id}>
                {getType(t) === "Tab" && renderTab(t) }
                {getType(t) === "TabGroup" && (t as ReactElement<TabPropsGroup>).props.children.length > 0 && <>
                    <li className={`tab tab-group ${group === t.props.id ? 'selected' : ''}`}>
                        <div className="tab-link"
                             onClick={() => {
                                 if (group !== t.props.id) {
                                     setGroup(t.props.id);
                                     const firstChildID = (t as ReactElement<TabPropsGroup>).props.children[0].props.id;
                                     setSelected( firstChildID )
                                     if (!mounted.includes(firstChildID)) setMounted([...mounted, firstChildID]);
                                 }
                             }}>
                            {t.props.icon && <img alt="" src={t.props.icon}/>}
                            {t.props.icon && t.props.title && <>&nbsp;</>}
                            {t.props.title &&
                                <span className={t.props.icon ? 'hide-md hide-sm' : ''}>{t.props.title}</span>}
                        </div>
                    </li>
                    { (t as ReactElement<TabPropsGroup>).props.children.map(v => <React.Fragment key={v.props.id}>{ renderTab(v, group !== t.props.id, t.props.id) }</React.Fragment>) }
                </>}
            </React.Fragment>)}
        </ul>
        {mountOnlyActive && !keepInactiveMounted && leafs.find(t => t.props.id === selected)}
        {!mountOnlyActive && leafs.map(t => <div key={t.props.id}
                                                 className={selected === t.props.id ? 'opt-tab-container' : 'opt-tab-container hidden'}>{t}</div>)}
        {mountOnlyActive && keepInactiveMounted && leafs.filter(t => mounted.includes(t.props.id)).map(t => <div
            key={t.props.id} className={selected === t.props.id ? 'opt-tab-container' : 'opt-tab-container hidden'}>{t}</div>)}
    </>
}

export class Tab extends React.Component<TabPropsLeaf, {}> {
    render() {
        return this.props.children;
    }
}

export class TabGroup extends React.Component<TabPropsGroup, {}> {
    render() {
        return null;
    }
}