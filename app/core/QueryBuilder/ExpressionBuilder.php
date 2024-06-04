<?php

namespace QueryBuilder;

/**
 * ExpressionBuilder creates SQL expressions for use in QueryBuilder
 * 
 * @author Lukas Velek
 */
class ExpressionBuilder {
    private array $queryData;

    /**
     * Class constructor
     */
    public function __construct() {
        $this->queryData = [];
    }

    /**
     * Appends AND
     * 
     * @return self
     */
    public function and() {
        $this->queryData[] = 'AND';

        return $this;
    }

    /**
     * Appends OR
     * 
     * @return self
     */
    public function or() {
        $this->queryData[] = 'OR';

        return $this;
    }

    /**
     * Appends left bracket
     * 
     * @return self
     */
    public function lb() {
        $this->queryData[] = '(';

        return $this;
    }

    /**
     * Appends right bracket
     * 
     * @return self
     */
    public function rb() {
        $this->queryData[] = ')';

        return $this;
    }

    /**
     * Appends where condition
     * 
     * @param string $cond Condition
     * @param array $values Condition parameter values
     * @return self
     */
    public function where(string $cond, array $values = []) {
        if(str_contains($cond, '?') && !empty($values)) {
            $count = count(explode('?', $cond));

            if($count != (count($values) + 1)) {
                die();
            }

            $search = [];

            for($i = 0; $i < ($count - 1); $i++) {
                $search[] = '?';
            }

            $tmp = [];
            foreach($values as $value) {
                $tmp[] = "'" . $value . "'";
            }

            $values = $tmp;

            $cond = str_replace($search, $values, $cond);
        }

        $this->queryData[] = $cond;

        return $this;
    }

    /**
     * Appends AND and where condition
     * 
     * @param string $cond Condition
     * @param array $values Condition parameter values
     * @return self
     */
    public function andWhere(string $cond, array $values = []) {
        $this->and();
        $this->where($cond, $values);

        return $this;
    }

    /**
     * Appends OR and where condition
     * 
     * @param string $cond Condition
     * @param array $values Condition parameter values
     * @return self
     */
    public function orWhere(string $cond, array $values = []) {
        $this->or();
        $this->where($cond, $values);

        return $this;
    }

    /**
     * Builds the expression
     * 
     * @return string Expression SQL code
     */
    public function build() {
        $code = '';

        foreach($this->queryData as $qd) {
            $code .= ' ' . $qd . ' ';
        }

        return $code;
    }
}

?>