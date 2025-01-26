import * as React from "react";
import {Const, Global} from "../../defaults";
import {BaseMounter} from "../index";
import {ProgressBar} from "../progress-bar/Wrapper";
import {useState} from "react";

declare var c: Const;
declare var $: Global;

type Props = {

}

export class HordesBuySkillPoint extends BaseMounter<Props>{
    protected render(props: Props): React.ReactNode {
        return <HordesBuySkillPointWrapper {...props} />;
    }
}

export const Globals = React.createContext<{}>(null);

const HordesBuySkillPointWrapper = (props: Props) => {

    const [hxp, setHxp] = useState<number>(100);

    console.log(hxp);

    return <div onClick={() => setHxp(Math.round(Math.random() * 200))}>
        <ProgressBar animateFrom={0} animateTo={hxp} limit={200} plain={true} />
    </div>

}