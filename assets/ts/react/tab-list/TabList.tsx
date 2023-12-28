import * as React from "react";
import {ReactElement, useState} from "react";

export interface TabProps {
    title?: string,
    icon?: string,
    id: string,
    children: any|any[],
    'if'?: boolean
}

export const TabbedSection = ( {defaultTab, children, mountOnlyActive, keepInactiveMounted}: {
    defaultTab?: string,
    children: ReactElement<TabProps>[],
    mountOnlyActive?: boolean
    keepInactiveMounted?: boolean
} ) => {
    const [selected, setSelected] = useState<string>(children.find(t => t.props.id === (defaultTab ?? 'default'))?.props?.id ?? children[0]?.props.id ?? null);
    const [mounted, setMounted] = useState<string[]>([selected].filter(v=>v!==null))

    return <>
        <ul className="tabs plain">
            {children.filter(t => (typeof t['if'] === "undefined") || t['if']).map(t => <li key={t.props.id} className={`tab ${selected === t.props.id ? 'selected' : ''}`}>
                <div className="tab-link" onClick={selected === t.props.id ? ()=>{} : ()=> {
                    setSelected(t.props.id);
                    if (!mounted.includes(t.props.id)) setMounted([...mounted,t.props.id]);
                }}>
                    { t.props.icon && <img alt="" src={t.props.icon}/> }
                    { t.props.title && <span className={t.props.icon ? 'hide-md hide-sm' : ''}>{t.props.title}</span> }
                </div>
            </li>)}
        </ul>
        { mountOnlyActive && !keepInactiveMounted && children.find(t => t.props.id === selected) }
        { !mountOnlyActive && children.map( t => <div key={t.props.id} className={selected === t.props.id ? '' : 'hidden'}>{ t }</div> ) }
        { mountOnlyActive && keepInactiveMounted && children.filter(t => mounted.includes(t.props.id)).map( t => <div key={t.props.id} className={selected === t.props.id ? '' : 'hidden'}>{ t }</div> ) }
    </>
}



export class Tab extends React.Component<TabProps, { }> {
    render() {
        return this.props.children;
    }
}