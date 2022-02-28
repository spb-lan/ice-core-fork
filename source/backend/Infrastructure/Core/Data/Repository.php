<?php

namespace Ifacesoft\Ice\Core\Infrastructure\Core\Data;

use Ifacesoft\Ice\Core\Domain\Data\Dto;
use Ifacesoft\Ice\Core\Domain\Data\Entity;
use Ifacesoft\Ice\Core\Infrastructure\Core\Service;
use RuntimeException;
use Throwable;

abstract class Repository extends Service
{
    /**
     * @var Entity
     */
    private $entities;

    final protected function init()
    {
        parent::init();

        $this->entities = Entity::create();
    }

    /**
     * @param $id
     * @return Dto
     * @throws Throwable
     */
    final public function get($id)
    {
        if (!$id) {
            throw new RuntimeException('Entity id is empty in ' . get_class($this));
        }

        return $this->entities->get($id);
    }

    /**
     * @param Dto[] $entities
     *
     * @return Repository
     *
     * @throws Throwable
     */
    final public function add(array $entities)
    {
        $this->entities->set(
            $entities,
            [
                'callbacks' => static function ($alias, $entity) {
                    /** @var Dto $entity */
                    return [$entity->getId(), $entity];
                }
            ]
        );

        return $this;
    }

    /**
     * @param array $entities
     *
     * @return Repository
     *
     * @throws Throwable
     */
    final public function remove(array $entities)
    {
        $this->entities->delete($entities);

        return $this;
    }
}