<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Proxy\Proxy;

class DDC633Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(
                [
                $this->_em->getClassMetadata(DDC633Patient::class),
                $this->_em->getClassMetadata(DDC633Appointment::class),
                ]
            );
        } catch(\Exception $e) {

        }
    }

    /**
     * @group DDC-633
     * @group DDC-952
     * @group DDC-914
     */
    public function testOneToOneEager()
    {
        $app = new DDC633Appointment();
        $pat = new DDC633Patient();
        $app->patient = $pat;
        $pat->appointment = $app;

        $this->_em->persist($app);
        $this->_em->persist($pat);
        $this->_em->flush();
        $this->_em->clear();

        $eagerAppointment = $this->_em->find(DDC633Appointment::class, $app->id);

        // Eager loading of one to one leads to fetch-join
        $this->assertNotInstanceOf(Proxy::class, $eagerAppointment->patient);
        $this->assertTrue($this->_em->contains($eagerAppointment->patient));
    }

    /**
     * @group DDC-633
     * @group DDC-952
     */
    public function testDQLDeferredEagerLoad()
    {
        for ($i = 0; $i < 10; $i++) {
            $app = new DDC633Appointment();
            $pat = new DDC633Patient();
            $app->patient = $pat;
            $pat->appointment = $app;

            $this->_em->persist($app);
            $this->_em->persist($pat);
        }
        $this->_em->flush();
        $this->_em->clear();

        $appointments = $this->_em->createQuery("SELECT a FROM " . __NAMESPACE__ . "\DDC633Appointment a")->getResult();

        foreach ($appointments AS $eagerAppointment) {
            $this->assertInstanceOf(Proxy::class, $eagerAppointment->patient);
            $this->assertTrue($eagerAppointment->patient->__isInitialized__, "Proxy should already be initialized due to eager loading!");
        }
    }
}

/**
 * @Entity
 */
class DDC633Appointment
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /**
     * @OneToOne(targetEntity="DDC633Patient", inversedBy="appointment", fetch="EAGER")
     */
    public $patient;

}

/**
 * @Entity
 */
class DDC633Patient
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /**
     * @OneToOne(targetEntity="DDC633Appointment", mappedBy="patient")
     */
    public $appointment;
}
