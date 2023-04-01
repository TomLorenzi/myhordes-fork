<?php

namespace App\Enum;

use Doctrine\ORM\QueryBuilder;

enum UserAccountType
{
    case Normal;
    case Local;
    case Deleted;
    case Crow;
    case Animator;

    public function canBeFiltered(): bool {
        return $this !== self::Normal;
    }

    /**
     * @return self[]
     */
    public static function filterable(): array {
        return array_filter( self::cases(), fn(self $f) => $f->canBeFiltered() );
    }

    public function isUsable(): bool {
        return $this !== self::Local && $this !== self::Deleted;
    }

    /**
     * @return self[]
     */
    public static function usable(): array {
        return array_filter( self::cases(), fn(self $f) => $f->isUsable() );
    }

    public function applyFilter(QueryBuilder $builder): void {
        switch ($this) {
            case self::Local:
                $builder->andWhere('u.email NOT LIKE :local')->setParameter('local', "%@localhost");
                break;
            case self::Deleted:
                $builder->andWhere('u.email != u.name');
                break;
            case self::Crow:
                $builder->andWhere('u.email NOT LIKE :crow')->setParameter('crow', 'crow');
                break;
            case self::Animator:
                $builder->andWhere('u.email NOT LIKE :anim')->setParameter('anim', 'anim');
                break;
            default: break;
        }
    }
}
