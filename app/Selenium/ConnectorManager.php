<?php

namespace App\Selenium;

use App\Selenium\Repositories\RepositoriesInterface;

class ConnectorManager
{
    public function __construct(
        protected RepositoriesInterface $repository,
        protected ConnectorFactory $factory
    ) {}

    public function add(string $name, Connector $connector): void
    {
        $this->repository->add(strtolower($name), $connector->getSession());
    }

    public function hasSession(string $name): bool
    {
        return $this->repository->has(strtolower($name));
    }

    public function isConnectorActive(string $name): bool
    {
        return $this->getConnector($name)->ping();
    }

    public function getConnector(?string $name = null): Connector|array
    {
        if ($name === null) {
            return $this->getAllConnectors();
        }

        $session = $this->repository->get(strtolower($name));
        if ($session === null) {
            throw new \InvalidArgumentException("Connector {$name} is not registered.");
        }

        $connector = $this->factory->create($session);
        $connector->start();
        return $connector;
    }

    public function remove(string $name): void
    {
        $this->repository->remove(strtolower($name));
    }

    public function restartSessions(): void
    {
        foreach ($this->repository->getAll() as $name => $session) {
            $connector = $this->getConnector($name);
            if ($connector->ping()) {
                $connector->restart();
            } else {
                $this->remove($name);
            }
        }
    }

    public function stopSessions(): void
    {
        foreach ($this->repository->getAll() as $name => $session) {
            $connector = $this->getConnector($name);
            if ($connector->ping()) {
                $connector->quit();
            }
            $this->remove($name);
        }
    }

    public function keepSessionsAlive(): void
    {
        foreach ($this->repository->getAll() as $name => $session) {
            $connector = $this->getConnector($name);
            if (!$connector->ping()) {
                $this->remove($name);
            }
        }
    }

    public function createConnector(): Connector
    {
        return $this->factory->create();
    }

    public function getAllConnectors(): array
    {
        $connectors = [];
        foreach ($this->repository->getAll() as $name => $session) {
            $connectors[$name] = $this->factory->create($session);
        }
        return $connectors;
    }
}
