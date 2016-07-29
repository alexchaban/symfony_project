<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="participant_type")
 */
class ParticipantType
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="Event")
     * @ORM\JoinColumn(name="event_id", referencedColumnName="id")
     */
    private $event;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=20, nullable=true)
     */
    private $name;
	
    /**
     * @var integer
     *
     * @ORM\Column(name="min_users", type="integer", length=4, nullable=true)
     */
    private $min_users;

    /**
     * @var integer
     *
     * @ORM\Column(name="max_users", type="integer", length=4, nullable=true)
     */
    private $max_users;
	
    /**
     * @var integer
     *
     * @ORM\Column(name="fee", type="float",nullable=true)
     */
    private $fee;
	

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set minUsers
     *
     * @param integer $minUsers
     *
     * @return ParticipantType
     */
    public function setMinUsers($minUsers)
    {
        $this->min_users = $minUsers;

        return $this;
    }

    /**
     * Get minUsers
     *
     * @return integer
     */
    public function getMinUsers()
    {
        return $this->min_users;
    }

    /**
     * Set maxUsers
     *
     * @param integer $maxUsers
     *
     * @return ParticipantType
     */
    public function setMaxUsers($maxUsers)
    {
        $this->max_users = $maxUsers;

        return $this;
    }

    /**
     * Get maxUsers
     *
     * @return integer
     */
    public function getMaxUsers()
    {
        return $this->max_users;
    }

    /**
     * Set event
     *
     * @param \AppBundle\Entity\Event $event
     *
     * @return ParticipantType
     */
    public function setEvent(\AppBundle\Entity\Event $event = null)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * Get event
     *
     * @return \AppBundle\Entity\Event
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return ParticipantType
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set fee
     *
     * @param float $fee
     *
     * @return ParticipantType
     */
    public function setFee($fee)
    {
        $this->fee = $fee;

        return $this;
    }

    /**
     * Get fee
     *
     * @return float
     */
    public function getFee()
    {
        return $this->fee;
    }
}
