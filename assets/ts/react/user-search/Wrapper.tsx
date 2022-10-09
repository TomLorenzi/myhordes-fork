import * as React from "react";
import {useLayoutEffect, useRef, useState} from "react";
import {Fetch} from "../../v2/fetch";
import {Global} from "../../defaults";
import {Simulate} from "react-dom/test-utils";
import input = Simulate.input;

declare var $: Global;

export type UserResponse = {
    'type': 'user'
    id: number
    name: string
    soul: string
    avatarHTML: string
}

export type UserResponses = UserResponse[]

export type GroupResponse = {
    'type': 'group'
    id: number
    name: string
    members: UserResponses
}

export type GroupResponses = GroupResponse[]

export const UserSearchBar = (
    {title, callback, exclude, clearOnCallback, acceptCSVListSearch}: {
        title?: string,
        callback: (UserResponses)=>void,
        exclude?: number[],
        clearOnCallback?: boolean
        acceptCSVListSearch?: boolean
    }) => {

    const apiRef = useRef<Fetch>( new Fetch('user-search') )

    const wrapper = useRef<HTMLDivElement>();
    const input = useRef<HTMLInputElement>();

    const tooltip = useRef<HTMLDivElement>();
    const tooltip_parent = useRef<HTMLDivElement>();

    const overlay = useRef<HTMLDivElement>();

    let [result, setResult] = useState<UserResponses|GroupResponses>([]);
    let [focus, setFocusState] = useState<boolean>(false);
    let [searching, setSearching] = useState<boolean>(false);

    useLayoutEffect( () => {
        if (tooltip.current) $.html.handleTooltip( tooltip.current );
        return () => $.html.clearTooltips( tooltip_parent.current );
    } );

    let searchTimeout = useRef<number>();
    let focusTimeout = useRef<number>();

    const execCallback = d => {
        if (clearOnCallback) {
            input.current.value = '';
            setResult([]);
        }
        callback(d);
    }

    const searchUserList = (s:string[], autoTrigger: boolean = false) => {
        if (!apiRef.current) return;

        if (s.length < 3) setResult([]);
        else apiRef.current.from('findList')
            .withoutLoader()
            .request().before(()=>setSearching(true)).post(
                {
                    names: s.map(name=>name.trim()),
                    withSelf: 0,
                    exclude: exclude ?? []
                }
            ).then(r => {
                setSearching(false);
                if (autoTrigger && r.length > 0) {
                    setResult([]);
                    execCallback((r as GroupResponses)[0].members);
                } else setResult(r as GroupResponses)
            }).catch(()=>setSearching(false));
    }

    const search = (s:string, autoTrigger: boolean = false) => {
        if (!apiRef.current) return;

        if (acceptCSVListSearch && s.indexOf(',') >= 0) {
            searchUserList( s.split(','), autoTrigger );
            return;
        }

        if (s.length < 3) setResult([]);
        else apiRef.current.from('find')
            .withoutLoader()
            .request().before(()=>setSearching(true)).post(
                {
                    name: s,
                    withSelf: 0,
                    exclude: exclude ?? []
                }
        ).then(r => {
            setSearching(false);
            if (autoTrigger && r.length > 0) {
                setResult([]);
                execCallback(r.slice(0,1));
            } else setResult(r as UserResponse[])
        }).catch(()=>setSearching(false));
    }

    const clearTimeout = () => {
        if (searchTimeout.current) {
            window.clearTimeout( searchTimeout.current );
            searchTimeout.current = null;
        }
    }

    const keyUp = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (e.key === "Enter") return;
        searchTimeout.current = window.setTimeout( () => search( (e.target as HTMLInputElement).value ), 500 );
    }

    const keyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (e.key === "Enter" && result.length > 0 && !searchTimeout.current) {
            execCallback(result);
        } else if (e.key === "Enter")
            search((e.target as HTMLInputElement).value, true)
        clearTimeout();
    }


    const setFocus = (b: boolean) => {
        window.clearTimeout( focusTimeout.current );
        if (b) setFocusState(b);
        else focusTimeout.current = window.setTimeout( ()=>setFocusState(b), 250 );
    }

    useLayoutEffect(() => {
        const content = overlay.current.firstElementChild as HTMLElement;
        if (!content) return;

        const h = content.clientHeight;

        const final = Math.min(300, h > 0 ? h + 2 : 0);
        const animation = overlay.current.animate({
            height: [`${final}px`],
            opacity: [h ? '1' : '0' ]
        }, {
            duration: 200,
            easing: 'ease-in-out'
        });

        const fix = () => {
            overlay.current.style.height = `${final}px`
            overlay.current.style.opacity = h ? '1' : '0'
        }

        animation.addEventListener('finish', fix);
        animation.addEventListener('cancel', fix);
        return ()=>animation.cancel();
    });

    useLayoutEffect(() => {
        if (wrapper.current) {
            const focus_in = () => setFocus(true);
            const focus_out = () => setFocus(false);

            wrapper.current.addEventListener('focusin', focus_in);
            wrapper.current.addEventListener('focusout', focus_out);

            return () => {
                wrapper.current.removeEventListener('focusin', focus_in);
                wrapper.current.removeEventListener('focusout', focus_out);
            }
        }

    })

    return (
        <div className="userSearchWrapper" ref={wrapper}>
            <div className="userSearchInputContainer"><label><input type="text" ref={input} onKeyDown={e=>keyDown(e)} onKeyUp={e=>keyUp(e)}/></label>
                { title && (
                    <div className="tooltip help" ref={tooltip} dangerouslySetInnerHTML={{__html: title}}/>
                ) }
                { searching && <div className="userSearchLoadIndicator"><i className="fa fa-pulse fa-spinner"></i></div> }
            </div>
            <div className="userSearchResultsContainer">
                <div ref={overlay} style={{opacity: 0}}>
                    <div>
                        { focus && result.map( u => u['type'] === 'group' && (u as GroupResponse).members.length > 0 && (
                            <div key={u.id} className="users-list-group-entry" onClick={() => execCallback((u as GroupResponse).members)}>
                                <div>{ u.name }</div>
                                <span className="small">
                                    { (u as GroupResponse).members.map( user => <span key={user.id}>{ user.name }</span> ) }
                                </span>
                            </div>
                        ) ) }
                        { focus && result.map( u => u['type'] === 'user' && (
                            <div key={u.id} className="users-list-entry" onClick={() => execCallback([u])}>
                                <div dangerouslySetInnerHTML={{__html: u.avatarHTML}}/>
                                <span>{u.name}</span>
                            </div>
                        ) ) }
                    </div>
                </div>
            </div>
        </div>
    )
};