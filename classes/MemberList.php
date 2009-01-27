<?php
/**
 * A collection class for Member objects
 *
 * This class creates a select statement, only selecting the ID from each row
 * PDOResultIterator handles iterating and paginating those results.
 * As the results are iterated over, PDOResultIterator will pass each desired
 * ID back to this class's loadResult() which will be responsible for hydrating
 * each Member object
 *
 * Beyond the basic $fields handled, you will need to write your own handling
 * of whatever extra $fields you need
 *
 * The PDOResultIterator uses prepared queries; it is recommended to use bound
 * parameters for each of the options you handle
 *
 * @copyright 2006-2008 City of Bloomington, Indiana
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 * @author Cliff Ingham <inghamn@bloomington.in.gov>
 */
class MemberList extends PDOResultIterator
{
	/**
	 * Creates a basic select statement for the collection.
	 * Populates the collection if you pass in $fields
	 *
	 * @param array $fields
	 */
	public function __construct($fields=null)
	{
		$this->select = 'select distinct m.id as id from members m ';
		if (is_array($fields)) {
			$this->find($fields);
		}
	}

	/**
	 * Populates the collection from the database based on the $fields you handle
	 *
	 * @param array $fields
	 * @param string $sort
	 * @param int $limit
	 * @param string $groupBy
	 */
	public function find($fields=null,$sort='term_start desc',$limit=null,$groupBy=null)
	{
		$this->sort = $sort;
		$this->limit = $limit;
		$this->groupBy = $groupBy;
		$this->joins = '';

		$options = array();
		$parameters = array();
		if (isset($fields['id'])) {
			$options[] = 'm.id=:id';
			$parameters[':id'] = $fields['id'];
		}
		if (isset($fields['seat_id'])) {
			$options[] = 'm.seat_id=:seat_id';
			$parameters[':seat_id'] = $fields['seat_id'];
		}
		if (isset($fields['user_id'])) {
			$options[] = 'm.user_id=:user_id';
			$parameters[':user_id'] = $fields['user_id'];
		}
		if (isset($fields['term_start'])) {
			$options[] = 'm.term_start=:term_start';
			$parameters[':term_start'] = $fields['term_start'];
		}
		if (isset($fields['term_end'])) {
			$options[] = 'm.term_end=:term_end';
			$parameters[':term_end'] = $fields['term_end'];
		}

		if (isset($fields['current'])) {
			$date = date('Y-m-d H:i:s',$fields['current']);

			$options[] = 'm.term_start<:currentStart';
			$options[] = '(m.term_end is null or m.term_end>:currentEnd)';

			$parameters[':currentStart'] = $date;
			$parameters[':currentEnd'] = $date;
		}


		// Finding on fields from other tables required joining those tables.
		// You can add fields from other tables to $options by adding the join SQL
		// to $this->joins here
		if (isset($fields['committee_id'])) {
			$this->joins.=" left join seats s on m.seat_id=s.id ";
			$options[] = 's.committee_id=:committee_id';
			$parameters[':committee_id'] = $fields['committee_id'];
		}

		/*
		*  list of members voted with the given member
		*/
		if (isset($fields['member_id'])) {
			$this->sort = "u.lastname,u.firstname";
			$this->joins.= " left join users u on m.user_id=u.id ";
			$this->joins.= " inner join votingRecords v1 on m.id=v1.member_id ";
			$this->joins.= " inner join votingRecords v2 on v1.vote_id=v2.vote_id ";

			$options[] = 'v2.member_id=:member_idA';
			$options[] = 'v1.member_id!=:member_idB';
			$parameters[':member_idA'] = $fields['member_id'];
			$parameters[':member_idB'] = $fields['member_id'];
		}

		/**
		 * List of members for any given set of Topics
		 */
		if (isset($fields['topicList'])) {
			$this->joins.= " left join votingRecords r on m.id=r.member_id";
			$this->joins.= " left join votes v on r.vote_id=v.id";
			$options[] = "v.topic_id in ({$fields['topicList']->getSQL()})";
			$parameters = array_merge($parameters,$fields['topicList']->getParameters());
		}


		$this->populateList($options,$parameters);
	}

	/**
	 * Loads a single Member object for the key returned from PDOResultIterator
	 * @param int $key
	 */
	protected function loadResult($key)
	{
		return new Member($this->list[$key]);
	}
}