<?php
namespace Stub;

class DbAuth implements Auth
{
    /**
     * @var Db
     */
    private $db;

    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    /**
     * @return Db
     */
    public function getDb()
    {
        return $this->db;
    }
}
