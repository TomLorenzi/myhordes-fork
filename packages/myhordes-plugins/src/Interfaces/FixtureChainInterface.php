<?php

namespace MyHordes\Plugins\Interfaces;

use Exception;

abstract class FixtureChainInterface
{
    private array $processors = [];

    public function addProcessor( FixtureProcessorInterface $if, string $source ): void {
        $this->processors[] = [$if,$source];
    }

    protected static function filter_fits(array $filters, string $subject): bool {

        if (empty($filters)) return true;
        $subject = explode('/', $subject);

        foreach ($filters as $filter) {
            $local_subject = $subject;
            $filter = explode('/', $filter);

            if ( $filter[ array_key_last( $filters ) ] === '*' ) {
                $filter = array_slice( $filter, 0, -1 );
                $local_subject = array_slice( $local_subject, 0, count($filter) );
            }

            if ( count( $filter ) !== count( $local_subject ) ) continue;

            if (
                array_reduce( array_map( fn($a,$b) => $a === $b, $filter, $local_subject ), fn($c, $a) => $c && $a, true )
            ) return true;
        }

        return false;
    }

    /**
     * @throws Exception
     */
    public function data( string|array $limit = [] ): array {
        if (empty($this->processors))
            throw new Exception('Fixture chain has no processors!');

        $limit = is_string($limit) ? [$limit] : $limit;

        $data = [];
        foreach ($this->processors as [$processor,$source])
            if (self::filter_fits( $limit, $source ))
                $processor->process($data);
        return $data;
    }
}