<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="locations")
 */
class Location
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $purpose;
	
	/**
     * @ORM\Column(type="string", length=100)
     */
    private $address;
	
	/**
     * @ORM\Column(type="string", length=100)
     */
    private $address_2;
	
	/**
     * @ORM\Column(type="string", length=100)
     */
    private $city;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $zip;
	
    /**
     * @ORM\Column(type="string", length=100)
     */
    private $state;

    /**
     * @ORM\Column(type="integer")
     */
    private $showInEvent;
	
    /**
     * @ORM\Column(type="integer")
     */
    private $preferred;
	
    /**
     * @ORM\ManyToOne(targetEntity="Event")
     * @ORM\JoinColumn(name="event_id", referencedColumnName="id")
     */
    private $eventId;
	
	/**
     * @ORM\Column(type="string", length=250)
     */
    private $info;

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
     * Set purpose
     *
     * @param string $purpose
     *
     * @return Location
     */
    public function setPurpose($purpose)
    {
        $this->purpose = $purpose;

        return $this;
    }

    /**
     * Get purpose
     *
     * @return string
     */
    public function getPurpose()
    {
        return $this->purpose;
    }

    /**
     * Set adress
     *
     * @param string $address
     *
     * @return Location
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get adress
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set city
     *
     * @param string $city
     *
     * @return Location
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set zip
     *
     * @param string $zip
     *
     * @return Location
     */
    public function setZip($zip)
    {
        $this->zip = $zip;

        return $this;
    }

    /**
     * Get zip
     *
     * @return string
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * Set showInEvent
     *
     * @param integer $showInEvent
     *
     * @return Location
     */
    public function setShowInEvent($showInEvent)
    {
        $this->showInEvent = $showInEvent;

        return $this;
    }

    /**
     * Get showInEvent
     *
     * @return integer
     */
    public function getShowInEvent()
    {
        return $this->showInEvent;
    }

    /**
     * Set preferred
     *
     * @param integer $preferred
     *
     * @return Location
     */
    public function setPreferred($preferred)
    {
        $this->preferred = $preferred;

        return $this;
    }

    /**
     * Get preferred
     *
     * @return integer
     */
    public function getPreferred()
    {
        return $this->preferred;
    }

    /**
     * Set eventId
     *
     * @param \AppBundle\Entity\Event $eventId
     *
     * @return Location
     */
    public function setEventId(\AppBundle\Entity\Event $eventId = null)
    {
        $this->eventId = $eventId;

        return $this;
    }

    /**
     * Get eventId
     *
     * @return \AppBundle\Entity\Event
     */
    public function getEventId()
    {
        return $this->eventId;
    }

    /**
     * Set info
     *
     * @param string $info
     *
     * @return Location
     */
    public function setInfo($info)
    {
        $this->info = $info;

        return $this;
    }

    /**
     * Get info
     *
     * @return string
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * Set address2
     *
     * @param string $address2
     *
     * @return Location
     */
    public function setAddress2($address2)
    {
        $this->address_2 = $address2;

        return $this;
    }

    /**
     * Get address2
     *
     * @return string
     */
    public function getAddress2()
    {
        return $this->address_2;
    }

    /**
     * Set state
     *
     * @param string $state
     *
     * @return Location
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }
}
