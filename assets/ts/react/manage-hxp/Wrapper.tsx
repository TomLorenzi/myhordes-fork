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

    const [strings, setStrings] = useState<TranslationStrings>(null);
    const [skillData, setSkillData] = useState<SkillState>(null);

    const [levels, setLevels] = useState<{[key:string]: number|null}>({})

    useEffect(() => {
        apiRef.current.index().then(data => setStrings(data));
        apiRef.current.skills().then( s => {
            setSkillData(s);
        } );
    }, []);

    const sortedSkills = skillData?.skills?.sort((a: HeroSkill, b: HeroSkill) => {
        return a.sort - b.sort || a.level - b.level;
    })
    const sortedGroups = [];
    sortedSkills?.forEach( s => {
        if (!sortedGroups.includes(s.group)) sortedGroups.push( s.group );
    } );

    const hxp_sum = (skillData?.hxp ?? 0) + (skillData?.skills.reduce((l, skill) => {
        if (skill.locked || !levels[skill.group]) return l;
        return (skill.level >= levels[skill.group]) ? (skill.value+l) : l;
    }, 0) ?? 0)

    return <Globals.Provider value={{
        strings,
    }}>
        { (strings === null || skillData === null) && <div className="loading"/> }
        { strings !== null && skillData !== null && <div>
            <ProgressBar animateFrom={0} animateTo={hxp_sum} limit={skillData.hxp_needed} plain={true} />
            <div className="row-flex">
                <div className="padded cell rw-8">
                    XP
                </div>
                <div>
                    { skillData.hxp }
                </div>
            </div>
            { sortedGroups.map( group => <div key={group}>
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
            <hr/>
            <div className="row-flex">
                <div className="padded cell rw-8">
                    RESULT
                </div>
                <div>
                    <b>{ hxp_sum } / { skillData?.hxp_needed }</b>
                </div>
            </div>
            <button onClick={() => {
                apiRef.current.sell_skills( skillData.skills.filter(skill =>
                    (levels[skill.group] ?? null) !== null &&
                    skill.level >= levels[skill.group]
                ).map(skill => skill.id) ).then(() => {
                    $.ajax.load(null, props.reload)
                });
            }}>OK</button>
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
                { props.skills.map(skill => <div
                    key={skill.id}
                    onClick={() => {
                        if (skill.locked) return;
                        if (level === null && skill.level === max) setLevel(skill.level);
                        else if (level !== null && skill.level === level) setLevel(level === max ? null : (level + 1));
                        else if (level !== null && skill.level === (level-1)) setLevel(skill.level);
                    }}
                    className={`pad ${(!skill.locked && ((level === null && skill.level === max) || (level !== null && skill.level === (level - 1)))) ? 'pointer' : '' }`}
                >
                    { skill.locked && <div style={{height: '6px', width: '6px', background: '#777777'}}/> }
                    { !skill.locked && level !== null && skill.level > level && <div style={{height: '6px', width: '6px', background: '#AA0000'}}/> }
                    { !skill.locked && level !== null && skill.level === level && <div style={{height: '6px', width: '6px', background: '#FF0000'}}/> }
                    { !skill.locked && level === null && skill.level === max && <div style={{height: '6px', width: '6px', background: '#00FF00'}}/> }
                    { !skill.locked && level !== null && skill.level === (level - 1) && <div style={{height: '6px', width: '6px', background: '#00FF00'}}/> }
                    { !skill.locked && ((level === null && skill.level < max) || (level !== null && skill.level < (level - 1))) && <div style={{height: '6px', width: '6px', background: '#00AA00'}}/> }

                    <Tooltip>
                        <b>{ skill.title } - { globals.strings.skills.levels[ skill.level ] }</b>
                        <ul>
                            { skill.bullets.map( (b,i) => <li key={i}>{ b }</li> ) }
                        </ul>
                    </Tooltip>
                </div>) }
            </div>
        </div>
        <div>
            { props.skills?.reduce( (n, skill) => n + ((level !== null && skill.level >= level) ? skill.value : 0 ), 0) ?? 0 }
        </div>
    </div>

}