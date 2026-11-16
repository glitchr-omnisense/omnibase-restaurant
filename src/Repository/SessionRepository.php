<?php

namespace Base\Restaurant\Repository;

use Base\Database\Type\Utc;
use Base\Restaurant\Entity\Order\Session;
use Base\Restaurant\Entity\Table;
use Base\Restaurant\Enum\SessionStatus;
use Base\Database\Repository\Finder\Criterion;
use Base\Database\Repository\Finder\Group;
use Base\Database\Repository\Finder\Key;
use Base\Database\Repository\Finder\Parser;
use Base\Database\Repository\Finders;
use Base\Database\Repository\ServiceEntityRepository;

/**
 * @method Session|null find($id)
 * @method Session|null findOneBy(array $criteria, ?array $orderBy = null)
 * @method list<Session> findAll()
 * @method list<Session> findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 * @method mixed maxOfUpdatedAtBy(array $criteria) the latest change, as the database gives it
 */
class SessionRepository extends ServiceEntityRepository
{
    public function __construct(Finders $finders)
    {
        parent::__construct($finders, Session::class);
    }

    public function openAt(Table $table): ?Session
    {
        return $this->findOneBy([
            'table' => Key::of($table),
            Group::of(Parser::SEPARATOR_AND, Criterion::of('status', SessionStatus::SETTLED, Parser::OPTION_NOT_EQUAL)),
        ], ['id' => 'DESC']);
    }

    /** @return array<int, Session> table id => its open session */
    public function openByTable(): array
    {
        $open = [];
        // The hand-written query fetch-joined the tickets and their lines (addSelect): they load when read now.
        foreach ($this->findBy([
            Group::of(Parser::SEPARATOR_AND, Criterion::of('status', SessionStatus::SETTLED, Parser::OPTION_NOT_EQUAL)),
        ], ['id' => 'ASC']) as $session) {
            $open[$session->getTable()->getId()] = $session;
        }

        return $open;
    }

    public function revision(): int
    {
        $last = $this->maxOfUpdatedAtBy([]);
        if ($last && !$last instanceof \DateTimeInterface) {
            $last = new \DateTimeImmutable($last, Utc::zone());
        }

        return $last ? (int) $last->format('Uv') : 0;
    }

    /** @return list<Session> settled since then, newest first */
    public function settledSince(\DateTimeImmutable $since): array
    {
        return $this->findBy([
            'status' => SessionStatus::SETTLED,
            Group::of(Parser::SEPARATOR_AND, Criterion::of('settledAt', \Base\Database\Type\Utc::from($since), Parser::OPTION_GREATER_EQUAL)),
        ], ['settledAt' => 'DESC']);
    }
}
