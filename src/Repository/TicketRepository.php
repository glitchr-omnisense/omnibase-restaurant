<?php

namespace Base\Restaurant\Repository;

use Base\Database\Type\Utc;
use Base\Restaurant\Entity\Order\Ticket;
use Base\Restaurant\Enum\TicketStatus;
use Base\Database\Repository\Finder\Criterion;
use Base\Database\Repository\Finder\Group;
use Base\Database\Repository\Finder\Parser;
use Base\Database\Repository\Finders;
use Base\Database\Repository\ServiceEntityRepository;

/**
 * @method Ticket|null find($id)
 * @method Ticket|null findOneBy(array $criteria, ?array $orderBy = null)
 * @method list<Ticket> findAll()
 * @method list<Ticket> findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 * @method mixed maxOfUpdatedAtBy(array $criteria) the latest change, as the database gives it
 * @method mixed maxOfIdBy(array $criteria)
 */
class TicketRepository extends ServiceEntityRepository
{
    public function __construct(Finders $finders)
    {
        parent::__construct($finders, Ticket::class);
    }

    /** The statuses of a ticket not finished. */
    private const OPEN = [TicketStatus::NEW, TicketStatus::ACCEPTED, TicketStatus::PREPARING, TicketStatus::READY];

    public function findOneByExternal(string $platform, string $ref): ?Ticket
    {
        return $this->findOneBy(['platform' => $platform, 'externalRef' => $ref]);
    }

    /**
     * @return list<Ticket> what the pass shows: every ticket not finished,
     *                      and those served in the last $servedFor minutes
     */
    public function onThePass(int $servedFor = 20): array
    {
        // The hand-written query fetch-joined the lines and the table (addSelect): they load when read now.
        return $this->findBy([
            Group::of(Parser::SEPARATOR_OR,
                Criterion::of('status', self::OPEN),
                Group::of(Parser::SEPARATOR_AND,
                    Criterion::of('status', TicketStatus::SERVED),
                    Criterion::of('servedAt', \Base\Database\Type\Utc::now()->modify(sprintf('-%d minutes', $servedFor)), Parser::OPTION_GREATER_EQUAL),
                ),
            ),
        ], ['createdAt' => 'ASC', 'id' => 'ASC']);
    }

    /** The newest ticket waiting for an answer: the pass rings when it grows. */
    public function latestNew(): int
    {
        return (int) $this->maxOfIdBy(['status' => TicketStatus::NEW]);
    }

    public function countOpen(): int
    {
        return $this->count(['status' => self::OPEN]);
    }

    public function revision(): int
    {
        $last = $this->maxOfUpdatedAtBy([]);
        if ($last && !$last instanceof \DateTimeInterface) {
            $last = new \DateTimeImmutable($last, Utc::zone());
        }

        return $last ? (int) $last->format('Uv') : 0;
    }
}
