<?php

namespace MyHordes\Fixtures\DTO\Awards;

use App\Entity\AwardPrototype;
use App\Entity\PictoPrototype;
use Doctrine\ORM\EntityManagerInterface;
use MyHordes\Fixtures\DTO\Element;

/**
 * @property string $title
 * @method self title(string $v)
 * @property string $associatedtag
 * @method self associatedtag(string $v)
 * @property string $associatedpicto
 * @method self associatedpicto(string $v)
 * @property int $unlockquantity
 * @method self unlockquantity(int $v)
 *
 * @method AwardTitlePrototypeDataContainer commit(string &$id = null)
 * @method AwardTitlePrototypeDataContainer discard()
 */
class AwardTitlePrototypeDataElement extends Element {

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
                ->setAssociatedTag($this->associatedtag)
                ->setTitle($this->title)
                ->setIcon(null)
                ->setUnlockQuantity($this->unlockquantity);
        } catch (\Throwable $t) {
            throw new \Exception(
                "Exception when persisting award title prototype to database: {$t->getMessage()} \n\nOccurred when processing the following award title:\n" . print_r($this->toArray(), true),
                previous: $t
            );
        }


    }

}