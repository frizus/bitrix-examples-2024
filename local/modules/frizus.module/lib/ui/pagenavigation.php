<?
namespace Frizus\Module\UI;

use Bitrix\Main\Application;

class PageNavigation extends \Bitrix\Main\UI\PageNavigation
{
    public function initFromUri()
    {
        $navParams = ['page' => 1];

        if(($value = Application::getInstance()->getContext()->getRequest()->getQuery($this->id)) !== null)
        {
            if ((filter_var($value, FILTER_VALIDATE_INT) !== false) &&
                (intval($value) > 0)
            ) {
                $navParams['page'] = intval($value);
            }
        }

        $currentPage = $navParams['page'];
        if ($this->recordCount !== null) {
            $maxPage = $this->getPageCount();

            if ($currentPage > $maxPage) {
                $currentPage = $maxPage;
            }
        }

        $this->setCurrentPage($currentPage);
    }

    public function addParams(\Bitrix\Main\Web\Uri $uri, $sef, $page, $size = null)
    {
        $uri->addParams([$this->id => $page]);
        return $uri;
    }

    public function clearParams(\Bitrix\Main\Web\Uri $uri, $sef)
    {
        $uri->deleteParams([$this->id]);
        return $uri;
    }
}