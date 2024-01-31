<?php

namespace MyHordes\Fixtures\DTO\Awards;

use App\Entity\AwardPrototype;
use App\Entity\PictoPrototype;
use Doctrine\ORM\EntityManagerInterface;
use MyHordes\Fixtures\DTO\Element;

/**
 * @property string $icon
 * @method self icon(string $v)
 * @property string $associatedpicto
 * @method self associatedpicto(string $v)
 * @property int $unlockquantity
 * @method self unlockquantity(int $v)
 *
 * @method AwardIconPrototypeDataContainer commit(string &$id = null)
 * @method AwardIconPrototypeDataContainer discard()
 */
class AwardIconPrototypeDataElement extends Element {

    /**
     * @throws \Exception
     */
    public function toEntity(EntityManagerInterface $em, AwardPrototype $entity): void {

        try {
            // Check the category
            $pp = $em->getRepository(PictoPrototype::class)->findOneBy(['name' => $this->associatedpicto]);
            if ($pp === null)
                throw new \Exception("Invalid picto '{$this->associatedpicto}' specified for award '{$this->title}'.");

            $entity
                ->setAssociatedPicto( $pp )
                ->setAssociatedTag(null)
                ->setTitle(null)
                ->setIcon($this->icon)
                ->setUnlockQuantity($this->unlockquantity);
        } catch (\Throwable $t) {
            throw new \Exception(
                "Exception when persisting award icon prototype to database: {$t->getMessage()} \n\nOccurred when processing the following award icon:\n" . print_r($this->toArray(), true),
                previous: $t
            );
        }


    }

}