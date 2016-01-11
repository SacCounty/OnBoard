<?php
/**
 * @copyright 2014 City of Bloomington, Indiana
 * @license http://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE.txt
 * @author Cliff Ingham <inghamn@bloomington.in.gov>
 */
namespace Application\Controllers;

use Application\Models\Committee;
use Application\Models\CommitteeTable;
use Application\Models\Seat;
use Application\Models\VoteTable;
use Blossom\Classes\Controller;
use Blossom\Classes\Block;
use Blossom\Classes\Url;

class CommitteesController extends Controller
{
    private function loadCommittee($id)
    {
        try {
            return new Committee($id);
        }
        catch (\Exception $e) {
            $_SESSION['errorMessages'][] = $e;
            header('Location: '.BASE_URL.'/committees');
            exit();
        }
    }

	public function index()
	{
		$table = new CommitteeTable();
		$committees = $table->find();
		if ($this->template->outputFormat == 'html') {
            $this->template->blocks[] = new Block('committees/breadcrumbs.inc');
        }
		$this->template->blocks[] = new Block('committees/list.inc', ['committees'=>$committees]);
	}

	public function info()
	{
        $committee = $this->loadCommittee($_GET['committee_id']);
        $this->template->blocks[] = new Block('committees/info.inc', ['committee'=>$committee]);
	}

	public function members()
	{
        $committee = $this->loadCommittee($_GET['committee_id']);
        $this->template->blocks[] = new Block('committees/breadcrumbs.inc',    ['committee' => $committee]);
        $this->template->blocks[] = new Block('committees/currentMembers.inc', ['committee' => $committee]);
	}

	public function update()
	{
        $committee = !empty($_REQUEST['committee_id'])
            ? $this->loadCommittee($_REQUEST['committee_id'])
            : new Committee();

		if (isset($_POST['name'])) {
			try {
				$committee->handleUpdate($_POST);
				$committee->save();

				$url = BASE_URL."/committees/members?committee_id=$committee_id";
				header("Location: $url");
				exit();
			}
			catch (\Exception $e) { $_SESSION['errorMessages'][] = $e; }
		}

		$this->template->blocks[] = new Block('committees/updateForm.inc', ['committee'=>$committee]);
	}

	public function seats()
	{
        $committee = $this->loadCommittee($_GET['committee_id']);
        $this->template->blocks[] = new Block('committees/breadcrumbs.inc', ['committee'=>$committee]);
        $this->template->blocks[] = new block('seats/list.inc', [
            'seats'     => $committee->getSeats(),
            'committee' => $committee
        ]);
	}
}
