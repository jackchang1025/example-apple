<?php

namespace App\Selenium\Repositories;

interface RepositoriesInterface
{
    public function add(string $name, string $session): void;
    public function get(string $name): ?string;
    public function has(string $name): bool;
    public function remove(string $name): void;
    public function getAll(): array;
    public function save(): bool;
}
