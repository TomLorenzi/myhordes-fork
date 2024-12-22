<?php

namespace MyHordes\Plugins\Interfaces;

use Adbar\Dot;
use Exception;

abstract class FixtureChainInterface
{
    private array $processors = [];

    public function addProcessor( FixtureProcessorInterface $if, string $source, string $tag ): void {
        $this->processors[] = [$if,$source,$tag];
    }

    /**
     * @throws Exception
     */
    public function data( string|array $limit = [] ): array {
        if (empty($this->processors))
            throw new Exception('Fixture chain has no processors!');

        $limit = is_string($limit) ? [$limit] : $limit;

        $data = [];

        // If no filters are enabled, we can simply walk the processor chain and return the final result
        if (empty($limit)) {
            foreach ($this->processors as [$processor,$_,$tag])
                $processor->process($data, $tag);
            return $data;
        // Otherwise, we need to track changes more carefully
        } else {
            // Make a separate data container to track changes
            $tracked_changes = new Dot();

            foreach ($this->processors as [$processor,$source,$tag]) {
                // Check if the current source is tracked
                $track = self::filter_fits($limit, $source);

                // If it is tracked, make a snapshot of the data before applying the processor
                if ($track) $data_before = new Dot($data);
                $processor->process($data,$tag);

                // If it is tracked...
                if ($track) {
                    // make a snapshot of the data after processing and extract a union of all existing flat array keys
                    $data_after = new Dot($data);
                    $keyset = array_unique( array_merge( array_keys($data_before->flatten()), array_keys($data_after->flatten()) ) );

                    // For each key, check if the value has been changed between snapshots
                    // If the value has changed, track the change
                    foreach ($keyset as $key)
                        if ($data_before->get( $key ) !== $data_after->get( $key ))
                            $tracked_changes->set( $key, $data_after->get( $key ) );

                }
            }

            // Return only tracked changes
            return $tracked_changes->all();
        }
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
}