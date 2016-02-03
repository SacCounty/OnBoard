<?php
/**
 * @copyright 2009-2016 City of Bloomington, Indiana
 * @license http://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE.txt
 * @author Cliff Ingham <inghamn@bloomington.in.gov>
 */
namespace Application\Models;

use Application\Models\PeopleTable;
use Application\Models\SeatTable;
use Application\Models\TermTable;
use Application\Models\TopicTable;
use Application\Models\VoteTable;

use Blossom\Classes\ActiveRecord;
use Blossom\Classes\Database;

class Committee extends ActiveRecord
{
    public static $types = ['seated', 'open'];

	protected $tablename = 'committees';
	protected $departments = [];
	private $departmentsHaveChanged = false;

	public function __construct($id=null)
	{
		if ($id) {
			if (is_array($id)) {
				$this->exchangeArray($id);
			}
			else {
				$zend_db = Database::getConnection();
				$sql = 'select * from committees where id=?';
				$result = $zend_db->createStatement($sql)->execute([$id]);
				if ($result) {
					$this->exchangeArray($result->current());
				}
				else {
					throw new Exception('committees/unknownCommittee');
				}
			}
		}
		else {
			// This is where the code goes to generate a new, empty instance.
			// Set any default values for properties that need it here
			$this->setType('seated');
			$this->setTermEndWarningDays(TERM_END_WARNING_DEFAULT);
		}
	}

	public function validate()
	{
        if (!$this->getType()) { $this->setType('seated'); }
		if (!$this->getName()) { throw new \Exception('missingName'); }
	}

	public function save()
	{
        parent::save();

        if ($this->departmentsHaveChanged) {
            $zend_db = Database::getConnection();
            $sql = 'delete from committee_departments where committee_id=?';
            $zend_db->query($sql, [$this->getId()]);

            $sql = 'insert committee_departments set committee_id=?,department_id=?';
            $insert = $zend_db->createStatement($sql);
            foreach (array_keys($this->departments) as $id) {
                $params = [$this->getId(), $id];
                try { $insert->execute($params); }
                catch (\Exception $e) { $_SESSION['errorMessages'][] = $e; }
            }
        }
    }

	//----------------------------------------------------------------
	// Generic Getters & Setters
	//----------------------------------------------------------------
	public function getId()                { return parent::get('id');               }
	public function getType()              { return parent::get('type');             }
	public function getName()              { return parent::get('name');             }
	public function getStatutoryName()     { return parent::get('statutoryName');    }
	public function getStatuteReference()  { return parent::get('statuteReference'); }
	public function getStatuteUrl()        { return parent::get('statuteUrl');       }
	public function getWebsite()           { return parent::get('website');          }
	public function getEmail()             { return parent::get('email');            }
	public function getPhone()             { return parent::get('phone');            }
    public function getAddress()           { return parent::get('address');          }
    public function getCity()              { return parent::get('city');             }
    public function getState()             { return parent::get('state');            }
    public function getZip()               { return parent::get('zip');              }
	public function getDescription()       { return parent::get('description');      }
	public function getYearFormed()        { return parent::get('yearFormed');       }
	public function getContactInfo()       { return parent::get('contactInfo');      }
	public function getMeetingSchedule()   { return parent::get('meetingSchedule');  }
	public function getTermEndWarningDays() { return parent::get('termEndWarningDays'); }

	public function setType($s) { parent::set('type', $s === 'seated' ? 'seated': 'open'); }
	public function setName            ($s) { parent::set('name',             $s); }
	public function setStatutoryName   ($s) { parent::set('statutoryName',    $s); }
	public function setStatuteReference($s) { parent::set('statuteReference', $s); }
	public function setStatuteUrl      ($s) { parent::set('statuteUrl',       $s); }
	public function setWebsite         ($s) { parent::set('website',          $s); }
    public function setEmail           ($s) { parent::set('email',            $s); }
    public function setPhone           ($s) { parent::set('phone',            $s); }
    public function setAddress         ($s) { parent::set('address',          $s); }
    public function setCity            ($s) { parent::set('city',             $s); }
    public function setState           ($s) { parent::set('state',            $s); }
    public function setZip             ($s) { parent::set('zip',              $s); }
	public function setDescription     ($s) { parent::set('description',      $s); }
	public function setYearFormed      ($s) { parent::set('yearFormed',  (int)$s); }
	public function setContactInfo     ($s) { parent::set('contactInfo',      $s); }
	public function setMeetingSchedule ($s) { parent::set('meetingSchedule',  $s); }
	public function setTermEndWarningDays($s) { parent::set('termEndWarningDays', (int)$s); }

	/**
	 * @param array $post The POST request
	 */
	public function handleUpdate($post)
	{
        if (!isset($post['departments'])) { $post['departments'] = null; }
        
		$fields = [
            'type', 'departments',
			'name', 'statutoryName', 'statuteReference', 'statuteUrl', 'website', 'yearFormed',
			'email', 'phone', 'address', 'city', 'state', 'zip',
			'description', 'contactInfo', 'meetingSchedule', 'termEndWarningDays'
		];
		foreach ($fields as $f) {
			$set = 'set'.ucfirst($f);
			$this->$set($post[$f]);
		}
	}

	//----------------------------------------------------------------
	// Custom Functions
	//----------------------------------------------------------------
	/**
	 * @return Member
	 */
	public function newMember()
	{
        if ($this->getType() === 'open') {
            $member = new Member();
            $member->setCommittee($this);
            return $member;
        }

        throw new \Exception('committees/invalidMember');
	}
	/**
	 * Returns members for the committee
	 *
	 * If timestamp is provided, will provide members current
	 * as of the provided timestamp
	 *
	 * @param int $timestamp
	 * @return Zend\Db\ResultSet
	 */
	public function getMembers($timestamp=null)
	{
		$search = ['committee_id'=>$this->getId()];
		if ($timestamp) { $search['current'] = (int)$timestamp; }

		$table = new MemberTable();
		return $table->find($search);
	}

	/**
	 * @param string $date
	 * @return Zend\Db\Result
	 */
	public function getOffices($date=null)
	{
        $search = ['committee_id'=>$this->getId()];
        if (!empty($date)) { $search['current'] = $date; }

        $table = new OfficeTable();
        return $table->find($search);
	}

	/**
	 * Returns a ResultSet containing Seats.
	 *
	 * If a timestamp is provided, will return seats current to that timestamp
	 *
	 * @param int $timestamp
	 * @return Zend\Db\ResultSet A ResultSet containing the Seat objects
	 */
	public function getSeats($timestamp=null)
	{
		$search = ['committee_id'=>$this->getId()];
		if ($timestamp) { $search['current'] = (int)$timestamp; }

		$table = new SeatTable();
		return $table->find($search);
	}

	public function getCurrentSeats()
	{
		return $this->getSeats(time());
	}

	/**
	 * @param array $fields Extra fields to search on
	 * @param string $sort Optional sorting
	 * @return Zend\Db\ResultSet
	 */
	public function getTopics(array $fields=null, $sort=null)
	{
		$search = ['committee_id' => $this->getId()];
		if ($fields) {
			$search = array_merge($search, $fields);
		}

		$table = new TopicTable();
		return $table->find($search, false, $sort);
	}

	/**
	 * @return boolean
	 */
	public function hasTopics()
	{
		return count($this->getTopics()) ? true : false;
	}

	/**
	 * Returns terms that were current for the given timestamp.
	 * If no timestamp is given, the current time is used.
	 *
	 * @param timestamp $timestamp The timestamp for when the terms would have been current
	 *
	 * @return Zend\Db\ResultSet
	 */
	public function getCurrentTerms($timestamp=null)
	{
		if (!$timestamp) {
			$timestamp = time();
		}
		$terms = new TermTable();
		return $terms->find(['committee_id'=>$this->getId(), 'current'=>$timestamp]);
	}

	/**
	 * @param int $timestamp
	 * @return boolean
	 */
	public function hasVacancy($timestamp=null)
	{
        $timestamp = $timestamp ? (int)$timestamp : time();

        if ($this->getType() === 'seated') {
            $seats = $this->getSeats($timestamp);
            foreach ($seats as $s) {
                if ($s->hasVacancy($timestamp)) { return true; }
            }
        }
        return false;
	}

	/**
	 * @param int $timestamp
	 * @return int
	 */
	public function getVacancyCount($timestamp=null)
	{
        $timestamp = $timestamp ? (int)$timestamp : time();

        $c = 0;
        if ($this->getType() === 'seated') {
            $seats = $this->getSeats($timestamp);
            foreach ($seats as $s) {
                if ($s->hasVacancy($timestamp)) { $c++; }
            }
        }
        return $c;
	}

	/**
	 * Returns all the terms for this committee
	 *
	 * @return Zend\Db\ResultSet
	 */
	public function getTerms()
	{
		$terms = new TermTable();
		return $terms->find(['committee_id'=>$this->getId()]);
	}

	/**
	 * Returns all the people who have served on this committee
	 *
	 * @return Zend\Db\ResultSet
	 */
	public function getMemberPeople()
	{
		$people = new PeopleTable();
		return $people->find(['committee_id'=>$this->getId()]);
	}

	/**
	 * @return array An array of Person objects
	 */
	public function getLiasonPeople()
	{
        $sql = 'select p.*
                from committee_liasons l
                join people p on l.person_id=p.id
                where committee_id=?';
        $zend_db = Database::getConnection();
        $result = $zend_db->query($sql, [$this->getId()]);

        $liasons = [];
        foreach ($result->toArray() as $row) {
            $liasons[] = new Person($row);
        }
        return $liasons;
	}

	/**
	 * @return Zend\Db\ResultSet
	 */
	public function getVotes()
	{
		$table = new VoteTable();
		return $table->find(['committee_id'=>$this->getId()]);
	}

	/**
	 * Votes are considered invalid when the number of votingRecords for
	 * the vote does not match this committeee's maxCurrentTerms.
	 * That means that either not all the votingRecords have been entered,
	 * or too many votingRecords have been entered.
	 *
	 * @return array An array of Vote objects
	 */
	public function getInvalidVotes()
	{
		$zend_db = Database::getConnection();

		$sql = "select v.id,count(vr.id) as count from votes v
				left join topics t on v.topic_id=t.id
				left join votingRecords vr on v.id=vr.vote_id
				where t.committee_id=?
				group by v.id having count!=?";

		$query = $zend_db->createStatement($sql);
		$result = $query->execute([$this->getId(), $this->getMaxCurrentTerms()]);

		$votes = array();
		foreach ($result as $row) {
			$votes[] = new Vote($row['id']);
		}
		return $votes;
	}

	/**
	 * Inserts a row to the committee_liasons table
	 *
	 * NOTE: This function immediately writes to the database
	 *
	 * @param array $post The POST array
	 */
	public function saveLiason($post)
	{
        if (!empty($post['person_id'])) {
            $sql = "select * from committee_liasons where committee_id=? and person_id=?";
            $zend_db = Database::getConnection();
            $result = $zend_db->query($sql, [$this->getId(), $post['person_id']]);
            if (!count($result)) {
                $sql = 'insert committee_liasons set committee_id=?, person_id=?';
                $zend_db->query($sql, [$this->getId(), $post['person_id']]);
            }
        }
	}

	/**
	 * Removes a row from the committee_liasons table
	 *
	 * NOTE: This function immediately writes to the database
	 *
	 * @param array $post
	 */
	public function removeLiason($person_id)
	{
        $sql = 'delete from committee_liasons where committee_id=? and person_id=?';
        $zend_db = Database::getConnection();
        $zend_db->query($sql)->execute([$this->getId(), $person_id]);
	}

	/**
	 * Returns an array of Department objects with ID as the key
	 *
	 * @return array
	 */
	public function getDepartments()
	{
        if (!$this->departments) {
            $table = new DepartmentTable();
            $list = $table->find(['committee_id'=>$this->getId()]);
            foreach ($list as $d) {
                $this->departments[$d->getId()] = $d;
            }
        }
        return $this->departments;
	}

	/**
	 * @param Department $d
	 * @return boolean
	 */
	public function hasDepartment(Department $d)
	{
        return array_key_exists($d->getId(), $this->getDepartments());
	}

	/**
	 * @param array $ids An array of (int) department_ids
	 */
	public function setDepartments(array $ids=null)
	{
        if ($ids) {
            $current = array_keys($this->getDepartments());

            if (array_diff($current, $ids) || array_diff($ids, $current)) {
                $this->departments = [];
                $this->departmentsHaveChanged = true;

                foreach ($ids as $id) {
                    try { $this->departments[$id] = new Department($id); }
                    catch (\Exception $e) {
                        // Just ignore invalid departments for now
                    }
                }
            }
        }
        else {
            $this->departments = [];
            $this->departmentsHaveChanged = true;
        }
	}
}
