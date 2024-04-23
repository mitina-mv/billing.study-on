<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

class UserDto
{
    #[Serializer\Type('string')]
    #[Assert\NotBlank(message: 'Email обязателен к заполнению.')]
    #[Assert\Email(message: 'Некорректный email.')]
    #[Assert\Unique(message: 'Email должен быть уникальным!')]
    #[Assert\Type(type: 'string', message: 'Email должен быть строкой')]
    public ?string $username = null;

    #[Serializer\Type('string')]
    #[Assert\NotBlank(message: 'Пароль обязателен к заполнению.')]
    #[Assert\Length(min: 6, minMessage: 'Минимальная длинна пароля: 6')]
    public ?string $password = null;
}
