<?php

class PagingQuery
{
    public $showInPage;
    public $pagesCount;
    public $page;
    public $totalRows;

    public $orderBy;
    public $orderDirection;

    /**
     * result rows
     * @var array
     */
    public $rows = array();

    public function pageCountCalculate()
    {
        if ($this->showInPage > 0)
            $this->pagesCount = ceil($this->totalRows / $this->showInPage);
        else
            $this->pagesCount = 1;
    }

    public function getPageOffset()
    {
        return $this->page * $this->showInPage;
    }

    public function getLimitString()
    {
        $offset = $this->getPageOffset();
        return " LIMIT {$offset},{$this->showInPage}";
    }

    public function appendLimit($query)
    {
        if ($this->showInPage > 0)
            $query = $query . "  " . $this->getLimitString();
        return $query;
    }

    public function retrievePageAndShowInPageFromRequest()
    {
        $this->showInPage = (isset($_REQUEST['showInPage'])) ? intval($_REQUEST['showInPage']) : 10;
        $this->page = (isset($_REQUEST['page'])) ? intval($_REQUEST['page']) : 0;
    }
}