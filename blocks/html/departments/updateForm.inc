<?php
/**
 * @copyright 2016-2017 City of Bloomington, Indiana
 * @license http://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE.txt
 * @param Department $this->department
 */
$fields = ['name'];
foreach ($fields as $f) {
    $get = 'get'.ucfirst($f);
    $$f = self::escape($this->department->$get());
}
$name = self::escape($this->department->getName());
$title = $this->department->getId() ? $this->_('department_edit') : $this->_('department_add');
?>
<section>
    <header>
        <h1><?= $title; ?></h1>
    </header>
	<form method="post" action="<?= BASE_URI; ?>/departments/update">
		<fieldset><legend><?= $name; ?></legend>
			<input name="department_id" type="hidden" value="<?= $this->department->getId(); ?>" />
			<?php
                $field = $this->template->getHelper('field');
                echo $field->field(['name'=>'name', 'id'=>'name', 'label'=>$this->_('name'), 'value'=>$name, 'required'=>true]);

				$helper = $this->template->getHelper('saveAndCancelButtons');
				echo $helper->saveAndCancelButtons(BASE_URI.'/departments');
			?>
		</fieldset>
	</form>
</section>
