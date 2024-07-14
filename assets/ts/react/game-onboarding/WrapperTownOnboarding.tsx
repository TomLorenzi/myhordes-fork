import * as React from "react";
import {useContext, useEffect, useLayoutEffect, useRef, useState} from "react";
import {Const, Global} from "../../defaults";
import {
    CitizenCount,
    GameOnboardingAPI,
    OnboardingIdentityPayload, OnboardingPayload,
    OnboardingProfessionPayload, ResponseCitizenCount,
    ResponseConfig,
    ResponseJobs
} from "./api";
import {createRoot} from "react-dom/client";
import {TranslationStrings} from "./strings";
import {Tooltip} from "../tooltip/Wrapper";
import {sharedWorkerCall} from "../../v2/init";

declare var c: Const;
declare var $: Global;

type Props = {
    town: number
}

export class HordesTownOnboarding {

    #_root = null;

    public mount(parent: HTMLElement, props: {town: number}): any {
        if (!this.#_root) this.#_root = createRoot(parent);
        this.#_root.render( <HordesTownOnboardingWrapper {...props} /> );
    }

    public unmount() {
        if (this.#_root) {
            this.#_root.unmount();
            this.#_root = null;
        }
    }
}

type OnboardingIdentityPayloadProps = {
    setting: OnboardingIdentityPayload|null|false,
    setPayload: (p:OnboardingIdentityPayload|null|false)=>void,
}

type OnboardingProfessionPayloadProps = {
    setting: OnboardingProfessionPayload|null,
    setPayload: (p:OnboardingProfessionPayload|null)=>void,
}

type TownOnboardingGlobals = {
    disabled: boolean,
    api: GameOnboardingAPI,
    strings: TranslationStrings,
    payload: OnboardingPayload,
    town: number
}

export const Globals = React.createContext<TownOnboardingGlobals>(null);

const HordesTownOnboardingWrapper = (props: Props) => {

    const apiRef = useRef(new GameOnboardingAPI());
    const [payload, setPayload] = useState<OnboardingPayload>({
        identity: null,
        profession: null
    });

    const [submitting, setSubmitting] = useState<boolean>(false);
    const [config, setConfig] = useState<ResponseConfig>();
    const [strings, setStrings] = useState<TranslationStrings>();

    useEffect(() => {
        apiRef.current.config( props.town ).then(i => setConfig(i));
        return () => setConfig(null);
    }, [props.town]);

    useEffect(() => {
        apiRef.current.index().then(i => setStrings(i));
        return () => setStrings(null);
    }, []);

    const ready = config && strings;
    const canSubmit = ready &&
        ( payload.identity !== null || !config.features.alias ) &&
        ( payload.profession !== null || !config.features.job );

    return <>
        { !ready && <div className="loading"/> }
        { ready && <Globals.Provider value={{api: apiRef.current, strings, town: props.town, payload, disabled: submitting}}>

            {config.features.alias && <>
                <h5>
                    {strings.identity.headline}
                    &nbsp;
                    <a className="help-button">
                        <Tooltip additionalClasses="help">
                            {strings.identity.help}
                        </Tooltip>
                        {strings.common.help}
                    </a>
                </h5>
                <IdentitySelection setting={payload.identity}
                              setPayload={identity => setPayload({...payload, identity})}/>
            </>}

            {config.features.job && <>
                <h5>
                    {strings.jobs.headline}
                    &nbsp;
                    <a className="help-button">
                        <Tooltip additionalClasses="help">
                            {strings.jobs.help}
                        </Tooltip>
                        {strings.common.help}
                    </a>
                </h5>
                <JobSelection setting={payload.profession}
                              setPayload={profession => setPayload({...payload, profession})}/>
            </>}

            <div className="row">
                <br/>
                <div className="cell ro-8 rw-4 ro-lg-7 rw-lg-5 ro-md-6 rw-md-6 ro-sm-0 rw-sm-12 right">
                    <button
                        disabled={!canSubmit || submitting}
                        onClick={() => {
                            setSubmitting(true);
                            apiRef.current.confirm(props.town, payload)
                                .then(({url}) => $.ajax.load(null, url, true))
                                .catch(() => setSubmitting(false));
                        }}
                    >
                        { strings.common.confirm }
                    </button>
                </div>
            </div>

        </Globals.Provider>}
    </>;
}

const IdentitySelection = (props: OnboardingIdentityPayloadProps) => {
    const globals = useContext(Globals);

    const inputRef = useRef<HTMLInputElement>()

    useEffect(() => {
        props.setPayload(false);
    }, []);

    return <div className="row-flex v-center">
        <div className="cell padded note note-lightest rw-3">
            <label htmlFor="onb_citizen_identity">{ globals.strings.identity.field }</label>
        </div>
        <div className="padded cell rw-9">
            <input type="text" id="onb_citizen_identity" ref={inputRef} maxLength={22} minLength={4}
                   data-disabled={globals.disabled ? 'disabled' : ''}
                   onKeyUp={() => {
                const l = inputRef.current.value.length;
                props.setPayload(inputRef.current.value.length === 0 ? false : (
                    l < 4 || l > 22 ? null : { name: inputRef.current.value }
                ));
            }}
            />
            <Tooltip additionalClasses="help">
                {globals.strings.identity.validation1}<br/>
                <b>{globals.strings.identity.validation2}</b>
            </Tooltip>
        </div>
    </div>
}

const JobSelection = (props: OnboardingProfessionPayloadProps) => {
    const globals = useContext(Globals);

    const [jobs, setJobs] = useState<ResponseJobs>();
    const [citizens, setCitizens] = useState<CitizenCount[]>();
    const [token, setToken] = useState<object>();

    const jobContainer = useRef<HTMLDivElement>();

    useEffect(() => {
        globals.api.jobs(globals.town).then(i => setJobs(i));
        globals.api.citizens(globals.town).then(i => {
            setCitizens(i.list)
            setToken(i.token ?? null)
        });
        return () => {
            setJobs(null);
            setCitizens(null);
        }
    }, [globals.town]);

    useEffect(() => {
        if (token) {
            sharedWorkerCall('mercure.configure', {connection: 'town-lobby', config: {reconnect: false}});
            sharedWorkerCall('mercure.alloc', {connection: 'town-lobby'});
            sharedWorkerCall('mercure.authorize', {connection: 'town-lobby', token});
            return () => {
                sharedWorkerCall('mercure.dealloc', {connection: 'town-lobby'});
            }
        }
    }, [token]);

    useLayoutEffect(() => {
        if (!jobContainer.current) return;

        jobContainer.current.querySelectorAll('div.jobs-choice').forEach(div => div.animate([
            {opacity: 0, transform: 'scale(90%)'},
            {transform: 'scale(100%)'},
        ], {
            duration: 300,
            delay: Math.random() * 500,
            fill: "backwards",
            easing: "ease-in-out",
        }));

    }, [jobs]);

    const existingProfessions = (citizens ?? []).filter(c => c.n > 0).map(c => c.id);

    return <>
        {!jobs && <div className="loading"/>}
        {jobs && <div className="row" ref={jobContainer}>
            {jobs?.map(job => <div
                key={job.id}
                data-disabled={globals.disabled ? 'disabled' : ''}
                className={`jobs-choice padded cell rw-4 rw-md-6 pointer ${props.setting?.id === job.id ? 'selected' : null}`}
                onClick={() => props.setPayload(props.setting?.id === job.id ? null : {id: job.id})}
            >
                <div className="text center">
                    <img alt={job.name} src={job.icon}/>
                    &nbsp;
                    {job.name}
                </div>
                <div className="helpbtn">
                    <a className="help-button" onClick={e => {
                        e.preventDefault();
                        e.stopPropagation();
                        window.open(job.help, '_blank')
                    }}>
                        <Tooltip additionalClasses="help">
                            {job.desc}<br/>
                            <em>{globals.strings.jobs.more}</em>
                        </Tooltip>
                        {globals.strings.common.help}
                    </a>
                </div>
                <img className="pointer" alt={job.name} src={job.poster}/>
            </div>)}
        </div> }
        <div>
            <div className="cell rw-12">
                <h5>
                    <hordes-service-worker-indicator data-connection="town-lobby"/>
                    &nbsp;
                    {globals.strings.jobs.in_town}
                </h5>
                {(!jobs || !citizens) && <div className="loading"/>}
                {jobs && citizens && <div className="row prof-list">
                    {jobs?.filter(job => existingProfessions.includes(job.id))?.map(job => <div key={job.id} className="padded cell small center rw-4 rw-lg-6">
                        <img alt={job.icon} src={job.icon}/>
                        {job.name}&nbsp;×&nbsp;{ citizens.find(c => c.id === job.id)?.n ?? 0 }
                    </div>)}
                </div>}
            </div>
            <div className="cell rw-12">
                <br/>
                <p className="ambiant hide-sm">
                    {globals.strings.jobs.flavour}
                </p>
            </div>
        </div>
    </>
}