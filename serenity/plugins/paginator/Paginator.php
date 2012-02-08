<?php
namespace Serenity;

/**
 * Pagination plugin
 * @author Pete
 */
class PaginatorPlugin extends SerenityPlugin
{
    private $model = null;
    private $orderBy = '';
    private $orderDir = 'DESC';
    private $pageNumber = 0;
    private $resultsPerPage = 20;
    private $maxPages = 0;

    public function onAppLoad($params)
    {

    }

    public function onActionEnd($page)
    {
    }

    public function parseAppConfig($config)
    {

    }

    public function parsePageConfig($page, $config)
    {

    }

    public function getTemplateVariables()
    {
        return array();
    }

    public function onActionStart($page)
    {

    }

    /**
     * Call before requesting the query to initilize the paginator.
     * 
     * @param SerenityModel $model The model to be returned
     * @param string $orderBy The field to order the results
     * @param int $orderDir 0 == ASC, 1 == DESC
     * @param int $pageNumber current page number
     * @param int $resultsPerPage number of results per page
     * @param int $totalResults number of total rows
     */
    public function init($model, $orderBy, $orderDir, $pageNumber, $resultsPerPage, $totalResults)
    {
        $this->model = $model;
        $this->orderBy = $orderBy;
        $this->orderDir = $orderDir;
        $this->pageNumber = $pageNumber;
        $this->resultsPerPage = $resultsPerPage;

        $model = 'Serenity\\' . $this->model . 'Model';
        $this->maxPages = ceil($totalResults / $resultsPerPage);
        if($this->maxPages < 1)
            $this->maxPages = 1;

        if($this->pageNumber > ($this->maxPages - 1))
            $this->pageNumber = ($this->maxPages - 1);
    }

    /**
     * Get the SerenityQuery for the current page. Call Paginator->init() first
     * @return SerenityQuery
     */
    public function getQuery()
    {
        $model = 'Serenity\\' . $this->model . 'Model';

        if($this->orderDir == 0)
            $orderDir = 'ASC';
        else
            $orderDir = 'DESC';

        $orderByB = explode(',', $this->orderBy);
        $orderBy = '';
        foreach($orderByB as $orderPart)
        {
            if($orderBy != '')
                $orderBy .= ', ';

            $orderBy .= $orderPart . ' ' . $orderDir;
        }

        return $model::query()->orderBy($orderBy)->
        limit($this->pageNumber * $this->resultsPerPage . ', ' . $this->resultsPerPage);
    }

    /**
     * Returns the html of the table header for the column
     * @param string $name name of the field
     * @param string $displayName optional name to be displayed
     * @param mixed $extraParams additional parameters to be added to the url
     * @return string
     */
    public function getTableHeader($name, $displayName = null, $extraParams = array())
    {
        if($name == $this->orderBy)
        {
            $orderDir = ($this->orderDir == 1 ? 0 : 1);
            $pageNumber = $this->pageNumber;
        }
        else
        {
            $orderDir = 0;
            $pageNumber = 0;
        }

        $baseParams = array('orderField' => $name, 'orderDir' => $orderDir, 'pageNumber' => $pageNumber);
        $params = array_merge($extraParams, $baseParams);

        $url = getPageUrl(sp::app()->getCurrentPage()->getName(), sp::app()->getCurrentPage()->getCurrentAction(), $params);

        if($displayName == null)
            $displayName = $name;

        $html = '<a href="' . $url . '">' . htmlentities(ucfirst($displayName));

        if($this->orderBy == $name)
        {
            if($this->orderDir == 0)
                $dir = 'asc';
            else
                $dir = 'desc';

            $html .= '<span class="paginator_' . $dir . '">';
        }

        $html .= '</a>';

        return $html;
    }

    /**
     * Returns the HTML for the page selector.
     * @param unknown_type $extraParams
     * @return string
     */
    function getPageSelector($extraParams = array())
    {
        $html = 'Page <b class="paginator_pageNumber">' . ($this->pageNumber + 1) . '</b> of <b class="paginator_pageNumber">' . $this->maxPages . '</b> &nbsp;';

        $sidePageCt = 2;
        $startPage = $this->pageNumber - $sidePageCt;
        if($startPage < 0)
            $startPage = 0;

        $endPage = $this->pageNumber + $sidePageCt + ($sidePageCt - ($this->pageNumber - $startPage));
        if($endPage > ($this->maxPages - 1))
            $endPage = ($this->maxPages - 1);

        if(($sidePageCt - ($endPage - $this->pageNumber)) > 0)
        {
            $startPage -= ($sidePageCt - ($endPage - $this->pageNumber));
            if($startPage < 0)
                $startPage = 0;
        }

        // First page
        if($startPage > 0)
        {
            $params = array_merge($extraParams, array('orderField' => $this->orderBy, 'orderDir' => $this->orderDir, 'pageNumber' => 0));
            $url = getPageUrl(sp::app()->getCurrentPage()->getName(), sp::app()->getCurrentPage()->getCurrentAction(), $params);

            $html .= '<a href="' . $url . '" class="pagination_arrowBox">&lt;&lt;</a>';
        }

        // Back page
        if($this->pageNumber > 0)
        {
            $params = array_merge($extraParams, array('orderField' => $this->orderBy, 'orderDir' => $this->orderDir, 'pageNumber' => ($this->pageNumber - 1)));
            $url = getPageUrl(sp::app()->getCurrentPage()->getName(), sp::app()->getCurrentPage()->getCurrentAction(), $params);

            $html .= '<a href="' . $url . '" class="pagination_arrowBox">&lt;</a>';
        }

        // Add pre-page numbers
        for($x = $startPage; $x < $this->pageNumber; $x++)
        {
            $params = array_merge($extraParams, array('orderField' => $this->orderBy, 'orderDir' => $this->orderDir, 'pageNumber' => $x ));
            $url = getPageUrl(sp::app()->getCurrentPage()->getName(), sp::app()->getCurrentPage()->getCurrentAction(), $params);

            $html .= '<a href="' . $url . '" class="pagination_pageBox">' . ($x + 1) . '</a>';
        }

        // Curent page number
        $html .= '<a class="pagination_pageBox">' . ($this->pageNumber + 1) . '</a>';

        // Post page numers
        for($x = ($this->pageNumber + 1); $x <= $endPage; $x++)
        {
            $params = array_merge($extraParams, array('orderField' => $this->orderBy, 'orderDir' => $this->orderDir, 'pageNumber' => $x ));
            $url = getPageUrl(sp::app()->getCurrentPage()->getName(), sp::app()->getCurrentPage()->getCurrentAction(), $params);

            $html .= '<a href="' . $url . '" class="pagination_pageBox">' . ($x + 1) . '</a>';
        }

        // Next Page
        if($this->pageNumber < ($this->maxPages - 1))
        {
            $params = array_merge($extraParams, array('orderField' => $this->orderBy, 'orderDir' => $this->orderDir, 'pageNumber' => ($this->pageNumber +1)));
            $url = getPageUrl(sp::app()->getCurrentPage()->getName(), sp::app()->getCurrentPage()->getCurrentAction(), $params);

            $html .= '<a href="' . $url . '" class="pagination_arrowBox">&gt;</a>';
        }

        if($endPage < ($this->maxPages - 1))
        {
            $params = array_merge($extraParams, array('orderField' => $this->orderBy, 'orderDir' => $this->orderDir, 'pageNumber' => ($this->maxPages - 1)));
            $url = getPageUrl(sp::app()->getCurrentPage()->getName(), sp::app()->getCurrentPage()->getCurrentAction(), $params);

            $html .= '<a href="' . $url . '" class="pagination_arrowBox">&gt;&gt;</a>';
        }


        return $html;
    }
}