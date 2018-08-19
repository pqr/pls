<?php
namespace Light\Tests;

use Light\Config;
use Light\Db;
use PHPUnit\Framework\TestCase;

class DbTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        //Инициализируем singleton объект базы
        Db::connect();
        Config::$sqlErrorThrowException = true;
    }

    public function testParse()
    {
        $query = " @n ";
        $query .= " @n_WithComment ";
        $query .= " @s ";
        $query .= " @s_WithComment ";
        $query .= " @i ";
        $query .= " @i_WithComment ";
        $query .= " @f ";
        $query .= " @f_WithComment ";
        $query .= " @a ";
        $query .= " @a_WithComment ";
        $query .= " @u ";
        $query .= " @u_WithComment ";
        $query .= " @p ";
        $query .= " @p_WithComment ";
        $query .= " @l ";
        $query .= " @l_WithComment ";
        $query .= " @l1 ";
        $query .= " @l1_WithComment ";
        $query .= " @l2 ";
        $query .= " @l2_WithComment ";

        $actual = Db::parse(
            $query,
            'table1.field1',
            'table2.field2',
            'some"string',
            "another\\string",
            "123int",
            "123.99int",
            "0.55float",
            "0.99float",
            ['a', 1, 'str"in\\array'],
            ['b', 2, 'another"@str\'in\\array'],
            ['table1.field1' => 'value1', 'db2.table2.field2' => 'value2'],
            ['field3' => 'val"ue\\3'],
            'part as is',
            'part as"is\\again',
            'like full',
            'like full with comment',
            'like left',
            'like left with comment',
            'like right',
            'like right with comment'
        );

        $expected = " `table1`.`field1` ";
        $expected .= " `table2`.`field2` ";
        $expected .= " 'some\\\"string' ";
        $expected .= " 'another\\\\string' ";
        $expected .= " 123 ";
        $expected .= " 123 ";
        $expected .= " 0.55 ";
        $expected .= " 0.99 ";
        $expected .= " 'a','1','str\\\"in\\\\array' ";
        $expected .= " 'b','2','another\\\"@str\\'in\\\\array' ";
        $expected .= " `table1`.`field1`='value1',`db2`.`table2`.`field2`='value2' ";
        $expected .= " `field3`='val\\\"ue\\\\3'  part as is  part as\"is\\again ";
        $expected .= " '%like full%' ";
        $expected .= " '%like full with comment%' ";
        $expected .= " '%like left' ";
        $expected .= " '%like left with comment' ";
        $expected .= " 'like right%' ";
        $expected .= " 'like right with comment%' ";

        self::assertEquals($expected, $actual);

    }

    public function testParseErr1()
    {
        //Передаём больше переменных, чем плейсхолдеров
        $this ->expectException(\Exception::class);
        Db::parse("@i", 1, 2);
    }

    public function testParseErr2()
    {
        $this ->expectException(\Exception::class);
        //Передаём меньше переменных, чем плейсхолдеров
        Db::parse("@i @s", 1);
    }
}
 