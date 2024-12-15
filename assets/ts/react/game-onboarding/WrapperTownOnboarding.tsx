import * as React from "react";
import {useContext, useEffect, useLayoutEffect, useRef, useState} from "react";
import {Const, Global} from "../../defaults";
import {
    CitizenCount,
    TownOnboardingAPI, OnboardingCache,
    OnboardingIdentityPayload, OnboardingPayload,
    OnboardingProfessionPayload, OnboardingSkillPayload,
    ResponseConfig,
    ResponseJobs, ResponseSkills, Skill
} from "./api";
import {TranslationStrings} from "./strings";
import {Tooltip} from "../tooltip/Wrapper";
import {html, sharedWorkerCall, sharedWorkerMessageHandler} from "../../v2/init";
import {ServiceWorkerIndicator} from "../service-worker-state/Wrapper";
import {dialogShim} from "../../shims";
import {BaseMounter} from "../index";
import Dialog from "../components/dialog";

declare var c: Const;
declare var $: Global;

type Props = {
    town: number
}

export class HordesTownOnboarding extends BaseMounter<Props>{
    protected render(props: Props): React.ReactNode {
        return <HordesTownOnboardingWrapper {...props} />;
    }
}

type OnboardingIdentityPayloadProps = {
    setting: OnboardingIdentityPayload|null|false,
    setPayload: (p:OnboardingIdentityPayload|null|false)=>void,
}

type OnboardingProfessionPayloadProps = {
    setting: OnboardingProfessionPayload|null,
    setPayload: (p:OnboardingProfessionPayload|null)=>void,
    setCache: (c:ResponseJobs)=>void,
    setHeroMode: (h:boolean)=>void,
}

type OnboardingSkillPayloadProps = {
    hero: boolean,
    setting: OnboardingSkillPayload|null,
    setPayload: (p:OnboardingSkillPayload|null)=>void,
    setCache: (c:ResponseSkills)=>void,
}

type TownOnboardingGlobals = {
    disabled: boolean,
    api: TownOnboardingAPI,
    strings: TranslationStrings,
    payload: OnboardingPayload,
    town: number
}

type ConfirmationDialogProps = {
    payload: OnboardingPayload,
    cache: OnboardingCache,
    open: boolean,
    setClose: ()=>void,
}

export const Globals = React.createContext<TownOnboardingGlobals>(null);

const HordesTownOnboardingWrapper = (props: Props) => {

    const [page, setPage] = useState(0);
    const head = useRef<HTMLDivElement>();

    const apiRef = useRef(new TownOnboardingAPI());
    const [payload, setPayload] = useState<OnboardingPayload>({
        identity: null,
        profession: null,
        skills: null
    });
    const [cache, setCache] = useState<OnboardingCache>({
        profession: null,
        skills: null
    });

    const [submitting, setSubmitting] = useState<boolean>(false);
    const [config, setConfig] = useState<ResponseConfig>();
    const [strings, setStrings] = useState<TranslationStrings>();
    const [hero, setHero] = useState<boolean>(false);

    useEffect(() => {
        apiRef.current.config( props.town ).then(i => setConfig(i));
        return () => setConfig(null);
    }, [props.town]);

    useEffect(() => {
        apiRef.current.index().then(i => setStrings(i));
        return () => setStrings(null);
    }, []);

    useLayoutEffect(() => {
        head.current?.scrollIntoView();
    }, [page]);

    const ready = config && strings;
    const canSubmit = ready &&
        ( payload.identity !== null || !config.features.alias ) &&
        ( payload.profession !== null || !config.features.job );

    const need_multipage = config?.features.skills && config?.features.job;

    const render_alias = () => <>
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
    </>

    const render_skills = () => <>
        <h5>
            {strings.skills.headline}
            &nbsp;
            <a className="help-button">
                <Tooltip additionalClasses="help">
                    {strings.skills.help}
                </Tooltip>
                {strings.common.help}
            </a>
        </h5>
        { !hero && <div className="note note-critical">
            { strings.skills.help_no_hero }
        </div> }
        <SkillSelection setting={payload.skills} hero={hero}
                        setPayload={skills => setPayload({...payload, skills})}
                        setCache={skills => setCache({...cache, skills})}
        />
    </>

    const render_jobs = () => <>
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
        <JobSelection setting={payload.profession} setHeroMode={b => setHero(b)}
                      setPayload={profession => setPayload({...payload, profession})}
                      setCache={profession => setCache({...cache, profession})}
        />
    </>

    return <>
        {!ready && <div className="loading"/>}
        {ready &&
            <Globals.Provider value={{api: apiRef.current, strings, town: props.town, payload, disabled: submitting}}>

                <div ref={head}/>

                {need_multipage && page === 0 && <>
                    {config.features.alias && render_alias()}
                    {config.features.job && render_jobs()}
                </>}

                {need_multipage && page === 1 && <>
                    {config.features.skills && render_skills()}
                </>}

                {!need_multipage && <>
                    {config.features.alias && render_alias()}
                    {config.features.job && render_jobs()}
                    {config.features.skills && render_skills()}
                </>}

                <br/>
                <div className="row">
                    <div className="cell rw-4 rw-lg-5 rw-md-6 rw-sm-12">
                        {need_multipage && page === 1 && <button onClick={() => setPage(0)}>
                            {strings.common.return}
                        </button>}
                        &nbsp;
                    </div>
                    <div className="cell ro-4 rw-4 ro-lg-2 rw-lg-5 ro-md-0 rw-md-6 ro-sm-0 rw-sm-12 right">
                        {need_multipage && page === 0 && <button
                            disabled={payload.profession === null}
                            onClick={() => setPage(1)}
                        >
                            {strings.common.continue}
                        </button>}

                        {(!need_multipage || page === 1) && <button
                            disabled={!canSubmit || submitting}
                            onClick={() => { setSubmitting(true); }}
                        >
                            {strings.common.confirm}
                        </button>}
                    </div>
                </div>
                <ConfirmationDialog payload={payload} cache={cache} open={submitting} setClose={() => setSubmitting(false)} />
            </Globals.Provider>}
    </>;
}

const ConfirmationDialog = (props: ConfirmationDialogProps) => {
    const globals = useContext(Globals);

    const [loading, setLoading] = useState<boolean>(false);

    const confirm = () => {
        setLoading(true);
        globals.api.confirm(globals.town, props.payload)
            .then(({url}) => $.ajax.load(null, url, true))
            .catch(() => {
                setLoading(false);
                props.setClose()
            });
    }

    const selected_profession = props.payload?.profession?.id
        ? (props.cache.profession.find( p => p.id === props.payload.profession.id ) ?? null)
        : null;

    const selected_skills =
        props.payload?.skills?.ids
            ?.map( i => props.cache.skills?.skills.list.find( s => s.id === i ) ?? null )
            ?.filter( s => s !== null && s.level > 0 )
            ?.sort((s1, s2) => s1.sort - s2.sort) ?? null;

    const missing_skill_pts = Math.max(0, selected_skills?.reduce( (carry, skill) => carry - skill.level, props.cache.skills?.skills.pts ) ?? 0);

    return <Dialog open={props.open} className="contained" onCancel={() => props.setClose()} onSubmit={() => confirm()}>
        <div className="modal-title">{globals.strings.confirm.title}</div>
        <div className="row"><div className="padded cell rw-12"><p className="small">
            {globals.strings.confirm.help}
        </p></div></div>
        <form method="dialog">
            {selected_profession && <div className="row">
                <div className="padded cell rw-12">
                    <h5>{globals.strings.confirm.job}</h5>
                    <div className="note note-lightest flex gap">
                        <img alt={selected_profession.name} src={selected_profession.icon}/>
                        <span><b>{selected_profession.name}</b></span>
                    </div>
                </div>
            </div>}

            {selected_skills !== null && selected_profession.hero && <div className="row">
                <div className="padded cell rw-12">
                    <h5>{globals.strings.confirm.skills}</h5>
                    <div className="flex column gap">
                        { selected_skills.map(skill => <React.Fragment key={skill.id}>

                            { skill.level > 0 && <>
                                <div className="relative">
                                    <div style={{
                                        display: 'grid',
                                        gridTemplateColumns: '1fr',
                                        gridTemplateRows: `repeat(${skill.level}, auto)`,
                                        gridRowGap: '2px'
                                    }}>
                                        {Array.from(Array(skill.level).keys()).map(i => <div
                                            className="note note-lightest center" style={{minHeight: '32px', background: 'rgb(179,206,0)'}} key={i}>
                                            &nbsp;
                                        </div>)}
                                    </div>

                                    <div className="flex middle stretch note note-lightest" style={{
                                        position: 'absolute',
                                        backdropFilter: 'blur(2px)',
                                        background: 'rgba(153,103,57,0.9)',
                                        left: '8px', right: '8px', top: '4px', bottom: '4px'
                                    }}>
                                        <div className="full-width flex gap center">
                                            <span>{skill.group}</span>
                                            <span><b>{globals.strings.skills.levels[skill.level]}</b></span>
                                        </div>
                                    </div>
                                    <Tooltip additionalClasses="help">
                                        <ul>
                                            {skill.bullets.map((val,i) => <li key={i}>{val}</li>)}
                                        </ul>
                                    </Tooltip>
                                </div>
                            </>}

                            {skill.level <= 0 && <>
                                <div className="note note-lightest flex gap center">
                                    <span>{skill.group}</span>
                                    <span><b>{globals.strings.skills.levels[skill.level]}</b></span>
                                </div>
                            </>}

                        </React.Fragment>)}

                        { missing_skill_pts > 0 && <div style={{
                            display: 'grid',
                            gridTemplateColumns: '1fr',
                            gridTemplateRows: `repeat(${missing_skill_pts}, auto)`,
                            gridRowGap: '2px'
                        }}>
                            {Array.from(Array(missing_skill_pts).keys()).map(i => <div
                                className="note note-lightest flex middle center"
                                style={{minHeight: '32px', background: 'rgb(206,48,0)'}} key={i}>
                                <span><b>{globals.strings.confirm.empty_pt}</b></span>
                            </div>)}
                        </div>}

                        { missing_skill_pts > 0 && <div className="note note-warning" dangerouslySetInnerHTML={{__html: globals.strings.confirm.empty_pt_warn}}/>}
                    </div>
                </div>
            </div>}

            <div className="row" data-disabled={loading ? "disabled" : null}>
                <div className="padded cell rw-4 rw-lg-5 rw-md-6 rw-sm-12">
                    <button type="button" onClick={() => props.setClose()}>
                        {globals.strings.confirm.back}
                    </button>
                </div>
                <div className="padded cell ro-4 rw-4 ro-lg-2 rw-lg-5 ro-md-0 rw-md-6 ro-sm-0 rw-sm-12 right">
                    <button type="button" onClick={() => confirm()}>
                        {globals.strings.confirm.ok}
                    </button>
                </div>
            </div>
        </form>
    </Dialog>
}

const IdentitySelection = (props: OnboardingIdentityPayloadProps) => {
    const globals = useContext(Globals);

    const inputRef = useRef<HTMLInputElement>()

    useEffect(() => {
        props.setPayload(false);
    }, []);

    return <div className="row-flex v-center">
        <div className="cell padded note note-lightest rw-3">
            <label htmlFor="onb_citizen_identity">{globals.strings.identity.field}</label>
        </div>
        <div className="padded cell rw-9">
            <input type="text" id="onb_citizen_identity" ref={inputRef} maxLength={22} minLength={4}
                   data-disabled={globals.disabled ? 'disabled' : ''}
                   onKeyUp={() => {
                       const l = inputRef.current.value.length;
                       props.setPayload(inputRef.current.value.length === 0 ? false : (
                           l < 4 || l > 22 ? null : {name: inputRef.current.value}
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
        globals.api.jobs(globals.town).then(i => {
            setJobs(i);
            props.setCache(i);
        });
        globals.api.citizens(globals.town).then(i => {
            setCitizens(i.list)
            setToken(i.token ?? null)
        });
        return () => {
            setJobs(null);
            setCitizens(null);
        }
    }, [globals.town]);

    const mercureHandler = sharedWorkerMessageHandler('town-lobby', 'citizen-count-update', s => {
        if (s.list) setCitizens(s.list)
    });

    useEffect(() => {
        if (token) {
            html().addEventListener('mercureMessage', mercureHandler);
            sharedWorkerCall('mercure.configure', {connection: 'town-lobby', config: {reconnect: false}});
            sharedWorkerCall('mercure.alloc', {connection: 'town-lobby'});
            sharedWorkerCall('mercure.authorize', {connection: 'town-lobby', token});
            return () => {
                html().removeEventListener('mercureMessage', mercureHandler);
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
                onClick={() => {
                    props.setPayload(props.setting?.id === job.id ? null : {id: job.id});
                    props.setHeroMode(job.hero);
                }}
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
                    <ServiceWorkerIndicator connection="town-lobby"/>
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

const SkillSelection = (props: OnboardingSkillPayloadProps) => {
    const globals = useContext(Globals);

    const [skills, setSkills] = useState<ResponseSkills>();
    const [levels, setLevels] = useState<{ [key: string]: number; }>();
    const [current, setCurrent] = useState(0);

    const skillContainer = useRef<HTMLDivElement>()

    useEffect(() => {
        globals.api.skills(globals.town).then(i => {
            setSkills(i);
            props.setCache(i);

            let initial_ids = props.setting?.ids ?? [];
            if (initial_ids.length === 0) {
                i.skills.list.forEach(skill => {
                    if (skill.level === 0 && !initial_ids.includes(skill.id))
                        initial_ids.push(skill.id);
                });
                props.setPayload({ids: initial_ids});
            }

            let g = {};
            i.skills?.groups?.forEach(s => {
                g[s] = i.skills.list
                    .filter(skill => skill.group === s && initial_ids.includes(skill.id))
                    .reduce((c,v) => Math.max(v.level,c), 0);
            });

            setLevels(g);
        });
        return () => {
            setSkills(null);
            setLevels(null);
        }
    }, [globals.town]);

    useLayoutEffect(() => {
        if (!skillContainer.current) return;

        skillContainer.current.querySelectorAll('div.skill-nav>div').forEach((div, n) => div.animate([
            {opacity: 0, transform: 'translateY(-24px)'},
            {transform: 'translateY(0)'},
        ], {
            duration: 300,
            delay: n * 200,
            fill: "backwards",
            easing: "ease-in-out",
        }));

        skillContainer.current.querySelectorAll('div.skill-slider > div').forEach(
            div => (div as HTMLDivElement).style.left = '0%'
        );

    }, [skills]);

    useLayoutEffect(() => {
        if (!skillContainer.current) return;

        skillContainer.current.querySelectorAll('div.skillset-nav').forEach(div => div.animate([
            {offset: 0,   opacity: '1'},
            {offset: 0.05, opacity: '0'},
            {offset: 0.95, opacity: '0'},
            {offset: 1,   opacity: '1'},
        ], {
            duration: 1000,
            delay: 0,
            fill: "forwards",
            easing: "ease-in-out",
        }));

        skillContainer.current.querySelectorAll('div.skill-slider > div').forEach(div => div.animate([
            {offset: 0,   transform: 'scale(1)'},
            {offset: 0.1, transform: 'scale(0.8)'},
            {offset: 0.9, transform: 'scale(0.8)', left: `${current*-100}%`},
            {offset: 1,   transform: 'scale(1)', left: `${current*-100}%`},
        ], {
            duration: 1000,
            delay: 0,
            fill: "forwards",
            easing: "ease-in-out",
        }));
    }, [current]);

    const applySkill = (skill: Skill) => {
        const skill_group = skills.skills.list.filter(s => s.group === skill.group);

        const ids_on = skill_group.filter(s => s.level === skill.level).map(s => s.id);
        const ids_off= skill_group.filter(s => s.level !== skill.level).map(s => s.id);

        let tmp_levels = {...levels};
        tmp_levels[skill.group] = skill.level;
        setLevels(tmp_levels);

        props.setPayload({ids: [
                ...((props.setting?.ids ?? []).filter( n => !ids_off.includes(n) )),
                ...ids_on
            ]});
    }

    useEffect(() => {
        if (!props.hero) props.setPayload({ids: []});
    }, [props.hero]);

    const applyPreviousSkill = (skill: Skill) => {
        const prev_skill = skills.skills.list
            .filter(s => s.group === skill.group && s.level < skill.level)
            .sort((a,b) => b.level - a.level)
            [0] ?? null;

        if (prev_skill) applySkill(prev_skill);
    }

    const pts_left = (skills?.skills?.pts ?? 0) - Object.values(levels ?? {}).reduce((c,v) => c + v, 0);

    return <>
        {!skills && <div className="loading"/>}
        { props.hero && <>
            { skills?.skills?.unlock_url && <div className="note green-note flex column middle">
                <img src={globals.strings.skills.unlock_img} alt="" />
                { globals.strings.skills.unlock }
                <button onClick={() => $.ajax.load(null, skills.skills.unlock_url, true)}>{ globals.strings.skills.unlock_button }</button>
            </div> }
            { skills?.skills && <div className="note current-pts" dangerouslySetInnerHTML={{__html: globals.strings.skills.pts.replace('{pts}', `${pts_left}`)}} /> }
            <div ref={skillContainer}>
                <div className="row">
                    <div className="padded cell rw-12">
                        <div className="skill-nav">
                            {skills?.skills?.groups.map((g,i) =>
                                <div className={i === current ? 'current' : ''} key={g} onClick={() => setCurrent(i)}>
                                    {g.slice(0, 1)}
                                </div>
                            )}
                        </div>
                        <div className="padded cell rw-12">
                            <div className="skill-slider">
                                {skills?.skills?.groups.map(g =>
                                    <div key={g} className="note note-lightest skillset-parent">
                                        <div className="skillset-nav skillset-nav-prev" onClick={() => setCurrent(current - 1)}><img alt="" src={globals.strings.common.prev}/></div>
                                        <div className="skillset-group">
                                            <strong className="group-title">
                                                <span className="first-letter">{g.slice(0, 1)}</span>
                                                <span className="last-letters">{g.slice(1)}</span>
                                            </strong>
                                            <div>
                                                {skills.skills.list.filter(s => s.group === g).sort((a, b) => a.level - b.level).map(skill =>
                                                    <div
                                                        className={`${((skill.level - levels[g]) > pts_left) ? 'skill-unreachable' : 'pointer'}`}
                                                        key={skill.id}>
                                                        <div className="row-flex gap v-center" onClick={() => {
                                                            if ((skill.level - levels[g]) > pts_left) return;
                                                            if (levels[g] === skill.level) applyPreviousSkill(skill)
                                                            else applySkill(skill);
                                                        }}>
                                                            <div className="cell factor-0">
                                                                {levels[g] >= skill.level &&
                                                                    <img alt="" src={globals.strings.common.on}/>}
                                                                {levels[g] < skill.level &&
                                                                    <img alt="" src={globals.strings.common.off}/>}
                                                            </div>
                                                            <div className="cell factor-1">
                                                                <b>{globals.strings.skills.levels[skill.level] ?? globals.strings.skills.level.replace('{skill_level}', `${skill.level}`)}:&nbsp;</b>
                                                                {skill.description}
                                                                {skill.bullets.length > 0 && <ul>
                                                                    {skill.bullets.map((bullet, i) => <li
                                                                        key={i}>{bullet}</li>)}
                                                                </ul>}
                                                            </div>
                                                        </div>
                                                    </div>)}
                                            </div>
                                        </div>
                                        <div className="skillset-nav skillset-nav-next" onClick={() => setCurrent(current + 1)}><img alt="" src={globals.strings.common.next}/></div>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </> }
    </>
}