<?php

require_once APPLICATION_PATH . '/common/models/Filter/BaseMapper.php';

// TODO: закончить все виды getRenderedParts

class StrMapper extends BaseMapper
{
    const TAB = '...';
    private $compiledParts = array();

    protected function getRenderedParts() // array(part => method,)
    {
        return array(
            BaseQuery::COLUMNS       => '_renderColumns',
            /*
            BaseQuery::FROM,
            BaseQuery::INNER_JOIN,
            BaseQuery::LEFT_JOIN,
            BaseQuery::WHERE,
            BaseQuery::GROUP,
            BaseQuery::HAVING,
            BaseQuery::ORDER,
            BaseQuery::LIMIT,
            */
        );
    }

    public function _renderColumns($items)
    {
        $res = "Columns:\n";
        $columns = array();
        foreach ($items as $item)
        {
            $col = self::TAB . $item['tableName'] . ':';
            if (is_array($item['cols'])) {
                $col .= implode(',', $item['cols']);
            } else {
                $col .= $item['cols'];
            }
            $columns[] = $col;
        }
        if ($columns) {
            $this->compiledParts[BaseQuery::COLUMNS] = "Columns:\n" . implode("\n", $columns);
        }
    }

    protected function compileParts()
    {
        return implode("\n", $this->compiledParts);
    }
}
