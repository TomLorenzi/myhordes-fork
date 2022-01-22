<?php


namespace App\Translation;

use App\Service\Globals\TranslationConfigGlobal;
use Iterator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Translation\Extractor\PhpExtractor;
use Symfony\Component\Translation\Extractor\PhpStringTokenParser;
use Symfony\Component\Translation\MessageCatalogue;

class CorePhpExtractor extends PhpExtractor
{

    private TranslationConfigGlobal $config;
    private KernelInterface $kernel;

    /**
     * Prefix for new found message.
     *
     * @var string
     */
    private $prefix = '';

    public function __construct(TranslationConfigGlobal $config, KernelInterface $kernel)
    {
        $this->config = $config;
        $this->kernel = $kernel;
    }

    /**
     * @return bool
     *
     * @throws \InvalidArgumentException
     */
    protected function canBeExtracted(string $file): bool
    {
        if (!$this->config->usePHP() || !parent::canBeExtracted($file)) return false;

        $content = file_get_contents($file);
        if (
            !str_contains($content, '->trans') &&
            !str_contains($content, 'TranslatableMessage')
        ) return false;

        return !$this->config->useFileNameMatching() || in_array(basename($file),$this->config->matchingFileNames());
    }

    private function seekToNextRelevantToken(\Iterator $tokenIterator)
    {
        for (; $tokenIterator->valid(); $tokenIterator->next()) {
            $t = $tokenIterator->current();
            if (\T_WHITESPACE !== $t[0]) {
                break;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setPrefix(string $prefix)
    {
        $this->prefix = $prefix;
    }

    private function getValue(\Iterator $tokenIterator)
    {
        $message = '';
        $docToken = '';
        $docPart = '';

        for (; $tokenIterator->valid(); $tokenIterator->next()) {
            $t = $tokenIterator->current();
            if ('.' === $t) {
                // Concatenate with next token
                continue;
            }
            if (!isset($t[1])) {
                break;
            }

            switch ($t[0]) {
                case \T_START_HEREDOC:
                    $docToken = $t[1];
                    break;
                case \T_ENCAPSED_AND_WHITESPACE:
                case \T_CONSTANT_ENCAPSED_STRING:
                    if ('' === $docToken) {
                        $message .= PhpStringTokenParser::parse($t[1]);
                    } else {
                        $docPart = $t[1];
                    }
                    break;
                case \T_END_HEREDOC:
                    if ($indentation = strspn($t[1], ' ')) {
                        $docPartWithLineBreaks = $docPart;
                        $docPart = '';

                        foreach (preg_split('~(\r\n|\n|\r)~', $docPartWithLineBreaks, -1, \PREG_SPLIT_DELIM_CAPTURE) as $str) {
                            if (\in_array($str, ["\r\n", "\n", "\r"], true)) {
                                $docPart .= $str;
                            } else {
                                $docPart .= substr($str, $indentation);
                            }
                        }
                    }

                    $message .= PhpStringTokenParser::parseDocString($docToken, $docPart);
                    $docToken = '';
                    $docPart = '';
                    break;
                case \T_WHITESPACE:
                    break;
                default:
                    break 2;
            }
        }

        return $message;
    }

    /**
     * Extracts trans message from PHP tokens.
     */
    protected function parseTokens(array $tokens, MessageCatalogue $catalog, string $filename)
    {
        $tokenIterator = new \ArrayIterator($tokens);

        for ($key = 0; $key < $tokenIterator->count(); ++$key) {
            foreach ($this->sequences as $sequence) {
                $message = '';
                $domain = 'messages';
                $tokenIterator->seek($key);

                foreach ($sequence as $sequenceKey => $item) {
                    $this->seekToNextRelevantToken($tokenIterator);

                    if ($this->normalizeToken($tokenIterator->current()) === $item) {
                        $tokenIterator->next();
                        continue;
                    } elseif (self::MESSAGE_TOKEN === $item) {
                        $message = $this->getValue($tokenIterator);

                        if (\count($sequence) === ($sequenceKey + 1)) {
                            break;
                        }
                    } elseif (self::METHOD_ARGUMENTS_TOKEN === $item) {
                        $this->skipMethodArgument($tokenIterator);
                    } elseif (self::DOMAIN_TOKEN === $item) {
                        $domainToken = $this->getValue($tokenIterator);
                        if ('' !== $domainToken) {
                            $domain = $domainToken;
                        }

                        break;
                    } else {
                        break;
                    }
                }

                if ($message) {
                    $catalog->set($message, $this->prefix.$message, $domain);
                    $metadata = $catalog->getMetadata($message, $domain) ?? [];
                    $normalizedFilename = preg_replace('{[\\\\/]+}', '/', $filename);
                    $metadata['sources'][] = $normalizedFilename.':'.$tokens[$key][2];
                    $catalog->setMetadata($message, $metadata, $domain);
                    $this->config->add_source_for($message, $domain, 'php', str_replace($this->kernel->getProjectDir(),'',$normalizedFilename));
                    break;
                }
            }
        }
    }

    private function skipMethodArgument(\Iterator $tokenIterator)
    {
        $openBraces = 0;

        for (; $tokenIterator->valid(); $tokenIterator->next()) {
            $t = $tokenIterator->current();

            if ('[' === $t[0] || '(' === $t[0]) {
                ++$openBraces;
            }

            if (']' === $t[0] || ')' === $t[0]) {
                --$openBraces;
            }

            if ((0 === $openBraces && ',' === $t[0]) || (-1 === $openBraces && ')' === $t[0])) {
                break;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function extractFromDirectory($directory): iterable
    {
        $files = parent::extractFromDirectory($directory);
        return $files->filter(function(\SplFileInfo $file) {
            return $this->canBeExtracted($file->getRealPath());
        });
    }

}