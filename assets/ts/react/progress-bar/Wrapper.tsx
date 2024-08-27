import * as React from "react";
import { createRoot } from "react-dom/client";
import {useLayoutEffect, useRef, useState} from "react";
import {number} from "prop-types";


export class HordesProgressBar {

    #_root = null;

    public mount(parent: HTMLElement, props: any): any {
        if (!this.#_root) this.#_root = createRoot(parent);
        this.#_root.render(<ProgressBar {...props} />);
    }

    public unmount() {
        if (this.#_root) {
            this.#_root.unmount();
            this.#_root = null;
        }
    }
}

export const ProgressBar = (
    {animateFrom, animateTo, limit, step, xp}: {
        animateFrom: number,
        animateTo: number,
        limit: number,
        step: number,
        xp: boolean
    }) => {

    const [baseLimit, setBaseLimit] = useState(xp ? step : limit);
    const [value, setValue] = useState(animateTo);

    const bar = useRef<HTMLDivElement>(null);
    const current = useRef(animateFrom);
    const fast = useRef(false);

    const fun_next_goal = (c: number) => {
        return c + step;
    }

    useLayoutEffect(() => {
        const goal = 100 * Math.min(Math.max(0, value/baseLimit), 1);
        const animation = bar.current.animate([
            { width: `${current.current}%` },
            { width: `${goal}%` },
        ], {
            easing: "ease-in-out", fill: "both",
                ...(fast.current ? {delay: 100, duration: 100} : {delay: 500, duration: 500} )
        });
        animation.addEventListener('finish', () => {
            current.current = goal;
            if (xp && goal >= 100) {
                const next = fun_next_goal(baseLimit);
                fast.current = value > next;
                setBaseLimit(next)
            }
            else fast.current = false;
        })

        return () => animation.cancel();
    }, [baseLimit,value]);

    return (
        <div className="fancy-progress-bar">
            <div className="text">
                {xp && <span>{value}&nbsp;/&nbsp;{baseLimit}</span>}
            </div>
            <div className="progressbar">
                <div className="progressbar-container">
                    <div className={`inner ${xp && animateTo > step ? 'flashy' : ''}`} ref={bar}/>
                </div>
            </div>
        </div>
    )
};