<?php


namespace App\Service;

use App\Entity\BuildingPrototype;
use App\Entity\Citizen;
use App\Entity\Forum;
use App\Entity\ForumUsagePermissions;
use App\Entity\Inventory;
use App\Entity\ItemGroup;
use App\Entity\ItemProperty;
use App\Entity\ItemPrototype;
use App\Entity\PictoPrototype;
use App\Entity\Recipe;
use App\Entity\Town;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Entity\ZonePrototype;
use App\Interfaces\NamedEntity;
use App\Structures\IdentifierSemantic;
use DirectoryIterator;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionProperty;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;

class CommandHelper
{
    private EntityManagerInterface $entity_manager;
    private KernelInterface $app;
    private $_db = null;
    private TranslatorInterface $trans;

    private $language = 'en';

    public function __construct(EntityManagerInterface $em, KernelInterface $kernel, TranslatorInterface $trans) {
        $this->entity_manager = $em;
        $this->app = $kernel;
        $this->trans = $trans;
    }

    public function setLanguage(string $language): void {
        $this->language = $language;
    }

    public function leChunk( OutputInterface $output, string $repository, int $chunkSize, array $filter, bool $manualChain, bool $alwaysPersist, callable $handler, bool $clearEM = false, ?callable $revitalize = null) {
        if ($revitalize !== null) $revitalize();

        $tc = $this->entity_manager->getRepository($repository)->count($filter);
        $tc_chunk = 0;

        $output->writeln("Processing <info>$tc</info> <comment>$repository</comment> entities...");
        $progress = new ProgressBar( $output->section() );
        $progress->start($tc);

        while ($tc_chunk < $tc) {
            $entities = $this->entity_manager->getRepository($repository)->findBy($filter,['id' => 'ASC'], $chunkSize, $manualChain ? $tc_chunk : 0);
            foreach ($entities as $entity) {
                if ($handler($entity) or $alwaysPersist) $this->entity_manager->persist($entity);
                $tc_chunk++;
            }
            $this->entity_manager->flush();
            if ($clearEM) {
                $this->entity_manager->clear();
                if ($revitalize !== null) $revitalize();
            }
            $progress->setProgress(min($tc,$tc_chunk));
        }

        $output->writeln('OK!');
    }

    /**
     * @param string $command
     * @param int|null $ret
     * @param bool|false $detach
     * @param OutputInterface|null $output
     * @return string[]
     */
    public function bin( string $command, ?int &$ret = null, bool $detach = false, ?OutputInterface $output = null ): array {
        $process_handle = popen( $command, 'r' );

        $lines = [];
        if ($output) $output->writeln( "" );
        if (!$detach) while (($line = fgets( $process_handle )) !== false) {
            if ($output) $output->write( "> {$line}" );
            $lines[] = $line;
        }

        $ret = pclose($process_handle);
        return $lines;
    }

    public function console( string $command, array $arguments = [], ?OutputInterface &$output = null ): int {

        $application = new Application($this->app);
        $application->setAutoExit(false);

        $input = new ArrayInput(array_merge($arguments,['command' => $command]));
        if ($output === null) $output = new BufferedOutput();

        return $application->run($input, $output);
    }

    public function capsule( string $command, OutputInterface $output, ?string $note = null, bool $bin_console = true, ?string &$ret_str = null, $php_bin = "php", bool $enforce_verbosity = false, int $retry = 0, ?array $as = null ): bool {
        $su = '';
        if ($as !== null) {
            if ($as[0] !== null && $as[1] !== null) $su = "sudo -u {$as[0]} -g {$as[1]} ";
            elseif ($as[0] !== null) $su = "sudo -u {$as[0]} ";
            elseif ($as[1] !== null) $su = "sudo -g {$as[1]} ";
        }

        $run_command = $bin_console ? "$su $php_bin bin/console $command 2>&1" : "$su $command 2>&1";

        $verbose = $enforce_verbosity || $output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE;

        $output->write($note !== null ? $note : ("<info>Executing " . ($bin_console ? 'encapsulated' : '') . " command \"<comment>$command</comment>\"" . ($as ? (' as <comment>' . implode( ':', $as ) . '</comment>') : '') . "... </info>"));
        $lines = $this->bin( $run_command, $ret, false, $verbose ? $output : null );

        if ($ret !== 0) {
            $output->writeln('');
            if ($note !== null) $output->writeln("<info>Command was \"<comment>{$run_command}</comment>\"</info>");
            if (!$verbose) foreach ($lines as $line) $output->write( "> {$line}" );
            if ($retry <= 0) $ret_str = implode("\r\n", $lines);
            $output->writeln("<error>Command exited with error code {$ret}</error>");
            if ($retry > 0)
                $output->writeln("\n<info>Retrying ($retry attempts left).</info>\n");
        } else $output->writeln("<info>Ok.</info>");

        if ($ret !== 0 && $retry > 0) {
            sleep(5);
            return $this->capsule($command, $output, $note, $bin_console, $ret_str, $php_bin, $enforce_verbosity, $retry - 1, $as);
        }
        else return $ret === 0;
    }

    public function printObject(object $e): string {
        $class = get_class($e);
        while (strpos($class, 'Proxies\__CG__\\') === 0)
            $class = get_parent_class($class);

        switch ($class) {
            case User::class:
                /** @var User $e */
                return "User #{$e->getId()} <comment>{$e->getUsername()}</comment> ({$e->getEmail()})";
            case Citizen::class:
                /** @var Citizen $e */
                return "Citizen #{$e->getId()} <comment>{$e->getUser()->getUsername()}</comment> ({$e->getProfession()->getLabel()} in {$e->getTown()->getName()})";
            case ItemPrototype::class:
                /** @var ItemPrototype $e */
                return "Item Type #{$e->getId()} <comment>{$this->trans->trans($e->getLabel(), [], $e::getTranslationDomain(), $this->language)}</comment> ({$e->getName()})";
            case ItemProperty::class:
                /** @var ItemProperty $e */
                return "Item Property #{$e->getId()} <comment>{$e->getName()}</comment>";
            case PictoPrototype::class:
                /** @var PictoPrototype $e */
                return "Picto Type #{$e->getId()} <comment>{$this->trans->trans($e->getLabel(), [], $e::getTranslationDomain(), $this->language)}</comment> ({$e->getName()})";
            case BuildingPrototype::class:
                /** @var BuildingPrototype $e */
                return "Building Type #{$e->getId()} <comment>{$this->trans->trans($e->getLabel(), [], $e::getTranslationDomain(), $this->language)}</comment> ({$e->getName()})";
            case Town::class:
                /** @var Town $e */
                return "Town #{$e->getId()} <comment>{$e->getName()}</comment> ({$e->getLanguage()}, Day {$e->getDay()})";
            case Forum::class:
                /** @var Forum $e */
                return $e->getTown()
                    ? "Town Forum #{$e->getId()} <comment>{$e->getTitle()}</comment>"
                    : "Forum #{$e->getId()} <comment>{$e->getTitle()}</comment> (Type: <comment>{$e->getType()}</comment>)";
            case UserGroup::class:
                /** @var UserGroup $e */
                return "User group #{$e->getId()} <comment>{$e->getName()}</comment> (Ref1 '<comment>{$e->getRef1()}</comment>' Ref2 '<comment>{$e->getRef2()}</comment>')";
            case ForumUsagePermissions::class:
                /** @var ForumUsagePermissions $e */
                $front = $e->getForum() ? "Forum Permissions #{$e->getId()} ('<comment>{$e->getForum()->getTitle()}</comment>')" : "Default Forum Permissions #{$e->getId()}";
                if ($e->getPrincipalUser()) return "$front for User '<comment>{$e->getPrincipalUser()->getUsername()}</comment>'";
                elseif ($e->getPrincipalGroup()) return "$front for User '<comment>{$e->getPrincipalGroup()->getName()}</comment>'";
                else return $front;
            case Inventory::class:
                /** @var Inventory $e */
                if ($e->getTown())              return "Inventory #{$e->getId()} (Bank of {$e->getTown()->getName()})";
                else if ($e->getHome())         return "Inventory #{$e->getId()} (Home of {$e->getHome()->getCitizen()->getUser()->getUsername()} in {$e->getHome()->getCitizen()->getTown()->getName()})";
                else if ($e->getCitizen())      return "Inventory #{$e->getId()} (Rucksack of {$e->getCitizen()->getUser()->getUsername()} in {$e->getCitizen()->getTown()->getName()})";
                else if ($e->getZone())         return "Inventory #{$e->getId()} (Floor of {$e->getZone()->getX()}/{$e->getZone()->getY()} in {$e->getZone()->getTown()->getName()})";
                else if ($e->getRuinZone())     return "Inventory #{$e->getId()} (Ruin Floor of {$e->getRuinZone()->getX()}/{$e->getRuinZone()->getY()} at {$e->getRuinZone()->getZone()->getX()}/{$e->getRuinZone()->getZone()->getY()} in {$e->getRuinZone()->getZone()->getTown()->getName()})";
                else if ($e->getRuinZoneRoom()) return "Inventory #{$e->getId()} (Room Floor of {$e->getRuinZoneRoom()->getX()}/{$e->getRuinZoneRoom()->getY()} at {$e->getRuinZoneRoom()->getZone()->getX()}/{$e->getRuinZoneRoom()->getZone()->getY()} in {$e->getRuinZoneRoom()->getZone()->getTown()->getName()})";
                else return "Inventory #{$e->getId()} (unknown)";
			case ZonePrototype::class:
				return "Ruin Prototype #{$e->getId()} <comment>{$this->trans->trans($e->getLabel(), [], $e::getTranslationDomain(), $this->language)}</comment> ({$e->getLabel()})";
            default:
                $cls_ex = explode('\\', get_class($e));
                $niceName =  preg_replace('/(\w)([ABCDEFGHIJKLMNOPQRSTUVWXYZ\d])/', '$1 $2', array_pop($cls_ex));
                $niceName = get_class($e);
                if (is_a($e, NamedEntity::class))
                    return "$niceName #{$e->getId()} <comment>{$this->trans->trans($e->getLabel(), [], $e::getTranslationDomain(), $this->language)}</comment> ({$e->getName()})";
                else return "$niceName #{$e->getId()}";
        }
    }

    private function resolverDatabase(): array {
        if ($this->_db !== null) return $this->_db;
        $this->_db = [];
        foreach (new DirectoryIterator("{$this->app->getProjectDir()}/src/Entity") as $fileInfo) {
            if( !$fileInfo->isFile() || strtolower($fileInfo->getExtension()) !== 'php' ) continue;
            $class = "App\\Entity\\{$fileInfo->getBasename(".{$fileInfo->getExtension()}")}";
            if (!class_exists($class)) continue;

            $id_type = null;
            try {
                $id_type = match((new ReflectionProperty($class, 'id'))->getType()->getName()) {
                    'int' => '#id',
                    Uuid::class => '4id',
                    default => null
                };
            } catch (\Throwable $e) {}

            $this->_db[$class] = [
                IdentifierSemantic::GuessMatch => [],
                IdentifierSemantic::WeakMatch => [],
                IdentifierSemantic::StrongMatch => [],
                IdentifierSemantic::PerfectMatch => $id_type ? [$id_type] : [],
                IdentifierSemantic::LikelyMatch => [],
            ];

            if (is_a($class, NamedEntity::class, true)) {
                $this->_db[$class][IdentifierSemantic::PerfectMatch][] = ":{$class::getTranslationDomain()}:label";
                $this->_db[$class][IdentifierSemantic::PerfectMatch][] = 'name';
                $this->_db[$class][IdentifierSemantic::GuessMatch][] = ":{$class::getTranslationDomain()}:%label";
                $this->_db[$class][IdentifierSemantic::GuessMatch][] = '%name';
            }
        }

        $this->_db[User::class][IdentifierSemantic::LikelyMatch] = ['#id','name','email'];
        $this->_db[User::class][IdentifierSemantic::GuessMatch] =  ['%name','%email'];
        $this->_db[Town::class][IdentifierSemantic::LikelyMatch] = ['#id','name'];
        $this->_db[Town::class][IdentifierSemantic::GuessMatch] =  ['%name'];
        foreach ([ItemPrototype::class,BuildingPrototype::class,PictoPrototype::class] as $c) {
            $this->_db[$c][IdentifierSemantic::LikelyMatch][] = ":{$c::getTranslationDomain()}:label";
            $this->_db[$c][IdentifierSemantic::LikelyMatch][] = 'name';
        }

        $this->_db[Forum::class][IdentifierSemantic::PerfectMatch] = ['#id'];
        $this->_db[Forum::class][IdentifierSemantic::LikelyMatch]  = ['title'];
        $this->_db[Forum::class][IdentifierSemantic::GuessMatch]   = ['%title', '%description'];

        $this->_db[UserGroup::class][IdentifierSemantic::PerfectMatch] = ['#id'];
        $this->_db[UserGroup::class][IdentifierSemantic::LikelyMatch]  = ['name'];
        $this->_db[UserGroup::class][IdentifierSemantic::GuessMatch]   = ['%name'];

        $this->_db[Recipe::class][IdentifierSemantic::PerfectMatch] = ['#id'];
        $this->_db[Recipe::class][IdentifierSemantic::LikelyMatch]  = ['name'];
        $this->_db[Recipe::class][IdentifierSemantic::GuessMatch]   = ['%name'];

        $this->_db[ItemGroup::class][IdentifierSemantic::PerfectMatch] = ['#id'];
        $this->_db[ItemGroup::class][IdentifierSemantic::LikelyMatch]  = ['name'];
        $this->_db[ItemGroup::class][IdentifierSemantic::GuessMatch]   = ['%name'];

        $this->_db[ZonePrototype::class][IdentifierSemantic::PerfectMatch] = ['#id'];
        $this->_db[ZonePrototype::class][IdentifierSemantic::LikelyMatch]  = ['label', 'icon'];
        $this->_db[ZonePrototype::class][IdentifierSemantic::GuessMatch]   = ['%label', '%icon'];

        $this->_db[ItemProperty::class][IdentifierSemantic::PerfectMatch] = ['#id'];
        $this->_db[ItemProperty::class][IdentifierSemantic::LikelyMatch]  = ['name'];
        $this->_db[ItemProperty::class][IdentifierSemantic::GuessMatch]   = ['%name'];
        return $this->_db;
    }

    public function resolve(string $id): IdentifierSemantic {
        $sem = new IdentifierSemantic();
        foreach ($this->resolverDatabase() as $class => &$config) {
            $repo = $this->entity_manager->getRepository($class);

            if ($repo === null) continue;
            foreach ($config as $strength => &$prop_list)
                foreach ($prop_list as &$prop) {
                    $e = null;

                    $sys_trans = false;
                    if ($prop[0] === ':') list( $sys_trans, $prop ) = explode(':', substr($prop, 1));

                    $mod = '';
                    if (in_array($prop[0], ['#','%','4'])) {
                        $mod = $prop[0];
                        $prop = substr($prop, 1);
                    }

                    if ($mod === '#' && is_numeric($id))
                        $e = $repo->findBy([$prop => (int)$id]);
                    elseif ($mod === '4' && Uuid::isValid($id))
                        $e = $repo->findBy([$prop => $id]);
                    elseif ( $sys_trans ) {
                        $e = $repo->findBy(['id' => array_map( fn(array $entity) => $entity['id'] ,array_filter( $repo->createQueryBuilder('e')->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY), function( array $entity ) use ($mod, $id, $prop, $sys_trans) {
                            $field = $this->trans->trans( $entity[$prop], [], $sys_trans, $this->language );
                            return $mod === '%' ? str_contains( mb_strtolower($field), mb_strtolower($id) ) : (mb_strtolower($field) === mb_strtolower($id));
                        } ))]);
                    }
                    elseif ($mod === '%')
                        $e = $repo->createQueryBuilder('e')
                            ->where("e.{$prop} LIKE :param")->setParameter('param', "%{$id}%")
                            ->getQuery()->getResult();
                    elseif ($mod === '') $e = $repo->findBy([$prop => $id]);

                    if ($e !== null) foreach ($e as $entity)
                        $sem->addResult($entity, $strength, $prop);
                }
        }
        $sem->sortResults();
        return $sem;
    }

    private function semantic_resolve( object $base, string $class, ?string $hint ): ?object {
        switch ($class) {
            case Citizen::class:
                switch (get_class($base)) {
                    case User::class:
                        /** @var User $base */
                        return $base->getActiveCitizen();
                    default: return null;
                }
            case Town::class: {
                switch (get_class($base)) {
                    case User::class:
                        /** @var User $base */
                        return $base->getActiveCitizen() ? $base->getActiveCitizen()->getTown() : null;
                    case UserGroup::class:
                        /** @var UserGroup $base */
                        return $base->getType() === UserGroup::GroupTownInhabitants ? $this->entity_manager->getRepository(Town::class)->find($base->getRef1()) : null;
                    default: null;
                }
            }
            case Forum::class: {
                switch (get_class($base)) {
                    case Town::class:
                        /** @var Town $base */
                        return $base->getForum();
                    default: null;
                }
            }
            case UserGroup::class: {
                switch (get_class($base)) {
                    case Town::class:
                        /** @var Town $base */
                        return $this->entity_manager->getRepository(UserGroup::class)->findOneBy(['type' => UserGroup::GroupTownInhabitants, 'ref1' => $base->getID()]);
                    default: null;
                }
            }
            case Inventory::class: {
                switch (get_class($base)) {
                    case User::class:
                        if ($hint === null) $hint = 'rucksack';

                        /** @var User $base */
                        if (!$base->getActiveCitizen()) return null;

                        switch ($hint) {
                            case 'rucksack':case 'ruck':case 'sack':case 'inventory':case 'inv': return $base->getActiveCitizen()->getInventory();
                            case 'home':case 'house':case 'chest': return $base->getActiveCitizen()->getHome()->getChest();
                            case 'bank':case 'town': return $base->getActiveCitizen()->getTown()->getBank();
                            case 'zone':case 'floor': return $base->getActiveCitizen()->getZone() ? $base->getActiveCitizen()->getZone()->getFloor() : null;
                            default: return null;
                        }
                    default: null;
                }
            }
            default: return null;
        }
    }

    public function resolve_as(string $id, string $class, ?string $hint = null, ?IdentifierSemantic &$final = null) {

        $base = $this->resolve($id);
        if (!$final) $final = new IdentifierSemantic();
        foreach ($this->resolve($id)->getMatches() as $match) {

            if (is_a($e = $base->getMatchedObject($match), $class))
                $final->addResult($e, $base->getMatchedStrength($match), $base->getMatchedProperty($match));
            elseif ($base->getMatchedStrength($match) >= IdentifierSemantic::GuessMatch) {
                $derived = $this->semantic_resolve($e, $class, $hint);
                if ($derived !== null && is_a($derived, $class))
                    $final->addResult($derived, max(1,$base->getMatchedStrength($match) - 1), 'semantics');
            }
        }

        $final->sortResults();
        return $final;
    }

    /**
     * @param string $id
     * @param string|array $class
     * @param string $label
     * @param QuestionHelper|null $qh
     * @param InputInterface|null $in
     * @param OutputInterface|null $out
     * @param TranslatorInterface|null $trans
     * @return object|null
     */
    public function resolve_string(string $id, string|array $class, string $label = 'Principal Object', ?QuestionHelper $qh = null, ?InputInterface $in = null, ?OutputInterface $out = null, ?TranslatorInterface $trans = null): ?object {

        $sem = explode(':', $id);
        $hint = null;
        if (count($sem) > 1) {
            $hint = array_pop($sem);
            if (preg_match('/^\w*$/', $hint) !== false) {
                $id = implode(':', $sem);
            } else $hint = null;

            if ($hint === 'auto') $hint = null;
        }

        if (!is_array( $class )) $class = [$class];

        $sem = null;
        foreach ($class as $subclass)
            $sem = $this->resolve_as($id, $subclass, $hint, $sem);

        if (count($sem->getMatches(IdentifierSemantic::LikelyMatch)) === 1)
            return $sem->getMatchedObject(array_values($sem->getMatches(IdentifierSemantic::LikelyMatch))[0]);
        elseif (count($sem->getMatches(IdentifierSemantic::PerfectMatch)) === 1)
            return $sem->getMatchedObject(array_values($sem->getMatches(IdentifierSemantic::PerfectMatch))[0]);
        elseif (count($sem->getMatches(IdentifierSemantic::StrongMatch)) === 1)
            return $sem->getMatchedObject(array_values($sem->getMatches(IdentifierSemantic::StrongMatch))[0]);
        elseif ($qh !== null && $in !== null && $out !== null) {

            $l = [];
            foreach ( $sem->getMatches() as $match ) {
                $o = $sem->getMatchedObject($match);
                $l[$this->printObject($o)] = $o;
            }

            if (empty($l)) {
                if ($hint)
                    $out->writeln("<error>Your query '$id' ('$hint') for $label did not yield any results.</error>");
                else $out->writeln("<error>Your query '$id' for $label did not yield any results.</error>");
                return null;
            }

            if(count($l) === 1) {
                $out->writeln("Your query '$id' has been resolved to : <comment>{$this->printObject($l[array_keys($l)[0]])}</comment>");
                return $l[array_keys($l)[0]];
            }

            $result = $qh->ask($in, $out, new ChoiceQuestion(
                "Your input for '$label' is ambiguous. Please select an option from the list below:",
                array_keys($l)
            ) );

            return $l[$result];
        }
        else return null;
    }

    public function interactiveConfirm(mixed $helper, InputInterface $input, OutputInterface $output, string $question = 'Confirm?', bool $default = false): bool {
        if ( $input->getOption('yes') ) return true;
        return $helper->ask($input, $output, new ConfirmationQuestion("<fg=yellow>$question</> (y/n)\n> ", $default));
    }
}