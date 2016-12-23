<?php

namespace SZonov\SQL\Splitter;

class Postgresql extends Parser {

    protected function normalizeQuery($query)
    {
        if (preg_match("/^COPY .* FROM STDIN.*/i", $query))
        {
            $query .= ";\n";
            while (($l=$this->input->getLine()) !== false)
            {
                $query .= $l;
                if ($l == "\\.\n") break;
            }
        }
        return $query;
    }

    protected function getNextStopStr()
    {
        $pattern = '@(\'|"|/\*|--|'
            . preg_quote($this->delimiter, '@') . '|(\$\S*\$))@i';

        if ($this->buffer == '')
            $this->buffer = $this->input->getLine();

        while ($this->buffer !== false)
        {
            if (preg_match($pattern, $this->buffer, $regs, PREG_OFFSET_CAPTURE)) {
                $pos = $regs[1][1];
                $str = $regs[1][0];
                $this->query .= substr($this->buffer, 0, $pos);
                $this->buffer = substr($this->buffer, $pos+strlen($str));
                return $str;
            }
            $this->query .= $this->buffer;
            $this->buffer = $this->input->getLine();
        }
        return false;
    }

    protected function processStopStr($str)
    {
        $this->query .= $str;
        $this->getInQuote($str);
        return false;
    }
}