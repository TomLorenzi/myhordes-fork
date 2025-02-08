import * as React from "react";
import {Const, Global} from "../../defaults";
import {BaseMounter} from "../index";
import {ProgressBar} from "../progress-bar/Wrapper";
import {useContext, useEffect, useRef, useState} from "react";
import {TranslationStrings} from "./strings";
import {HeroSkill, HxpManagementApi, SkillState} from "./api";
import {Tooltip} from "../tooltip/Wrapper";

declare var c: Const;
declare var $: Global;

type Props = {
    reload: string,
    proxy: string,
    enabled: boolean,
}

export class HordesBuySkillPoint extends BaseMounter<Props>{
    protected render(props: Props): React.ReactNode {
        return <HordesBuySkillPointWrapper {...props} />;
    }
}

const Globals = React.createContext<{
    strings: TranslationStrings
}>(null);

const HordesBuySkillPointWrapper = (props: Props) => {

    const apiRef = useRef(new HxpManagementApi())

    const [proxyMode, setProxyMode] = useState<boolean>(props.proxy.length > 0);
    const [strings, setStrings] = useState<TranslationStrings>(null);
    const [skillData, setSkillData] = useState<SkillState>(null);

    const [levels, setLevels] = useState<{[key:string]: number|null}>({})
    const [loading, setLoading] = useState<boolean>(false);

    useEffect(() => {
        if (!proxyMode) {
            apiRef.current.index().then(data => setStrings(data));
            apiRef.current.skills().then( s => {
                setSkillData(s);
            } );
        }
    }, [proxyMode]);

    const sortedSkills = skillData?.skills?.sort((a: HeroSkill, b: HeroSkill) => {
        return a.sort - b.sort || a.level - b.level;
    })
    const sortedGroups = [];
    sortedSkills?.forEach( s => {
        if (!sortedGroups.includes(s.group)) sortedGroups.push( s.group );
    } );

    const hxp_sell = (skillData?.skills.reduce((l, skill) => {
        if (skill.locked || !levels[skill.group]) return l;
        return (skill.level >= levels[skill.group]) ? (skill.value+l) : l;
    }, 0) ?? 0)
    const hxp_consume = Math.min( Math.max(0, (skillData?.hxp_needed ?? 0) - hxp_sell), skillData?.hxp ?? 0 )

    return <Globals.Provider value={{
        strings,
    }}>
        { proxyMode && <div>
            { props.proxy.length > 0 && <button onClick={() => setProxyMode(false)} disabled={!props.enabled}>{ props.proxy }</button> }
        </div> }
        { !proxyMode && <div className="xp-merchant">
            { (strings === null || skillData === null) && <div className="loading"/> }
            { strings !== null && skillData !== null && <div className="small">
                { sortedSkills.filter(s => !s.locked).length > 0 && <div>
                    <div className="row">
                        <div className="padded cell rw-12">
                            <div className="help">{strings.help.skills}</div>
                        </div>
                    </div>
                    <div className="row-flex">
                        <div className="padded cell rw-4">
                            <b>{strings.table.skill}</b>
                        </div>
                        <div className="padded cell rw-4">
                            <b>{strings.table.level}</b>
                        </div>
                        <div className="padded cell rw-4">
                            <b>{strings.table.sell}</b>
                        </div>
                    </div>
                    { sortedGroups.filter(group => sortedSkills.filter(s => !s.locked && s.group === group).length > 0).map( group => <div key={group}>
                        <SkillSellBar
                            skills={ sortedSkills.filter(s => s.group === group) }
                            label={group}
                            onSetLabel={(level) => {
                                const tmp = {};
                                tmp[group] = level;
                                setLevels({
                                    ...levels,
                                    ...tmp
                                });
                            }}
                        />
                    </div> ) }
                </div> }

                <div className="row-flex">
                    <div className="padded cell rw-8">
                        { strings.table.xp }
                    </div>
                    <div><b>{ hxp_consume }</b></div>
                </div>
                <ProgressBar animateFrom={0} animateTo={hxp_sell + hxp_consume} limit={skillData.hxp_needed} plain={true} text={`${hxp_sell + hxp_consume} / ${skillData?.hxp_needed}`} />
                <button disabled={ loading || ((hxp_consume + hxp_sell) < (skillData?.hxp_needed ?? -1)) } onClick={() => {
                    let c = '';
                    if (hxp_sell > 0 && hxp_consume > 0) c = strings.help.confirm_both.replace('{sum}', `${hxp_consume}`);
                    else if (hxp_sell > 0) c = strings.help.confirm_skills;
                    else c = strings.help.confirm_xp.replace('{sum}', `${hxp_consume}`);

                    if (hxp_sell > skillData.hxp_needed) c += "\n\n" + strings.help.confirm_overflow.replace('{overflow}', `${hxp_sell - skillData.hxp_needed}`);

                    if (confirm(c)) {
                        setLoading(true);
                        apiRef.current.sell_skills(skillData.skills.filter(skill =>
                            (levels[skill.group] ?? null) !== null &&
                            skill.level >= levels[skill.group]
                        ).map(skill => skill.id)).then(() => {
                            $.ajax.load(null, props.reload)
                        }).finally(() => setLoading(false));
                    }
                }} style={{marginTop: '12px'}}>{ strings.table.button }</button>
            </div> }
        </div> }
    </Globals.Provider>

}

const SkillSellBar = (props: {
    skills: HeroSkill[],
    label: string,
    onSetLabel: (level: number|null) => void,
}) => {

    const globals = useContext(Globals);

    const [level, setLevelCore] = useState<number|null>(null);
    const setLevel = (level: number) => {
        setLevelCore(level);
        props.onSetLabel(level);
    }

    const max = props.skills.reduce( (n,skill) => Math.max(n, skill.level), 0 );

    return <div className="row-flex">
        <div className="padded cell rw-4">
            { props.label }
        </div>
        <div className="padded cell rw-4">
            <div className="flex gap">
                { props.skills.filter(skill => !skill.locked).map(skill => <div
                    key={skill.id}
                    onClick={() => {
                        if (skill.locked) return;
                        if (level === null && skill.level === max) setLevel(skill.level);
                        else if (level !== null && skill.level === level) setLevel(level === max ? null : (level + 1));
                        else if (level !== null && skill.level === (level-1)) setLevel(skill.level);
                    }}
                    className={`skill-sell ${skill.locked ? 'skill-sell-locked' : ''} ${((level === null && skill.level === max) || (level !== null && skill.level >= (level - 1) && skill.level <= level)) ? 'skill-sell-enabled' : '' }`}
                >
                    { skill.locked && <div/> }
                    { !skill.locked && level !== null && skill.level >= level && <div className="active"/> }
                    { !skill.locked && (level === null || skill.level < level) && <div className="inactive"/> }

                    <Tooltip additionalClasses="help">
                        { !skill.locked && level !== null && skill.level >= level && <b>{ globals.strings.help.active.replace('{sum}', `${skill.value}`) }</b> }
                        { !skill.locked && (level === null || skill.level < level) && <b>{ globals.strings.help.inactive.replace('{sum}', `${skill.value}`) }</b> }
                        <hr/>
                        <b>{ skill.title } - { globals.strings.skills.levels[ skill.level ] }</b>
                        <ul>
                            { skill.bullets.map( (b,i) => <li key={i}>{ b }</li> ) }
                        </ul>
                    </Tooltip>
                </div>) }
            </div>
        </div>
        <div><b>
            { props.skills?.reduce( (n, skill) => n + ((level !== null && skill.level >= level) ? skill.value : 0 ), 0) ?? 0 }
        </b></div>
    </div>

}