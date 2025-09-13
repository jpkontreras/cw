<?php

declare(strict_types=1);

namespace Colame\OrderEs\Exceptions;

class OrderException extends \DomainException {}

class OrderAlreadyConfirmedException extends OrderException {}

class OrderAlreadyCancelledException extends OrderException {}

class ItemNotFoundException extends OrderException {}

class InvalidQuantityException extends OrderException {}

class EmptyOrderException extends OrderException {}