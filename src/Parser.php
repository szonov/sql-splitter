<?php

namespace SZonov\SQL\Splitter;

use SZonov\Text\Parser\ParserInterface;
use SZonov\Text\Parser\ParserIterator;
use SZonov\Text\Source\SourceInterface as Input;
use SZonov\Text\Source\File as FileInput;
use SZonov\Text\Source\Text as TextInput;

abstract class Parser implements ParserInterface
{
    protected string $delimiter = ';';
    protected string $query = '';
    protected ?string $buffer = '';

    public function __construct(protected Input $input)
    {
    }

    public function queries(): ParserIterator
    {
        return new ParserIterator($this);
    }

    abstract protected function getNextStopStr(): ?string;

    abstract protected function processStopStr(?string $str): bool;

    public function rewind(): void
    {
        $this->input->rewind();
    }

    protected function isEndOfInput(): bool
    {
        return $this->buffer === null;
    }

    protected function isBufferEmpty(): bool
    {
        return $this->buffer === '' || $this->isEndOfInput();
    }

    protected function readLine(): static
    {
        $line = $this->input->getLine();
        $this->buffer = is_string($line) ? $line : null;

        return $this;
    }

    public function getItem()
    {
        $this->query = '';
        $stop = false;
        while (!$stop)
        {
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
                    $this->readLine();
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

    protected function normalizeQuery(string $query): string
    {
        return rtrim($query, ';');
    }

    protected function skipXComment()
    {
        $pattern = '@(\*/)@';

        $this->isBufferEmpty() && $this->readLine();

        while ($this->buffer !== false)
        {
            if (preg_match($pattern, $this->buffer, $regs, PREG_OFFSET_CAPTURE))
            {
                $pos = $regs[1][1];
                $str = $regs[1][0];
                $this->buffer = substr($this->buffer, $pos+strlen($str));
                return;
            }
            $this->readLine();
        }
    }

    protected function getInQuote(string $quote): void
    {
        $pattern = '@('.preg_quote($quote, '@').')@';

        $this->isBufferEmpty() && $this->readLine();

        while (!$this->isEndOfInput())
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

                $this->isBufferEmpty() && $this->readLine();

                if ($continue) continue;
                return;
            }
            $this->query .= $this->buffer;
            $this->readLine();
        }
    }

    public static function fromFile(string $file): static
    {
        return new static(new FileInput($file));
    }

    public static function fromText(string $text): static
    {
        return new static(new TextInput($text));
    }

    /**
     * @param Input $input
     * @param string $driver name of driver (example: $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME))
     *
     * @return static
     */
    protected static function makeUsingDriver(Input $input, string $driver): static
    {
        return preg_match('/^(post|pg)/i', $driver) ? new Postgresql($input) : new Mysql($input);
    }

    public static function fromFileUsingDriver(string $file, string $driver): static
    {
        return self::makeUsingDriver(new FileInput($file), $driver);
    }

    public static function fromTextUsingDriver(string $text, string $driver): static
    {
        return self::makeUsingDriver(new TextInput($text), $driver);
    }
}