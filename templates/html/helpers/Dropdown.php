<?php
/**
 * @copyright 2016 City of Bloomington, Indiana
 * @license http://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE.txt
 */
namespace Application\Templates\Helpers;

use Blossom\Classes\Template;

class Dropdown
{
	public function __construct(Template $template)
	{
		$this->template = $template;
	}

	public function dropdown(array $links, $title, $class=null)
	{
        $html = "
        <nav         class=\"fn1-dropdown $class\">
            <button  class=\"fn1-dropdown-launcher\" aria-haspopup=\"true\" aria-expanded=\"false\">$title</button>
            <div     class=\"fn1-dropdown-links\">
                {$this->renderLinks($links)}
            </div>
        </nav>
        ";
        return $html;
	}

	private function renderLinks(array $links)
	{
        $html = '';
        foreach ($links as $l) {
            $html.= empty($l['subgroup'])
                ? "<a href=\"$l[url]\" class=\"fn1-dropdown-link\">$l[label]</a>"
                : "<div class=\"fn1-dropdown-subgroup\">{$this->renderLinks($l['subgroup'])}</div>";
        }
        return $html;
	}
}