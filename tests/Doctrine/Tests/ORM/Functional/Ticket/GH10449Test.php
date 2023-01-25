<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Tests\OrmTestCase;
use Doctrine\Tests\PHPUnitCompatibility\ExceptionMatching;

class GH10449Test extends OrmTestCase
{
    use ExceptionMatching;

    public function testToManyAssociationOnMappedSuperclassShallBeRejected(): void
    {
        $em = $this->getTestEntityManager();

        $this->expectException(MappingException::class);
        $this->expectExceptionMessageMatches('/illegal to put an inverse side one-to-many or many-to-many association on mapped superclass/');

        // Currently the ClassMetadataFactory performs the check only when loading the subclasses, this might change with
        // https://github.com/doctrine/orm/pull/10398
        $em->getClassMetadata(GH10449Entity::class);
    }
}

/**
 * @ORM\Entity
 */
class GH10449ToManyAssociationTarget
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="GH10449MappedSuperclass", inversedBy="targets")
     *
     * @var GH10449MappedSuperclass
     */
    public $base;
}

/**
 * @ORM\MappedSuperclass
 */
class GH10449MappedSuperclass
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\OneToMany(targetEntity="GH10449ToManyAssociationTarget", mappedBy="base")
     *
     * @var Collection
     */
    public $targets;
}

/**
 * @ORM\Entity
 */
class GH10449Entity extends GH10449MappedSuperclass
{
}
