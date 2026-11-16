<?php

namespace Base\Restaurant\Repository;

use Base\Restaurant\Entity\Layout;
use Base\Database\Repository\Finders;
use Base\Database\Repository\ServiceEntityRepository;

/**
 * @method Layout|null find($id)
 * @method Layout|null findOneBy(array $criteria, ?array $orderBy = null)
 * @method list<Layout> findAll()
 * @method list<Layout> findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class LayoutRepository extends ServiceEntityRepository
{
    public function __construct(Finders $finders)
    {
        parent::__construct($finders, Layout::class);
    }

    /** @return list<Layout> */
    public function ordered(): array
    {
        return $this->findBy([], ["name" => "ASC"]);
    }
}
