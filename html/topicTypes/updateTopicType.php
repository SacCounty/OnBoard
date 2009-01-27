<?php
/**
 * @copyright 2006-2009 City of Bloomington, Indiana
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 * @param REQUEST topicType_id
 */
verifyUser('Administrator');

$topicType = new TopicType($_REQUEST['topicType_id']);
if (isset($_POST['topicType'])) {
	foreach ($_POST['topicType'] as $field=>$value) {
		$set = 'set'.ucfirst($field);
		$topicType->$set($value);
	}

	try {
		$topicType->save();
		header('Location: '.BASE_URL.'/topicTypes');
		exit();
	}
	catch (Exception $e) {
		$_SESSION['errorMessages'][] = $e;
	}
}

$template = new Template();
$template->title = 'Update Topic Type';
$template->blocks[] = new Block('topicTypes/updateTopicTypeForm.inc',
								array('topicType'=>$topicType));
echo $template->render();