import * as React from "react";
import {useContext, useEffect, useLayoutEffect, useRef, useState} from "react";
import {Const, Global} from "../../defaults";
import {
    OnboardingPayload, GameOnboardingAPI, Town, Citizen, TownDetailsResponse
} from "./api";
import {GameTranslationStrings, TranslationStrings} from "./strings";
import {BaseMounter} from "../index";
import {Tooltip} from "../tooltip/Wrapper";
import {useStickyToggle} from "../utils";
import Username from "../components/username";
import Dialog from "../components/dialog";

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
            <TownTable
                name={t.name} help={t.help} lang={props.lang}
                locked={!t.access}
                towns={towns.filter(town => town.type === t.id)}/>
        </React.Fragment>)}
    </Globals.Provider>;
}

const TownTable = (props: {name: string, help?: string|null, lang: string, towns: Town[], locked: boolean}) => {

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
            { props.towns.map(t => <TownTableRow key={t.id} lang={props.lang} town={t} locked={props.locked}/>)}
        </div>}
    </>

}

const TownTableRow = (props: {lang: string, town: Town, locked: boolean}) => {
    const globals = useContext(Globals);

    const [showCitizenList, enableCitizenList, setCitizenList] = useStickyToggle(false);
    const [showDetails, setShowDetails] = useState(false);

    return <div className="row-flex wrap town-row v-center">
        <div className="padded cell rw-1 rw-sm-2">
            <span className="language relative">
                <img src={globals.strings.flags[props.town.language] ?? globals.strings.flags['multi']}
                     alt={props.town.language}/>
                {props.town.language !== props.lang && <div style={{position: "absolute", right: 0, bottom: 0}}>
                    <img alt="!" src={globals.strings.common.warn}/>
                    <Tooltip>
                        <div>{globals.strings.table.lang}</div>
                        <b>{globals.strings.table.lang_warn}</b>
                    </Tooltip>
                </div>}
            </span>
        </div>
        <div className="padded cell rw-6 rw-sm-10 flex gap">
            {props.town.mayor && <div>
                <img alt="" src={globals.strings.table.mayor_icon}/>
                <Tooltip additionalClasses="help">
                    <div><b>{globals.strings.table.mayor}</b></div>
                    {globals.strings.table.mayor_lines.map((l, i) => <div key={i}>{l}</div>)}
                </Tooltip>
            </div>}
            {Object.values(props.town.protection).reduce((carry: boolean, value: boolean) => carry || value, false) && <div>
                <img alt="" src={globals.strings.common.lock}/>
                <Tooltip additionalClasses="help">
                    { props.town.protection.password && <div>{ globals.strings.table.password }</div> }
                    { props.town.protection.whitelist && <div>{ globals.strings.table.whitelist }</div> }
                </Tooltip>
            </div>}
            {props.town.custom_rules && <div>
                <img alt="" src={globals.strings.common.rules}/>
                <Tooltip additionalClasses="help">
                    <b>{globals.strings.table.rules}</b>&nbsp;{globals.strings.table.more_info}
                </Tooltip>
            </div>}
            <div data-disabled={props.locked ? 'grayed' : ''}>
                <div onClick={() => setShowDetails(true)}>
                    <span className="link bold">{props.town.name}</span>
                </div>
                { props.town.event && <div className="small flex gap">
                    { globals.strings.table.event }
                    <a className="help-button">
                        { globals.strings.common.help }
                        <Tooltip additionalClasses="help" html={globals.strings.table.event_help}/>
                    </a>
                </div>}
            </div>

        </div>
        <div className="padded cell rw-2 rw-sm-5">
            <div className="small hide-desktop hide-lg hide-md">{globals.strings.table.head.citizens}</div>
            {props.town.citizenCount}/{props.town.population}
        </div>
        <div className="padded cell rw-2 rw-sm-5">
            <div className="small hide-desktop hide-lg hide-md">{globals.strings.table.head.coas}</div>
            {props.town.coalitions}/{props.town.population}
        </div>
        <div className="padded cell rw-1 rw-sm-2 right">
            {!showCitizenList && props.town.citizenCount > 0 && <div className="inline">
                <img
                    className="pointer" src={globals.strings.common.plus} alt="+"
                    onClick={() => setCitizenList(true)}
                />
                <Tooltip additionalClasses="help" html={globals.strings.table.show_players}/>
            </div>}
        </div>
        {enableCitizenList && <TableRowCitizenList
            town={props.town}
            show={showCitizenList}
            onClose={() => setCitizenList(false)}
        />}
        <TownDetailsDialog town={props.town} locked={props.locked} open={showDetails} lang={props.lang}
                           onClose={() => setShowDetails(false)} />
    </div>
}

const TableRowCitizenList = (props: { town: Town, show: boolean, onClose?: () => void }) => {

    const globals = useContext(Globals);
    const [citizens, setCitizens] = useState<Citizen[]>(null);

    useEffect(() => {
        globals.api.citizens( props.town.id ).then( v => setCitizens(v.citizens) );
        return () => setCitizens(null);
    }, [props.town.id]);

    if (!props.show) return null;

    return <div className="padded cell rw-12" style={{backgroundColor: '#3b3249'}}>
        {citizens === null && <div className="loading"></div>}
        {citizens !== null && <>
            <CitizenList citizens={citizens} />
            {props.onClose && <div className="right">
                <span className="small pointer" onClick={() => props.onClose()}>
                    {globals.strings.common.close}
                </span>
            </div>}
        </>}

    </div>
}

const CitizenList = (props: { citizens: Citizen[] }) => {
    const globals = useContext(Globals);

    const profMap = useRef( Object.fromEntries(
        globals.strings.professions.map(({name, icon}) => [name, icon])
    ) );

    return <ul className="citizen-list">
        {props.citizens.sort((a, b) => (b.alive ? 1 : 0) - (a.alive ? 1 : 0) || a.name.localeCompare(b.name)).map((citizen) =>
            <li key={citizen.id}>
                <div className="flex gap">
                    <img alt=""
                         src={citizen.alive ? profMap.current[citizen.profession] : globals.strings.common.death}/>
                    <Username userId={citizen.id} userName={citizen.name}/>
                </div>
            </li>)}
    </ul>
}

const TownDetailsDialog = (props: { town: Town, locked: boolean, open: boolean, lang: string, onClose: () => void }) => {
    const globals = useContext(Globals);

    const [loading, setLoading] = useState(false);
    const [details, setDetails] = useState<TownDetailsResponse>(null);
    const [password, setPassword] = useState<string>('');

    useEffect(() => {
        if (props.open) {
            globals.api.details(props.town.id).then(s => setDetails(s));
            return () => setDetails(null);
        }
    }, [props.town.id, props.open]);

    const town = details?.town ?? props.town;

    return <Dialog className="contained" open={props.open} onClose={ () => props.onClose() }>
        <div className="modal-title composed">
            <div className="flex large-gap middle">
                <img src={globals.strings.flags[town.language] ?? globals.strings.flags['multi']}
                     alt={town.language}/>
                <div>
                    <div>{town.name}</div>
                    <div className="small">{globals.strings.types.find(t => t.id === town.type)?.name}</div>
                </div>
            </div>

        </div>
        <div className="modal-content">
            {details === null && <div className="loading"/>}
            {details !== null && <>
                { details.locks.map( (s,i) => <div className="note note-warning" key={i}>{s}</div> ) }

                { details.warnings.length > 0 && <>
                    { details.warnings.map((warning, i) => <div key={i} className="note note-critical">{warning}</div> ) }
                    <hr/>
                </> }

                { details.locks.length == 0 && <div className="flex column large-gap">
                    { town.mayor && <div>
                        <b>{globals.strings.table.mayor}</b>
                        <div>{ globals.strings.table.mayor_lines.join(' ') }</div>
                    </div> }

                    { town.event && <div>
                        <div>
                            {globals.strings.details.event1}&nbsp;
                            <b>{ globals.strings.details.event2 }</b>
                        </div>
                    </div> }

                    { town.language !== props.lang && town.language !== 'multi' && <div>
                        <div>
                            {globals.strings.table.lang}&nbsp;
                            <b>{ globals.strings.table.lang_warn }</b>
                        </div>
                    </div> }

                    <b dangerouslySetInnerHTML={{__html: globals.strings.details.headline.replace('{town_name}', `<i>${town.name}</i>`)}}/>
                </div> }

                { details.coa.length > 0 && <>
                    { globals.strings.details.coa }
                    <div>{ details.coa.map( c => <Username userId={c.id} key={c.id} userName={c.name}/> ) }</div>
                </> }

                { ((details.citizens.length + details.rules.length) > 0) && <>
                    <hr/>
                    <div className="row">
                        {details.citizens.length > 0 &&
                            <div className={`padded cell rw-${details.rules.length > 0 ? 6 : 12} rw-sm-0`}>
                                <div>{globals.strings.details.in_town}</div>
                                <div className="blue-note" style={{maxHeight: '120px', overflowY: 'auto'}}>
                                    <CitizenList citizens={details.citizens}/>
                                </div>
                            </div>}

                        {details.rules.length > 0 &&
                            <div className={`padded cell rw-${details.citizens.length > 0 ? 6 : 12} rw-sm-12`}>
                                <div>{globals.strings.table.rules}</div>
                                <div className="blue-note" style={{maxHeight: '120px', overflowY: 'auto'}}>
                                    {details.rules.map((rule, i) => <div className="small" key={i}>{rule}</div>)}
                                </div>
                            </div>}
                    </div>
                </>}

                { town.protection.password && details.locks.length == 0 && <>
                    <hr/>
                    <div className="row">
                        <div className="cell rw-6 rw-md-12">
                            <div>{globals.strings.details.password}</div>
                            <input type="password" disabled={loading} value={password} onChange={e => setPassword(e.target.value)} autoComplete="off"/>
                        </div>
                    </div>
                </> }
            </>}
        </div>
        <div id="modal-actions">
            <button type="button" className="modal-button small inline" disabled={loading}
                    onClick={() => props.onClose()}>{globals.strings.common.cancel}</button>
            <button type="button" disabled={loading || !details || details.locks.length > 0 || (password.length == 0 && town.protection.password)}
                    className="modal-button small inline"
                    onClick={() => {
                        setLoading(true);
                        globals.api.join( town.id, password )
                            .then( ({url}) => {
                                props.onClose();
                                $.ajax.load(null, url, true)
                            } )
                            .catch(() => props.onClose())
                            .finally( () => setLoading(false) )
                    }}
            >
                {globals.strings.details.join}
            </button>
        </div>
    </Dialog>
}