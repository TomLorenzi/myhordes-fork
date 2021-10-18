<?php


namespace App\Translation;

use App\Service\Globals\TranslationConfigGlobal;
use Iterator;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Translation\Extractor\PhpExtractor;
use Symfony\Component\Translation\Extractor\PhpStringTokenParser;
use Symfony\Component\Translation\MessageCatalogue;

class ExpandedPhpExtractor extends PhpExtractor
{

    private TranslationConfigGlobal $config;
    private KernelInterface $kernel;

    /**
     * Prefix for new found message.
     *
     * @var string
     */
    private $prefix = '';
    private $first_run = true;

    protected $sequences = [
        [
            '->',
            'trans',
            '(',
            [T_VARIABLE],
            '?',
            self::MESSAGE_TOKEN,
            ':',
            self::MESSAGE_TOKEN,
            ',',
            self::METHOD_ARGUMENTS_TOKEN,
            ',',
            self::DOMAIN_TOKEN,
        ],
        [
            '->',
            'trans',
            '(',
            [T_VARIABLE],
            '?',
            self::MESSAGE_TOKEN,
            ':',
            self::MESSAGE_TOKEN,
        ],
        [
            'T',
            '::',
            '__',
            '(',
            self::MESSAGE_TOKEN,
            ',',
            self::DOMAIN_TOKEN,
        ],
        [
            'T',
            '::',
            '__',
            '(',
            self::MESSAGE_TOKEN,
            ')'
        ],
    ];

    public function __construct(TranslationConfigGlobal $config, KernelInterface $kernel)
    {
        $this->config = $config;
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    public function extract($resource, MessageCatalogue $catalog)
    {
        $files = $this->extractFiles($resource);
        foreach ($files as $file) {
            $this->parseTokens(token_get_all(file_get_contents($file)), $catalog, $file);

            gc_mem_caches();
        }

        if ($this->first_run) {
            $this->first_run = false;
            $file = $this->kernel->getProjectDir() . '/src/Structures/Hook.php';
            $this->parseTokens(token_get_all(file_get_contents($file)), $catalog, $file);

            gc_mem_caches();
        }
    }

    /**
     * Seeks to a non-whitespace token.
     */
    private function seekToNextRelevantToken(\Iterator $tokenIterator)
    {
        for (; $tokenIterator->valid(); $tokenIterator->next()) {
            $t = $tokenIterator->current();
            if (T_WHITESPACE !== $t[0]) {
                break;
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
     * Extracts the message from the iterator while the tokens
     * match allowed message tokens.
     * @param Iterator $tokenIterator
     * @return string
     */
    private function getValue(Iterator $tokenIterator)
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
                case T_START_HEREDOC:
                    $docToken = $t[1];
                    break;
                case T_ENCAPSED_AND_WHITESPACE:
                case T_CONSTANT_ENCAPSED_STRING:
                    if ('' === $docToken) {
                        $message .= PhpStringTokenParser::parse($t[1]);
                    } else {
                        $docPart = $t[1];
                    }
                    break;
                case T_END_HEREDOC:
                    $message .= PhpStringTokenParser::parseDocString($docToken, $docPart);
                    $docToken = '';
                    $docPart = '';
                    break;
                case T_WHITESPACE:
                    break;
                default:
                    break 2;
            }
        }

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function setPrefix(string $prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * Extracts trans message from PHP tokens.
     * @param array $tokens
     * @param MessageCatalogue $catalog
     * @param string $filename
     */
    protected function parseTokens(array $tokens, MessageCatalogue $catalog, string $filename)
    {
        $tokenIterator = new \ArrayIterator($tokens);

        for ($key = 0; $key < $tokenIterator->count(); ++$key) {
            foreach ($this->sequences as $sequence) {
                $messages = [];
                $domain = 'messages';
                $tokenIterator->seek($key);

                foreach ($sequence as $sequenceKey => $item) {
                    $this->seekToNextRelevantToken($tokenIterator);

                    if (is_array($item)) {
                        if (!is_array( $tokenIterator->current()) || $tokenIterator->current()[0] !== $item[0])
                            break;
                        $tokenIterator->next();
                        continue;
                    } elseif ($this->normalizeToken($tokenIterator->current()) === $item) {
                        $tokenIterator->next();
                        continue;
                    } elseif (self::MESSAGE_TOKEN === $item) {
                        $messages[] = $this->getValue($tokenIterator);

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

                if ($messages) {
                    foreach ($messages as $message) {
                        $normalizedFilename = preg_replace('{[\\\\/]+}', '/', $filename);
                        if(empty($message)) {
                            echo "Empty string detected : $normalizedFilename:{$tokens[$key][2]}\n";
                            continue;
                        }
                        $catalog->set($message, $this->prefix.$message, $domain);
                        $metadata = $catalog->getMetadata($message, $domain) ?? [];
                        $metadata['sources'][] = $normalizedFilename.':'.$tokens[$key][2];
                        $catalog->setMetadata($message, $metadata, $domain);
                        $this->config->add_source_for($message, $domain, 'php', str_replace($this->kernel->getProjectDir(),'',$normalizedFilename));
                    }
                    break;
                }
            }
        }
    }

    /**
     * @return bool
     *
     * @throws \InvalidArgumentException
     */
    protected function canBeExtracted(string $file)
    {
        if (!$this->config->usePHP() || !parent::canBeExtracted($file)) return false;
        if ($this->config->useFileNameMatching() && in_array(basename($file),$this->config->matchingFileNames()))
            return true;

        $content = file_get_contents($file);
        if (
            !str_contains($content, '->trans') &&
            !str_contains($content, 'T::__')
        ) return false;

        return !$this->config->useFileNameMatching();
    }

    /**
     * {@inheritdoc}
     */
    protected function extractFromDirectory($directory)
    {
        $files = parent::extractFromDirectory($directory);
        return $files->filter(function(\SplFileInfo $file) {
            return $this->canBeExtracted($file->getRealPath());
        });
    }

}