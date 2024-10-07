<?php

namespace MyHordes\Prime\Service;

use App\Entity\LogEntryTemplate;
use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class LogsDataService implements FixtureProcessorInterface {

    public function process(array &$data): void
    {
		$data = array_replace_recursive($data, [
			['text'=>'{citizen} hat {items} in der Müllhalde wiederhergestellt (-{def} Verteidigung)', 'name'=>'dumpItemsRecover', 'type'=>LogEntryTemplate::TypeDump, 'class'=>LogEntryTemplate::ClassNone, 'secondaryType'=>null, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"list",'name'=>'items','listType'=>'item'],['type'=>'num','name'=>'def'])],
			['text'=>'{citizen} hat die Asche von {disposed} in das Gemüsebeet gestreut. Wenigstens war er am Ende noch nützlich.', 'name'=>'citizenDisposalBurn', 'type'=>LogEntryTemplate::TypeVarious, 'class'=>LogEntryTemplate::ClassNone, 'secondaryType'=>LogEntryTemplate::TypeCitizens, 'variableTypes'=>array(['type'=>"citizen",'name'=>'citizen'],['type'=>"citizen",'name'=>'disposed'])],
		]);
    }
}
