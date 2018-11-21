<?php


namespace Light\Autolaunch\BackupHelpres;


class P7zip
{
    /** @var string */
    private $fromStdinInnerFilename;

    /** @var string */
    private $outputFilepath;

    /** @var string */
    private $inputFilepath;

    /** @var string */
    private $password;

    /** @var boolean */
    private $noCompression = false;

    /** @var int */
    private $volumeSizeMb;

    public static function create(string $outputFilepath)
    {
        $instance = new self();
        $instance->outputFilepath = $outputFilepath;
        return $instance;
    }

    public function inputFilepath(string $inputFilepath)
    {
        $this->inputFilepath = $inputFilepath;
        return $this;
    }

    public function fromStdin(string $innerFilename)
    {
        $this->fromStdinInnerFilename = $innerFilename;
        return $this;
    }

    public function password(string $password)
    {
        $this->password = $password;
        return $this;
    }

    public function noCompression()
    {
        $this->noCompression = true;
        return $this;
    }

    public function volumeSizeMb(int $mb)
    {
        $this->volumeSizeMb = $mb;
        return $this;
    }

    public function getCmd(): string
    {
        // a - добавить файл к архиву
        // -y - не спрашивать вопросы
        $cmd = "7z a -y";

        if ($this->fromStdinInnerFilename) {
            $cmd .= " -si" . $this->fromStdinInnerFilename;
        }
        if ($this->password) {
            $cmd .= ' -p' . escapeshellarg($this->password);
        }

        if ($this->noCompression) {
            $cmd .= " -m0=Copy";
        }

        // С разбиением на блоки по 10m
        if ($this->volumeSizeMb) {
            $cmd .= " -v" . $this->volumeSizeMb . "m";
        }

        $cmd .= " \"" . $this->outputFilepath . "\"";

        if ($this->inputFilepath) {
            $cmd .= " \"" . $this->inputFilepath . "\"";
        }

        return $cmd;
    }
}