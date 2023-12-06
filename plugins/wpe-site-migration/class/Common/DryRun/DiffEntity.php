<?php

namespace DeliciousBrains\WPMDB\Common\DryRun;

class DiffEntity implements \JsonSerializable
{

    /**
     * @var string
     */
    private $original_expression;

    /**
     * @var string
     */
    private $replace_expression;

    /**
     * @var int
     */
    private $row;

    /**
     * @var string
     */
    private $column;

    /**
     * @var string
     */
    private $table;

    /**
     * @param string $original_expression
     * @param string $replace_expression
     * @param string $table
     * @param string $column
     * @param int    $row
     *
     * @return DiffEntity
     */
    public static function create($original_expression, $replace_expression, $table, $column, $row)
    {
        return new self($original_expression, $replace_expression, $table, $column, $row);
    }

    /**
     * @param string $original_expression
     * @param string $replace_expression
     * @param string $table
     * @param string $column
     * @param int    $row
     */
    public function __construct($original_expression, $replace_expression, $table, $column, $row)
    {
        $this->original_expression = $original_expression;
        $this->replace_expression  = $replace_expression;
        $this->table               = $table;
        $this->column              = $column;
        $this->row                 = $row;
    }

    /**
     * @return string
     */
    public function getOriginalExpression()
    {
        return $this->original_expression;
    }

    /**
     * @param mixed $original_expression
     */
    public function setOriginalExpression($original_expression)
    {
        $this->original_expression = $original_expression;
    }

    /**
     * @return string
     */
    public function getReplaceExpression()
    {
        return $this->replace_expression;
    }

    /**
     * @param mixed $replace_expression
     */
    public function setReplaceExpression($replace_expression)
    {
        $this->replace_expression = $replace_expression;
    }

    /**
     * @return int
     */
    public function getRow()
    {
        return $this->row;
    }

    /**
     * @return string
     */
    public function getColumn()
    {
        return $this->column;
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Json serializes the class data
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'original' => $this->original_expression,
            'replace'  => $this->replace_expression,
            'row'      => $this->row,
            'column'   => $this->column,
            'table'    => $this->table,
        ];
    }
}
