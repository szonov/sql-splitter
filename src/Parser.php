<?php

namespace SZonov\SQL\Splitter;

use SZonov\Text\Parser\ParserInterface;
use SZonov\Text\Source\SourceInterface as Input;
use SZonov\Text\Source\File as FileInput;
use SZonov\Text\Source\Text as TextInput;

abstract class Parser implements ParserInterface {

    /**
     * @var string
     */
    protected $delimiter = ';';

    /**
     * @var Input
     */
    protected $input;

    /**
     * @var string
     */
    protected $query;

    /**
     * @var string
     */
    protected $buffer;

    public function __construct(Input $input)
    {
        $this->input = $input;
    }

    abstract protected function getNextStopStr();

    abstract protected function processStopStr($str);

    public function rewind()
    {
        $this->input->rewind();
    }

    public function getItem()
    {
        $this->query = '';
        $stop = false;
        while (!$stop) {
            $str = $this->getNextStopStr();
            switch ($str)
            {
                case $this->delimiter:

                    $query = trim($this->query);
                    return ($query == '') ? $this->getItem() : $this->normalizeQuery($query);

                case '/*':
                    $this->skipXComment();
                    break;

                case '--':
                    $this->buffer = $this->input->getLine();
                    break;

                case '"':
                case "'":
                    $this->query .= $str;
                    $this->getInQuote($str);
                    break;

                case '':
                    $stop = true;
                    break;

                default:
                    $stop = $this->processStopStr($str);
                    break;
            }
        }
        return false;
    }

    /**
     * @param string $query
     * @return string
     */
    protected function normalizeQuery($query)
    {
        return rtrim($query, ';');
    }

    /**
     *
     */
    protected function skipXComment()
    {
        $pattern = '@(\*/)@';
        if ($this->buffer == '')
            $this->buffer = $this->input->getLine();

        while ($this->buffer !== false)
        {
            if (preg_match($pattern, $this->buffer, $regs, PREG_OFFSET_CAPTURE))
            {
                $pos = $regs[1][1];
                $str = $regs[1][0];
                $this->buffer = substr($this->buffer, $pos+strlen($str));
                return;
            }
            $this->buffer = $this->input->getLine();
        }
    }

    /**
     * @param string $quote
     */
    protected function getInQuote($quote)
    {
        $pattern = '@('.preg_quote($quote, '@').')@';
        if ($this->buffer == '')
            $this->buffer = $this->input->getLine();

        while ($this->buffer !== false)
        {
            if (preg_match($pattern, $this->buffer, $regs, PREG_OFFSET_CAPTURE))
            {
                $pos = $regs[1][1];
                $str = $regs[1][0];
                $this->query .= substr($this->buffer, 0, $pos+strlen($str));
                $continue = false;
                if ($quote == '"' || $quote == "'")
                {
                    $back_slash_amount = 0;
                    $x = $pos;
                    while (substr($this->buffer, $x-1, 1) == '\\')
                    {
                        $x--;
                        $back_slash_amount++;
                    }
                    $continue = ($back_slash_amount % 2) ? 1 : 0;
                }
                $this->buffer = substr($this->buffer, $pos+strlen($str));
                if ($this->buffer == '')
                    $this->buffer = $this->input->getLine();

                if ($continue) continue;
                return;
            }
            $this->query .= $this->buffer;
            $this->buffer = $this->input->getLine();
        }
        return;
    }

    public static function fromFile($file)
    {
        return new static(new FileInput($file));
    }

    public static function fromText($text)
    {
        return new static(new TextInput($text));
    }
}