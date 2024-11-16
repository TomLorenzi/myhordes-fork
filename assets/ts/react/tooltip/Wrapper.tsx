import * as React from "react";
import { createPortal } from "react-dom";
import {ReactNode, useLayoutEffect, useRef, useState} from "react";
import {Const, Global} from "../../defaults";
import {BaseMounter} from "../index";

declare var c: Const;
declare var $: Global;

interface mountProps {
    'for': HTMLElement,
    textContent: string|null,
    additionalClasses: string|string[]
    children: ChildNode[]
}

export class HordesTooltip extends BaseMounter<mountProps> {
    protected render(props: mountProps): React.ReactNode {
        return <TooltipImplementation
            forParent={props['for']}
            additionalClasses={props.additionalClasses}
            textContent={props.textContent}
            childNodes={props.children}
            onShowTooltip={ div => props['for'].dispatchEvent( new CustomEvent('tooltipAppear', { detail: { content: div } }),  ) }
            onHideTooltip={ div => props['for'].dispatchEvent( new CustomEvent('tooltipDisappear', { detail: { content: div } }),  ) }
        />;
    }
}

class TooltipGlobal {
    static counter: number = 0;
}

export const Tooltip = (
    props: {
        children?: ReactNode|ReactNode[],
        textContent?: string|null,
        additionalClasses?: string|string[],
        html?: string,
        onShowTooltip?: (elem: HTMLDivElement)=>void,
        onHideTooltip?: (elem: HTMLDivElement)=>void,
    }) => {

    const locationProxy = useRef<HTMLDivElement>(null);
    const [parent, setParent] = useState<HTMLElement>(null);

    useLayoutEffect( () => {
        if (locationProxy.current) {
            setParent(locationProxy.current.parentElement.closest(":not([hordes-tooltip])") as HTMLElement);
            return () => setParent(null);
        }
    } )

    return <><div style={{display: "none"}} ref={locationProxy}><TooltipImplementation
        forParent={parent}
        additionalClasses={props.additionalClasses}
        textContent={props.textContent}
        children={props.children}
        childNodes={[]}
        html={props.html}
        onShowTooltip={ props.onShowTooltip ?? ( ()=>{} ) }
        onHideTooltip={ props.onHideTooltip ?? ( ()=>{} ) }
    /></div></>
}

let tooltipStack = [];

const TooltipImplementation = (
    {forParent = null, children = null,childNodes=[],textContent = null, additionalClasses = [], html = null, onHideTooltip = ()=>{}, onShowTooltip = () => {}}: {
        children?: ReactNode|ReactNode[]|null,
        childNodes?: ChildNode[]|null,
        textContent?: string|null,
        additionalClasses?: string|string[],
        forParent?: HTMLElement|null,
        html?: string,
        onShowTooltip?: (HTMLDivElement)=>void,
        onHideTooltip?: (HTMLDivElement)=>void,
    }) => {

    const key = useRef<number>( ++TooltipGlobal.counter );
    const tooltip = useRef<HTMLDivElement>();

    const getOffset = (obj: HTMLDivElement) => {
        let found = false;
        let accum = 0;
        tooltipStack.forEach( (tt) => {
            if (found) return;
            if (tt === obj) found = true;
            else accum += tt.clientHeight + 5;
        } );
        return accum;
    }

    useLayoutEffect(() => {
        if (!forParent) return () => {};
        const fun_tooltip_pos = function(pointer: boolean = false) {
            return function(e: PointerEvent|MouseEvent) {

                if (pointer) {
                    if (e instanceof PointerEvent && e.pointerType === 'mouse') return;

                    // Center the tooltip below the parent
                    tooltip.current.style.top  = forParent.getBoundingClientRect().top + forParent.clientHeight + getOffset(tooltip.current) + 'px';
                    tooltip.current.style.left = (window.innerWidth - tooltip.current.clientWidth)/2 + 'px';

                } else if (tooltip.current.dataset.touchtip !== '1') {
                    tooltip.current.style.top  = e.clientY + getOffset(tooltip.current) + 'px';

                    // Make sure the tooltip does not exit the screen on the right
                    // If it does, attach it left to the cursor instead of right
                    if (e.clientX + tooltip.current.clientWidth + 25 > window.innerWidth) {

                        // Make sure the tooltip does not exit the screen on the left
                        // If it does, center it on screen below the cursor
                        if ( (e.clientX - tooltip.current.clientWidth - 50) < 0 ) {
                            tooltip.current.style.left = (window.innerWidth - tooltip.current.clientWidth)/2 + 'px';
                        } else tooltip.current.style.left = (e.clientX - tooltip.current.clientWidth - 50) + 'px';

                    } else tooltip.current.style.left = e.clientX + 'px';

                    // Make sure the tooltip does not exit the screen on the bottom
                    // If it does, attach it above the cursor instead of below
                    if (e.clientY + tooltip.current.clientHeight + 25 > window.innerHeight)
                        tooltip.current.style.top  = e.clientY - 25 - tooltip.current.clientHeight - getOffset(tooltip.current) + 'px';
                }
            }
        }

        const fun_tooltip_hide = function(e: PointerEvent|TouchEvent|MouseEvent) {
            tooltipStack = tooltipStack.filter(t => t !== tooltip.current);
            onHideTooltip( tooltip.current );
            tooltip.current.removeAttribute('style');
            tooltip.current.dataset.touchtip = '0';
        }

        const fun_tooltip_show = function(pointer: boolean) {
            return function(e: PointerEvent|MouseEvent) {
                if (pointer && e instanceof PointerEvent && e.pointerType === 'mouse') return;
                tooltip.current.style.display = 'block';
                fun_tooltip_pos(pointer)(e);
                onShowTooltip( tooltip.current );
                tooltipStack.push( tooltip.current );
                if (pointer && $.client.config.twoTapTooltips.get()) {
                    if (forParent.dataset.stage !== '1') {
                        document.body.addEventListener('click', e => e.stopPropagation(),
                            {capture: true, once: true});
                        forParent.addEventListener('click', () => forParent.dataset.stage = '0', {once: true})
                        window.addEventListener('scroll', () => fun_tooltip_hide(e), {once: true})
                    }

                    document.querySelectorAll( '[data-stage="1"]').forEach(e => (e as HTMLElement).dataset.stage = '0' );
                    document.querySelectorAll( '[data-touchtip="1"]').forEach(e => {
                        (e as HTMLElement).dataset.touchtip = '0';
                        (e as HTMLElement).removeAttribute('style');
                        }
                    );

                    forParent.dataset.stage = tooltip.current.dataset.touchtip = '1';

                    if (!$.client.config.ttttHelpSeen.get()) {
                        alert(c.taptut);
                        $.client.config.ttttHelpSeen.set(true);
                    }
                    document.body.addEventListener('click', () => fun_tooltip_hide(e), {once: true})
                }
            }
        }

        const fun_tooltip_pos_false = fun_tooltip_pos(false);

        const fun_tooltip_show_true = fun_tooltip_show(true);
        const fun_tooltip_show_false = fun_tooltip_show(false);

        forParent.addEventListener('pointerdown',  fun_tooltip_show_true);
        forParent.addEventListener('mouseenter',   fun_tooltip_show_false);

        forParent.addEventListener('mousemove', fun_tooltip_pos_false);
        forParent.addEventListener('mouseleave',   fun_tooltip_hide);

        if (!$.client.config.twoTapTooltips.get()) {
            forParent.addEventListener('pointerleave', fun_tooltip_hide);
            forParent.addEventListener('pointerup',    fun_tooltip_hide);
            forParent.addEventListener('touchend',     fun_tooltip_hide);
        }

        return () => {
            forParent.removeEventListener('pointerdown',  fun_tooltip_show_true);
            forParent.removeEventListener('mouseenter',   fun_tooltip_show_false);

            forParent.removeEventListener('mousemove', fun_tooltip_pos_false);
            forParent.removeEventListener('mouseleave',   fun_tooltip_hide);

            if (!$.client.config.twoTapTooltips.get()) {
                forParent.removeEventListener('pointerleave', fun_tooltip_hide);
                forParent.removeEventListener('pointerup',    fun_tooltip_hide);
                forParent.removeEventListener('touchend',     fun_tooltip_hide);
            }
        }

    }, [forParent]);

    useLayoutEffect(() => {
        if (childNodes && tooltip.current) {
            childNodes.forEach( f => tooltip.current.appendChild( f ));
            return () => childNodes.forEach( f => tooltip.current.removeChild( f ));
        }
    });

    const classNames = ['tooltip', ...(typeof additionalClasses === "object" ? (additionalClasses as string[]) : [additionalClasses as string])].join(' ');

    const parentDialog = forParent?.closest('dialog');
    if (parentDialog && !parentDialog.querySelector('div[data-dialog-tooltip-target]')) {
        const d = document.createElement('div');
        d.dataset.dialogTooltipTarget = '1';
        parentDialog.appendChild(d);
    }


    return createPortal(
        html
            ? <div ref={tooltip} className={classNames} dangerouslySetInnerHTML={{__html: html}}/>
            : <div ref={tooltip} className={classNames}>
                { textContent }
                { children }
            </div>
    , parentDialog?.querySelector('div[data-dialog-tooltip-target]') ?? document.getElementById('tooltip_container'), `react-tooltip-${key.current}` )
};