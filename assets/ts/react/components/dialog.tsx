import * as React from "react";
import {DialogHTMLAttributes, HTMLAttributes, useLayoutEffect, useRef} from "react";
import {dialogShim} from "../../shims";

export default function Dialog(props: DialogHTMLAttributes<HTMLDialogElement>&{open: boolean, children?: React.ReactNode|React.ReactNode[]}) {
    const me = useRef<HTMLDialogElement>(null);

    useLayoutEffect(() => {
        if (me.current) {
            dialogShim(me.current);
            if (props.open) {
                me.current.showModal();
                return () => me.current?.close();
            }
        }
    }, [props.open])

    const htmlProps = {...props};
    delete htmlProps.open;
    delete htmlProps.children;

    return props.open && <dialog ref={me} {...htmlProps}>
        {props.children}
    </dialog>
}