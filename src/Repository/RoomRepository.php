<?php

namespace Base\Restaurant\Repository;

use Base\Restaurant\Entity\Room;
use Base\Database\Repository\Finders;
use Base\Database\Repository\ServiceEntityRepository;

/**
 * @method Room|null find($id)
 * @method Room|null findOneBy(array $criteria, ?array $orderBy = null)
 * @method list<Room> findAll()
 * @method list<Room> findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class RoomRepository extends ServiceEntityRepository
{
    public function __construct(Finders $finders)
    {
        parent::__construct($finders, Room::class);
    }

    /** @return list<Room> */
    public function ordered(): array
    {
        return $this->findBy([], ["position" => "ASC", "id" => "ASC"]);
    }
}
