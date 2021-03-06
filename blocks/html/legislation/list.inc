<?php
/**
 * @copyright 2017 City of Bloomington, Indiana
 * @license http://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE.txt
 * @param array       $this->list      An array of Legislation bjects
 * @param Committee   $this->committee
 * @param Legislation $this->parent    The parent legislation for the list
 */
use Application\Models\Person;
use Application\Models\Legislation\ActionTypesTable;
use Application\Models\Legislation\TypesTable;

use Blossom\Classes\Url;
?>
<section>
    <header>
        <div class="tools">
        <?php
            $userCanEdit = Person::isAllowed('legislation', 'update');
            $h = $this->template->getHelper('buttonLink');
            if ($userCanEdit
                && (!$this->parent || !$this->parent->getType()->isSubtype())) {

                if ($this->committee) {
                    $params = [
                        'committee_id' => $this->committee->getId(),
                        'return_url'   => urlencode(Url::current_url(BASE_HOST))
                    ];

                    if ($this->parent) {
                        $params['parent_id'] = $this->parent->getId();

                        $table = new TypesTable();
                        $list = $table->find(['subtype'=>true]);
                        foreach ($list as $t) {
                            $params['type_id'   ] = $t->getId();

                            echo $h->buttonLink(
                                BASE_URI.'/legislation/update?'.http_build_query($params),
                                sprintf($this->_('add_something', 'messages'), $t->getName()),
                                'add'
                            );
                        }
                    }
                    else {
                        echo $h->buttonLink(
                            BASE_URI.'/legislation/update?'.http_build_query($params),
                            $this->_('legislation_add'),
                            'add'
                        );
                    }
                }
            }
        ?>
        </div>
    </header>
    <div>
    <?php
        foreach ($this->list as $l) {
            $type   = parent::escape($l->getType  ()->getName());
            $number = parent::escape($l->getNumber());
            $title  = parent::escape($l->getTitle ());
            $status = $l->getStatus_id()
                    ? '('.parent::escape($l->getStatus()->getName()).')'
                    : '';

            $uri = BASE_URI.'/legislation/view?id='.$l->getId();

            $downloadButton = '';
            $files = $l->getFiles();
            if (count($files)) {
                foreach ($files as $f) {
                    $u    = BASE_URI.'/legislationFiles/download?id='.$f->getId();
                    $name = parent::escape($f->getFilename());
                    $downloadButton.= $h->buttonLink($u, $this->_('download'), 'download');
                }
            }
            echo "
            <article>
                <header>
                    <h2><a href=\"$uri\">$type $number $status $downloadButton</a></h2>
                    <h3>$title</h3>
                </header>
            </article>
            ";
        }
    ?>
    </div>
</section>
