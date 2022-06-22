<?php


namespace App\Translation;

use App\Service\Globals\TranslationConfigGlobal;
use Symfony\Component\Translation\Dumper\YamlFileDumper;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExpandedDumper extends YamlFileDumper
{
    private TranslatorInterface $trans;
    private YamlFileDumper $dumper;
    private TranslationConfigGlobal $conf;

    public function __construct(YamlFileDumper $dumper, TranslatorInterface $trans, TranslationConfigGlobal $conf)
    {
        $this->dumper = $dumper;
        $this->trans = $trans;
        $this->conf = $conf;
        parent::__construct();
    }

    protected function preprocess(MessageCatalogue &$messages, $domain) {
        foreach ($messages->all($domain) as $source => $target) {
            $german = trim($this->trans->trans($source, [], $domain, 'de'));

            $existing_notes = $messages->getMetadata( $source, $domain );
            $m = $this->conf->get_sources_for($source,$domain);
            $filtered_notes = [];
            if (isset($existing_notes['notes'])) {

                foreach ( $existing_notes['notes'] as &$note) {
                    if (isset($note['category']) && $note['category'] === 'german') {
                        $note['content'] = $german;
                        $filtered_notes['german'] = $note;
                    }
                    if (isset($note['category']) && $note['category'] === 'state') {
                        if ( $source === $target || $note['content'] !== 'new' )
                            $filtered_notes['state'] = $note;
                    }
                    if (isset($note['category']) && $note['category'] === 'from') {
                        $filtered_notes['from'] = $note;
                        $m = $this->conf->isExhaustive() ? $m : array_unique( array_merge($m, array_filter( explode(';', $note['content']), fn(string $s) => $s !== '[unused]' ) ) );
                    }

                }

                sort($m);

                if (!isset($filtered_notes['german'])) $filtered_notes['german'] = ['category' => 'german', 'content' => $german];
                if (!isset($filtered_notes['state']) && $source === $target) $filtered_notes['state'] = ['category' => 'state', 'content' => 'new'];
                if ($this->conf->isExhaustive() || !empty($m))
                    $filtered_notes['from'] = ['category' => 'from', 'content' => empty($m) ? '[unused]' : implode(';', $m)];

            } else {
                sort($m);
                $filtered_notes = ['german' => ['category' => 'german', 'content' => $german], 'from' => ['category' => 'from', 'content' => empty($m) ? '[unused]' : implode(';', $m)]];
                if ($this->conf->isExhaustive() || !empty($m)) $filtered_notes['from'] = ['category' => 'from', 'content' => empty($m) ? '[unused]' : implode(';', $m)];
                if ($source === $target) $filtered_notes['state'] = ['category' => 'state', 'content' => 'new'];
            }

            if ($messages->getLocale() === 'de') {
                if (isset($filtered_notes['from'])) $filtered_notes = [ 'from' => $filtered_notes['from'] ];
                else $filtered_notes = [];
            }
            $messages->setMetadata($source, ['notes' => $filtered_notes], $domain );
        }

    }

    public function formatCatalogue(MessageCatalogue $messages, $domain, array $options = []): string
    {
        if ( $this->conf->isConfigured() ) $this->preprocess($messages, $domain);
        return $this->dumper->formatCatalogue( $messages, $domain, $options );
    }
}