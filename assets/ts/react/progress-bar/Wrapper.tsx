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
        step: number[],
        xp: boolean
    }) => {

    const [baseLimit, setBaseLimit] = useState(xp ? step[0] : limit);
    const [value, setValue] = useState(animateTo);

    const bar = useRef<HTMLDivElement>(null);
    const bars = useRef<HTMLDivElement>(null);
    const current = useRef(animateFrom);
    const currents = useRef<number[]>([]);
    const fast = useRef(false);

    const [currentStep, setCurrentStep] = useState(0);

    const fun_next_goal = (c: number) => {
        return c + step[Math.min(currentStep + 1, step.length - 1)];
    }

    const fun_level_goal = (s: number) => {
        let c = 0;
        for (let i = 0; i <= s; i++)
            c += step[Math.min(i, step.length - 1)];
        return c;
    }

    useLayoutEffect(() => {
        const goal = 100 * Math.min(Math.max(0, value/baseLimit), 1);

        const animation = [bar.current.animate([
            { width: `${current.current}%` },
            { width: `${goal}%` },
        ], {
            easing: "ease-in-out", fill: "both",
                ...(fast.current ? {delay: 100, duration: 200} : {delay: 100, duration: 500} )
        })];

        bars.current.querySelectorAll('[data-pack-step]').forEach(b => {
            const pack = parseInt( (b as HTMLDivElement).dataset.packStep );
            const prev = currents.current[pack] ?? 100;
            const now = currentStep <= 0 ? 100 : (100 * Math.min(Math.max(0, fun_level_goal( pack )/baseLimit), 1));

            const sub = b.animate([
                { width: `${prev}%`, opacity: prev < 100 ? 1 : 0 },
                { width: `${now}%`, opacity: now < 100 ? 1 : 0 },
            ], {
                easing: "ease-in-out", fill: "both",
                ...(fast.current ? {delay: 100, duration: 200} : {delay: 100, duration: 500} )
            });
            sub.addEventListener('finish', () => currents.current[pack] = now);
            animation.push(sub);
        })

        animation[0].addEventListener('finish', () => {
            current.current = goal;
            if (xp && goal >= 100) {
                const next = fun_next_goal(baseLimit);
                fast.current = value > next;
                setBaseLimit(next)
                setCurrentStep(currentStep+1);
            }
            else fast.current = false;
        })

        return () => animation.forEach(a => a.cancel());
    }, [baseLimit,value]);

    return (
        <div className="fancy-progress-bar">
            <div className="text">
                {/*xp && <span>{value}&nbsp;/&nbsp;{baseLimit}</span>*/}
            </div>
            <div className="progressbar">
                <div className="progressbar-container">
                    <div className="base-layer">
                        <div className={`inner ${xp && animateTo > step[0] ? 'flashy' : ''}`} ref={bar}/>
                    </div>
                    <div className="completed-layers" ref={bars}>
                        {Array.from(Array(Math.min(step.length,currentStep)).keys()).reverse().map(k => <div key={k} data-pack-step={k} className="inner flashy"/>)}
                    </div>
                </div>
            </div>
        </div>
    )
};