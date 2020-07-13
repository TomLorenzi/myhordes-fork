<?php


namespace App\Service;



use App\Entity\CitizenRankingProxy;
use App\Entity\Picto;
use App\Entity\Season;
use App\Entity\TownClass;
use App\Entity\TownRankingProxy;
use App\Entity\User;
use App\Structures\MyHordesConf;
use App\Structures\TwinoidPayload;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TwinoidHandler
{

    private $conf;
    private $generator;
    private $em;

    private $fallback_sk = null;
    private $fallback_id = null;
    private $code = null;
    private $token = null;

    public function __construct( ConfMaster $confMaster, UrlGeneratorInterface $generator, EntityManagerInterface $em ) {
        $this->conf = $confMaster->getGlobalConf();
        $this->generator = $generator;
        $this->em = $em;
    }

    public function hasBuiltInTwinoidAccess(): bool {
        return
            $this->conf->get(MyHordesConf::CONF_TWINOID_SK, null) !== null &&
            $this->conf->get(MyHordesConf::CONF_TWINOID_ID, null) !== null;
    }

    public function hasTwinoidAccess(): bool {
        return $this->twinoidID() !== null && $this->twinoidSK() !== null;
    }

    public function setFallbackAccess(int $id, string $sk): void {
        $this->fallback_id = $id;
        $this->fallback_sk = $sk;
    }

    public function setCode(string $code): void {
        $this->code = $code;
    }

    protected function twinoidSK(): ?string {
        return $this->conf->get(MyHordesConf::CONF_TWINOID_SK, null) ?? $this->fallback_sk ?? null;
    }

    protected function twinoidID(): ?int {
        return $this->conf->get(MyHordesConf::CONF_TWINOID_ID, null) ?? $this->fallback_id ?? null;
    }

    protected function twinoidToken(?string &$error): ?string {
        if ($this->token) return $this->token;
        if (!$this->code) return null;

        $response = file_get_contents('https://twinoid.com/oauth/token', false, stream_context_create([
            'http' => [
                'method' => "POST",
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'timeout' => 30,
                'content' => http_build_query($form = [
                    'client_id'         => "{$this->twinoidID()}",
                    'client_secret'     => $this->twinoidSK(),
                    'redirect_uri'      => $this->generator->generate('twinoid_auth_endpoint', [], UrlGeneratorInterface::ABSOLUTE_URL),
                    'code'              => "{$this->code}",
                    'grant_type'        => 'authorization_code',
                ])
            ]
        ]));

        if ($response === false) {
            $error = 'connection_error';
            return null;
        }

        $token_response = json_decode($response, true, 512, JSON_INVALID_UTF8_IGNORE);
        if (!is_array($token_response)) {
            $error = 'invalid_data';
            return null;
        }
        if (isset($token_response["error"])) {
            $error = $token_response["error"];
            return null;
        }
        if (!isset($token_response["access_token"])) {
            $error = 'no_token_given';
            return null;
        }

        return ($this->token = $token_response["access_token"]);
    }

    public function getTwinoidAuthURL(string $state, $scope): ?string {
        if (is_array($scope)) $scope = implode(' ', $scope);

        return $this->hasTwinoidAccess() ?
            'https://twinoid.com/oauth/auth?response_type=code' .
            "&client_id={$this->twinoidID()}" .
            '&redirect_uri=' . $this->generator->generate('twinoid_auth_endpoint', [], UrlGeneratorInterface::ABSOLUTE_URL) .
            "&scope={$scope}" .
            "&state={$state}" .
            '&access_type=online'
            : null;
    }

    public function getData( string $service, string $api, $fields, ?string &$error ): ?array {

        if (($token = $this->twinoidToken($error)) === null) return null;

        $builder = null;
        $builder = function( $k, $v ) use(&$builder): string {

            if (is_array($v)) {

                if (isset($v['fields']) && isset($v['filter']))
                    return "$k.filter({$v['filter']}).fields(" . implode(',', array_map($builder, array_keys($v['fields']), $v['fields'])) . ')';
                elseif (isset($v['fields']))
                    return "$k.fields(" . implode(',', array_map($builder, array_keys($v['fields']), $v['fields'])) . ')';
                else return "$k.fields(" . implode(',', array_map($builder, array_keys($v), $v)) . ')';
            } else return $v;
        };

        $field_list = implode(',', array_map( $builder, array_keys($fields), $fields ));

        $f = (strpos($api,'?') === false) ? '?' : '&';
        $response = file_get_contents("http://{$service}/graph/{$api}{$f}access_token={$token}&fields={$field_list}", false, stream_context_create([
            'http' => [
                'method' => "GET",
                'timeout' => 30
            ]
        ]));

        if ($response === false) {
            $error = 'connection_error';
            return null;
        }

        $data = json_decode($response, true, 512, JSON_INVALID_UTF8_IGNORE);
        if (!is_array($data)) {
            $error = 'invalid_data';
            return null;
        }

        return $data;
    }

    private function getScopeLanguage( string $scope ): ?string {
        //['www.hordes.fr','www.die2nite.com','www.dieverdammten.de','www.zombinoia.com']
        switch ($scope) {
            case 'www.hordes.fr':        return 'fr';
            case 'www.die2nite.com':     return 'en';
            case 'www.dieverdammten.de': return 'de';
            case 'www.zombinoia.com':    return 'es';
            default: return null;
        }
    }

    function clearImportedData( User $user, ?string $scope, bool $isPrimary ) {
        $lang = $scope === null ? null : $this->getScopeLanguage($scope);

        // Remove towns
        foreach ($user->getPastLifes() as $past) {
            if ($past->getTown()->getImported() && ($scope === null || $past->getTown()->getLanguage() === $lang)) {
                $user->removePastLife($past);
                $this->em->remove( $past );
            }
        }

        if ($isPrimary) {

            // Remove pictos
            foreach ($this->em->getRepository(Picto::class)->findBy(['imported' => true, 'user' => $user]) as $picto) {
                $user->removePicto($picto);
                $this->em->remove( $picto );
            }

        }
    }

    function importData( User $user, string $scope, TwinoidPayload $data, bool $isPrimary ): bool {
        if (($lang = $this->getScopeLanguage($scope)) === null) return false;

        //<editor-fold desc="Town Import">
        // Get existing towns
        $tid_list = [];
        foreach ($data->getPastTowns() as $town) $tid_list[$town->getID()] = false;

        foreach ($user->getPastLifes() as $past) {
            if ($past->getTown()->getImported() && $past->getTown()->getLanguage() === $lang ) {

                // The town is not in the list of imported towns; remove citizen
                if (!isset($tid_list[$past->getTown()->getBaseID()])) {
                    $user->removePastLife($past);
                    $this->em->remove( $past );
                } else
                    $tid_list[$past->getTown()->getBaseID()] = true;
            }
        }

        $default_town_type = $this->em->getRepository(TownClass::class)->findOneBy(['name' => TownClass::DEFAULT]);

        $seasons = [];
        foreach ($data->getPastTowns() as $town) if ($tid_list[$town->getID()] == false) {

            if (!isset($seasons[$town->getSeason()])) {

                $seasons[$town->getSeason()] = $this->em->getRepository(Season::class)->findOneBy(['number' => 0, 'subNumber' => $town->getSeason()]);
                if ($seasons[$town->getSeason()] === null) {
                    $seasons[$town->getSeason()] = (new Season())
                        ->setNumber(0)->setSubNumber($town->getSeason());
                    $this->em->persist( $seasons[$town->getSeason()] );
                }

            }

            $entry = $this->em->getRepository(TownRankingProxy::class)->findOneBy( ['imported' => true, 'baseID' => $town->getID(), 'language' => $lang] );
            if ($entry === null)
                $entry = (new TownRankingProxy())
                    ->setName( $town->getName() )
                    ->setBaseID( $town->getID() )
                    ->setImported( true )
                    ->setLanguage( $lang )
                    ->setType( $default_town_type )
                    ->setSeason( $seasons[$town->getSeason()] )
                    ->setDays( $town->getDay() )
                    ->setPopulation( 40 );
            else $entry->setDays( max( $entry->getDays(), $town->getDay() ) );

            $entry->addCitizen(
                (new CitizenRankingProxy())
                    ->setBaseID( $user->getId() )
                    ->setImportID( $town->getID() )
                    ->setUser( $user )
                    ->setCod( $town->convertDeath() )
                    ->setComment( $town->getComment() )
                    ->setLastWords( $town->getMessage() )
                    ->setDay( $town->getDay() )
                    ->setConfirmed( true )
                    ->setPoints( $town->getScore() )
            );

            $this->em->persist( $entry );
        }
        //</editor-fold>

        //<editor-fold desc="Picto Import">
        // Get existing pictos
        $pid_list = [];
        foreach ($data->getPictos() as $picto)
            if ($picto->convertPicto())
                $pid_list[$picto->convertPicto()->getID()] = true;

        foreach ($this->em->getRepository(Picto::class)->findBy(['imported' => true, 'user' => $user]) as $picto) {

            // The picto is not in the list of imported pictos; remove it
            if (!isset($pid_list[$picto->getPrototype()->getId()])) {
                $user->removePicto($picto);
                $this->em->remove($picto);
            }
        }

        foreach ($data->getPictos() as $picto) if ($picto->convertPicto()) {

            $entry = $this->em->getRepository(Picto::class)->findOneBy( ['imported' => true, 'user' => $user, 'prototype' => $picto->convertPicto()] );
            if ($entry === null)
                $entry = (new Picto())
                    ->setUser($user)
                    ->setImported(true)
                    ->setPersisted(2)
                    ->setPrototype($picto->convertPicto());
            $entry->setCount($picto->getCount());
            $this->em->persist( $entry );
        }
        //</editor-fold>

        $user->setImportedSoulPoints( $data->getSummarySoulPoints() );
        $user->setImportedHeroDaysSpent( $data->getSummaryHeroDays() );

        return true;
    }
}