<?php

namespace MostafaFathi\UserAuth\Contracts;

interface AuthDriverInterface
{
    public function authenticate(array $credentials): bool;
    
    public function getUser(): ?object;
    
    public function logout(): void;
}
