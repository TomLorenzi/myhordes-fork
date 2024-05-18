<?php

namespace MyHordes\Fixtures\DTO\Actions\Atoms\Requirement;

use App\Enum\ActionHandler\PointType;
use App\Service\Actions\Game\AtomProcessors\Require\ProcessPointRequirement;
use MyHordes\Fixtures\DTO\Actions\RequirementsAtom;

/**
 * @method self require(PointType $v)
 * @property PointType $require
 * @method self min(?int $v)
 * @property ?int $min
 * @method self max(?int $v)
 * @property ?int $max
 * @method self fromLimit(?bool $v = true)
 * @property ?bool $fromLimit
 */
class PointRequirement extends RequirementsAtom {
    public function getClass(): string
    {
        return ProcessPointRequirement::class;
    }

    protected static function beforeSerialization(array $data): array {
        $data['require'] = ($data['require'] ?? PointType::AP)->value;
        return parent::beforeSerialization( $data );
    }

    protected static function afterSerialization(array $data): array {
        $data['require'] = PointType::from( ($data['require'] ?? PointType::AP->value) );
        return parent::afterSerialization( $data );
    }

    public function __call(string $name, array $arguments): self
    {
        if (($name === 'fromLimit') && count($arguments) === 0) return parent::__call( $name, [true] );
        else return parent::__call( $name, $arguments );
    }

    protected function default(string $name): mixed
    {
        return match ($name) {
            'require' => PointType::AP,
            default => parent::default($name)
        };
    }
}