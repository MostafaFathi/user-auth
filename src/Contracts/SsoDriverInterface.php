<?php

namespace MostafaFathi\UserAuth\Contracts;

interface SsoDriverInterface
{
    public function redirect();
    
    public function user(): ?array;
    
    public function logout();
    
    public function getEmail(): ?string;
    
    public function getName(): ?string;
    
    public function getAttributes(): array;
}
