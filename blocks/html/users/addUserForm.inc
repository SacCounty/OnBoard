<?php
/**
 * @copyright 2008 City of Bloomington, Indiana
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 * @author Cliff Ingham <inghamn@bloomington.in.gov>
 * @param string $this->return_url
 * @param User $this->user
 */
?>
<h1>New User</h1>
<form method="post" action="<?php echo $_SERVER['SCRIPT_NAME']; ?>">

<?php
	if (userHasRole('Administrator'))
	{
		include APPLICATION_HOME.'/blocks/html/users/authenticationFields.inc';
	}
	include APPLICATION_HOME.'/blocks/html/users/personalInfoFields.inc';
	include APPLICATION_HOME.'/blocks/html/users/phoneNumberFields.inc';
?>

<fieldset><legend>Submit</legend>
	<input name="return_url" type="hidden" value="<?php echo View::escape($this->return_url); ?>" />
	<button type="submit" class="submit">Submit</button>
	<button type="button" class="cancel" onclick="document.location.href='<?php echo View::escape($this->return_url); ?>';">Cancel</button>
</fieldset>

</form>