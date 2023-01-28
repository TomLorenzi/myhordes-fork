import * as React from "react";

import {Global} from "../../defaults";
import {ResponseIndex, ResponseTownList, Template, TownRules} from "./api";
import {useContext, useEffect, useRef, useState} from "react";
import {Globals} from "./Wrapper";
import {OptionCoreTemplate, OptionFreeText, OptionSelect, OptionToggleMulti} from "./Input";
import {number} from "prop-types";

declare var $: Global;

/**
 *
 * @param { function(): TownRules} getOptions
 * @constructor
 */
export const TownCreatorSectionTemplate = ({getOptions}) => {
    const globals = useContext(Globals)

    const [templateList, setTemplateList] = useState<Template[]>([])
    const [templateListLoaded, setTemplateListLoaded] = useState<boolean>(false)
    const [dataTransfer, setDataTransfer] = useState<boolean>(false)
    const [selectedTemplate, setSelectedTemplate] = useState<string>('')

    useEffect( () => {
        if (!templateListLoaded)
            globals.api.listTemplates()
                .then( list => {
                    setTemplateListLoaded( true );
                    setTemplateList( list );
                } )
    } )

    const createTemplate = () => {
        const name = prompt( globals.strings.template.saveConfirm, '' );
        if (name === null) return;

        if (name.length < 3 || name.length > 64) {
            $.html.error( globals.strings.template.saveNameError );
            return;
        }

        setDataTransfer(true);
        globals.api.createTemplate( getOptions(), name )
            .then( template => {
                const list = templateList;
                list.push( template );
                setTemplateList( list );
                setSelectedTemplate( template.uuid );
                $.html.notice( globals.strings.template.saveDone );
                setDataTransfer(false);
            } ).catch( () => setDataTransfer(false) )
    }

    const updateTemplate = () => {
        if (confirm(globals.strings.template.updateConfirm)) {
            setDataTransfer(true);
            globals.api.updateTemplate(getOptions(), selectedTemplate)
                .then(() => {
                    $.html.notice(globals.strings.template.updateDone);
                    setDataTransfer(false);
                }).catch( () => setDataTransfer(false) )
        }
    }

    const deleteTemplate = () => {
        if (confirm(globals.strings.template.deleteConfirm)) {
            setDataTransfer(true);
            globals.api.deleteTemplate(selectedTemplate)
                .then(() => {
                    setTemplateList(templateList.filter(t => t.uuid !== selectedTemplate));
                    setSelectedTemplate('');
                    $.html.notice(globals.strings.template.deleteDone);
                    setDataTransfer(false);
                }).catch(() => setDataTransfer(false))
        }
    }

    const loadTemplate = () => {
        if (confirm(globals.strings.template.loadConfirm)) {
            setDataTransfer(true);
            globals.api.getTemplate(selectedTemplate)
                .then(data => {
                    globals.setOption('head.customJobs', true)
                    globals.setOption('head.customConstructions', true)
                    globals.setOption('rules', data.rules)
                    $.html.notice(globals.strings.template.loadDone);
                    setDataTransfer(false);
                }).catch(() => setDataTransfer(false))
        }
    }

    return <div>
        <h5>{ globals.strings.template.section }</h5>

        <div className="help">{ globals.strings.template.description }</div>

        <div className={ (templateListLoaded || dataTransfer) ? '' : 'disabled' } >
            <div className="row">
                <div className="cell padded rw-3 rw-md-6 rw-sm-12 note note-lightest">
                    { globals.strings.template.select }
                </div>
                <div className="cell padded rw-9 rw-md-6 rw-sm-12">
                    <select value={ selectedTemplate } onChange={ e => setSelectedTemplate((e.target as HTMLSelectElement).value) }>
                        <option value="">[ { globals.strings.template.none } ]</option>
                        { templateList.map( t => <option value={t.uuid}>{ t.name }</option> ) }
                    </select>
                </div>
            </div>

            <div className="row">
                <div className="cell padded rw-9 ro-3 rw-md-6 ro-md-6 rw-sm-12 ro-sm-0">

                    <div className="row">
                        { selectedTemplate == '' && (
                            <div className="padded cell rw-12">
                                <button type="button" onClick={createTemplate}>
                                    { globals.strings.template.save }
                                </button>
                            </div>
                        ) }

                        { selectedTemplate != '' && <>
                            <div className="padded cell rw-4 rw-md-6 rw-sm-12">
                                <button type="button" onClick={loadTemplate}>
                                    { globals.strings.template.load }
                                </button>
                            </div>
                            <div className="padded cell rw-4 rw-md-6 rw-sm-12">
                                <button type="button" onClick={updateTemplate}>
                                    { globals.strings.template.update }
                                </button>
                            </div>
                            <div className="padded cell rw-4 rw-md-6 rw-sm-12">
                                <button type="button" onClick={deleteTemplate}>
                                    { globals.strings.template.delete }
                                </button>
                            </div>
                        </> }
                    </div>
                </div>
            </div>

        </div>

    </div>
};