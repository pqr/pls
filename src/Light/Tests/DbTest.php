<?php
namespace Light\Tests;

use Light\Db;
use PHPUnit\Framework\TestCase;

class DbTest extends TestCase
{
    public function testParse()
    {
        $query = " @n \n";
        $query .= " @n_WithComment \n";
        $query .= " @s \n";
        $query .= " @s_WithComment \n";
        $query .= " @i \n";
        $query .= " @i_WithComment \n";
        $query .= " @f \n";
        $query .= " @f_WithComment \n";
        $query .= " @a \n";
        $query .= " @a_WithComment \n";
        $query .= " @u \n";
        $query .= " @u_WithComment \n";
        $query .= " @p \n";
        $query .= " @p_WithComment \n";
        $query .= " @l \n";
        $query .= " @l_WithComment \n";
        $query .= " @l1 \n";
        $query .= " @l1_WithComment \n";
        $query .= " @l2 \n";
        $query .= " @l2_WithComment \n";

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

        $expected = " `table1`.`field1` \n";
        $expected .= " `table2`.`field2` \n";
        $expected .= " 'some\\\"string' \n";
        $expected .= " 'another\\\\string' \n";
        $expected .= " 123 \n";
        $expected .= " 123 \n";
        $expected .= " 0.55 \n";
        $expected .= " 0.99 \n";
        $expected .= " 'a',1,'str\\\"in\\\\array' \n";
        $expected .= " 'b',2,'another\\\"@str\\'in\\\\array' \n";
        $expected .= " `table1`.`field1`='value1',`db2`.`table2`.`field2`='value2' \n";
        $expected .= " `field3`='val\\\"ue\\\\3' \n";
        $expected .= " part as is \n";
        $expected .= " part as\"is\\again \n";
        $expected .= " '%like full%' \n";
        $expected .= " '%like full with comment%' \n";
        $expected .= " '%like left' \n";
        $expected .= " '%like left with comment' \n";
        $expected .= " 'like right%' \n";
        $expected .= " 'like right with comment%' \n";

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
