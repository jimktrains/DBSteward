<?php
/**
 * DBSteward unit test for mysql5 table ddl generation
 *
 * @package DBSteward
 * @license http://www.opensource.org/licenses/bsd-license.php Simplified BSD License
 * @author Austin Hyde <austin109@gmail.com>
 */

require_once __DIR__ . '/../dbstewardUnitTestBase.php';

/**
 * @group mysql5
 * @group nodb
 */
class Mysql5TableSQLTest extends PHPUnit_Framework_TestCase {

  public function setUp() {
    dbsteward::set_sql_format('mysql5');
    dbsteward::$quote_schema_names = TRUE;
    dbsteward::$quote_table_names = TRUE;
    dbsteward::$quote_column_names = TRUE;
    dbsteward::$quote_function_names = TRUE;
    dbsteward::$quote_object_names = TRUE;
    mysql5::$use_auto_increment_table_options = FALSE;
    mysql5::$use_schema_name_prefix = FALSE;
  }

  public function testSimple() {
    $xml = <<<XML
<schema name="public" owner="NOBODY">
  <table name="test" primaryKey="id" owner="NOBODY" description="test desc'ription">
    <column name="id" type="int"/>
    <column name="foo" type="int"/>
  </table>
</schema>
XML;
    $schema = new SimpleXMLElement($xml);

    $expected = <<<SQL
CREATE TABLE `test` (
  `id` int,
  `foo` int
)
COMMENT 'test desc\'ription';
SQL;
    
    $this->assertEquals($expected, mysql5_table::get_creation_sql($schema, $schema->table));

    $this->assertEquals("DROP TABLE `test`;", mysql5_table::get_drop_sql($schema, $schema->table));
  }

  public function testSerials() {
    $xml = <<<XML
<schema name="public" owner="NOBODY">
  <table name="test" primaryKey="id" owner="NOBODY" description="test desc'ription">
    <column name="id" type="serial"/>
    <column name="other" type="bigserial"/>
  </table>
</schema>
XML;
    $schema = new SimpleXMLElement($xml);

    $expected = <<<SQL
CREATE TABLE `test` (
  `id` int NOT NULL,
  `other` bigint NOT NULL
)
COMMENT 'test desc\'ription';
SQL;

    $this->assertEquals($expected, mysql5_table::get_creation_sql($schema, $schema->table));
  }

  public function testInheritance() {
    $xml = <<<XML
<schema name="public" owner="NOBODY">
  <table name="test" owner="NOBODY" inherits="other">
  </table>
</schema>
XML;
    $schema = new SimpleXMLElement($xml);
    
    $this->assertEquals("-- Skipping table 'test' because MySQL does not support table inheritance", mysql5_table::get_creation_sql($schema, $schema->table));
  }

  public function testAutoIncrement() {
    $xml = <<<XML
<schema name="public" owner="NOBODY">
  <table name="test" primaryKey="id" owner="NOBODY">
    <column name="id" type="int auto_increment"/>
  </table>
</schema>
XML;
    $schema = new SimpleXMLElement($xml);

    $this->assertEquals("CREATE TABLE `test` (\n  `id` int\n);", mysql5_table::get_creation_sql($schema, $schema->table));

    // ensure that foreign auto_increment flags aren't transferred to the table definition
    $xml = <<<XML
<dbsteward>
<database/>
<schema name="public" owner="NOBODY">
  <table name="test" primaryKey="id" owner="NOBODY">
    <column name="id" type="int auto_increment"/>
    <column name="fk" foreignSchema="public" foreignTable="other" foreignColumn="id"/>
  </table>
  <table name="other" primaryKey="id" onwer="NOBODY">
    <column name="id" type="int auto_increment"/>
  </table>
</schema>
</dbsteward>
XML;
    $dbs = new SimpleXMLElement($xml);
    dbsteward::$new_database = $dbs;
    $this->assertEquals("CREATE TABLE `test` (\n  `id` int,\n  `fk` int\n);", mysql5_table::get_creation_sql($dbs->schema, $dbs->schema->table[0]));
  }
}
?>
