<?php

namespace Base\Restaurant\Repository;

use Base\Restaurant\Entity\Table;
use Base\Database\Repository\Finders;
use Base\Database\Repository\ServiceEntityRepository;

/**
 * @method Table|null find($id)
 * @method Table|null findOneBy(array $criteria, ?array $orderBy = null)
 * @method list<Table> findAll()
 * @method list<Table> findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class TableRepository extends ServiceEntityRepository
{
    public function __construct(Finders $finders)
    {
        parent::__construct($finders, Table::class);
    }

    /** @return list<Table> */
    public function ordered(): array
    {
        return $this->findBy([], ["position" => "ASC", "id" => "ASC"]);
    }

    public function findOneByToken(string $token): ?Table
    {
        return '' === $token ? null : $this->findOneBy(['token' => $token]);
    }

    /** @return list<Table> every table in use, room by room */
    public function active(): array
    {
        // The hand-written query fetch-joined the room (addSelect): it loads when read now.
        return $this->findBy(['active' => true], ['room.position' => 'ASC', 'room.id' => 'ASC', 'position' => 'ASC', 'id' => 'ASC']);
    }
}
