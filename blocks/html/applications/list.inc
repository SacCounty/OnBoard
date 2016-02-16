<?php
/**
 * @copyright 2016 City of Bloomington, Indiana
 * @license http://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE.txt
 * @param Zend\Db\Result $this->applications
 * @param string $this->title (optional)
 * @param Committee $this->committee (optional)
 * @param Applicant $this->applicant (optional)
 * @param boolean $this->selectable (optional)
 */
$title = $this->title ? self::escape($this->title) : $this->_(['application', 'applications', count($this->applications)]);
?>
<section>
    <h1><?= $title; ?></h1>
    <?php
        $this->_include('html/applications/partials/table.inc');
    ?>
</section>