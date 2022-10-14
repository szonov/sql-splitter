<?php

namespace SZonov\SQL\Splitter;

class Postgresql extends Parser
{
    protected function normalizeQuery(string $query): string
    {
        if (preg_match("/^COPY .* FROM STDIN.*/i", $query))
        {
            $query .= ";\n";
            $this->readLine();
            while (!$this->isEndOfInput())
            {
                $query .= $this->buffer;
                if ($this->buffer === "\\.\n") break;
                $this->readLine();
            }
        }
        return $query;
    }

    protected function getNextStopStr(): ?string
    {
        $pattern = '@(\'|"|/\*|--|'
            . preg_quote($this->delimiter, '@') . '|(\$\S*\$))@i';

        $this->isBufferEmpty() && $this->readLine();

        while (!$this->isEndOfInput())
        {
            if (preg_match($pattern, $this->buffer, $regs, PREG_OFFSET_CAPTURE)) {
                $pos = $regs[1][1];
                $str = $regs[1][0];
                $this->query .= substr($this->buffer, 0, $pos);
                $this->buffer = substr($this->buffer, $pos+strlen($str));
                return $str;
            }
            $this->query .= $this->buffer;
            $this->readLine();
        }

        return null;
    }

    protected function processStopStr(?string $str): bool
    {
        $this->query .= $str ?? '';
        $this->getInQuote($str);

        return false;
    }
}