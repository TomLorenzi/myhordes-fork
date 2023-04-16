import * as React from "react";
import {ReactElement, useState} from "react";

interface TabProps {
    title: string,
    icon?: string,
    id: string,
    children: any|any[]
}

export const TabbedSection = ( {defaultTab, children, mountOnlyActive}: {
    defaultTab?: string,
    children: ReactElement<TabProps>[],
    mountOnlyActive?: boolean
} ) => {
    const [selected, setSelected] = useState<string>(children.find(t => t.props.id === (defaultTab ?? 'default'))?.props?.id ?? children[0]?.props.id ?? null);

    return <>
        <ul className="tabs plain">
            {children.map(t => <li key={t.props.id} className={`tab ${selected === t.props.id ? 'selected' : ''}`}>
                <div className="tab-link" onClick={selected === t.props.id ? ()=>{} : ()=>setSelected(t.props.id)}>
                    { t.props.icon && <img alt="" src={t.props.icon}/> }
                    <span className={ t.props.icon ? 'hide-md hide-sm' : '' }>{ t.props.title }</span>
                </div>
            </li>)}
        </ul>
        { mountOnlyActive && children.find(t => t.props.id === selected) }
        { !mountOnlyActive && children.map( t => <div key={t.props.id} className={selected === t.props.id ? '' : 'hidden'}>{ t }</div> ) }
    </>
}



export class Tab extends React.Component<TabProps, { }> {
    render() {
        return this.props.children;
    }
}