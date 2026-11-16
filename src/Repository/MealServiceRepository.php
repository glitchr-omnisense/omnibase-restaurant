<?php

namespace Base\Restaurant\Repository;

use Base\Restaurant\Entity\MealService;
use Base\Database\Repository\Finders;
use Base\Database\Repository\ServiceEntityRepository;

/**
 * @method MealService|null find($id)
 * @method MealService|null findOneBy(array $criteria, ?array $orderBy = null)
 * @method list<MealService> findAll()
 * @method list<MealService> findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class MealServiceRepository extends ServiceEntityRepository
{
    public function __construct(Finders $finders)
    {
        parent::__construct($finders, MealService::class);
    }

    /** @return list<MealService> */
    public function ordered(): array
    {
        return $this->findBy([], ["position" => "ASC", "id" => "ASC"]);
    }

    /** @return list<MealService> the services running that day, in their order */
    public function runningOn(\DateTimeInterface $day): array
    {
        return array_values(array_filter($this->findBy(['active' => true], ['position' => 'ASC', 'id' => 'ASC']), fn (MealService $s) => $s->runsOn($day)));
    }

    /** The service a time of that day falls in. */
    public function at(\DateTimeInterface $day, string $time): ?MealService
    {
        foreach ($this->runningOn($day) as $service) {
            if ($service->covers($time)) {
                return $service;
            }
        }

        return null;
    }
}
