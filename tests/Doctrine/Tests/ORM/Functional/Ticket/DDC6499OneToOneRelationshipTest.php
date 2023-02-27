<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-6499
 */
class DDC6499OneToOneRelationshipTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->_schemaTool->createSchema(
            [
                $this->_em->getClassMetadata(DDC6499A::class),
                $this->_em->getClassMetadata(DDC6499B::class),
            ]
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->_schemaTool->dropSchema(
            [
                $this->_em->getClassMetadata(DDC6499A::class),
                $this->_em->getClassMetadata(DDC6499B::class),
            ]
        );
    }

    /**
     * Test for the bug described in issue #6499.
     */
    public function testIssue(): void
    {
        if (! $this->_em->getConnection()->getDatabasePlatform()->supportsForeignKeyConstraints()) {
            self::markTestSkipped('Platform does not support foreign keys.');
        }

        $a = new DDC6499A();

        $this->_em->persist($a);
        $this->_em->flush();
        $this->_em->clear();

        self::assertEquals($this->_em->find(DDC6499A::class, $a->id)->b->id, $a->b->id, 'Issue #6499 will result in a Integrity constraint violation before reaching this point.');
    }
}

/** @ORM\Entity */
class DDC6499A
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\OneToOne(targetEntity="DDC6499B", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     *
     * @var DDC6499B
     */
    public $b;

    public function __construct()
    {
        $this->b = new DDC6499B();
    }
}

/** @ORM\Entity */
class DDC6499B
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    public $id;
}
