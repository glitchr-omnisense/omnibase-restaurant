<?php

namespace Base\Restaurant\Repository;

use Base\Restaurant\Entity\Product\TakeHome;
use Base\Database\Repository\Finders;
use Base\Database\Repository\ServiceEntityRepository;

/**
 * @method TakeHome|null find($id)
 * @method TakeHome|null findOneBy(array $criteria, ?array $orderBy = null)
 * @method list<TakeHome> findAll()
 * @method list<TakeHome> findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class TakeHomeRepository extends ServiceEntityRepository
{
    public function __construct(Finders $finders)
    {
        parent::__construct($finders, TakeHome::class);
    }

    /** @return list<TakeHome> the shop's dishes in its order; those withdrawn from sale left out */
    public function shop(bool $onSaleOnly = true): array
    {
        $dishes = $this->findAll();
        if ($onSaleOnly) {
            $dishes = array_values(array_filter($dishes, fn (TakeHome $d) => $d->isForSell() || \Base\Marketplace\Enum\ProductAvailability::OUT_OF_STOCK === $d->getAvailability()));
        }
        usort($dishes, fn (TakeHome $a, TakeHome $b) => [$a->getShopPosition(), $a->getId()] <=> [$b->getShopPosition(), $b->getId()]);

        return $dishes;
    }

    public function findOneBySlug(string $slug): ?TakeHome
    {
        return $this->findOneBy(['slug' => $slug]);
    }
}
