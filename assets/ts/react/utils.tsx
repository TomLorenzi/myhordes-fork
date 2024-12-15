import {useState} from "react";

/**
 * Generates two boolean states and a setter; the first one can be directly set by the setter, the second one will
 * become true once the setter sets a truthful value, and then never becomes false again.
 * @param {boolean} init
 */
export function useStickyToggle(init: boolean): [boolean, boolean, (v: boolean) => void] {

    const [show, setShow] = useState(init);
    const [render, setRender] = useState(init);

    return [
        show, render, (value: boolean) => {
            setShow(value);
            if (value) setRender(value);
        }
    ]

}