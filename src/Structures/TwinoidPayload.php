<?php


namespace App\Structures;

class TwinoidPayload
{

    private $_data;

    /**
     * TwinoidPayload constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->_data = $data;
    }


    public function getTwinoidName(): string {
        return $this->_data['name'];
    }

    public function getTwinoidId(): int {
        return $this->_data['twinId'];
    }

    public function getScopeId(): int {
        return $this->_data['id'];
    }

    public function getPastTowns() {

        return new class($this->_data['cadavers']) implements \Iterator {
            private $_towns;
            private $_pos = 0;

            public function __construct(array $towns)
            {
                $this->_towns = $towns;
            }

            public function current()
            {
                return new class($this->_towns[$this->_pos]) {

                    private $_town;

                    public function __construct(array $town)
                    {
                        $this->_town = $town;
                    }

                    public function getName():    string { return $this->_town['mapName'] ?? $this->_town['name']; }
                    public function getMessage(): string { return $this->_town['msg'] ?? ''; }
                    public function getComment(): string { return $this->_town['comment'] ?? $this->_town['m']; }

                    public function getSeason():       int { return $this->_town['season']; }
                    public function getScore():        int { return $this->_town['score']; }
                    public function getDay():          int { return $this->_town['d']; }
                    public function getSurvivedDays(): int { return $this->_town['survival'] ?? $this->_town['d']; }
                    public function getID():           int { return $this->_town['mapId'] ?? $this->_town['id']; }

                    public function getDeath():  int { return $this->_town['dtype'] ?? 0; }

                    public function isOld(): bool { return $this->_town['v1']; }

                };
            }

            public function next()
            {
                $this->_pos++;
            }

            public function key(): int
            {
                return $this->_pos;
            }

            public function valid(): bool
            {
                return $this->_pos < count($this->_towns);
            }

            public function rewind()
            {
                $this->_pos = 0;
            }
        };
    }
}