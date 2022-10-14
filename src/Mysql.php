<?php

namespace SZonov\SQL\Splitter;

class Mysql extends Parser
{
    protected function getNextStopStr(): ?string
    {
        $pattern = '@(\'|"|/\*|--|'
            . preg_quote($this->delimiter, '@') . '|DELIMITER (\S+))@i';

        $this->isBufferEmpty() && $this->readLine();

        while (!$this->isEndOfInput())
        {
            if (preg_match($pattern, $this->buffer, $regs, PREG_OFFSET_CAPTURE))
            {
                $pos = $regs[1][1];
                $str = $regs[1][0];
                if (isset($regs[2]) && is_array($regs[2]))
                {
                    $this->delimiter = $regs[2][0];
                }
                else
                {
                    $this->query .= substr($this->buffer, 0, $pos);
                }
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
        return false;
    }
}