import * as React from "react";
import {
    ChangeEventHandler,
    HTMLInputTypeAttribute,
    InputHTMLAttributes,
    useContext,
    useLayoutEffect,
    useRef
} from "react";
import {Globals} from "./Wrapper";
import {Global} from "../../defaults";

declare var $: Global;

interface OptionTemplateArgs {
    propName: string
    propTitle?: string
    propHelp?: string
    propTip?: string
}

interface OptionCoreTemplateArgs extends OptionTemplateArgs {
    children: React.ReactNode,
    wide?: boolean
}

interface OptionArgs extends OptionTemplateArgs {
    onChange?: ChangeEventHandler
}

export const OptionCoreTemplate = (props: OptionCoreTemplateArgs) => {
    const globals = useContext(Globals)

    const tooltip = useRef<HTMLDivElement>();
    const tooltip_parent = useRef<HTMLDivElement>();

    useLayoutEffect( () => {
        if (tooltip.current) $.html.handleTooltip( tooltip.current );
        return () => $.html.clearTooltips( tooltip_parent.current );
    } );

    return (
        <div className="row">
            <div ref={tooltip_parent} className={`cell padded rw-3 rw-md-6 rw-sm-12 ${ props.propTitle ? 'note note-lightest' : '' }`}>
                { props.propTitle && (
                    <label htmlFor={props.propName} style={{display: 'inline-flex', columnGap: '6px', alignItems: 'center' }}>
                        { props.propTitle }
                        { props.propTip && (
                            <a className="help-button">
                                { globals.strings.common.help }
                                <div className="tooltip help" ref={tooltip} dangerouslySetInnerHTML={{__html: props.propTip}}/>
                            </a>
                        ) }
                    </label>
                ) }
            </div>
            <div className={`cell padded ${(props.wide ?? false) ? 'rw-9' : 'rw-3'} rw-md-6 rw-sm-12`}>
                { props.children }
            </div>
            { props.propHelp && (
                <div className={`cell padded ${(props.wide ?? false) ? 'rw-12' : 'rw-6'} rw-md-12`}>
                    <div className="help" dangerouslySetInnerHTML={{__html: props.propHelp }}/>
                </div>
            ) }
        </div>
    )
};

interface OptionFreeTextArgs extends OptionArgs {
    value: string|undefined,
    'type'?: HTMLInputTypeAttribute,
    inputArgs?: InputHTMLAttributes<HTMLInputElement>
}
export const OptionFreeText = (props: OptionFreeTextArgs) => {
    const globals = useContext(Globals)
    return (
        <OptionCoreTemplate {...props}>
            <input {...props.inputArgs ?? {}} type={props["type"] ?? 'text'} name={props.propName} value={props.value ?? ''} onChange={props.onChange ?? globals.setOption} />
        </OptionCoreTemplate>
    )
}

interface OptionSelectArgs extends OptionArgs {
    value: string|undefined,
    multi?: boolean, 'type'?: HTMLInputTypeAttribute,
    options: { value: string, title: string, help?: string }[]
}
export const OptionSelect = (props: OptionSelectArgs) => {
    const globals = useContext(Globals)

    const selection = props.options?.filter( option => option.value === props.value )?.pop();
    const combined_help = [
        ...(props.propHelp ? [props.propHelp] : []),
        ...(selection?.help ? [`<strong>${selection.title}:</strong> ${selection?.help}`] : [])
    ].filter( v=>v ).join('<br/>');

    return (
        <OptionCoreTemplate {...props} propHelp={combined_help}>
            <select name={props.propName} value={props.value ?? ''} data-value-type={props.type ?? 'text'} onChange={props.onChange ?? globals.setOption} multiple={props.multi ?? false}>
                { props.options.map( option => <React.Fragment key={option.value}>
                    <option value={option.value} title={ option.help ?? '' }>{ option.title }</option>
                </React.Fragment> ) }
            </select>
        </OptionCoreTemplate>
    )
}

interface OptionToggleMultiArgs extends OptionArgs {
    options: { value: boolean, name: string, title: string, help?: string, onChange?: ChangeEventHandler }[]
}
export const OptionToggleMulti = (props: OptionToggleMultiArgs) => {
    const globals = useContext(Globals)
    return (
        <OptionCoreTemplate {...props} wide={true}>
            <div data-map-property={props.propName} className="mod">
                { props.options.map( option => <React.Fragment key={option.name}>
                    <div><input type="checkbox" data-prop-name={option.name} name={`${props.propName}-${option.name}`} checked={option.value} onChange={option.onChange ?? props.onChange ?? globals.setOption} />
                        <label htmlFor={`${props.propName}-${option.name}`}>
                            <strong>{option.title}{ option.help ? ':' : '' }</strong>
                            { option.help ? ' ' : '' }
                            { option.help }
                        </label>
                    </div>
                </React.Fragment> ) }
            </div>
        </OptionCoreTemplate>
    )
}