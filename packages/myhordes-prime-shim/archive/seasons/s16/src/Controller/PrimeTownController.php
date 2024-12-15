<?php

namespace MyHordes\Prime\Controller;

use App\Controller\InventoryAwareController;
use App\Controller\Town\TownController;
use App\Entity\Citizen;
use App\Entity\ItemPrototype;
use App\Entity\PictoPrototype;
use App\Response\AjaxResponse;
use App\Service\ErrorHelper;
use App\Service\ItemFactory;
use App\Structures\ItemRequest;
use MyHordes\Prime\Helpers\PrimeCitizenDisposal;
use MyHordes\Prime\Helpers\PrimeLogTemplateHandler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

class PrimeTownController extends InventoryAwareController {
	#[Route(path: 'api/town/visit/{id}/burn', name: 'town_visit_burn_controller')]
	public function burnCadaver(int $id, PrimeLogTemplateHandler $primeLog, ItemFactory $itemFactory): Response {
		if ($id === $this->getActiveCitizen()->getId())
			return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable );

		$ac = $this->getActiveCitizen();

		/** @var Citizen $c */
		$c = $this->entity_manager->getRepository(Citizen::class)->find( $id );
		if (!$c || $c->getTown()->getId() !== $this->getActiveCitizen()->getTown()->getId() || $c->getAlive())
			return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable );

		if (!$c->getHome()->getHoldsBody()) {
			if ($c->getDisposed() === Citizen::Thrown) {
				return AjaxResponse::error(TownController::ErrorAlreadyThrown);
			} else if ($c->getDisposed() === Citizen::Watered) {
				return AjaxResponse::error(TownController::ErrorAlreadyWatered);
			} else if ($c->getDisposed() === Citizen::Cooked) {
				return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);
			} else if ($c->getDisposed() === Citizen::Ghoul) {
				return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);
			} else  {
				return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);
			}
		}

		if ($ac->getAp() < 2 || $this->citizen_handler->isTired( $ac ))
			return AjaxResponse::error( ErrorHelper::ErrorNoAP );

		$items = $this->inventory_handler->fetchSpecificItems($ac->getInventory(), [new ItemRequest("torch_#00", 1)]);
		if (count($items) === 0)
			return AjaxResponse::error(ErrorHelper::ErrorItemsMissing);

		// Remove 2 APs and the torch from the inventory
		$this->citizen_handler->setAP($ac, true, -2);
		$items[0]->setPrototype($this->entity_manager->getRepository(ItemPrototype::class)->findOneBy(['name' => 'torch_off_#00']));
		$this->entity_manager->persist($items[0]);

		$garden = $this->town_handler->getBuilding($c->getTown(), 'item_vegetable_tasty_#00');
		if ($garden)
			// We add a corpse to the Garden to mark it as fertilized with the ashes
			$this->inventory_handler->forceMoveItem($garden->getInventory(), $itemFactory->createItem('cadaver_#00'));

        $c->setDisposed(PrimeCitizenDisposal::Burned);
		$c->addDisposedBy($ac);
		$this->entity_manager->persist( $primeLog->citizenDisposalBurn( $ac, $c, !!$garden ) );
		$c->getHome()->setHoldsBody( false );

		// Give picto according to action
		$pictoPrototype = $this->doctrineCache->getEntityByIdentifier(PictoPrototype::class, 'r_cburn_#00');
		$this->picto_handler->give_picto($ac, $pictoPrototype);

		$this->addFlash('notice', $this->translator->trans('Du verbrennst die Leiche von {disposed} mit der Fackel und verstreust dann die Asche in das Gemüsebeet. Das sollte unsere Ernte verbessern...', ['{disposed}' => '<span>' . $c->getName() . '</span>'], 'game'));

		try {
			$this->entity_manager->persist($ac);
			$this->entity_manager->persist($c);
			$this->entity_manager->flush();
		} catch (Exception $e) {
			return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
		}

		return AjaxResponse::success();
	}
}