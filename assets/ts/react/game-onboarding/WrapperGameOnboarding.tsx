import * as React from "react";
import {useContext, useEffect, useLayoutEffect, useRef, useState} from "react";
import {Const, Global} from "../../defaults";
import {
    OnboardingPayload, GameOnboardingAPI, Town
} from "./api";
import {GameTranslationStrings, TranslationStrings} from "./strings";
import {BaseMounter} from "../index";
import {Tooltip} from "../tooltip/Wrapper";

declare var c: Const;
declare var $: Global;

type Props = {
    lang: string;
}

export class HordesGameOnboarding extends BaseMounter<Props>{
    protected render(props: Props): React.ReactNode {
        return <HordesGameOnboardingWrapper {...props} />;
    }
}


type GameOnboardingGlobals = {
    api: GameOnboardingAPI,
    strings: GameTranslationStrings,
}

export const Globals = React.createContext<GameOnboardingGlobals>(null);

const HordesGameOnboardingWrapper = (props: Props) => {
    const api = useRef<GameOnboardingAPI>( new GameOnboardingAPI());

    const [towns, setTowns] = useState<Town[]>(null);
    const [strings, setStrings] = useState<GameTranslationStrings>(null);

    useEffect(() => {
        api.current.index().then(s => setStrings(s));
        api.current.list().then(s => setTowns(s.towns));
    }, []);


    return <Globals.Provider value={{api: api.current, strings}}>
        { (strings ===  null || towns === null) && <div className="loading"/> }
        { strings !== null && towns !== null && strings.types.map(t => <React.Fragment key={t.id}>
            <TownTable name={t.name} help={t.help} lang={props.lang} towns={towns.filter(town => town.type === t.id)}/>
        </React.Fragment>)}
    </Globals.Provider>;
}

const TownTable = (props: {name: string, help?: string|null, lang: string, towns: Town[]}) => {

    const globals = useContext(Globals);

    return <>
        <h5>{props.name}
            {props.help && <>
            &nbsp;<a className="help-button">
                {globals.strings.common.help}
                <Tooltip html={props.help}/>
            </a></>}
        </h5>
        { props.towns.length === 0 && <span className="small">{ globals.strings.table.no_towns }</span> }
        { props.towns.length > 0 && <div className="row-table">
            <div className="row-flex header bottom small">
                <div className="padded cell rw-1">&nbsp;</div>
                <div className="padded cell rw-6 hide-sm">{globals.strings.table.head.name}</div>
                <div className="padded cell rw-2 hide-sm">{globals.strings.table.head.citizens}</div>
                <div className="padded cell rw-3 hide-sm">
                    {globals.strings.table.head.coas}&nbsp;<a className="help-button">
                    {globals.strings.common.help}
                    <Tooltip html={globals.strings.table.head.coas_help}/>
                </a>
                </div>
            </div>
            { props.towns.map(t => <div key={t.id} className="row-flex wrap town-row v-center">
                <div className="padded cell rw-1 rw-sm-2">
                    <span className="language relative">
                        <img src={ globals.strings.flags[t.language] ?? globals.strings.flags['multi'] } alt={t.language}/>
                        { t.language !== props.lang && <div style={{position: "absolute", right: 0, bottom: 0}}>
                            <img alt="!" src={ globals.strings.common.warn }/>
                            <Tooltip>
                                <div>{ globals.strings.table.lang }</div>
                                <b>{ globals.strings.table.lang_warn }</b>
                            </Tooltip>
                        </div> }
                    </span>
                </div>
                <div className="padded cell rw-6 rw-sm-10 flex">
                    { t.mayor && <div>
                        <img alt="" src={globals.strings.table.mayor_icon}/>
                        <Tooltip additionalClasses="help">
                            <div><b>{ globals.strings.table.mayor }</b></div>
                            { globals.strings.table.mayor_lines.map( (l,i) => <div key={i}>{l}</div>) }
                        </Tooltip>
                    </div> }
                    <a href="#"><span className="link">{t.name}</span></a>
                </div>
                <div className="padded cell rw-2 rw-sm-5">
                    <div className="small hide-desktop hide-lg hide-md">{globals.strings.table.head.citizens}</div>
                    {t.citizenCount}/{t.population}
                </div>
                <div className="padded cell rw-2 rw-sm-5">
                    <div className="small hide-desktop hide-lg hide-md">{globals.strings.table.head.coas}</div>
                    {t.coalitions}/{t.population}
                </div>
                <div className="padded cell rw-1 rw-sm-2 right">
                </div>
            </div>)}

        </div>}
    </>

}