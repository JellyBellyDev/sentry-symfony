<?php

namespace Sentry\SentryBundle;

/**
 * Evaluate an error types expression.
 */
class ErrorTypesParser
{
    private $expression = null;

    /**
     * Initialize ErrorParser
     *
     * @param string $expression Error Types e.g. E_ALL & ~E_DEPRECATED & ~E_NOTICE
     */
    public function __construct($expression)
    {
        $this->expression = $expression;
    }

    /**
     * Parse and compute the error types expression
     *
     * @return int the parsed expression
     */
    public function parse()
    {
        // convert constants to ints
        $this->expression = $this->convertErrorConstants($this->expression);
        $this->expression = str_replace(
            array(",", " "),
            array(".", ""),
            $this->expression
        );

        return $this->compute($this->expression);
    }

    /**
     * Converts error constants from string to int.
     *
     * @param  string $expression e.g. E_ALL & ~E_DEPRECATED & ~E_NOTICE
     * @return string   converted expression e.g. 32767 & ~8192 & ~8
     */
    private function convertErrorConstants($expression)
    {
        $output = preg_replace_callback("/(E_[a-zA-Z_]+)/", function ($errorConstant) {
            if (defined($errorConstant[1])) {
                return constant($errorConstant[1]);
            }

            return $errorConstant[0];
        }, $expression);

        return $output;
    }

    /**
     * Let PHP compute the prepared expression for us.
     *
     * @param  string $expression prepared expression e.g. 32767&~8192&~8
     * @return int  computed expression e.g. 24567
     */
    private function compute($expression)
    {
        // catch anything which could be a security issue
        if (0 !== preg_match("/[^\d.+*%^|&~<>\/()-]/", $this->expression)) {
            throw new \InvalidArgumentException('Wrong value in error types config value');
        }

        return 0 + (int)eval('return ' . $expression . ';');
    }
}
