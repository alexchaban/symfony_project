<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Entity\Repository\EventRepository")
 * @ORM\Table(name="events")
 */
class Event
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
	
    /**
     * @var string
     *
     * @ORM\Column(name="hash", type="string", length=50, nullable=false)
     */
    private $hash;
	
    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=50, nullable=false)
     */
    private $title;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\EventType")
     * @ORM\JoinColumn(name="type", referencedColumnName="id")
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="dayInformation", type="text", nullable=true)
     */
    private $dayInformation;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\File")
     * @ORM\JoinColumn(name="logo_url", referencedColumnName="id")
     */
    private $logo;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_seats", type="integer", length=9, nullable=true)
     */
    private $totalSeats;

    /**
     * @var integer
     *
     * @ORM\Column(name="register_togheter", type="integer", length=9, nullable=true)
     */
    private $registerTogheter;

    /**
     * @var integer
     *
     * @ORM\Column(name="waiting_seats", type="integer", length=9, nullable=true)
     */
    private $waitingSeats;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="waiting_list", type="boolean", options={"default" = "0"})
     */
    private $waitingList = false;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="move_waiting_list", type="boolean", options={"default" = "0"})
     */
    private $moveWaitingList = false;

    /**
     * @var date
     *
     * @ORM\Column(name="start_date", type="datetime", nullable=true)
     */
    private $startDateTime;

    /**
     * @var date
     *
     * @ORM\Column(name="end_date", type="datetime", nullable=true)
     */
    private $endDateTime;

    /**
     * @var date
     *
     * @ORM\Column(name="start_registration_date", type="datetime", nullable=true)
     */
    private $startRegDateTime;

    /**
     * @var date
     *
     * @ORM\Column(name="end_registration_date", type="datetime", nullable=true)
     */
    private $endRegDateTime;

    /**
     * @var date
     *
     * @ORM\Column(name="cancel_date", type="date", nullable=true)
     */
    private $cancelDate;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="can_cancel", type="boolean", options={"default" = "0"})
     */
    private $canCancel = false;

    /**
     * @var date
     *
     * @ORM\Column(name="invitation_date", type="date", nullable=true)
     */
    private $invitationDate;

    /**
     * @var string
     *
     * @ORM\Column(name="invitation_contact", type="string", length=255, nullable=true)
     */
    private $invitationContact;

    /**
     * @var string
     *
     * @ORM\Column(name="timezone", type="string", length=10, nullable=true)
     */
    private $timezone;
    
	/**
     * @var boolean
     * 
     * @ORM\Column(name="has_fee", type="boolean", options={"default" = "0"})
     */
    private $hasFee= false;
    
	/**
     * @var boolean
     * 
     * @ORM\Column(name="fees", type="array", nullable=true)
     */
    private $fees= false;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", length=1, nullable=false)
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id")
     */
    private $organizedBy;

    /**
     * @var integer
     *
     * @ORM\Column(name="private", type="integer", length=1, nullable=true)
     */
    private $private;
	
    public function __construct()
    {
        $this->pictures = new ArrayCollection;
        $this->status = 0;
        $this->private = 0;
        $this->hash = uniqid();
    }



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
     * Set userId
     *
     * @param \AppBundle\Entity\User $userId
     *
     * @return Event
     */
    public function setUserId(\AppBundle\Entity\User $userId = null)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return \AppBundle\Entity\User
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Event
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
	
    /**
     * Set hash
     *
     * @param string $hash
     *
     * @return Event
     */
    public function setHash($hash)
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * Get hash
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Event
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set totalSeats
     *
     * @param integer $totalSeats
     *
     * @return Event
     */
    public function setTotalSeats($totalSeats)
    {
        $this->totalSeats = $totalSeats;

        return $this;
    }

    /**
     * Get totalSeats
     *
     * @return integer
     */
    public function getTotalSeats()
    {
        return $this->totalSeats;
    }

    /**
     * Set registerTogheter
     *
     * @param integer $registerTogheter
     *
     * @return Event
     */
    public function setRegisterTogheter($registerTogheter)
    {
        $this->registerTogheter = $registerTogheter;

        return $this;
    }

    /**
     * Get registerTogheter
     *
     * @return integer
     */
    public function getRegisterTogheter()
    {
        return $this->registerTogheter;
    }

    /**
     * Set waitingSeats
     *
     * @param integer $waitingSeats
     *
     * @return Event
     */
    public function setWaitingSeats($waitingSeats)
    {
        $this->waitingSeats = $waitingSeats;

        return $this;
    }

    /**
     * Get waitingSeats
     *
     * @return integer
     */
    public function getWaitingSeats()
    {
        return $this->waitingSeats;
    }

    /**
     * Set waitingList
     *
     * @param boolean $waitingList
     *
     * @return Event
     */
    public function setWaitingList($waitingList)
    {
        $this->waitingList = $waitingList;

        return $this;
    }

    /**
     * Get waitingList
     *
     * @return boolean
     */
    public function getWaitingList()
    {
        return $this->waitingList;
    }

    /**
     * Set moveWaitingList
     *
     * @param boolean $moveWaitingList
     *
     * @return Event
     */
    public function setMoveWaitingList($moveWaitingList)
    {
        $this->moveWaitingList = $moveWaitingList;

        return $this;
    }

    /**
     * Get moveWaitingList
     *
     * @return boolean
     */
    public function getMoveWaitingList()
    {
        return $this->moveWaitingList;
    }

    /**
     * Set startDateTime
     *
     * @param \DateTime $startDateTime
     *
     * @return Event
     */
    public function setStartDateTime($startDateTime)
    {
        $this->startDateTime = $startDateTime;

        return $this;
    }

    /**
     * Get startDateTime
     *
     * @return \DateTime
     */
    public function getStartDateTime()
    {
        return $this->startDateTime;
    }

    /**
     * Set endDateTime
     *
     * @param \DateTime $endDateTime
     *
     * @return Event
     */
    public function setEndDateTime($endDateTime)
    {
        $this->endDateTime = $endDateTime;

        return $this;
    }

    /**
     * Get endDateTime
     *
     * @return \DateTime
     */
    public function getEndDateTime()
    {
        return $this->endDateTime;
    }

    /**
     * Set startRegDateTime
     *
     * @param \DateTime $startRegDateTime
     *
     * @return Event
     */
    public function setStartRegDateTime($startRegDateTime)
    {
        $this->startRegDateTime = $startRegDateTime;

        return $this;
    }

    /**
     * Get startRegDateTime
     *
     * @return \DateTime
     */
    public function getStartRegDateTime()
    {
        return $this->startRegDateTime;
    }

    /**
     * Set endRegDateTime
     *
     * @param \DateTime $endRegDateTime
     *
     * @return Event
     */
    public function setEndRegDateTime($endRegDateTime)
    {
        $this->endRegDateTime = $endRegDateTime;

        return $this;
    }

    /**
     * Get endRegDateTime
     *
     * @return \DateTime
     */
    public function getEndRegDateTime()
    {
        return $this->endRegDateTime;
    }

    /**
     * Set cancelDate
     *
     * @param \DateTime $cancelDate
     *
     * @return Event
     */
    public function setCancelDate($cancelDate)
    {
        $this->cancelDate = $cancelDate;

        return $this;
    }

    /**
     * Get cancelDate
     *
     * @return \DateTime
     */
    public function getCancelDate()
    {
        return $this->cancelDate;
    }

    /**
     * Set canCancel
     *
     * @param boolean $canCancel
     *
     * @return Event
     */
    public function setCanCancel($canCancel)
    {
        $this->canCancel = $canCancel;

        return $this;
    }

    /**
     * Get canCancel
     *
     * @return boolean
     */
    public function getCanCancel()
    {
        return $this->canCancel;
    }

    /**
     * Set invitationDate
     *
     * @param \DateTime $invitationDate
     *
     * @return Event
     */
    public function setInvitationDate($invitationDate)
    {
        $this->invitationDate = $invitationDate;

        return $this;
    }

    /**
     * Get invitationDate
     *
     * @return \DateTime
     */
    public function getInvitationDate()
    {
        return $this->invitationDate;
    }

    /**
     * Set invitationContact
     *
     * @param string $invitationContact
     *
     * @return Event
     */
    public function setInvitationContact($invitationContact)
    {
        $this->invitationContact = $invitationContact;

        return $this;
    }

    /**
     * Get invitationContact
     *
     * @return string
     */
    public function getInvitationContact()
    {
        return $this->invitationContact;
    }

    /**
     * Set timezone
     *
     * @param string $timezone
     *
     * @return Event
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * Get timezone
     *
     * @return string
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * Set user
     *
     * @param \AppBundle\Entity\User $user
     *
     * @return Event
     */
    public function setUser(\AppBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \AppBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set type
     *
     * @param \AppBundle\Entity\EventType $type
     *
     * @return Event
     */
    public function setType(\AppBundle\Entity\EventType $type = null)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return \AppBundle\Entity\EventType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set logo
     *
     * @param \AppBundle\Entity\File $logo
     *
     * @return Event
     */
    public function setLogo(\AppBundle\Entity\File $logo = null)
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * Get logo
     *
     * @return \AppBundle\Entity\File
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * Add picture
     *
     * @param \AppBundle\Entity\File $picture
     *
     * @return Event
     */
    public function addPicture(\AppBundle\Entity\File $picture)
    {
        $this->pictures[] = $picture;

        return $this;
    }

    /**
     * Remove picture
     *
     * @param \AppBundle\Entity\File $picture
     */
    public function removePicture(\AppBundle\Entity\File $picture)
    {
        $this->pictures->removeElement($picture);
    }

    /**
     * Get pictures
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPictures()
    {
        return $this->pictures;
    }

    /**
     * Set organizedBy
     *
     * @param \AppBundle\Entity\Organization $organizedBy
     *
     * @return Event
     */
    public function setOrganizedBy(\AppBundle\Entity\Organization $organizedBy = null)
    {
        $this->organizedBy = $organizedBy;

        return $this;
    }

    /**
     * Get organizedBy
     *
     * @return \AppBundle\Entity\Organization
     */
    public function getOrganizedBy()
    {
        return $this->organizedBy;
    }

    /**
     * Set hasFee
     *
     * @param boolean $hasFee
     *
     * @return Event
     */
    public function setHasFee($hasFee)
    {
        $this->hasFee = $hasFee;

        return $this;
    }

    /**
     * Get hasFee
     *
     * @return boolean
     */
    public function getHasFee()
    {
        return $this->hasFee;
    }

    /**
     * Set fees
     *
     * @param array $fees
     *
     * @return Event
     */
    public function setFees($fees)
    {
        $this->fees = $fees;

        return $this;
    }

    /**
     * Get fees
     *
     * @return array
     */
    public function getFees()
    {
        return $this->fees;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return Event
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set dayInformation
     *
     * @param string $dayInformation
     *
     * @return Event
     */
    public function setDayInformation($dayInformation)
    {
        $this->dayInformation = $dayInformation;

        return $this;
    }

    /**
     * Get dayInformation
     *
     * @return string
     */
    public function getDayInformation()
    {
        return $this->dayInformation;
    }

    /**
     * Set private
     *
     * @param integer $private
     *
     * @return Event
     */
    public function setPrivate($private)
    {
        $this->private = $private;

        return $this;
    }

    /**
     * Get private
     *
     * @return integer
     */
    public function getPrivate()
    {
        return $this->private;
    }
}
