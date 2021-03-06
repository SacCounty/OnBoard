<?php
/**
 * @copyright 2014-2017 City of Bloomington, Indiana
 * @license http://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE.txt
 */
namespace Application\Controllers;

use Application\Models\Committee;
use Application\Models\CommitteeHistory;
use Application\Models\CommitteeTable;
use Application\Models\GoogleGateway;
use Application\Models\Person;
use Application\Models\Seat;
use Application\Models\SeatTable;
use Application\Models\VoteTable;

use Blossom\Classes\Controller;
use Blossom\Classes\Block;
use Blossom\Classes\Url;

class CommitteesController extends Controller
{
    public function index()
    {
        if ($this->template->outputFormat === 'html') {
            $this->template->blocks[] = new Block('committees/breadcrumbs.inc');
        }

        $search =  ['current' => true];
        if (isset($_GET['current']) && !$_GET['current']) {
            $search['current'] = false;
        }
        $data = Committee::data($search);

        if ($this->template->outputFormat === 'html') {
            $block = $search['current'] ? 'current' : 'past';
        }
        else {
            $block = 'list';
        }
        $this->template->title = $this->template->_("committees_$block").' - '.APPLICATION_NAME;
        $this->template->blocks[] = new Block("committees/$block.inc", ['data'=>$data]);
    }

    public function report()
    {
        $table = new CommitteeTable();
        $list = $table->find(['current'=>true]);

        $block = new Block('committees/report.inc');
        foreach ($list as $committee) {
            $this->template->blocks[] = new Block('committees/report.inc', ['committee'=>$committee]);
        }

    }

    public function info()
    {
        if (!empty($_GET['committee_id'])) {
            try { $committee = new Committee($_GET['committee_id']); }
            catch (\Exception $e) { $_SESSION['errorMessages'][] = $e; }
        }
        if (isset($committee)) {
            if ($this->template->outputFormat === 'html') {
                $this->template->title = $committee->getName();
                $this->template->blocks[] = new Block('committees/breadcrumbs.inc', ['committee' => $committee]);
                $this->template->blocks[] = new Block('committees/header.inc',      ['committee' => $committee]);
                $this->template->blocks[] = new Block('committees/info.inc',        ['committee' => $committee]);
                $this->template->blocks[] = new Block('committeeStatutes/list.inc', [
                    'statutes'  => $committee->getStatutes(),
                    'committee' => $committee
                ]);
                $this->template->blocks[] = new Block('departments/list.inc', [
                    'departments'    => $committee->getDepartments(),
                    'disableButtons' => true
                ]);
                $this->template->blocks[] = new Block('committees/liaisons.inc',    ['committee' => $committee]);
            }
            else {
                $this->template->blocks[] = new Block('committees/info.inc',        ['committee' => $committee]);
            }
        }
        else {
            header('HTTP/1.1 404 Not Found', true, 404);
            $this->template->blocks[] = new Block('404.inc');
        }
    }

    public function members()
    {
        if (!empty($_GET['committee_id'])) {
            try { $committee = new Committee($_GET['committee_id']); }
            catch (\Exception $e) { $_SESSION['errorMessages'][] = $e; }
        }

        if (isset($committee)) {
            if ($this->template->outputFormat === 'html') {
                $this->template->title = $committee->getName();
                $this->template->blocks[] = new Block('committees/breadcrumbs.inc', ['committee' => $committee]);
                $this->template->blocks[] = new Block('committees/header.inc',      ['committee' => $committee]);
            }

            if ($committee->getType() === 'seated') {
                $data = SeatTable::currentData(['committee_id'=>$committee->getId()]);
                $this->template->blocks[] = new Block('seats/data.inc', [
                    'data'      => $data,
                    'committee' => $committee,
                    'title'     => $this->template->_(['current_member', 'current_members', count($data['results'])])
                ]);
            }
            else {
                $search =  ['current' => true];
                if (isset($_GET['current']) && !$_GET['current']) {
                    $search['current'] = false;
                }

                $members = $committee->getMembers($search);
                $block = new Block('members/list.inc', [
                    'committee' => $committee,
                    'members'   => $members,
                ]);

                $block->title = ($search['current'])
                    ? $this->template->_(['current_member', 'current_members', count($members)])
                    : $this->template->_(['past_member',    'past_members',    count($members)]);
                $this->template->blocks[] = $block;
            }
        }
        else {
            header('HTTP/1.1 404 Not Found', true, 404);
            $this->template->blocks[] = new Block('404.inc');
        }
    }

    public function update()
    {
        if (!empty($_REQUEST['committee_id'])) {
            try { $committee = new Committee($_REQUEST['committee_id']); }
            catch (\Exception $e) {
                $_SESSION['errorMessages'][] = $e;
                header('HTTP/1.1 404 Not Found', true, 404);
                $this->template->blocks[] = new Block('404.inc');
                return;
            }
        }
        else { $committee = new Committee(); }

        if (isset($_POST['name'])) {
            try {
                CommitteeTable::update($committee, $_POST);
                $url = BASE_URL."/committees/info?committee_id={$committee->getId()}";
                header("Location: $url");
                exit();
            }
            catch (\Exception $e) { $_SESSION['errorMessages'][] = $e; }
        }

        $this->template->blocks[] = new Block('committees/breadcrumbs.inc', ['committee' => $committee]);
        $this->template->blocks[] = new Block('committees/header.inc',      ['committee' => $committee]);
        $this->template->blocks[] = new Block('committees/updateForm.inc',  ['committee' => $committee]);
    }

    public function end()
    {
        if (!empty($_REQUEST['committee_id'])) {
            try { $committee = new Committee($_REQUEST['committee_id']); }
            catch (\Exception $e) {
                $_SESSION['errorMessages'][] = $e;
                header('HTTP/1.1 404 Not Found', true, 404);
                $this->template->blocks[] = new Block('404.inc');
                return;
            }
        }
        else { $committee = new Committee(); }

        if (isset($_POST['endDate'])) {
            try {
                CommitteeTable::end($committee, $_POST);

                $url = BASE_URL."/committees/info?committee_id={$committee->getId()}";
                header("Location: $url");
                exit();
            }
            catch (\Exception $e) { $_SESSION['errorMessages'][] = $e; }
        }
        $this->template->blocks[] = new Block('committees/breadcrumbs.inc', ['committee' => $committee]);
        $this->template->blocks[] = new Block('committees/header.inc',      ['committee' => $committee]);
        $this->template->blocks[] = new Block('committees/endDateForm.inc', ['committee' => $committee]);
    }

    public function seats()
    {
        if (!empty($_GET['committee_id'])) {
            try { $committee = new Committee($_GET['committee_id']); }
            catch (\Exception $e) { $_SESSION['errorMessages'][] = $e; }
        }

        if (isset($committee)) {
            if ($this->template->outputFormat === 'html') {
                $this->template->title = $committee->getName();
                $this->template->blocks[] = new Block('committees/breadcrumbs.inc', ['committee' => $committee]);
                $this->template->blocks[] = new Block('committees/header.inc',      ['committee' => $committee]);
            }

            $block = new block('seats/list.inc', [
                'seats'     => $committee->getSeats($_GET),
                'committee' => $committee
            ]);
            if (isset($_GET['current'])) {
                $block->title = $_GET['current'] ? $this->template->_('seats_current') : $this->template->_('seats_past');
            }
            $this->template->blocks[] = $block;
        }
        else {
            header('HTTP/1.1 404 Not Found', true, 404);
            $this->template->blocks[] = new Block('404.inc');
        }
    }

    public function applications()
    {
        if (!empty($_GET['committee_id'])) {
            try { $committee = new Committee($_GET['committee_id']); }
            catch (\Exception $e) { $_SESSION['errorMessages'][] = $e; }
        }

        if (isset($committee)) {
            $this->template->title = $committee->getName();
            $this->template->blocks[] = new Block('committees/breadcrumbs.inc',  ['committee' => $committee]);
            $this->template->blocks[] = new Block('committees/header.inc',       ['committee' => $committee]);
            if (Person::isAllowed('applications', 'report')) {
                $this->template->blocks[] = new Block('applications/reportForm.inc', ['committee' => $committee]);
            }
            else {
                $this->template->blocks[] = new Block('applications/list.inc', [
                    'committee'    => $committee,
                    'applications' => $committee->getApplications(),
                    'type'         => 'current'
                ]);
            }

            $this->template->blocks[] = new Block('applications/list.inc', [
                'committee'    => $committee,
                'applications' => $committee->getApplications(['archived'=>time()]),
                'title'        => $this->template->_('applications_archived'),
                'type'         => 'archived'
            ]);
        }
        else {
            header('HTTP/1.1 404 Not Found', true, 404);
            $this->template->blocks[] = new Block('404.inc');
        }
    }

    public function meetings()
    {
        if (!empty($_GET['committee_id'])) {
            try { $committee = new Committee($_GET['committee_id']); }
            catch (\Exception $e) { $_SESSION['errorMessages'][] = $e; }
        }

        if (!empty($_GET['start'])) {
            try {
                $start = new \DateTime($_GET['start']);
                if (!empty($_GET['end'])) { $end = new \DateTime($_GET['end']); }

                if (!isset($end)) {
                    $end = clone $start;
                    $end->add(new \DateInterval('P1Y'));
                }
                $year = (int)$start->format('Y');
            }
            catch (\Exception $e) {
                $_SESSION['errorMessages'][] = new \Exception('invalidDate');
            }
        }
        else {
            $year = !empty($_GET['year'])
                  ?  (int) $_GET['year']
                  :  (int) date('Y');

            $start = new \DateTime("$year-01-01");
            $end   = new \DateTime("$year-01-01");
            $end->add(new \DateInterval('P1Y'));
        }

        if (!isset($year) || !isset($start) || !isset($end)) {
            header('HTTP/1.1 400 Bad Request', true, 400);
            return false;
        }

        if (isset($committee)) {

            $meetings = $committee->getMeetings($start, $end);

            $this->template->title = $committee->getName();
            if ($this->template->outputFormat == 'html') {
                $this->template->blocks[] = new Block('committees/breadcrumbs.inc',  ['committee' => $committee]);
                $this->template->blocks[] = new Block('committees/header.inc',       ['committee' => $committee]);
            }
            $this->template->blocks[] = new Block('committees/meetings.inc', [
                'committee' => $committee,
                'meetings'  => $meetings,
                'year'      => $year
            ]);
        }
        else {
            header('HTTP/1.1 404 Not Found', true, 404);
            $this->template->blocks[] = new Block('404.inc');
        }
    }

    public function history()
    {
        if (!empty($_GET['committee_id'])) {
            try { $committee = new Committee($_GET['committee_id']); }
            catch (\Exception $e) { $_SESSION['errorMessages'][] = $e; }
        }

        if (isset($committee)) {
            $this->template->title = $committee->getName();
            $this->template->blocks[] = new Block('committees/breadcrumbs.inc',  ['committee' => $committee]);
            $this->template->blocks[] = new Block('committees/header.inc',       ['committee' => $committee]);
            $this->template->blocks[] = new Block('committees/history.inc', ['history' => $committee->getHistory()]);
        }
        else {
            header('HTTP/1.1 404 Not Found', true, 404);
            $this->template->blocks[] = new Block('404.inc');
        }
    }
}
