<?php


namespace App\Translation;

use Symfony\Component\Translation\Dumper\XliffFileDumper;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExpandedDumper extends XliffFileDumper
{
    private TranslatorInterface $trans;
    private XliffFileDumper $dumper;

    public function __construct(XliffFileDumper $dumper, TranslatorInterface $trans)
    {
        $this->dumper = $dumper;
        $this->trans = $trans;
    }

    protected function preprocess(MessageCatalogue &$messages, $domain) {
        foreach ($messages->all($domain) as $source => $target) {
            $german = trim($this->trans->trans($source, [], $domain, 'de'));

            $existing_notes = $messages->getMetadata( $source, $domain );
            if (isset($existing_notes['notes'])) {

                $found = false;
                foreach ( $existing_notes['notes'] as &$note)
                    if (isset($note['category']) && $note['category'] === 'german') {
                        $found = true;
                        $note['content'] = $german;
                        break;
                    }

                if (!$found) $existing_notes['notes'][] = ['category' => 'german', 'content' => $german];

            } else $existing_notes['notes'] = [['category' => 'german', 'content' => $german]];
            $messages->setMetadata($source, $existing_notes, $domain );
        }

    }

    public function formatCatalogue(MessageCatalogue $messages, $domain, array $options = [])
    {
        $options['xliff_version'] = '2.0';
        $this->preprocess($messages, $domain);
        return $this->dumper->formatCatalogue( $messages, $domain, $options );
    }
}