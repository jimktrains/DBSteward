<?php
/**
 * DBSteward serial start confirmat test
 *
 * 1) Confirm serial starts are applied when creating new tables
 * 2) Confirm when adding new tables with serial columns that serial starts are applied in stage 2
 *
 *
 * @package DBSteward
 * @license http://www.opensource.org/licenses/bsd-license.php Simplified BSD License
 * @author Nicholas J Kiraly <kiraly.nicholas@gmail.com>
 */

require_once __DIR__ . '/../lib/DBSteward/dbsteward.php';
require_once __DIR__ . '/mock_output_file_segmenter.php';

class serialStartTest extends PHPUnit_Framework_TestCase {

  private $pgsql8_xml_a = <<<XML
<dbsteward>
  <database>
    <role>
      <application>dbsteward_phpunit_app</application>
      <owner>deployment</owner>
      <replication/>
      <readonly/>
    </role>
    <slony clusterName="duplicate_slony_ids_testsuite">
      <slonyNode id="1" comment="DSI - Local Primary"  dbName="test" dbHost="db-dev1" dbUser="unittest_slony" dbPassword="drowssap1"/>
      <slonyNode id="2" comment="DSI - Local Backup"   dbName="test" dbHost="db-dev1" dbUser="unittest_slony" dbPassword="drowssap1"/>
      <slonyNode id="3" comment="DSI - Local Backup"   dbName="test" dbHost="db-dev1" dbUser="unittest_slony" dbPassword="drowssap1"/>
      <slonyReplicaSet id="100" originNodeId="1" upgradeSetId="101" comment="common duplicate testing database definition">
        <slonyReplicaSetNode id="2" providerNodeId="1"/>
        <slonyReplicaSetNode id="3" providerNodeId="2"/>
      </slonyReplicaSet>
    </slony>
    <configurationParameter name="TIME ZONE" value="America/New_York"/>
  </database>
  <schema name="dbsteward" owner="ROLE_OWNER">
    <function name="db_config_parameter" returns="text" owner="ROLE_OWNER" cachePolicy="VOLATILE" description="used to push configurationParameter values permanently into the database configuration">
      <functionParameter name="config_parameter" type="text"/>
      <functionParameter name="config_value" type="text"/>
      <functionDefinition language="plpgsql" sqlFormat="pgsql8">
        DECLARE
          q text;
          name text;
          n text;
        BEGIN
          SELECT INTO name current_database();
          q := 'ALTER DATABASE ' || name || ' SET ' || config_parameter || ' ''' || config_value || ''';';
          n := 'DB CONFIG CHANGE: ' || q;
          RAISE NOTICE '%', n;
          EXECUTE q;
          RETURN n;
        END;
      </functionDefinition>
    </function>
  </schema>
  <schema name="user_info" owner="ROLE_OWNER">
    <table name="user" owner="ROLE_OWNER" primaryKey="user_id" description="user logins" slonyId="1">
      <column name="user_id" type="bigserial" serialStart="1234" slonyId="1"/>
      <column name="user_name" type="varchar(100)" null="false"/>
      <column name="user_role" type="varchar(100)" null="false"/>
      <column name="user_create_date" type="timestamp with time zone" null="false" default="NOW()"/>
      <grant role="ROLE_APPLICATION" operation="SELECT, INSERT, UPDATE"/>
      <rows columns="user_id, user_name, user_role">
        <tabrow>1	toor	super_admin</tabrow>
      </rows>
    </table>
  </schema>
</dbsteward>
XML;
  private $pgsql8_xml_b = <<<XML
<dbsteward>
  <database>
    <host>db-host</host>
    <name>dbsteward</name>
    <role>
      <application>dbsteward_phpunit_app</application>
      <owner>deployment</owner>
      <replication/>
      <readonly/>
    </role>
    <slony clusterName="duplicate_slony_ids_testsuite">
      <slonyNode id="1" comment="DSI - Local Primary"  dbName="test" dbHost="db-dev1" dbUser="unittest_slony" dbPassword="drowssap1"/>
      <slonyNode id="2" comment="DSI - Local Backup"   dbName="test" dbHost="db-dev1" dbUser="unittest_slony" dbPassword="drowssap1"/>
      <slonyNode id="3" comment="DSI - Local Backup"   dbName="test" dbHost="db-dev1" dbUser="unittest_slony" dbPassword="drowssap1"/>
      <slonyReplicaSet id="100" originNodeId="1" upgradeSetId="101" comment="common duplicate testing database definition">
        <slonyReplicaSetNode id="2" providerNodeId="1"/>
        <slonyReplicaSetNode id="3" providerNodeId="2"/>
      </slonyReplicaSet>
    </slony>
    <configurationParameter name="TIME ZONE" value="America/New_York"/>
  </database>

  <schema name="dbsteward" owner="ROLE_OWNER">
    <function name="db_config_parameter" returns="text" owner="ROLE_OWNER" cachePolicy="VOLATILE" description="used to push configurationParameter values permanently into the database configuration">
      <functionParameter name="config_parameter" type="text"/>
      <functionParameter name="config_value" type="text"/>
      <functionDefinition language="plpgsql" sqlFormat="pgsql8">
        DECLARE
          q text;
          name text;
          n text;
        BEGIN
          SELECT INTO name current_database();
          q := 'ALTER DATABASE ' || name || ' SET ' || config_parameter || ' ''' || config_value || ''';';
          n := 'DB CONFIG CHANGE: ' || q;
          RAISE NOTICE '%', n;
          EXECUTE q;
          RETURN n;
        END;
      </functionDefinition>
    </function>
  </schema>
  <schema name="user_info" owner="ROLE_OWNER">
    <table name="user" owner="ROLE_OWNER" primaryKey="user_id" description="user logins" slonyId="1">
      <column name="user_id" type="bigserial" serialStart="1234" slonyId="1"/>
      <column name="user_name" type="varchar(100)" null="false"/>
      <column name="user_role" type="varchar(100)" null="false"/>
      <column name="user_create_date" type="timestamp with time zone" null="false" default="NOW()"/>
      <grant role="ROLE_APPLICATION" operation="SELECT, INSERT, UPDATE"/>
      <rows columns="user_id, user_name, user_role">
        <tabrow>1	toor	super_admin</tabrow>
      </rows>
    </table>
    <table name="user_attribute" owner="ROLE_OWNER" primaryKey="user_id" description="user attribute data" slonyId="2">
      <column name="user_id" foreignSchema="user_info" foreignTable="user" foreignColumn="user_id"/>
      <column name="user_attribute_id" type="bigserial" serialStart="5678" slonyId="2"/>
      <column name="user_attribute_name" type="varchar(200)" null="false"/>
      <column name="user_attribute_value" type="text" null="false"/>
      <column name="user_attribute_create_date" type="timestamp with time zone" null="false" default="NOW()"/>
      <column name="user_attribute_modify_date" type="timestamp with time zone" null="false" default="NOW()"/>
      <grant role="ROLE_APPLICATION" operation="SELECT, INSERT, UPDATE"/>
    </table>
  </schema>
</dbsteward>
XML;

  private $mysql5_xml_a = <<<XML
<dbsteward>
  <database>
    <host>db-host</host>
    <name>dbsteward</name>
    <role>
      <application>dbsteward_pu_app</application>
      <owner>deployment</owner>
      <replication/>
      <readonly/>
    </role>
    <configurationParameter name="TIME ZONE" value="America/New_York"/>
  </database>
  <schema name="public" owner="ROLE_OWNER">
    <table name="user" owner="ROLE_OWNER" primaryKey="user_id" description="user logins">
      <column name="user_id" type="bigserial" serialStart="1234"/>
      <column name="user_name" type="varchar(100)" null="false"/>
      <column name="user_role" type="varchar(100)" null="false"/>
      <column name="user_create_date" type="timestamp" null="false" default="NOW()"/>
      <grant role="ROLE_APPLICATION" operation="SELECT, INSERT, UPDATE"/>
      <rows columns="user_id, user_name, user_role">
        <tabrow>1	toor	super_admin</tabrow>
      </rows>
    </table>
  </schema>
</dbsteward>
XML;
  private $mysql5_xml_b = <<<XML
<dbsteward>
  <database>
    <host>db-host</host>
    <name>dbsteward</name>
    <role>
      <application>dbsteward_pu_app</application>
      <owner>deployment</owner>
      <replication/>
      <readonly/>
    </role>
    <configurationParameter name="TIME ZONE" value="America/New_York"/>
  </database>
  <schema name="public" owner="ROLE_OWNER">
    <table name="user" owner="ROLE_OWNER" primaryKey="user_id" description="user logins">
      <column name="user_id" type="bigserial" serialStart="1234"/>
      <column name="user_name" type="varchar(100)" null="false"/>
      <column name="user_role" type="varchar(100)" null="false"/>
      <column name="user_create_date" type="timestamp" null="false" default="NOW()"/>
      <grant role="ROLE_APPLICATION" operation="SELECT, INSERT, UPDATE"/>
      <rows columns="user_id, user_name, user_role">
        <tabrow>1	toor	super_admin</tabrow>
      </rows>
    </table>
    <table name="user_attribute" owner="ROLE_OWNER" primaryKey="user_id" description="user attribute data">
      <column name="user_id" foreignSchema="public" foreignTable="user" foreignColumn="user_id"/>
      <column name="user_attribute_id" type="bigserial" serialStart="5678"/>
      <column name="user_attribute_name" type="varchar(200)" null="false"/>
      <column name="user_attribute_value" type="text" null="false"/>
      <column name="user_attribute_create_date" type="timestamp" null="false" default="NOW()"/>
      <grant role="ROLE_APPLICATION" operation="SELECT, INSERT, UPDATE"/>
    </table>
  </schema>
</dbsteward>
XML;

  protected function build_db($format, $ofs) {
    dbsteward::set_sql_format($format);

    $doc_a = new SimpleXMLElement($this->{$format.'_xml_a'});
    dbsteward::$new_database = $doc_a;
    $table_dependency_a = xml_parser::table_dependency_order($doc_a);

    $format::build_schema($doc_a, $ofs, $table_dependency_a);
  }

  protected function upgrade_db($format, $ofs1, $ofs2, $ofs3, $ofs4) {
    dbsteward::set_sql_format($format);

    $doc_a = new SimpleXMLElement($this->{$format.'_xml_a'});
    $doc_b = new SimpleXMLElement($this->{$format.'_xml_b'});

    dbsteward::$old_database = $doc_a;
    dbsteward::$new_database = $doc_b;

    mysql5_diff::diff_doc_work($ofs1, $ofs2, $ofs3, $ofs4);
  }
  
  /**
   * @group pgsql8
   */
  public function testSerialStartPGSQL8() {
    // build version a
    $ofs = new mock_output_file_segmenter();
    $this->build_db('pgsql8', $ofs);
    $xml_a_sql = $ofs->_get_output();

    // 1) Confirm serial starts are applied when creating new tables
    $this->assertRegExp(
      '/-- serialStart 1234 specified for user_info\.user.user_id/i',
      $xml_a_sql,
      "serialStart specification not announced in a comment"
    );
    $this->assertRegExp(
      "/SELECT setval\(pg_get_serial_sequence\('user_info.user', 'user_id'\), 1234, TRUE\);/i",
      $xml_a_sql,
      "sequence start not being set via setval"
    );
    
    // diff and apply upgrade
    $ofs4 = new mock_output_file_segmenter();
    $this->upgrade_db('pgsql8', $ofs, $ofs, $ofs, $ofs4);
    $xml_b_upgrade_stage4_data1_sql = $ofs4->_get_output();

    // 2) Confirm when adding new tables with serial columns that serial starts are applied in stage 4
    $this->assertRegExp(
      '/-- serialStart 5678 specified for user_info.user_attribute.user_attribute_id/i',
      $xml_b_upgrade_stage4_data1_sql,
      "serialStart specification not announced in a comment in testdata/unit_test_xml_a_build.sql"
    );
    $this->assertRegExp(
      "/SELECT setval\(pg_get_serial_sequence\('user_info.user_attribute', 'user_attribute_id'\), 5678, TRUE\);/i",
      $xml_b_upgrade_stage4_data1_sql,
      "sequence start not being set via setval in testdata/unit_test_xml_a_build.sql"
    );
  }

  /**
   * @group mysql5
   */
  public function testSerialStartMySQL5() {
    // build version a
    $ofs = new mock_output_file_segmenter();
    $this->build_db('mysql5', $ofs);
    $xml_a_sql = $ofs->_get_output();

    // 1) Confirm serial starts are applied when creating new tables
    $this->assertRegExp(
      '/-- serialStart 1234 specified for public.user.user_id/i',
      $xml_a_sql,
      "serialStart specification not announced in a comment in testdata/unit_test_xml_a_build.sql"
    );
    $this->assertRegExp(
      "/SELECT setval\('__public_user_user_id_serial_seq',1234,TRUE\);/i",
      $xml_a_sql,
      "sequence start not being set via setval in testdata/unit_test_xml_a_build.sql"
    );
    
    // diff and apply upgrade
    $ofs4 = new mock_output_file_segmenter();
    $this->upgrade_db('mysql5', $ofs, $ofs, $ofs, $ofs4);
    $xml_b_upgrade_stage4_data1_sql = $ofs4->_get_output();

    // 2) Confirm when adding new tables with serial columns that serial starts are applied in stage 4
    $this->assertRegExp(
      '/-- serialStart 5678 specified for public.user_attribute.user_attribute_id/i',
      $xml_b_upgrade_stage4_data1_sql,
      "serialStart specification not announced in a comment in testdata/unit_test_xml_a_build.sql"
    );
    $this->assertRegExp(
      "/SELECT setval\('__public_user_attribute_user_attribute_id_serial_seq',5678,TRUE\);/i",
      $xml_b_upgrade_stage4_data1_sql,
      "sequence start not being set via setval in testdata/upgrade_stage4_data1.sql"
    );
  }
}

?>
