<?php


namespace App\Service;



use App\Structures\MyHordesConf;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TwinoidHandler
{

    private $conf;
    private $generator;

    private $fallback_sk = null;
    private $fallback_id = null;
    private $code = null;
    private $token = null;

    public function __construct( ConfMaster $confMaster, UrlGeneratorInterface $generator ) {
        $this->conf = $confMaster->getGlobalConf();
        $this->generator = $generator;
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
}