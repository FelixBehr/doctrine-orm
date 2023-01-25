<?php

declare(strict_types=1);

namespace Doctrine\Tests\PHPUnitCompatibility;

use function get_parent_class;
use function method_exists;

trait ExceptionMatching
{
    /**
     * Override for BC with PHPUnit <8
     */
    public function expectExceptionMessageMatches(string $regularExpression): void
    {
        if (method_exists(get_parent_class($this), 'expectExceptionMessageMatches')) {
            parent::expectExceptionMessageMatches($regularExpression);
        } else {
            parent::expectExceptionMessageRegExp($regularExpression);
        }
    }
}
